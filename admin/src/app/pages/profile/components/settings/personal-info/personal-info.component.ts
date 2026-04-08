import { Component, OnDestroy, OnInit, ChangeDetectorRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, Subscription, of } from 'rxjs';
import { first, catchError, tap, finalize } from 'rxjs/operators';

import { SettingsService } from '../../../../../core';
import { AuthService, StaffModel } from '../../../../../modules/auth';
import { ProfileService } from '../../../services'

@Component({
  selector: 'app-personal-info',
  templateUrl: './personal-info.component.html',
})
export class PersonalInfoComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  staff!: StaffModel;
  firstStaffState!: StaffModel;

  hasError = false;
  errorMessage = '';
  avatar = 'none';
  
  isLoading$?: Observable<boolean>;
  isLoading?: boolean;

  showChangeEmailForm = false;

  subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    // Services
    public settings: SettingsService,
    private authService: AuthService,
    private profileService: ProfileService
    ) { }

  ngOnInit(): void {
    this.isLoading$ = this.authService.isLoadingSubject.asObservable();
    this.loadProfile();
  }

  loadProfile() {
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

  loadForm() {
    this.formGroup = this.fb.group({
      avatar: [this.staff.avatar],
      firstname: [this.staff.firstname, Validators.required],
      lastname: [this.staff.lastname, Validators.required],
      phone: [this.staff.phone, Validators.required],
      email: [this.staff.email],
      address: [this.staff.address, Validators.required]
    });
  }  

  // actions
  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.staff = { ...this.staff, ...this.formGroup.value };
    this.edit();
  }

  edit() {
    this.authService.isLoadingSubject.next(true);
    const sbUpdate = this.profileService.update(this.staff).pipe(
      tap(() => this.updateCurrentUser(this.staff)),
      finalize(() => this.authService.isLoadingSubject.next(false)),
      catchError(err => {
        console.error('UPDATE ERROR', err);
        // restaura estado original se falhar
        this.staff = { ...this.firstStaffState };
        return of(undefined);
      }),
    ).subscribe(() => this.loadProfile());
    this.subscriptions.push(sbUpdate);
  } 

  cancel() {
    this.staff = { ...this.staff, ...this.formGroup.value };
    this.loadForm();
  }  

  onSelectedAvatar(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.formGroup.get('avatar')?.setValue(input.files[0]);
      this.uploadAvatar();
    }
  }

  private uploadAvatar(): void {
    const formData = new FormData();
    formData.append('avatar', this.formGroup.get('avatar')?.value);
    
    this.isLoading = true;
    const sbUpload = this.profileService.uploadAvatar(this.staff, formData).pipe(  
      finalize(() => this.isLoading = false),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(undefined);
      }),
    ).subscribe((res) => {
      if(!res?.ok) {
        this.errorMessage = res?.alert?.message ?? 'Error uploading file';
        return;         
      }     
      this.updateCurrentUser({ ...this.staff, avatar: res.data?.avatar })
      this.errorMessage = '';           
    })
    this.subscriptions.push(sbUpload);      
  } 


  deleteAvatar(): void {
    this.authService.isLoadingSubject.next(true);
    const sbDelete = this.profileService.deleteAvatar(this.staff).pipe(
      tap(() => this.updateCurrentUser({ ...this.staff, avatar: null })),
      finalize(() => this.authService.isLoadingSubject.next(false)),
      catchError(err => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),      
    ).subscribe();
    this.subscriptions.push(sbDelete); 
  }   

  private updateCurrentUser(user: StaffModel) {
    this.staff = { ...user };
    this.authService.currentUserSubject.next({ ...user });
  }

  getAvatar(): string {
    return this.staff?.avatar
      ? `url('${this.staff.avatar}')`
      : `url('./assets/media/avatars/blank.png')`;
  }
  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }

  toggleEmailForm(show: boolean) {
    this.showChangeEmailForm = show;
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
