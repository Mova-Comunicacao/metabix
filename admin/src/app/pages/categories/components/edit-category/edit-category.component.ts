import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../modules/auth';

import { CategoriesService } from '../../services/categories.service';
import { Category } from '../../models/category.model';

const EMPTY_CATEGORY: Category = {
  id: 0,
  name: '',
  description: '',
  file_name: '',
  date: new Date
};

@Component({
  selector: 'app-edit-category',
  templateUrl: './edit-category.component.html',
})
export class EditCategoryComponent implements OnInit, OnDestroy {
  @Input() id!: number;
  
  staffid?: number;
  category!: Category;

  formGroup!: FormGroup;

  // Getters
  get isLoading$() {
    return this.categoriesService.isLoading$;
  }

  get categories$() {
    return this.categoriesService.items$;
  }  

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    public modal: NgbActiveModal,
    // Services 
    private adminService: AuthService,
    private categoriesService: CategoriesService,
  ) { 
    this.staffid = this.adminService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadItems();
  }

  loadItems() {
    if (!this.id) {
      this.category = EMPTY_CATEGORY;
      this.loadForm();
    } else {
      const sb = this.categoriesService.getItemById(this.id).pipe(
        first(),
        catchError((err) => {
          this.modal.dismiss(err);
          return of(EMPTY_CATEGORY);
        })
      ).subscribe((res: Category) => {
        this.category = res as Category;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.category.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.category.description, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3)
      ])],
      languageid: [this.category.language?.languageid],
      staffid: [this.staffid],
    });
  }  

  save() {
    this.category = { ...this.category, ...this.formGroup.value };
    if (this.category.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.categoriesService.update(this.category).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.category);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.categoriesService.create(this.category).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.category);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbCreate);
  }

  ngOnDestroy(): void {
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
 
}
