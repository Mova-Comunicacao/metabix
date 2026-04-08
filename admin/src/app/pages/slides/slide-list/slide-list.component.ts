import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError, finalize, tap } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import { CreateSlideComponent } from '../components/create-slide/create-slide.component'
import { DeleteSlideComponent } from '../components/delete-slide/delete-slide.component'

import {
  SortState,
  IDeleteAction
} from '../../../shared/crud-table';

import { SlideService } from '../services';
import { AuthService } from '../../../modules/auth';
import { Slide } from '../models';

@Component({
  selector: 'app-slide-list',
  templateUrl: './slide-list.component.html',
})
export class SlideListComponent
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {

  sorting: SortState;
  isLoading: boolean;

  active?: number;
  staffid?: number;

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private authService: AuthService,
    public slideService: SlideService, 
  ) { 
    this.staffid = this.authService.currentUserValue?.staffid;
  }

  ngOnInit(): void {
    this.slideService.fetch();
    const sb = this.slideService.isLoading$.subscribe(res => this.isLoading = res);
    this.slideService.sorting.column = 'order';
    this.subscriptions.push(sb);
  }

  changeStatus(ev: any){
    const ids = ev.target.value;
    const checked = ev.target.checked;
    this.active = checked == true ? 1 : 0;

    const sb = this.slideService.updateStatusForItems(Array.from(ids), +this.active).pipe(
      catchError((errorMessage) => {
        console.error('UPDATE STATUS FOR SELECTED ITEMS', ids, errorMessage);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sb);    
  }  


  drop(event: CdkDragDrop<Slide[] | any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }  
   
  sortable(data: Slide[]) {
    const sb = this.slideService.sortable(data).pipe(
      catchError((errorMessage) => {
        console.log(errorMessage);
        return of(undefined);
      }),
      finalize(() => this.isLoading = false)
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(CreateSlideComponent, {size: 'lg'});
    modalRef.componentInstance.staffid = this.staffid;
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.slideService.fetch());
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteSlideComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.slideService.fetch());
  }  

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }  

}
