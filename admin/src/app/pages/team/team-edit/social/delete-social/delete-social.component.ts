import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { SocialService } from '../../../services';

@Component({
  selector: 'app-delete-social',
  templateUrl: './delete-social.component.html',
})
export class DeleteSocialComponent implements OnInit, OnDestroy {
  @Input() id: number | any;

  isLoading = false;
  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private socialService: SocialService, 
  ) { }

  ngOnInit(): void {
  }

  deleteSocial() {
    this.isLoading = true;
    const sb = this.socialService.deleteSocial(this.id).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(undefined);
      }),
      finalize(() => {
        this.modal.close();
        this.isLoading = false;
      })
    ).subscribe();
    this.subscriptions.push(sb);
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 

}
