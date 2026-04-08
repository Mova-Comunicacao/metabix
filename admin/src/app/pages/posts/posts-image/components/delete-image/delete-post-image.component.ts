import { Component, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { PictureService } from '../../../core/services';

@Component({
  selector: 'app-delete-post-image',
  templateUrl: './delete-post-image.component.html',
})
export class DeletePostImageComponent implements OnDestroy {
  @Input() id: number;

  isLoading = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private pictureService: PictureService,
  ) { }


  deletePicture() {
    this.isLoading = true;

    const sb = this.pictureService.deletePicture(this.id).pipe(
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