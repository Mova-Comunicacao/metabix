import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import {
  SortState,
  IDeleteAction,
  PaginatorState,
  GroupingState
} from '../../../shared';

import { CategoriesService } from '../services';

import { EditCategoryComponent } from '../components/edit-category/edit-category.component';
import { DeleteCategoryComponent } from '../components/delete-category/delete-category.component';
import { Category } from '../models/category.model';

@Component({
  selector: 'app-categories-list',
  templateUrl: './categories-list.component.html',
})
export class CategoriesListComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {

  isLoading?: boolean = false;
  
  paginator!: PaginatorState;
  grouping!: GroupingState;
  sorting!: SortState;
  
  // Getters
  get categories$() {
    return this.categories.items$;
  }

  private subscriptions: Subscription[] = [];
      
  constructor(
    private modalService: NgbModal,
    // Services
    private categories: CategoriesService,
  ) { }

  ngOnInit(): void {
    this.categories.fetch();
    const sb = this.categories.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);
    this.grouping = this.categories.grouping;
    this.paginator = this.categories.paginator;
    this.sorting = this.categories.sorting;
    this.sorting.column = 'order';
  }

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditCategoryComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.categories.fetch());
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteCategoryComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.categories.fetch());    
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
    this.categories.patchState({ sorting });
  }  

  // pagination
  paginate(paginator: PaginatorState) {
    this.categories.patchState({ paginator });
  }

  // dragging  
  dragDropped(event: CdkDragDrop<Category[] | any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }

  sortable(data: Category[]) {
    const sb = this.categories.sortable(data).pipe(
      catchError((errorMessage) => {
        console.log(errorMessage);
        return of(undefined);
      }),
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  } 
}
