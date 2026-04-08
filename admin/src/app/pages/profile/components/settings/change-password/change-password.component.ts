import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, Subscription, of } from 'rxjs';
import { first, catchError, tap, finalize, debounceTime, distinctUntilChanged } from 'rxjs/operators';

import { 
  AuthService, 
  StaffModel, 
  PasswordService,
  ConfirmPasswordValidator 
} from '../../../../../modules/auth';

import { ProfileService } from '../../../services';

@Component({
  selector: 'app-change-password',
  templateUrl: './change-password.component.html',
})
export class ChangePasswordComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  staff!: StaffModel;
  firstStaffState!: StaffModel;

  hasError = false;
  errorMessage = '';
    
  isLoading$?: Observable<boolean>;
  isLoading?: boolean;

  showChangePasswordForm = false;
  showPassword = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    // Services
    private profile: ProfileService,
    private authService: AuthService,  
    private passwordService: PasswordService
  ) { }

  ngOnInit(): void {
    this.isLoading$ = this.authService.isLoadingSubject.asObservable();
    this.loadInfo();
  }

  private loadInfo() {
    const sb = this.authService.currentUserSubject.asObservable().pipe(
      first(staff => !!staff)
    ).subscribe(staff => {
      this.staff = staff as StaffModel;

      this.staff = { ...this.staff };
      this.firstStaffState = { ...this.staff };

      this.loadForm();
    });
    this.subscriptions.push(sb);    
  } 

  private loadForm() {
    this.formGroup = this.fb.group({
     password: ['', [Validators.required, Validators.minLength(6)]],
      cPassword: ['', Validators.required]
    }, {
      validator: ConfirmPasswordValidator.MatchPassword
    });
  }  

  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    // prepar user
    const { currentPassword, password } = this.formGroup.value;
    
    const sbUpdate = this.profile.changePassword(this.staff, { currentPassword, password }).pipe(
      debounceTime(150),
      distinctUntilChanged(),      
      tap(() => this.isLoading = true),
      catchError(error => {
        console.error('UPDATE ERROR', error);
        return of({ type: 'error', message: 'unexpected_error' });
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
    this.subscriptions.push(sbUpdate);
  }

  edit() {
    this.authService.isLoadingSubject.next(true);
    const sbUpdate = this.passwordService
    .changePassword(this.staff, this.staff.password).pipe(
      tap(() => {
        this.authService.currentUserSubject.next({ ...this.staff });
      }),
      catchError(error => {
        console.error('UPDATE ERROR', error);
        return of(this.staff);
      }),
      finalize(() => this.authService.isLoadingSubject.next(false)),
    ).subscribe(() => this.loadInfo());
    this.subscriptions.push(sbUpdate);
  }    

  cancel() {
    this.staff = { ...this.firstStaffState };
    this.loadForm();
  }  

  togglePasswordForm(show: boolean) {
    this.showChangePasswordForm = show;
  }
    
  showHidePassword() {
    this.showPassword = !this.showPassword;
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

  controlHasError(validation: string, controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  } 

}
