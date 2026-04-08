import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Observable, of, Subscription } from 'rxjs';
import { catchError, finalize, switchMap, tap } from 'rxjs/operators';

import { EditorChangeContent, EditorChangeSelection } from 'ngx-quill';

import { AuthService } from '../../../modules/auth';

import { CategoriesService } from '../services/categories.service';
import { Category } from '../models/category.model';

const EMPTY_CATEGORY: Category = {
  id: undefined,
  name: '',
  description: '',
  file_name: ''
};

@Component({
  selector: 'app-categories-edit',
  templateUrl: './categories-edit.component.html',
})
export class CategoriesEditComponent implements OnInit, OnDestroy {
  id?: number;
  staffid?: number;

  category!: Category;
  previous!: Category;

  formGroup!: FormGroup;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,    
    private router: Router,    
    // Services
    private authService: AuthService,     
    public categoriesService: CategoriesService,
  ) {
    this.staffid = this.authService.currentUserValue?.staffid;    
  }

  ngOnInit(): void {
    this.loadCategories();  
  }

  loadCategories() {
    const sb = this.route.paramMap.pipe(
      switchMap(params => {
        // get id from URL
        this.id = Number(params.get('id'));
        if (this.id || this.id > 0) {
          return this.categoriesService.getItemById(this.id);
        }
        return of(EMPTY_CATEGORY);
      }),
    ).subscribe((res: Category) => {
      if (!res) {
        this.router.navigate(['/categories'], { relativeTo: this.route });
      }

      this.category = res as Category;
      this.previous = Object.assign({}, this.category);  

      this.loadForm();
    });
    this.subscriptions.push(sb);
  }

  loadForm() {
    if (!this.category) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.category.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.category.description, Validators.compose([
        Validators.nullValidator,
        Validators.minLength(3),
        Validators.maxLength(250),
      ])],                             
      staffid: [this.staffid],                 
    });    
  }

  changedEditor(event: EditorChangeContent | EditorChangeSelection) {
     }    

  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    const formValues = this.formGroup.value;
    this.category = Object.assign(this.category, formValues);
    if (this.category.id) {
      this.edit();
    } else {
      this.create();
    }   
  }  

  edit() {
    const sbUpdate = this.categoriesService.update(this.category).pipe(
      finalize(() => {}),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.category);
      })
    ).subscribe((res: Category) => {
      this.cdr.detectChanges()
    });
    this.subscriptions.push(sbUpdate);
  }  

  create() {
    const sbCreate = this.categoriesService.create(this.category).pipe(
      finalize(() => this.router.navigate(['/categories'])),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.category);
      })
    ).subscribe((res) => {
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sbCreate);
  }  

  editExit() {
    const formValues = this.formGroup.value;
    this.category = Object.assign(this.category, formValues);

    const sbUpdate = this.categoriesService.update(this.category).pipe(
      tap(() => this.router.navigate(['/categories'])),
      catchError((errorMessage) => {
        console.error('UPDATE ERROR', errorMessage);
        return of(this.category);
      })
    ).subscribe(((res: Category) => this.category = res));
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

  controlHasError(validation: string, controlName: string) {
    const control = this.formGroup.controls[controlName];
    return control.hasError(validation) && (control.dirty || control.touched);
  }

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }    
}
