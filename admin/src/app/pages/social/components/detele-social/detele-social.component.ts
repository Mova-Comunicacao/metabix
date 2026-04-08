import { Component, OnInit, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { SocialService } from '../../services';

@Component({
  selector: 'app-detele-social',
  templateUrl: './detele-social.component.html',
})
export class DeteleSocialComponent implements OnInit, OnDestroy {

  @Input() id!: number;
  isLoading = false;

  subscriptions: Subscription[] = [];

  constructor(
    private socialService: SocialService, 
    public modal: NgbActiveModal
  ) { }

  ngOnInit(): void {
  }

  deleteSocial() {
    this.isLoading = true;
    const sb = this.socialService.delete(this.id).pipe(
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
