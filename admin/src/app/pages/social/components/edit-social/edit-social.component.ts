import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { catchError, finalize, first, tap } from 'rxjs/operators';
import { Observable, of, Subscription } from 'rxjs';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../../../modules/auth';

import { Social } from '../../models';
import { SocialService } from '../../services';

const EMPTY_SOCIAL: Social = {
  id: 0,
  name: '',
  link: '',
  active: 1,
  order: 0,
  date: new Date
};

@Component({
  selector: 'app-edit-social',
  templateUrl: './edit-social.component.html',
})
export class EditSocialComponent implements OnInit, OnDestroy {

  socials: any[] = [
    {
      name: 'facebook',
    },
    {
      name: 'instagram',
    },
    {
      name: 'tiktok',
    },      
    {
      name: 'linkedin',
    },  
    {
      name: 'youtube',
    },        
    {
      name: 'whatsapp',
    },      
  ];

  @Input() id!: number;
  staffid?: number;

  social!: Social;

  formGroup!: FormGroup;

  // Getters
  get socials$() {
    return this.socialService.items$;
  }
  get isLoading$() {
    return this.socialService.isLoading$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,
    // Services
    private adminService: AuthService,
    private socialService: SocialService,
  ) { 
    this.staffid = this.adminService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadSocial();
  }

  loadSocial() {
    if (!this.id) {
      this.social = EMPTY_SOCIAL;
      this.loadForm();
    } else {
      const sb = this.socialService.getItemById(this.id).pipe(
        first(),
        catchError((errorMessage) => {
          this.modal.dismiss(errorMessage);
          return of(EMPTY_SOCIAL);
        })
      ).subscribe(social => {
        this.social = social as Social;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    if (!this.social) {
      return;
    } 

    this.formGroup = this.fb.group({
      name: [this.social.name, Validators.required],
      link: [this.social.link, Validators.required],
      active: [this.social.active, Validators.required],
      staffid: [this.staffid],
    });
  }  

  save() {
    this.formGroup.markAllAsTouched();
    if (!this.formGroup.valid) {
      return;
    }

    this.social = { ...this.social, ...this.formGroup.value };
    if (this.social.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  edit() {
    const sbUpdate = this.socialService.update(this.social).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.social);
      }),
      finalize(() => this.modal.close()),
    ).subscribe(res => this.social = res);
    this.subscriptions.push(sbUpdate);
  }

  create() {
    const sbCreate = this.socialService.create(this.social).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.social);
      }),
      finalize(() => this.modal.close()),
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

}
