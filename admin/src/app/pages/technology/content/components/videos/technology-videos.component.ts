import { Component, ElementRef, Input, ViewChild, OnInit, OnDestroy } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { catchError, of, Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, moveItemInArray } from '@angular/cdk/drag-drop';

import { AuthService } from '../../../../../modules/auth';

import { Technology, VideoService, Videos } from '../../../core';

import { EditVideoComponent } from './edit-video/edit-video.component';
import { DeleteVideoComponent } from './delete-video/delete-video.component';

@Component({
  selector: 'app-technology-videos',
  templateUrl: './technology-videos.component.html'
})
export class TechonolgyVideosComponent implements OnInit, OnDestroy {
  @ViewChild('dropListContainer', { static: false }) dropListContainer?: ElementRef<HTMLElement>;
  
  @Input() technology?: Technology

  isLoading?: boolean = false;
  staffid?: number;

  videos: Videos[] = [];

  formGroup!: FormGroup;
  
  // Getters
  get videos$() {
    return this.videoservice.items$;
  }

  private subscriptions: Subscription[] = [];  

  constructor(
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    public videoservice: VideoService,
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.loadVideos();
  }

  loadVideos() {
    this.videoservice.fetch();
    const sb = this.videoservice.isLoading$.subscribe((res) => this.isLoading = res);
    this.videoservice.sorting.column = 'order';
    this.subscriptions.push(sb);    
  }

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditVideoComponent, {size: 'lg'});
    modalRef.componentInstance.id = id;
    modalRef.componentInstance.staffid = this.staffid;    
    modalRef.closed.subscribe(() => this.loadVideos());
  }  

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteVideoComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.loadVideos());
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

  dragDropped(event: CdkDragDrop<Videos[] | any, Videos[], number>) {
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

  sortable(data: Videos[]) {
    const sb = this.videoservice.sortable(data).pipe(
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
