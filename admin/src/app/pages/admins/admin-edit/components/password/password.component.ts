import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, debounceTime, distinctUntilChanged, finalize, of, Subscription, tap } from 'rxjs';

import { ConfirmPasswordValidator } from '../../../../../modules/auth';

import { AdminService } from '../../../services';
import { Admin } from '../../../models/admin.model';

@Component({
  selector: 'app-password',
  templateUrl: './password.component.html',
})
export class PasswordComponent implements OnInit, OnDestroy {
  @Input() staff: Admin | any;

  formPasswordGroup!: FormGroup;
  
  isLoading?: boolean;

  hasError = false;
  errorMessage = '';

  showChangeEmailForm = false;
  showChangePasswordForm = false;
  showPassword = false;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private fb: FormBuilder,
    // Services
    private adminService: AdminService, 
  ) {}

  ngOnInit(): void {
    this.loadForm();
  }

  private loadForm() {
    this.formPasswordGroup = this.fb.group({
      currentPassword: ['', Validators.required],
      password: ['', [Validators.required, Validators.minLength(6)]],
      cPassword: ['', Validators.required]         
    }, {
      validator: ConfirmPasswordValidator.MatchPassword
    });       
  }

  changePassword() {
    this.formPasswordGroup.markAllAsTouched();
    if (!this.formPasswordGroup.valid) {
      return;
    }

    // prepar user
    const { currentPassword, password } = this.formPasswordGroup.value;

    const sbChangePassword = this.adminService.changePassword(this.staff, { currentPassword, password }).pipe(
      debounceTime(150),
      distinctUntilChanged(),
      tap(() => this.isLoading = true),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of({ type: 'error', message: 'Unexpected error' });
      }),
      finalize(() => this.isLoading = false),
    ).subscribe((res: any) => {
      if(res?.alert?.type === 'error' || res?.alert?.type === 'warnning') {
        this.hasError = true;
        this.errorMessage = res?.alert?.message ?? 'Error updating password'        
      } else {
        this.hasError = false;
        this.errorMessage = '';  
        this.loadForm(); // reset form
      }
    });
    this.subscriptions.push(sbChangePassword);
  }   

  togglePasswordForm(show: boolean) {
    this.showChangePasswordForm = show;
  }
    
  showHidePassword() {
    this.showPassword = !this.showPassword;
  } 

  cancel() {
    this.loadForm();
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
  
  // helpers for View
  isControlValid(controlName: string): boolean {
    const control = this.formPasswordGroup.controls[controlName];
    return control.valid && (control.dirty || control.touched);
  }

  isControlInvalid(controlName: string): boolean {
    const control = this.formPasswordGroup.controls[controlName];
    return control.invalid && (control.dirty || control.touched);
  }

  controlHasError(validation: string, controlName: string) {
    const control = this.formPasswordGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formPasswordGroup.controls[controlName];
    return control.dirty || control.touched;
  }    
}
