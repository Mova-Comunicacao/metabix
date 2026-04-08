import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { PictureService } from '../../../services';

@Component({
  selector: 'app-delete-image',
  templateUrl: './delete-image.component.html',
})
export class DeleteImageComponent implements OnInit, OnDestroy {
  @Input() pictureId!: number;

  isLoading = false;
  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private pictureService: PictureService,
  ) { }

  ngOnInit(): void {}

  deletePicture() {
    this.isLoading = true;
    const sb = this.pictureService.deletePicture(this.pictureId).pipe(
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
