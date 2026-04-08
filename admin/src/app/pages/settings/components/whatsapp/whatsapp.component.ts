import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';


import { 
  SettingsService, 
  TestService,
} from '../../services';
import { Settings } from '../../models';

@Component({
  selector: 'app-whatsapp',
  templateUrl: './whatsapp.component.html',
})
export class WhatsappComponent implements OnInit, OnDestroy {

  formGroup!: FormGroup;

  settings$:  Observable<Settings>;
  isLoading$: Observable<boolean>;

  settings!: Settings;
  firstSettingState!: Settings;

  showChangeTokenForm = false;

  loginUrl: string;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private cdr: ChangeDetectorRef,
    private fb: FormBuilder,
    // Services
    public settingService: SettingsService,   
    private testService: TestService    
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
      whatsapp_chat: [this.settings?.whatsapp_chat],
      whatsapp_chat_clients_area: [this.settings?.whatsapp_chat_clients_area, Validators.compose([
        Validators.nullValidator, 
      ])],
      whatsapp_chat_description: [this.settings?.whatsapp_chat_description, Validators.nullValidator],
      whatsapp_access_token: [this.settings?.whatsapp_access_token],
      whatsapp_number_id: [this.settings?.whatsapp_number_id, Validators.compose([
        Validators.nullValidator, 
        Validators.pattern("^[0-9]*$")
      ])],
      whatsapp_business_id: [this.settings?.whatsapp_business_id, Validators.compose([
        Validators.nullValidator, 
        Validators.pattern("^[0-9]*$")
      ])],
      whatsapp_version: [this.settings?.whatsapp_version],
      test_number: ['', Validators.compose([
        Validators.nullValidator, 
        Validators.pattern("^[0-9]*$")
      ])]      
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


  test_template() {
    const formData = new FormData();
    formData.append('test_number', this.formGroup.get('test_number')?.value); 
    const test_number = this.formGroup.value;
    const sbSend = this.testService.sent_whatsapp_test_template(test_number).pipe( 
      catchError((errorMessage) => {
        console.error('SEND EMAIL ERROR', errorMessage);
        return of(formData);
      }),
    ).subscribe();
    this.subscriptions.push(sbSend);         
  }  

  toggleTokenForm(show: boolean) {
    this.showChangeTokenForm = show;
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
