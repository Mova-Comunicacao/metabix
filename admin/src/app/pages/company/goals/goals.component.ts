import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError, finalize } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import { GoalService } from '../services';

import { EditGoalComponent } from '../goals/edit-goal/edit-goal.component';
import { DeleteGoalComponent } from '../goals/delete-goal/delete-goal.component';

@Component({
  selector: 'app-goals',
  templateUrl: './goals.component.html',
})
export class GoalsComponent implements OnInit, OnDestroy {

  isLoading?: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    public goalService: GoalService
  ) { }

  ngOnInit(): void {
    this.goalService.fetch();
    const sb = this.goalService.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);  
  }

  drop(event: CdkDragDrop<any[]> | any) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }

  sortable(data: any[]) {
    const sb = this.goalService.sortable(data).pipe(
      catchError((errorMessage) => {
        console.log(errorMessage);
        return of(undefined);
      }),
      finalize(() => {})
    ).subscribe();
    this.subscriptions.push(sb);
  }

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditGoalComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.result.then(() =>
      this.goalService.fetch(),
      () => { }
    );
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteGoalComponent);
    modalRef.componentInstance.id = id;
    modalRef.result.then(
      () => this.goalService.fetch(), 
      () => { }
    );
  }

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  } 

}
