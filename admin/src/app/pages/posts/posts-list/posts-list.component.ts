import { Component, OnInit, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { catchError, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import {
  GroupingState,
  PaginatorState,
  SortState,
  IDeleteAction,
  IDeleteSelectedAction,
  IUpdateStatusForSelectedAction,
  ISortView,
  IFilterView,
  IGroupingView,
  ISearchView,
} from '../../../shared';

import { 
  PostsService, 
  CategoriesService,
  Category
} from '../core';

import { DeletePostComponent } from './components/delete-post/delete-post.component';
import { DeletePostsComponent } from './components/delete-posts/delete-posts.component';
import { UpdateStatusComponent } from './components/update-status/update-status.component';

@Component({
  selector: 'app-posts-list',
  templateUrl: './posts-list.component.html',
})
export class PostsListComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction,
  IDeleteSelectedAction,
  IUpdateStatusForSelectedAction,
  ISortView,
  IFilterView,
  IGroupingView,
  ISearchView {

  isLoading?: boolean = false;
  active = 1;

  categories: Category[] = [];

  paginator!: PaginatorState;
  sorting!: SortState;
  grouping!: GroupingState;

  filterGroup!: FormGroup;
  searchGroup!: FormGroup;  

  private subscriptions: Subscription[] = []; // Read more: => https://brianflove.com/2016/12/11/anguar-2-unsubscribe-observables/

  constructor(
    private fb: FormBuilder,
    private modalService: NgbModal,
    // Services
    public posts: PostsService,
    public categoriesService: CategoriesService,
  ) {  }

  ngOnInit(): void {
    this.filterForm();
    this.searchForm();

    this.posts.fetch();
    const sb = this.posts.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);
    this.grouping = this.posts.grouping;
    this.paginator = this.posts.paginator;
    this.sorting = this.posts.sorting;
    this.sorting.column = 'date';
    this.sorting.direction = 'desc';
    
    this.loadCategories();    
  }

  loadCategories() {
    this.categoriesService.fetch();  
    const sb = this.categoriesService.isLoading$.subscribe(res => {
      this.isLoading = res;       
    });
    this.subscriptions.push(sb);   
  }

  // filtration
  filterForm() {
    this.filterGroup = this.fb.group({
      category_id: [''],
      search_string: [''],
    });
    this.subscriptions.push(
      this.filterGroup.controls['category_id'].valueChanges.subscribe(() =>
        this.filter()
      )
    );    
  }

  filter() {
    const filter: any = {};
    const category_id = this.filterGroup.get('category_id')?.value;
    if (category_id) {
      filter['category_id'] = category_id;
    }
  
    this.posts.patchState( filter );
  }  

  // search
  searchForm() {
    this.searchGroup = this.fb.group({
      search_string: [''],
    });
    const searchEvent = this.searchGroup.controls['search_string'].valueChanges
      .pipe(
        /*
        The user can type quite quickly in the input box, and that could trigger a lot of server requests. With this operator,
        we are limiting the amount of server requests emitted to a maximum of one every 150ms
        */
        debounceTime(150),
        distinctUntilChanged()
      )
      .subscribe((val) => this.search(val));
    this.subscriptions.push(searchEvent);
  }

  search(search_string: string) {
    this.posts.patchState({ search_string });
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
    this.posts.patchState({ sorting });
  }  

  // pagination
  paginate(paginator: PaginatorState) {
    this.posts.patchState({ paginator });
  }

  // actions
  delete(id: number) {
    const modalRef = this.modalService.open(DeletePostComponent);
    modalRef.componentInstance.id = id;
    modalRef.result.then(
      () => this.posts.fetch(),
      () => { }
    );       
   }

  deleteSelected() {
    const modalRef = this.modalService.open(DeletePostsComponent);
    modalRef.componentInstance.ids = this.grouping.getSelectedRows();
    modalRef.result.then(
      () => this.posts.fetch(),
      () => { }
    );       
  }

  changeStatus(ev: any){
    const ids = ev.target.value;
    const checked = ev.target.checked;
    this.active = checked == true ? 1 : 0;
    
    //this.isLoading = true;
    const sb = this.posts.updateStatusForItems([ids], +this.active).pipe(
    ).subscribe();
    this.subscriptions.push(sb);      
  }    

  updateStatus(id: number[]) {
    const modalRef = this.modalService.open('', {size: 'lg'});
    modalRef.componentInstance.ids = Array.from(id);
    modalRef.result.then(
      () => this.posts.fetch(),
      () => { }
    );
  }

  updateStatusForSelected() {
    const modalRef = this.modalService.open(UpdateStatusComponent, {size: 'lg'});
    modalRef.componentInstance.ids = this.grouping.getSelectedRows();
    modalRef.result.then(
      () => this.posts.fetch(),
      () => { }
    );
  }

  drop(event: CdkDragDrop<any[] | any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }
  
  sortable(data: any[]) {
    const sb = this.posts.sortable(data).pipe(
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
