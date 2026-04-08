import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first, tap } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../modules/auth';

import { CustomerService } from '../../services/customers.service';
import { Customers } from '../../models/customers.model';

const EMPTY_SERVCES: Customers = {
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
  customers!: Customers;

  formGroup!: FormGroup;

  // Getters
  get customers$() {
    return this.customerService.items$;
  }
  get isLoading$() {
    return this.customerService.isLoading$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    public modal: NgbActiveModal,
    // Services 
    private authService: AuthService,
    private customerService: CustomerService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadItems();
  }

  loadItems() {
    if (!this.id) {
      this.customers = EMPTY_SERVCES;
      this.loadForm();
    } else {
      const sb = this.customerService.getItemById(this.id).pipe(
        first(),
        catchError((err) => {
          this.modal.dismiss(err);
          return of(EMPTY_SERVCES);
        })
      ).subscribe((res: Customers) => {
        this.customers = res as Customers;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.customers.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      external_link: [this.customers.external_link, Validators.compose([
        Validators.nullValidator, 
      ])],      
      description: [this.customers.description, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3),
        Validators.maxLength(250),
      ])],  
      staffid: [this.staffid],
    });
  }  

  save() {
    const formValues = this.formGroup.value;
    this.customers = Object.assign(this.customers, formValues);
    if (this.customers.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.customerService.update(this.customers).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.customers);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.customerService.create(this.customers).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.customers);
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
