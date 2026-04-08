import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first, tap } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../modules/auth';
import { MiscService } from '../../../../core';

import { PartnerService } from '../../services/partners.service';
import { Partners } from '../../models/partners.model';

const EMPTY_SERVCES: Partners = {
  id: 0,
  name: '',
  description: '',
  type: 0,
  date: new Date
};

@Component({
  selector: 'app-edit-item',
  templateUrl: './edit-item.component.html',
})
export class EditItemComponent implements OnInit, OnDestroy {
  @Input() id!: number;

  staffid?: number;
  partners!: Partners;

  formGroup!: FormGroup;

  // Getters
  get partners$() {
    return this.partnerService.items$;
  }
  get countrires$() {
    return this.miscService.countries$;
  }  
  get isLoading$() {
    return this.partnerService.isLoading$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    public modal: NgbActiveModal,
    // Services 
    private authService: AuthService,
    private miscService: MiscService,
    private partnerService: PartnerService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadItems();
    this.loadCountries();
  }

  loadCountries() {
    const sb = this.miscService.getCountries().pipe(
    ).subscribe();
    this.subscriptions.push(sb);     
  }

  loadItems() {
    if (!this.id) {
      this.partners = EMPTY_SERVCES;
      this.loadForm();
    } else {
      const sb = this.partnerService.getItemById(this.id).pipe(
        first(),
        catchError((err) => {
          this.modal.dismiss(err);
          return of(EMPTY_SERVCES);
        })
      ).subscribe((res: Partners) => {
        this.partners = res as Partners;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.partners.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      external_link: [this.partners.external_link, Validators.compose([
        Validators.nullValidator, 
      ])],      
      description: [this.partners.description, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3),
        Validators.maxLength(250),
      ])],  
      type: [this.partners.type],
      countries: [this.partners.countries?.country_id],
      staffid: [this.staffid],
    });
  }  

  save() {
    const formValues = this.formGroup.value;
    this.partners = Object.assign(this.partners, formValues);
    if (this.partners.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.partnerService.update(this.partners).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.partners);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.partnerService.create(this.partners).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.partners);
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
