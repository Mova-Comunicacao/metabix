import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../../modules/auth';

import { 
  ItemService,
  Items 
} from '../../../core';

const EMPTY_ITEMS: Items = {
  id: 0,
  name: '',
  description: '',
  visible_draft: 0,
  date: new Date(),
};
@Component({
  selector: 'app-edit-items',
  templateUrl: './edit-items.component.html'
})
export class EditItemsComponent implements OnInit, OnDestroy {
  @Input() id!: number;

  staffid?: number;
  items!: Items;

  set_draft = 0;

  formGroup!: FormGroup;

  // Getters
  get items$() {
    return this.itemService.items$;
  }
  get isLoading$() {
    return this.itemService.isLoading$;
  }  

  private subscriptions: Subscription[] = [];  

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,
    // Services
    private adminService: AuthService,
    private itemService: ItemService,
  ) { 
    this.staffid = this.adminService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loaditems();
  }

  loaditems() {
    if (!this.id) {
      this.items = EMPTY_ITEMS;
      this.loadForm();
    } else {
      const sb = this.itemService.getItemById(this.id).pipe(
        first(),
        catchError((errorMessage) => {
          this.modal.dismiss(errorMessage);
          return of(EMPTY_ITEMS);
        })
      ).subscribe((res) => {
        this.items = res;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.items.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.items.description, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      link: [this.items.link, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3)
      ])],    
      visible_draft: [this.items.visible_draft, Validators.compose([
        Validators.nullValidator, 
      ])],            
      languageid: [this.items.language?.languageid],
      staffid: [this.staffid],
    });
  }  

  save() {
    const formValues = this.formGroup.value;
    this.items = Object.assign(this.items, formValues);
    if (this.items.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.itemService.updateItems(this.items).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.items);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.itemService.addItems(this.items).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.items);
      }),
      finalize(() => this.modal.close())
    ).subscribe({
      next: (res) => {
        this.modal.close(res.data?.id);
      },
      error: (err) => {
        this.modal.dismiss(err);
      }
    });
    this.subscriptions.push(sbCreate);
  }

  setDraft(ev: any){
    const checked = ev.target.checked;
    this.set_draft = checked == true ? 0 : 1;
    this.formGroup.get('visible_draft')?.setValue(this.set_draft);
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
