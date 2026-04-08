import { Component, OnInit, OnDestroy, Input } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { PictureService } from '../../../services';

import { 
  SettingsService, 
  Settings 
} from '../../../../../core';
import { ApiModel } from '../../../../../shared';

@Component({
  selector: 'app-upload-image',
  templateUrl: './upload-image.component.html',
})
export class UploadImageComponent implements OnInit, OnDestroy {
  settings$!: Observable<Settings>;
  
  isLoading = false;

  hasError = false;
  errorMessage = '';

  @Input() slideid!: number;
  @Input() staffid!: number;

  formGroup!: FormGroup;
    
  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,
    // Services
    private settings: SettingsService, 
    private pictureService: PictureService,   
  ) { }

  ngOnInit() {
    this.settings$ = this.settings.settings$;

    this.formGroup = this.fb.group({
      subject: ['', Validators.compose([
        Validators.required, 
      ])],      
      description: ['', Validators.compose([
        Validators.nullValidator, 
      ])],
      file: ['', Validators.compose([
        Validators.required, 
      ])],
      staffid: [this.staffid],
      slideid: [this.slideid],
    });   
  }

  onSelectedFile(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.formGroup.get('file')?.setValue(input.files[0]);
    }
  }

  onSubmit() {
    const formData = new FormData();
    formData.append('staffid', this.formGroup.get('staffid')?.value);
    formData.append('slideid', this.formGroup.get('slideid')?.value);
    formData.append('subject', this.formGroup.get('subject')?.value);
    formData.append('description', this.formGroup.get('description')?.value);
    formData.append('file', this.formGroup.get('file')?.value);

    this.isLoading = true;
    const sb = this.pictureService.uploadPicture(formData).pipe(
      finalize(() => {
        this.isLoading = false;
      }),
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of({ type: 'error', message: 'Unexpected error' } as ApiModel);
      }),      
    ).subscribe((res) => {
      if(!res?.ok) {
        this.hasError = true;
        this.errorMessage = res.alert?.message ?? 'Error uploading file';
        return; 
      }
      this.hasError = false;
      this.errorMessage = '';        
      this.modal.close();
    });
    this.subscriptions.push(sb); 
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
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

  controlHasError(validation: string, controlName: string) {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }  

}
