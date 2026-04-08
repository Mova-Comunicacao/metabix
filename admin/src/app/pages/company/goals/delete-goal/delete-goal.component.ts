import { Component, OnInit, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { GoalService } from '../../services';

@Component({
  selector: 'app-delete-goal',
  templateUrl: './delete-goal.component.html',
})
export class DeleteGoalComponent implements OnInit, OnDestroy {

  @Input() id!: number;
  isLoading = false;

  subscriptions: Subscription[] = [];

  constructor(
    private goalService: GoalService, 
    public modal: NgbActiveModal
  ) { }

  ngOnInit(): void {
  }

  deleteGoal() {
    this.isLoading = true;
    const sb = this.goalService.delete(this.id).pipe(
      tap(() => this.modal.close()),
      catchError((err) => {
        this.modal.dismiss(err);
        return of(undefined);
      }),
      finalize(() => {
        this.isLoading = false;
      })
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }
}
