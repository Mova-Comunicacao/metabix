import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../modules/auth';

import { Items } from '../../models/items.model';
import { ItemService } from '../../services';

const EMPTY_ITEMS: Items = {
  id: 0,
  name: '',
  description: '',
  date: new Date
};

@Component({
  selector: 'app-edit-item',
  templateUrl: './edit-item.component.html',
})
export class EditItemComponent implements OnInit, OnDestroy {
  @Input() id!: number;

  staffid?: number;
  items!: Items;

  formGroup!: FormGroup;

  // Getters
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
    this.loadItems();
  }

  loadItems() {
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
        this.items = res as Items;
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
      staffid: [this.staffid],
      languageid: [this.items.language?.languageid],
    });
  }  

  save() {
    this.items = { ...this.items, ...this.formGroup.value };
    if (this.items.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  // actions
  edit() {
    const sbUpdate = this.itemService.updateItems(this.items).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.items);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.itemService.addItems(this.items).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.items);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbCreate);
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
