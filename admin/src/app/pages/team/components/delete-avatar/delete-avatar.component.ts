import { Component, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { TeamService } from '../../services';

@Component({
  selector: 'app-delete-avatar',
  templateUrl: './delete-avatar.component.html',
})
export class DeleteAvatarComponent implements OnDestroy {
  @Input() teamid!: number;

  isLoading?: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private teamService: TeamService,
  ) { }

  deletePicture() {
    this.isLoading = true;

    const sb = this.teamService.deletePicture(this.teamid).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(undefined);
      }),
      finalize(() => {
        this.isLoading = false;
        this.modal.close();
      })
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
