import { ChangeDetectorRef, Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, switchMap, take } from 'rxjs/operators';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';
import { AuthService } from '../../../modules/auth';

import { SlideService } from '../services';
import { Slide } from '../models';

@Component({
  selector: 'app-slide-edit',
  templateUrl: './slide-edit.component.html',
})
export class SlideEditComponent implements OnInit, OnDestroy  {
  id?: number;
  staffid?: number;

  slide!: Slide;
  previous!: Slide;

  formGroup!: FormGroup;
  
  errorMessage?: string = '';
  isLoading?: boolean = false;
  
  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private localize: LocalizeRouterService,
    private route: ActivatedRoute,
    private router: Router,
    // Services
    private authService: AuthService,
    private slideService: SlideService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.isLoading = true;

    const sb = this.route.paramMap.pipe(
      take(1),
      switchMap(params => {
        // get id from URL
        this.id = Number(params.get('id'));
        return this.id && this.id > 0
          ? this.slideService.getItemById(this.id)
          : of(undefined);
      }),
      catchError((err) => {
        this.errorMessage = err;
        return of(undefined);
      }),
      finalize(() => {
          this.isLoading = false;
          this.cdr.markForCheck();        
      })
    ).subscribe((res) => {
      if (!res) {
        this.router.navigate(
          [this.localize.translateRoute(['/slides']), { relativeTo: this.route }]
        );        
      }

      this.slide = res as Slide;
      this.previous = { ...this.slide };
      this.loadForm();
    });
    this.subscriptions.push(sb);
  }

  loadForm() {
    if (!this.slide) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.slide.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],        
      link: [this.slide.link], 
      mask: [this.slide.mask], 
      description: [this.slide.description, Validators.compose([
        Validators.nullValidator,
        Validators.minLength(3),
        Validators.maxLength(250)
      ])],       
      staffid: [this.staffid], 
      languageid: [this.slide.language?.languageid],   
    });
  } 

  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.slide = { ...this.slide, ...this.formGroup.value };
    this.edit();
  }  

  // actions
  edit() {
    this.isLoading = true;

    const sbUpdate = this.slideService.update(this.slide).pipe(
      finalize(() => {
        this.isLoading = false;
        this.cdr.markForCheck();
      }),      
      catchError((err) => {
        console.error('UPDATE ERROR', err);
        return of(this.slide);
      })
    ).subscribe();
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
