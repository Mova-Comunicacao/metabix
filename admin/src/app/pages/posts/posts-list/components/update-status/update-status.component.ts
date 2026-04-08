import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, first } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';

import { 
  PostsService,
  Posts
} from '../../../core';

@Component({
  selector: 'app-update-status',
  templateUrl: './update-status.component.html',
})
export class UpdateStatusComponent implements OnInit, OnDestroy {
  @Input() ids!: number[];

  active = 0;
  posts: Posts[] = [];

  isLoading = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private postsService: PostsService, 
  ) { }

  ngOnInit(): void {
    this.loadPosts();
  }

  loadPosts() {
    const sb = this.postsService.items$.pipe(
      first()
    ).subscribe((res: Posts[]) => {
      this.posts = res.filter(c => this.ids.indexOf(c.id) > -1);
    });
    this.subscriptions.push(sb);
  }

  updatePostStatus() {
    this.isLoading = true;
    const sb = this.postsService.updateStatusForItems(this.ids, +this.active).pipe(
      catchError((errorMessage) => {
        this.modal.dismiss(errorMessage);
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
