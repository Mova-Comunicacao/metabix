import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, first, tap } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { EditorChangeContent, EditorChangeSelection } from 'ngx-quill';

import { AuthService } from '../../../../modules/auth';

import { Goals } from '../../models/goals.model';
import { GoalService } from '../../services';

const EMPTY_GOALS: Goals = {
  id: 0,
  goalId: 0,
  goalName: '',
  goalNotes: '',
  goalPos: 0,
};

@Component({
  selector: 'app-edit-goal',
  templateUrl: './edit-goal.component.html',
})
export class EditGoalComponent implements OnInit, OnDestroy {

  @Input() id!: number;
  adminId: number;

  isLoading$?: Observable<boolean>;

  goals: Goals | any;

  formGroup!: FormGroup;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,

    private adminService: AuthService,
    private goalService: GoalService,
  ) { 
    this.adminId = this.adminService.currentAdminValue.adminId;
  }

  ngOnInit(): void {
    this.isLoading$ = this.goalService.isLoading$;
    this.loadGoals();
  }

  loadGoals() {
    if (!this.id) {
      this.goals = EMPTY_GOALS;
      this.loadForm();
    } else {
      const sb = this.goalService.getItemById(this.id).pipe(
        first(),
        catchError((errorMessage) => {
          this.modal.dismiss(errorMessage);
          return of(EMPTY_GOALS);
        })
      ).subscribe((res) => {
        this.goals = res;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      goalName: [this.goals.goalName, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      goalNotes: [this.goals.goalNotes, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      adminId: [this.adminId],
    });
  }  

  save() {
    const formValues = this.formGroup.value;
    this.goals = Object.assign(this.goals, formValues);
    if (this.goals.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.goalService.update(this.goals).pipe(
      tap(() => {
        this.modal.close();
      }),
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.goals);
      }),
    ).subscribe(res => this.goals = res);
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.goalService.create(this.goals).pipe(
      tap(() => {
        this.modal.close();
      }),
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.goals);
      }),
    ).subscribe((res: Goals) => this.goals = res);
    this.subscriptions.push(sbCreate);
  }

  changedEditor(event: EditorChangeContent | EditorChangeSelection) {
     } 

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }

  // helpers for View
  isControlValid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.valid && (control.dirty || control.touched);
  }

  isControlInvalid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.invalid && (control.dirty || control.touched);
  }

  controlHasError(validation: string, controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  } 
}
