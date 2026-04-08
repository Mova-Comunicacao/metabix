import { Component, OnDestroy, OnInit, Input, ChangeDetectorRef, ViewChild, ElementRef } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { catchError, finalize, first, map } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, moveItemInArray } from '@angular/cdk/drag-drop';

import { AuthService } from '../../../modules/auth';

import {
  IDeleteAction
} from '../../../shared';

import { 
  PictureService,
  Pictures
} from '../core';

import { UploadImageComponent } from './components/upload-image/upload-image.component';
import { DeletePostImageComponent } from './components/delete-image/delete-post-image.component';

@Component({
  selector: 'app-post-image',
  templateUrl: './post-image.component.html',
})
export class PostImageComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {
  @ViewChild('dropListContainer', { static: false }) dropListContainer?: ElementRef<HTMLElement>;

  @Input() postId!: number;
  @Input() postFolder!: string;

  staffid?: number;

  formGroup: FormGroup;

  files: File[];
  pictures: Pictures[] = [];

  // Getters
  get isLoading$() {
    return this.pictureService.isLoading$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private cdr: ChangeDetectorRef,
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    private pictureService: PictureService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadPictures();
  }

  loadPictures() {
    const sb = this.pictureService.getItemById(this.postId).pipe(
      map((res: any) => {
        if (Array.isArray(res)) return res as Pictures[];

        if (Array.isArray(res?.pictures)) return res.pictures as Pictures[];
        if (Array.isArray(res?.data))     return res.data as Pictures[];

        return [] as Pictures[];
      }),      
      first(),
      catchError((err) => {
         console.error(err);
        return of([] as Pictures[]);
      })
    ).subscribe((res) => {
      this.pictures = res;
      this.cdr.detectChanges();
    });
    this.subscriptions.push(sb); 
  }

  upload(): void {
    const modalRef = this.modalService.open(UploadImageComponent, {size: 'lg'});
    modalRef.componentInstance.postId = this.postId;
    modalRef.componentInstance.staffid = this.staffid;
    modalRef.closed.subscribe(() => this.loadPictures());
  }  

  delete(id: number) {
    const modalRef = this.modalService.open(DeletePostImageComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.loadPictures());
  }   

  // Dragging
  private dropListReceiverElement?: HTMLElement;
  private dragDropInfo?: { dragIndex: number; dropIndex: number };
  

  dragEntered(event: CdkDragEnter<any>) {
    const drag = event.item;
    const dropList = event.container;
    const dragIndex = drag.data as number;
    const dropIndex = dropList.data as number;

    this.dragDropInfo = { dragIndex, dropIndex };

    const dragEl = drag.element.nativeElement as HTMLElement;
    const phContainer = dropList.element.nativeElement as HTMLElement;
    const phElement = phContainer.querySelector('.cdk-drag-placeholder') as HTMLElement | null;

    if (phElement) {
      phElement.style.width = `${dragEl.offsetWidth}px`;
      phElement.style.height = `${dragEl.offsetHeight}px`;

      phContainer.removeChild(phElement);
      phContainer.parentElement?.insertBefore(phElement, phContainer);

      moveItemInArray(event.container.data, dragIndex, dropIndex);
    }
  } 

  dragMoved(event: CdkDragMove<number>) {
    if (!this.dropListContainer || !this.dragDropInfo) return;

    const phContainer = this.dropListContainer.nativeElement as HTMLElement;
    const phElement = phContainer.querySelector('.cdk-drag-placeholder') as HTMLElement | null;
    if (!phElement) return;

    const receiverElement =
      this.dragDropInfo.dragIndex > this.dragDropInfo.dropIndex
        ? (phElement.nextElementSibling as HTMLElement | null)
        : (phElement.previousElementSibling as HTMLElement | null);

    if (!receiverElement) return;

    receiverElement.classList.add('cdk-drag-receiver-hidden');
    this.dropListReceiverElement = receiverElement;
  }  

  dragDropped(event: CdkDragDrop<Pictures[] | any, Pictures[], number>) {
    if (this.dropListReceiverElement) {
      this.dropListReceiverElement.classList.remove('cdk-drag-receiver-hidden');
      this.dropListReceiverElement = undefined;
    }
    this.dragDropInfo = undefined;

    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      requestAnimationFrame(() => this.sortable(event.container.data));
    }
  }  

  sortable(data: Pictures[]) {
    const sb = this.pictureService.sortable(data).pipe(
      catchError((err) => {
        console.log(err);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sb);
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
