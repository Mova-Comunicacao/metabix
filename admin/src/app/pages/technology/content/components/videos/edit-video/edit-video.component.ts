import { Component, OnInit, OnDestroy, Input } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, first } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { AuthService } from '../../../../../../modules/auth';

import { 
  VideoService,
  Videos
} from '../../../../core';

const EMPTY_VIDEOS: Videos = {
  id: 0,
  subject: '',
  description: '',
  file_name: '',
  original_file_name: '',
  visible_to_customer: 0,
  visible_full: 0,
  filetype: '',
  external: '',
  dateadded: ''
};

@Component({
  selector: 'app-edit-video',
  templateUrl: './edit-video.component.html',
})
export class EditVideoComponent implements OnInit, OnDestroy {
  @Input() id: number;

  videos!: Videos;

  isLoading?: boolean = false;
  visible_to_customer = 0;
  staffid?: number;

  formGroup!: FormGroup;

  // Getters
  get isLoading$() {
    return this.videoService.isLoading$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder, 
    public modal: NgbActiveModal,
    // Services
    private adminService: AuthService,
    private videoService: VideoService,       
  ) {
    this.staffid = this.adminService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadVideos();     
  }

  loadVideos() {
    this.isLoading = true;

    if (!this.id) {
      this.videos = EMPTY_VIDEOS;
      this.loadForm();
    } else {
      const sb = this.videoService.getVideoById(this.id).pipe(
        first(),
        catchError((err) => {
          this.modal.dismiss(err);
          return of(EMPTY_VIDEOS);
        }),
        finalize(() => this.isLoading = false)
      ).subscribe((res) => {
        this.videos = res as Videos;
        this.loadForm();
      });
      this.subscriptions.push(sb);
    }
  }

  loadForm() {
    this.formGroup = this.fb.group({
      subject: [this.videos.subject, Validators.compose([
        Validators.required, 
      ])],
      description: [this.videos.description, Validators.compose([
        Validators.nullValidator, 
      ])],
      external: [this.videos.external, Validators.compose([
        Validators.required, 
      ])],      
      visible_to_customer: [this.videos.visible_to_customer],     
      staffid: [this.staffid],     
    });    
  } 
  
  save() {
    const formValues = this.formGroup.value;
    this.videos = Object.assign(this.videos, formValues);
    if (this.videos.id) {
      this.edit();
    } else {
      this.create();
    }
  }

  create() {
    const sbCreate = this.videoService.create(this.videos).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.videos);
      }),
      finalize(() => this.modal.close())
    ).subscribe();
    this.subscriptions.push(sbCreate);
  }  

  edit() {
    const sbUpdate = this.videoService.update(this.videos).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(this.videos);
      }),
      finalize(() => this.modal.close()),
    ).subscribe();
    this.subscriptions.push(sbUpdate);
  }  

  toggleVisibility(ev: any){
    const checked = ev.target.checked;
    this.visible_to_customer = checked == true ? 0 : 1;
    this.formGroup.get('visible_to_customer')?.setValue(this.visible_to_customer);
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
