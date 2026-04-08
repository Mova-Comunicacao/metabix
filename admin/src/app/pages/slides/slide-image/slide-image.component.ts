import { Component, OnDestroy, OnInit, Input } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';

import { Pictures } from '../models';
import { PictureService } from '../services';

import { UploadImageComponent } from './components/upload-image/upload-image.component';
import { DeleteImageComponent } from './components/delete-image/delete-image.component';

import { AuthService } from '../../../modules/auth';

@Component({
  selector: 'app-slide-image',
  templateUrl: './slide-image.component.html',
})
export class SlideImageComponent implements OnInit, OnDestroy {
  @Input() slideid: number | undefined;
  @Input() folder: string | undefined;

  staffid?: number;
  isLoading?: boolean;

  formGroup!: FormGroup;

  // Getters
  get pictures$() {
    return this.pictureService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    public pictureService: PictureService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadPictures();
  }

  loadPictures() {
    this.pictureService.fetch();
    const sb = this.pictureService.isLoading$.subscribe((res) => this.isLoading = res);
    this.pictureService.patchState({ entityId: this.slideid });
    this.subscriptions.push(sb);    
  }

  upload(): void {
    const modalRef = this.modalService.open(UploadImageComponent, {size: 'lg'});
    modalRef.componentInstance.slideid = this.slideid;
    modalRef.componentInstance.staffid = this.staffid;
    modalRef.closed.subscribe(() => this.loadPictures());
  }  
 
  deleteImage(pictureId: number): void {  
    const modalRef = this.modalService.open(DeleteImageComponent);
    modalRef.componentInstance.pictureId = pictureId;
    modalRef.closed.subscribe(() => this.loadPictures());
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
    
}
