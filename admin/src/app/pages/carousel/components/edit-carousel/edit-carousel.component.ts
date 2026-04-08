import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, first } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../../../modules/auth';

import { CarouselService } from '../../services';
import { Carousel } from '../../models';
import { SettingsService } from '../../../../core';

const EMPTY_CAROUSEL: Carousel = {
  id: 0,
  name: '',
  description: '',
  date: new Date,
  active: 0, 
};

@Component({
  selector: 'app-edit-carousel',
  templateUrl: './edit-carousel.component.html',
})
export class EditCarouselComponent implements OnInit, OnDestroy {
  @Input() id: number;

  carousel!: Carousel;

  staffid?: number;
  active?: number = 0;

  formGroup!: FormGroup;
  
  // Getters
  get isLoading$() {
    return this.carouselService.isLoading$;
  }
  get settings$() {
    return this.settings.settings$;
  }  

  private subscriptions: Subscription[] = [];  

  constructor(
    private fb: FormBuilder, 
    // Modal
    public modal: NgbActiveModal,
    // Services
    private authService: AuthService,
    private settings: SettingsService,
    private carouselService: CarouselService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadSlide();
  }

  loadSlide() {
    if (!this.id) {
      this.carousel = EMPTY_CAROUSEL;
      this.loadForm();
    } else {
      const sb = this.carouselService.getItemById(this.id).pipe(
        first(),
        catchError((err) => {
          this.modal.dismiss(err);
          return of(EMPTY_CAROUSEL);
        }),
      ).subscribe((res) => {
        this.carousel = res as Carousel;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      name: [this.carousel.name, Validators.compose([
        Validators.required, 
        Validators.minLength(3)
      ])],
      description: [this.carousel.description, Validators.compose([
        Validators.nullValidator, 
        Validators.minLength(3), 
        Validators.maxLength(250)
      ])],
      external: [this.carousel.url, Validators.compose([
        Validators.nullValidator, 
      ])],             
      active: [this.carousel.active],
      type: [this.carousel.type ?? 'video'],
      staffid: [this.staffid],
    });
  }  

  save() {
    this.carousel = { ...this.carousel, ...this.formGroup.value };

    if (this.carousel.id) {
      this.edit();
    } else {
      this.create();
    }
  }  

  create() {
    const sbCreate = this.carouselService.create(this.carousel).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.carousel);
      })
    ).subscribe({
      next: (res) => {
        this.modal.close(res.data?.id);
      },
      error: (err) => {
        this.modal.dismiss(err);
      }
    });
    this.subscriptions.push(sbCreate);
  }  
  
  edit() {
    const sbUpdate = this.carouselService.update(this.carousel).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.carousel);
      }),
      finalize(() => this.modal.close()),
    ).subscribe();
    this.subscriptions.push(sbUpdate);

  }    

  toggleVisibility(ev: any){
    const checked = ev.target.checked;
    this.active = checked == true ? 0 : 1;
    this.formGroup.get('active')?.setValue(this.active);
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