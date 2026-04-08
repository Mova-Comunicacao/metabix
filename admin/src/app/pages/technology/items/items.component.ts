import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError, finalize } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import { EditItemsComponent } from './components/edit-items/edit-items.component';
import { DeleteItemsComponent } from './components/delete-items/delete-items.component';

import {
  GroupingState,
  PaginatorState,
  SortState,
} from '../../../shared/crud-table';


import { ItemService } from '../core';
import { Items } from '../../company/models';

@Component({
  selector: 'app-items',
  templateUrl: './items.component.html'
})
export class ItemsComponent implements OnInit, OnDestroy {
  isLoading?: boolean = false;

  paginator!: PaginatorState;
  sorting!: SortState;
  grouping!: GroupingState;

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    public items: ItemService,
  ) { }

  ngOnInit(): void {
    this.items.fetch();
    const sb = this.items.isLoading$.subscribe(res => this.isLoading = res);
    this.grouping = this.items.grouping;
    this.paginator = this.items.paginator;
    this.sorting = this.items.sorting;
    this.sorting.column = 'order';
    this.subscriptions.push(sb);  
  }  
  
  drop(event: CdkDragDrop<Items[]> | any) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
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
    this.items.patchState({ sorting });
  }  

  // pagination
  paginate(paginator: PaginatorState) {
    this.items.patchState({ paginator });
  }

  sortable(data: any[]) {
    const sb = this.items.sortableItems(data).pipe(
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
    const modalRef = this.modalService.open(EditItemsComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe((result) =>  {
      this.items.fetch();

      if (result && typeof result === 'number') {
        const nextRef = this.modalService.open(EditItemsComponent, { size: 'lg' });
        nextRef.componentInstance.id = result;
      }      
    });
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteItemsComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.items.fetch())
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }   
}
