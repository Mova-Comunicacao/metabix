import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription, of } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { SettingsService, LanguageService } from '../../services';
import { Settings } from '../../models';

@Component({
  selector: 'app-general',
  templateUrl: './general.component.html',
})
export class GeneralComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  settings$:  Observable<Settings>;
  isLoading$: Observable<boolean>;

  isLoading?: boolean

  settings!: Settings;
  firstSettingState!: Settings;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    // Services
    public settingService: SettingsService,   
    public languageService: LanguageService
  ) { }

  ngOnInit(): void {
    this.loadSettings();
    this.loadLanguage();
  }

  loadSettings() {
    const sb = this.settingService.settings$.pipe(
    ).subscribe(res => {
      this.settings = res as Settings;
      this.settings = { ...this.settings }
      this.firstSettingState = { ...this.settings }

      this.loadForm();
    });
    this.subscriptions.push(sb);    
  }  

  loadLanguage() {
    const sb = this.languageService.getLanguages().pipe(
    ).subscribe();
    this.subscriptions.push(sb);    
  }    
  
  loadForm() {
    this.formGroup = this.fb.group({
      main_domain: [this.settings?.main_domain, Validators.required],
      company_name: [this.settings?.company_name, Validators.required],
      business_name: [this.settings?.business_name, Validators.required],
      company_city: [this.settings?.company_city, Validators.required],
      company_email: [this.settings?.company_email, Validators.compose([
        Validators.required, 
        Validators.email
      ])],
      company_alt_phonenumber: [this.settings?.company_alt_phonenumber, Validators.nullValidator],
      company_postal_code: [this.settings?.company_postal_code, Validators.nullValidator],
      company_phonenumber: [this.settings?.company_phonenumber, Validators.required],
      company_description: [this.settings?.company_description, Validators.nullValidator],
      company_address: [this.settings?.company_address, Validators.required],
      active_language: [this.settings?.active_language, Validators.required],
    });    
  }

  onSubmit(): void {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.edit(); 
  }  

  edit() {
    this.isLoading = true;

    const updatedSettings = {
      ...this.settings,
      ...this.formGroup.value
    };    
    
    const sbUpdate = this.settingService.update(updatedSettings).pipe(
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();
      }),           
      catchError((err) => {
        console.error('UPDATE ERROR', err);
        return of(this.settings);
      })
    ).subscribe((res) => {
      if (res) {
        console.log('Setting update');
      }
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
