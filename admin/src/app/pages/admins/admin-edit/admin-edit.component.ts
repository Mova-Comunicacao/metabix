import { Component, OnDestroy, OnInit, ChangeDetectorRef } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, of, Subscription } from 'rxjs';
import { catchError, switchMap, finalize, take, tap } from 'rxjs/operators';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';

import { 
  SettingsService, 
  Settings 
} from '../../../core';

import { AdminService } from '../services';
import { Admin } from '../models/admin.model';
import { Roles } from '../models/roles.model';


@Component({
  selector: 'app-admin-edit',
  templateUrl: './admin-edit.component.html',
})
export class AdminEditComponent implements OnInit, OnDestroy {
  staffid: number;
  
  formGroup!: FormGroup;
  formPasswordGroup!: FormGroup;

  settings$!: Observable<Settings>

  staff: Admin | any;
  firstStaffState: Admin | any;

  isLoading?: boolean;

  errorMessage = '';
  avatar = 'none';

  showChangeEmailForm = false;
  
  tabs = {
    PROFILE_TAB: 0,
    ACCOUNT_TAB: 1,
    CHANGE_PASSWORD_TAB: 2,
    PERMISSIONS_TAB: 3
  };
  activeTabId = this.tabs.PROFILE_TAB; // 0 => Basic info | 1 => Remarks | 2 => Specifications

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private localize: LocalizeRouterService,
    private route: ActivatedRoute,
    private router: Router,
    // Services
    public settings: SettingsService, 
    private adminService: AdminService,    
  ) { }

  ngOnInit(): void {
    this.loadAdmin();
  }

  loadAdmin() {
    this.isLoading = true;

    const sb = this.route.paramMap.pipe(
      take(1),
      switchMap(params => {
        // get id from URL
        this.staffid = Number(params.get('id'));
        return this.staffid && this.staffid > 0
          ? this.adminService.getItemById(this.staffid)
          : of(undefined);
      }),
      catchError((err) => {
        this.errorMessage = err;
        return of(undefined);
      }),
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();        
      })      
    ).subscribe(res => {
      if (!res) {
        this.router.navigate(
          [this.localize.translateRoute(['/admins']), { relativeTo: this.route }]
        );        
      }
      this.staff = res as Admin;

      this.staff = { ...this.staff }
      this.firstStaffState = { ...this.staff }

      this.initForm();
    });
    this.subscriptions.push(sb);    
  }

  initForm() {
    this.formGroup = this.fb.group({
      avatar: [this.staff.avatar],
      firstname: [this.staff.firstname, Validators.required],
      lastname: [this.staff.lastname, Validators.required],
      phone: [this.staff.phone, Validators.required],
      address: [this.staff.address, Validators.required],
      username: [this.staff.username, Validators.nullValidator],
      email: [this.staff.email, Validators.compose([
        Validators.required, 
        Validators.email
      ])],
      role: [this.staff.role?.roleid],  
      permissions: [this.staff?.permissions],
      admin: [this.staff?.admin],  
      default_language: [this.staff.default_language],       
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
    this.isLoading = true;

    const sbUpdate = this.adminService.update(this.staff).pipe(
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();
      }),       
      catchError((err) => {
        console.error('UPDATE ERROR', err);
        return of(this.staff);
      })
    ).subscribe(() => this.loadAdmin());
    this.subscriptions.push(sbUpdate);
  } 

  permissionsValues(values?: any) {
    this.formGroup.get('permissions')?.setValue(values);
  }

  rolesValues(values: Roles) {
    this.formGroup.get('role')?.setValue(values);
  }

  cancel() {
    this.staff = { ...this.firstStaffState };
    this.loadAdmin();
  }  

  onSelectedAvatar(event: any) {
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
    const sbUpload = this.adminService.uploadAvatar(this.staff, formData).pipe(  
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
    this.isLoading = true;
    const sbDelete = this.adminService.deleteAvatar(this.staff).pipe(
      tap(() => this.updateCurrentUser({ ...this.staff, avatar: null })),
      catchError((err) => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),
      finalize(() => this.isLoading = false)
    ).subscribe();
    this.subscriptions.push(sbDelete);

  }   

  getAvatar(): string {
    return this.staff?.avatar
      ? `url('${this.staff.avatar}')`
      : `url('./assets/media/avatars/blank.png')`;
  }

  private updateCurrentUser(user: Admin) {
    this.staff = { ...user };
  }  
  

  changeTab(tabId: number) {
    this.activeTabId = tabId;
  }   

  toggleEmailForm(show: boolean) {
    this.showChangeEmailForm = show;
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }

  // helpers for View
  isControlValid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName] || this.formPasswordGroup.controls[controlName];
    return control.valid && (control.dirty || control.touched);
  }

  isControlInvalid(controlName: string): boolean {
    const control = this.formGroup.controls[controlName] || this.formPasswordGroup.controls[controlName];
    return control.invalid && (control.dirty || control.touched);
  }

  controlHasError(validation: string, controlName: string) {
    const control = this.formGroup.controls[controlName] || this.formPasswordGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName] || this.formPasswordGroup.controls[controlName];
    return control.dirty || control.touched;
  }    
}
