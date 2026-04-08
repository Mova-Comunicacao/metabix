import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Observable, of, Subscription } from 'rxjs';
import { catchError, finalize, first } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../../../../modules/auth';

import { SocialService } from '../../../services';
import { Social } from '../../../models/social.model';

const EMPTY_SOCIAL: Social = {
  id: undefined,
  teamid: undefined,
  name: '',
  link: '',
  active: 1,
  order: 0
};

@Component({
  selector: 'app-edit-social',
  templateUrl: './edit-social.component.html',
})
export class EditSocialComponent implements OnInit, OnDestroy {
  @Input() id!: number;
  @Input() teamid!: number;

  isLoading$?: Observable<boolean>;

  staffid?: number;

  social!: Social;
  formGroup!: FormGroup;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,
    // Services
    private auth: AuthService,
    private socialService: SocialService,  
  ) { 
    this.staffid = this.auth.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.isLoading$ = this.socialService.isLoading$;
    this.loadSocial();
  } 

  loadSocial() {
    if (!this.id) {
      this.social = EMPTY_SOCIAL;
      this.social.teamid = this.teamid;
      this.loadForm();
    } else {
      const sb = this.socialService.getItemById(this.id).pipe(
        first(),
        catchError((errorMessage) => {
          this.modal.dismiss(errorMessage);
          const empty = EMPTY_SOCIAL;
          empty.teamid = this.teamid;
          return of(empty);
        })
      ).subscribe((res) => {
        this.social = res;
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
      name: [this.social.name, Validators.compose([
        Validators.required
      ])],
      link: [this.social.link, Validators.compose([
        Validators.required
      ])],
      active: [this.social.active, Validators.compose([
        Validators.required
      ])],           
      teamid: [this.teamid, Validators.compose([
        Validators.required, 
      ])],     
      staffid: [this.staffid, Validators.compose([
        Validators.required, 
      ])],     
    });
  }  
    
  save() {
    const formData = this.formGroup.value;
    this.social = Object.assign(this.social, formData);

    if (this.id) {
      this.edit();
    } else {
      this.create();
    }
  } 

  create() {
    const sbCreate = this.socialService.addSocial(this.social).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.social);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbCreate);
  }

  edit() {
    const sbUpdate = this.socialService.updateSocial(this.social).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
        return of(this.social);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbUpdate);
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
}
