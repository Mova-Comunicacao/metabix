import { Component, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';

import { AuthService } from '../../../modules/auth';
import { Technology, TechnologyService } from '../core';

@Component({
  selector: 'app-content',
  templateUrl: './content.component.html'
})
export class ContentComponent implements OnInit, OnDestroy {
  formGroup!: FormGroup;

  staffid?: number;
  isLoading?: boolean;

  technology!: Technology;
  firstTechnologyState!: Technology;

  // Getters
  get technology$() {
    return this.technologyService.items$;
  }

  get isLoading$() {
    return this.technologyService.isLoading$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    // Services
    private authService: AuthService,
    private technologyService: TechnologyService,
  ) {
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadTechnology();
  }  

  private loadTechnology() {
    const sb = this.technologyService.getTechnology().pipe(
      ).subscribe({
        next: (res: Technology) => {
          this.technology = res as Technology;
          this.firstTechnologyState = { ...this.technology };
          this.loadForm();
        },
        error: (err) => {
          console.error('LOAD ERROR:', err);
        }
    });
    this.subscriptions.push(sb);       
  }

  loadForm() {
    if (!this.technology) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.technology.name, Validators.compose([
        Validators.required,
        Validators.minLength(3)
      ])],
      description: [this.technology.description, Validators.compose([
        Validators.required,
        Validators.minLength(3),
        Validators.maxLength(250)
      ])],
      long_description: [this.technology.long_description, Validators.compose([
        Validators.required,
        Validators.minLength(3)
      ])],
      staffid: [this.staffid],
      languageid: [this.technology.language?.languageid]
    });
  }  

  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.technology = { ...this.technology, ...this.formGroup.value }

    const sbUpdate = this.technologyService.update(this.technology).pipe(
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();
      }),         
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.technology);
      })
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }    

  reset() {
    if (!this.firstTechnologyState) {
      return;
    }

    this.technology = Object.assign({}, this.firstTechnologyState);
    this.loadForm();
  }     

  ngOnDestroy() {
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

  controlHasError(validation: string, controlName: string) {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }   
}
