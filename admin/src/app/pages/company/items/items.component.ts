import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import { ItemService } from '../services';

import { EditItemComponent } from './edit-items/edit-item.component';
import { DeleteItemComponent } from './delete-items/delete-item.component';
import { Picture } from '../models';

@Component({
  selector: 'app-items',
  templateUrl: './items.component.html',
})
export class ItemsComponent implements OnInit, OnDestroy {

  isLoading?: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    public itemService: ItemService,
  ) { }

  ngOnInit(): void {
    this.itemService.fetch();
    const sb = this.itemService.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);  
    const sorting = this.itemService.sorting;
    sorting.column = 'order';
  }

  dragDropped(event: CdkDragDrop<Picture[] | any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }

  sortable(data: any[]) {
    const sb = this.itemService.sortableItems(data).pipe(
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
    const modalRef = this.modalService.open(EditItemComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.itemService.fetch());
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteItemComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.itemService.fetch());
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  } 

}
