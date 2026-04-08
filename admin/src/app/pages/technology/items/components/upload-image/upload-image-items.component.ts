import { ChangeDetectorRef, Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { catchError, map, tap } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { 
  ItemService,
  Items 
} from '../../../core';
import { ApiModel } from '../../../../../shared';

@Component({
  selector: 'app-upload-image-items',
  templateUrl: './upload-image-items.component.html',
})
export class UploadImageItemsComponent implements OnInit, OnDestroy {
  @Input() item_id!: number;

  items!: Items;

  hasError = false;
  errorMessage = '';

  file: File;
  formGroup: FormGroup;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    private cdr: ChangeDetectorRef,  
    // Services
    public itemService: ItemService,  
  ) { }

  ngOnInit(): void {
    this.loadItems();
  }

  loadItems() {
    const sb = this.itemService.getItemById(this.item_id).pipe()
    .subscribe((res) => {
      this.items = res as Items;
      this.loadForm();
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb);
  }

  loadForm() {
    if (!this.items.id) {
      return;
    } 

    this.formGroup = this.fb.group({
      id: [this.items.id],
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

    const sb = this.itemService.uploadPicturesItems(formData).pipe(
      tap(() => this.loadItems()),
      catchError((err) => {
        return of({ id: null, type: 'error', message: 'Unexpected error' } as ApiModel);
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
    const sbDelete = this.itemService.deletePicture(this.item_id).pipe(
      tap(() => this.loadItems()),
      catchError((err) => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sbDelete); 
  }   

  getPicture(): string {
    return this.items?.file_name
      ? `url('${this.items?.folder}${this.items?.file_name}')`
      : `url('./assets/media/svg/blank-image.svg')`;
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }

}
