import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import {
  SortState,
  IDeleteAction,
  PaginatorState,
} from '../../../../shared';

import { TeamService } from '../../services';
import { Teams } from '../../models/teams.model';

import { DeleteTeamComponent } from '../../components/delete-team/delete-team.component';

@Component({
  selector: 'app-team-list',
  templateUrl: './team-list.component.html',
})
export class TeamListComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {

  isLoading?: boolean = false;

  paginator!: PaginatorState;
  sorting!: SortState;
  
  // Getters
  get teams$() {
    return this.teamService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    public teamService: TeamService,
  ) { }

  ngOnInit(): void {
    this.teamService.fetch();
    const sb = this.teamService.isLoading$.subscribe(res => this.isLoading = res);
    this.paginator = this.teamService.paginator;
    this.sorting = this.teamService.sorting;
    this.sorting.column = 'order';  
    this.subscriptions.push(sb);
  }

  // pagination
  paginate(paginator: PaginatorState) {
    this.teamService.patchState({ paginator });
  }  

  // actions  
  delete(id: number) {
    const modalRef = this.modalService.open(DeleteTeamComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.teamService.fetch());   
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
    this.teamService.patchState({ sorting });
  }  

  // dragging  
  private dropListReceiverElement?: HTMLElement;
  private dragDropInfo?: { dragIndex: number; dropIndex: number };

  dragDropped(event: CdkDragDrop<Teams[] | any>) {
    if (this.dropListReceiverElement) {
      this.dropListReceiverElement.classList.remove('cdk-drag-receiver-hidden');
      this.dropListReceiverElement = undefined;
    }
    this.dragDropInfo = undefined;

    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }

  sortable(data: Teams[]) {
    const sb = this.teamService.sortable(data).pipe(
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
