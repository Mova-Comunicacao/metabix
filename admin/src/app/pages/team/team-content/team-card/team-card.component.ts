import { Component, OnInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import { catchError } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, CdkDragEnter, CdkDragMove, moveItemInArray } from '@angular/cdk/drag-drop';

import {
  SortState,
  IDeleteAction,
  PaginatorState,
} from '../../../../shared';

import { TeamService } from '../../services';
import { DeleteTeamComponent } from '../../components/delete-team/delete-team.component';
import { Teams } from '../../models/teams.model';

@Component({
  selector: 'app-team-card',
  templateUrl: './team-card.component.html',
})
export class TeamCardComponent
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {
  @ViewChild('dropListContainer', { static: true }) dropListContainer!: ElementRef<HTMLDivElement>;
    
  isLoading?: boolean = false;
  currentLang: string;

  paginator!: PaginatorState;
  sorting!: SortState;
  
  teams: Teams[] = [];

  // Getters
  get teams$() {
    return this.teamService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private teamService: TeamService,
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

  getAvatar(folder: string, avatar: string): string {
    return avatar
      ? `${folder}${avatar}`
      : `./assets/media/avatars/blank.png`;
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

  dragDropped(event: CdkDragDrop<Teams[] | any, Teams[], number>) {
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

  sortable(data: Teams[] | null) {
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
