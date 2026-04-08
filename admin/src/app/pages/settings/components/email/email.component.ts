import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription, of } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';

import { SettingsService, TestService } from '../../services';
import { Settings } from '../../models';

@Component({
  selector: 'app-email',
  templateUrl: './email.component.html',
})
export class EmailComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  settings$:  Observable<Settings>;
  isLoading$: Observable<boolean>;

  isLoading?: boolean;

  settings!: Settings;
  firstSettingState!: Settings;

  usingGmailAccount: any;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private cdr: ChangeDetectorRef,
    private fb: FormBuilder,
    // Services
    public settingService: SettingsService,   
    public testService: TestService  
  ) { }

  ngOnInit(): void {
    this.loadSettings();
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
  
  loadForm() {
    this.formGroup = this.fb.group({
      mail_engine: [this.settings?.mail_engine],
      email_protocolo: [this.settings?.email_protocolo],
      smtp_encryption: [this.settings?.smtp_encryption],
      smtp_host: [this.settings?.smtp_host],
      smtp_port: [this.settings?.smtp_port],
      smtp_email: [this.settings?.smtp_email],
      smtp_username: [this.settings?.smtp_username],
      smtp_password: [this.settings?.smtp_password],
      smtp_email_charset: [this.settings?.smtp_email_charset],
      bcc_emails: [this.settings?.bcc_emails],
      email_signature: [this.settings?.email_signature],
      email_header: [this.settings?.email_header],
      email_footer: [this.settings?.email_footer],
      test_email: ['', Validators.compose([
        Validators.nullValidator, 
        Validators.email
      ])]
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

  test_email() {
    const test_email = { test_email: this.formGroup.get('test_email')?.value };

    const sbSend = this.testService.sent_smtp_test_email(test_email).pipe( 
      catchError((err) => {
        console.error('SEND EMAIL ERROR', err);
        return of(null);
      }),
    ).subscribe();
    this.subscriptions.push(sbSend);         
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

