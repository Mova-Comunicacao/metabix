import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Observable, of, Subscription } from 'rxjs';
import { catchError } from 'rxjs/operators';

import { LocalizeRouterService } from '@gilsdav/ngx-translate-router';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../../../modules/auth';

import { SlideService } from '../../services';
import { Slide } from '../../models';

const EMPTY_SLIDE: Slide = {
  id: undefined,
  name: '',
  description: '',
  link: '',
  mask: 0,
  active: 0
};

@Component({
  selector: 'app-create-slide',
  templateUrl: './create-slide.component.html',
})
export class CreateSlideComponent implements OnInit, OnDestroy {

  slide: Slide | any;
  formGroup!: FormGroup;

  staffid?: number;

  private subscriptions: Subscription[] = [];  

  constructor(
    private fb: FormBuilder, 
    private router: Router,
    private localize: LocalizeRouterService,
    // Modal
    public modal: NgbActiveModal,
    // Services
    private authService: AuthService,
    public slideService: SlideService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadSlide();
  }

  loadSlide() {
    this.slide = EMPTY_SLIDE;
    this.loadForm();    
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.slide.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.slide.description, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3), 
        Validators.maxLength(250)
      ])],
      link: [this.slide.link],
      mask: [this.slide.mask],
      active: [this.slide.active],
      staffid: [this.staffid],
    });
  }  

  save() {
    const formValues = this.formGroup.value;
    this.slide = { ...this.slide, ...formValues };

    this.create();
  }  

  create() {
    const sbCreate = this.slideService.create(this.slide).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.slide);
      })
    ).subscribe((res: Slide) => {
      const id = res.data?.id;
      if (id) {
        this.modal.close();
        this.router.navigate(
          [this.localize.translateRoute(`/slide/edit/${id}`)]
        );
      }
    });
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

  isControlTouched(controlName: string): boolean {
    const control = this.formGroup.controls[controlName];
    return control.dirty || control.touched;
  }  
}
