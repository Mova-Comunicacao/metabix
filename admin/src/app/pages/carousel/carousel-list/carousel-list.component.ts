import { Component, OnInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import { catchError, finalize } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, moveItemInArray } from '@angular/cdk/drag-drop';

import { EditCarouselComponent } from '../components/edit-carousel/edit-carousel.component'
import { DeleteSlideComponent } from '../components/delete-slide/delete-slide.component'

import { AuthService } from '../../../modules/auth';
import {
  SortState,
  IDeleteAction
} from '../../../shared/crud-table';

import { CarouselService } from '../services';
import { Carousel } from '../models';

@Component({
  selector: 'app-carousel-list',
  templateUrl: './carousel-list.component.html',
})
export class CarouselListComponent
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {
  @ViewChild('dropListContainer', { static: true }) dropListContainer!: ElementRef<HTMLDivElement>;

  sorting: SortState;
  isLoading: boolean;

  active?: number;
  staffid?: number;

  // Getters
  get carousel$() {
    return this.carouselService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    private carouselService: CarouselService, 
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.carouselService.fetch();
    const sb = this.carouselService.isLoading$.subscribe(res => this.isLoading = res);
    this.carouselService.sorting.column = 'order';
    this.subscriptions.push(sb);
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

  dragDropped(event: CdkDragDrop<Carousel[] | any, Carousel[], number>) {
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
   
  sortable(data: Carousel[]) {
    const sb = this.carouselService.sortable(data).pipe(
      catchError((errorMessage) => {
        console.log(errorMessage);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditCarouselComponent, {size: 'lg'});
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe((result) =>  {
      this.carouselService.fetch();

      if (result && typeof result === 'number') {
        const nextRef = this.modalService.open(EditCarouselComponent, { size: 'lg' });
        nextRef.componentInstance.id = result;
      }      
    });    
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteSlideComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.carouselService.fetch());
  }  

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }  

}
