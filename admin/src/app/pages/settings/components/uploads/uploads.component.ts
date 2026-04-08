import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription, of } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { SettingsService } from '../../services';
import { Settings } from '../../models';

@Component({
  selector: 'app-uploads',
  templateUrl: './uploads.component.html',
})
export class UploadsComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  settings$:  Observable<Settings>;
  isLoading$: Observable<boolean>;

  settings!: Settings;
  firstSettingState!: Settings;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private cdr: ChangeDetectorRef,
    private fb: FormBuilder,
    // Services
    public settingService: SettingsService,     
  ) { }

  ngOnInit(): void {
    this.loadSettings();
  }

  loadSettings() {
    const sb = this.settingService.settings$.pipe(
    ).subscribe(res => {
      this.settings = res;
      this.firstSettingState = Object.assign({}, res);
      
      this.loadForm();
    });
    this.subscriptions.push(sb);    
  }  
  
  loadForm() {
    this.formGroup = this.fb.group({
      allowed_files: [this.settings?.allowed_files, Validators.required],
      avatar_types: [this.settings?.avatar_types, Validators.required],
      site_pic_types: [this.settings?.site_pic_types, Validators.required],
      dateformat: [this.settings?.dateformat, Validators.required],
      default_timezone: [this.settings?.default_timezone, Validators.required],
    });    
  }

  onSubmit(): void {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    const formValues = this.formGroup.value;
    this.settings = Object.assign(this.settings, formValues);   
    this.edit(); 
  }  

  edit() {
    const sbUpdate = this.settingService.update(this.settings).pipe(
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.settings);
      })
    ).subscribe(res => {
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sbUpdate);
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
