import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { TeamService } from '../../services';

@Component({
  selector: 'app-delete-team',
  templateUrl: './delete-team.component.html',
})
export class DeleteTeamComponent implements OnInit, OnDestroy {
  @Input() id!: number;

  isLoading = false;

  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private teamService: TeamService, 
  ) { }

  ngOnInit(): void {
  }

  deleteTeam() {
    this.isLoading = true;
    const sb = this.teamService.delete(this.id).pipe(
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
