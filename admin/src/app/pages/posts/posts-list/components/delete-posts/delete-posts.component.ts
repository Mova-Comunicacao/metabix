import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { PostsService } from '../../../core/services';

@Component({
  selector: 'app-delete-posts',
  templateUrl: './delete-posts.component.html',
})
export class DeletePostsComponent implements OnInit, OnDestroy {
  @Input() ids: number[] | any;

  isLoading = false;
  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private postsService: PostsService, 
  ) { }

  ngOnInit(): void {
  }

  deletePosts() {
    this.isLoading = true;
    const sb = this.postsService.deleteItems(this.ids).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
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
