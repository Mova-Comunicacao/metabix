import { ChangeDetectorRef, Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { catchError, map } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { SettingsService, Settings } from '../../../../core';

import { CategoriesService } from '../../services/categories.service';
import { Category } from '../../models/category.model';

@Component({
  selector: 'app-upload-image',
  templateUrl: './upload-image.component.html',
})
export class UploadImageComponent implements OnInit, OnDestroy {
  @Input() category_id!: number;

  category!: Category;

  settings$!: Observable<Settings>;
  isLoading$: Observable<boolean>;
  hasError = false;

  file: File;

  file_name = '';
  fileName = "No file selected";

  formGroup: FormGroup;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    private cdr: ChangeDetectorRef,  
    // Services
    private categoriesService: CategoriesService,  
    private settings: SettingsService,   
  ) { }

  ngOnInit(): void {
    this.isLoading$ = this.categoriesService.isLoading$;
    this.settings$ = this.settings.settings$;
    this.loadCategory();
  }

  loadCategory() {
    const sb = this.categoriesService.getItemById(this.category_id).pipe()
    .subscribe((res) => {
      this.category = res as Category;
      this.loadForm();
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb);
  }

  loadForm() {
    if (!this.category) {
      return;
    } 

    this.formGroup = this.fb.group({
      file: [this.category.file_name],
      id: [this.category_id],
    });
  }        

  onSelectedFile(event: any) {
    if(event.target.files.length) {
      const file = event.target.files[0];
      this.formGroup.get('file')?.setValue(file);
      this.uploadPicture();        
    }
  }
  
  uploadPicture(): void {
    const formData = new FormData();
    formData.append('file', this.formGroup.get('file')?.value);
    formData.append('id', this.formGroup.get('id')?.value);

    const sb = this.categoriesService.uploadPicture(formData).pipe(
    map((res: any) => {
      if(res.type === 'error') {
        this.hasError = true;
        this.hasError = res.message;;
      } else {
        this.hasError = false;
      }
      setTimeout(() => {
        this.loadCategory(); 
        this.cdr.detectChanges();  
      }, 100);     
    }),
    ).subscribe();
    this.subscriptions.push(sb);   
  }  
  
  deletePicture(): void {
    const sbDelete = this.categoriesService.deletePicture(this.category_id).pipe(
      map((res) => {
        //this.category.file_name = '';
        this.loadCategory();
        this.cdr.detectChanges();
      }),
      catchError((err) => {
        console.error('DELETE ERROR', err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sbDelete); 
  }   

  getPicture() {
    if (!this.category.file_name) {
      return 'none';
    }
    return `url('${this.category.folder}${this.category.file_name}')`;
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }

}
