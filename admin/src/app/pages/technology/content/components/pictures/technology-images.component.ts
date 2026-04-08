import { Component, ElementRef, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { catchError, of, Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, moveItemInArray } from '@angular/cdk/drag-drop';

import { Pictures, PictureService, Technology } from '../../../core';

import { UploadImageComponent } from './upload-image/upload-image.component';
import { DeleteImageComponent } from './delete-image/delete-image.component';

import { AuthService } from '../../../../../modules/auth';

@Component({
  selector: 'app-technology-images',
  templateUrl: './technology-images.component.html'
})
export class TechnologyImagesComponent implements OnInit, OnDestroy {
  @ViewChild('dropListContainer', { static: false }) dropListContainer?: ElementRef<HTMLElement>;

  @Input() technology?: Technology;

  pictures: Pictures[] = [];

  isLoading?: boolean = false;
  staffid?: number;

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
    this.subscriptions.push(sb);    
  }  

  upload(): void {
    const modalRef = this.modalService.open(UploadImageComponent, {size: 'lg'});
    modalRef.componentInstance.staffid = this.staffid;
    modalRef.closed.subscribe(() => this.loadPictures());
  }  
 
  deleteImage(id: number): void {  
    const modalRef = this.modalService.open(DeleteImageComponent);
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
}
