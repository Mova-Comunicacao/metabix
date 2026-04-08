import { ChangeDetectorRef, Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { catchError, tap } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';

import { SettingsService } from '../../../../core';
import { ApiModel } from '../../../../shared';

import { CustomerService } from '../../services/customers.service';
import { Customers } from '../../models/customers.model';

@Component({
  selector: 'app-upload-image',
  templateUrl: './upload-image.component.html',
})
export class UploadImageComponent implements OnInit, OnDestroy {
  @Input() customer_id: number;

  partners!: Customers;

  hasError = false;
  errorMessage?: string = ''

  formGroup!: FormGroup;
  file!: File;

  // Getters
  get settings$() {
    return this.settings.settings$;
  }  
  get isLoading$() {
    return this.customerService.isLoading$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    private cdr: ChangeDetectorRef,  
    // Services
    private settings: SettingsService,   
    private customerService: CustomerService,  
  ) { }

  ngOnInit(): void {
    this.loadPartners();
  }

  loadPartners() {
    const sb = this.customerService.getItemById(this.customer_id).pipe()
    .subscribe((res) => {
      this.partners = res as Customers;
      this.loadForm();
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb);
  }

  loadForm() {
    if (!this.partners) {
      return;
    } 

    this.formGroup = this.fb.group({
      id: [this.customer_id],
      file: [this.partners.file_name],
    });
  }        

  onSelectedFile(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.formGroup.get('file')?.setValue(input.files[0]);
      this.uploadPicture();
    }
  }
  
  private uploadPicture(): void {
    const formData = new FormData();
    formData.append('file', this.formGroup.get('file')?.value);
    formData.append('id', this.formGroup.get('id')?.value);

    const sb = this.customerService.uploadPicture(formData).pipe(
      tap(() => this.loadPartners()),
      catchError((err) => {
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
    });
    this.subscriptions.push(sb);   
  }  
  
  deletePicture(): void {
    const sbDelete = this.customerService.deletePicture(this.customer_id).pipe(
      tap(() => this.loadPartners()),
      catchError((err) => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sbDelete); 
  }   

  getPicture(): string {
    return this.partners?.file_name
      ? `url('${this.partners?.folder}${this.partners?.file_name}')`
      : `url('./assets/media/svg/blank-image.svg')`;
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }

}
