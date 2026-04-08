import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError, finalize } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import {
  SortState,
  IDeleteAction,
  PaginatorState,
  GroupingState,
  IGroupingView
} from '../../shared';

import { EditItemComponent } from './components/edit-items/edit-item.component';
import { DeleteItemComponent } from './components/delete-items/delete-item.component';

import { PartnerService } from './services';
import { Partners } from './models';

@Component({
  selector: 'app-partners',
  templateUrl: './partners.component.html',
})
export class PartnersComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction,
  IGroupingView {

  isLoading?: boolean = false;

  paginator!: PaginatorState;
  sorting!: SortState;
  grouping!: GroupingState;

  // Getters
  get partners$() {
    return this.partnerService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private partnerService: PartnerService,
  ) { }

  ngOnInit(): void {
    const sb = this.partnerService.isLoading$.subscribe(res => this.isLoading = res);
    this.partnerService.fetch();
    this.grouping = this.partnerService.grouping;
    this.paginator = this.partnerService.paginator;
    this.sorting = this.partnerService.sorting;    
    this.partnerService.sorting.column = 'order';
    this.subscriptions.push(sb);  
  }

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditItemComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe((result) =>  {
      this.partnerService.fetch();

      if (result && typeof result === 'number') {
        const nextRef = this.modalService.open(EditItemComponent, { size: 'lg' });
        nextRef.componentInstance.id = result;
      }      
    });
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteItemComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.partnerService.fetch())
  }

  // sorting
  sort(column: string) {
    const sorting = this.sorting;
    const isActiveColumn = sorting.column === column;
    if (!isActiveColumn) {
      sorting.column = column;
      sorting.direction = 'desc';
    } else {
      sorting.direction = sorting.direction === 'asc' ? 'desc' : 'asc';
    }
    this.partnerService.patchState({ sorting });
  }   

  // pagination
  paginate(paginator: PaginatorState) {
    this.partnerService.patchState({ paginator });
  }  

  drop(event: CdkDragDrop<any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }

  sortable(data: Partners[]) {
    const sb = this.partnerService.sortable(data).pipe(
      catchError((err) => {
        console.log(err);
        return of(undefined);
      }),
      finalize(() => this.isLoading = false)
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  } 
}
