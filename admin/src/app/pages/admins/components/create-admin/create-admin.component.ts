import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError } from 'rxjs/operators';

import { Admin } from '../../models/admin.model';
import { AdminService } from '../../services';

import { ConfirmPasswordValidator } from '../../../../modules/auth';

@Component({
  selector: 'app-create-admin',
  templateUrl: './create-admin.component.html',
})
export class CreateAdminComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;
  
  admin!: Admin;
  hasError?: boolean;

  send_mail = true;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,   
    // Services
    public adminService: AdminService,
  ) { }

  ngOnInit(): void {
    this.loadForm();
  }

  loadForm() {
    this.formGroup = this.fb.group({
      firstname: ['', Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      lastname: ['', Validators.compose([
        Validators.required,
      ])],
      email: ['', Validators.compose([
        Validators.required, 
        Validators.email
      ])],
      role: [1, Validators.compose([
        Validators.nullValidator
      ])],
      active: [1, Validators.compose([
        Validators.required
      ])],
      send_mail: [this.send_mail],
      password: ['',Validators.compose([
        Validators.required,
        Validators.minLength(3)
      ])],
      cPassword: ['', Validators.required]
    }, {
      validator: ConfirmPasswordValidator.MatchPassword
    });
  }

  toggleVisibility(ev: any){
    this.send_mail = ev.target.checked;
  }  

  save() {
    const formData = this.formGroup.value;
    const sbCreate = this.adminService.create(formData).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.admin);
      }),
    ).subscribe((res: any) => {
      console.log(res);
      this.admin = res;
      if(res.type === 'error') {
        this.hasError = true;
        this.hasError = res.message;
      } else {
        this.hasError = false;
        this.modal.close();
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
