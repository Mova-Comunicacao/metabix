import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize} from 'rxjs/operators';

import { PostsService } from '../../../core/services';

@Component({
  selector: 'app-delete-post',
  templateUrl: './delete-post.component.html',
})
export class DeletePostComponent implements OnInit, OnDestroy {
  @Input() id: number | any;

  isLoading = false;
  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private postsService: PostsService, 
  ) { }

  ngOnInit(): void {
  }

  deletePost() {
    this.isLoading = true;
    const sb = this.postsService.delete(this.id).pipe(
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
