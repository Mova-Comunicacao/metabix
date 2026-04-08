import { Component, OnInit, OnDestroy, Input, ChangeDetectorRef } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { of, Subscription } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';

import { CarouselService } from '../../../services';
import { Carousel } from '../../../models';

import { 
  SettingsService, 
} from '../../../../../core';

import { ApiModel } from '../../../../../shared';

@Component({
  selector: 'app-upload-image',
  templateUrl: './upload-image.component.html',
})
export class UploadImageComponent implements OnInit, OnDestroy {
  @Input() carousel_id!: number;

  items!: Carousel;

  hasError = false;
  errorMessage = '';

  file: File;
  formGroup!: FormGroup;
    
  // Getters
  get settings$() {
    return this.settings.settings$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    private cdr: ChangeDetectorRef,
    // Services
    private settings: SettingsService, 
    private pictureService: CarouselService,   
  ) { }


  ngOnInit() {
    this.loadItems();  
  }

  loadItems() {
    const sb = this.pictureService.getItemById(this.carousel_id).pipe()
    .subscribe((res) => {
      this.items = res as Carousel;
      this.loadForm();
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb);
  }  

  loadForm() {
    if (!this.carousel_id) {
      return;
    } 

    this.formGroup = this.fb.group({
      id: [this.carousel_id],
      file: [this.items.file_name],
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

    const sb = this.pictureService.uploadPicture(formData).pipe(
      tap(() => this.loadItems()),
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
    const sbDelete = this.pictureService.deletePicture(this.carousel_id).pipe(
      tap(() => this.loadItems()),
      catchError((err) => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sbDelete); 
  }   

  getPicture(): string {
    return this.items.url
      ? `url('${this.items.url}')`
      : `url('./assets/media/svg/blank-image.svg')`;
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
