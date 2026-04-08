import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { VideoService } from '../../../../core';

@Component({
  selector: 'app-delete-video',
  templateUrl: './delete-video.component.html',
})
export class DeleteVideoComponent implements OnInit, OnDestroy {
  @Input() id: number;

  isLoading?: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // services
    private videoservice: VideoService,
  ) { }

  ngOnInit(): void {}

  deleteVideo() {
    this.isLoading = true;
    const sb = this.videoservice.deleteVideo(this.id).pipe(
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

  closeModal() {
    this.modal.close();
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 

}