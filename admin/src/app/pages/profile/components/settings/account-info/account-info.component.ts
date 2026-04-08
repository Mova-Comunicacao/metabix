import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, Subscription, of } from 'rxjs';
import { first, catchError, tap, finalize } from 'rxjs/operators';

import { AuthService, StaffModel } from '../../../../../modules/auth';
import { ProfileService } from '../../../services'

@Component({
  selector: 'app-account-info',
  templateUrl: './account-info.component.html',
})
export class AccountInfoComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  staff!: StaffModel;
  firstStaffState!: StaffModel;

  isLoading$?: Observable<boolean>;
  isLoading?: boolean;

  subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    // Services
    private authService: AuthService,
    private profileService: ProfileService    
  ) { }

  ngOnInit(): void {
    this.isLoading$ = this.authService.isLoadingSubject.asObservable();
    this.loadInfo();
  }

  loadInfo() {
    const sb = this.authService.currentUserSubject.asObservable().pipe(
      first(staff => !!staff)
    ).subscribe(staff => {
      this.staff = staff as StaffModel;

      this.staff = { ...this.staff }
      this.firstStaffState = { ...this.staff }

      this.loadForm();
    });
    this.subscriptions.push(sb);    
  }  

  loadForm() {
    this.formGroup = this.fb.group({
      username: [this.staff.username, Validators.required],
      email: [this.staff.email, Validators.compose([
        Validators.required, 
        Validators.email
      ])],
      role: [this.staff.role], 
      admin: [this.staff.admin],      
      default_language: [this.staff.default_language]
    });
  }  

  // actions
  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    const { username, email, role, default_language, admin } = this.formGroup.value;

    this.staff = {
      ...this.staff,
      username,
      email,
      role,
      default_language,
      admin
    };

    this.edit();
  }

  edit() {
    this.authService.isLoadingSubject.next(true);
    const sbUpdate = this.profileService.update(this.staff).pipe(
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

}
