import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { PictureService } from '../../../../core';

@Component({
  selector: 'app-delete-image',
  templateUrl: './delete-image.component.html',
})
export class DeleteImageComponent implements OnInit, OnDestroy {
  @Input() id: number;

  isLoading = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private pictureService: PictureService,
  ) { }

  ngOnInit(): void {
  }

  deletePicture() {
    this.isLoading = true;
    const sb = this.pictureService.deletePicture(this.id).pipe(
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

  closeModal() {
    this.modal.close();
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 

}