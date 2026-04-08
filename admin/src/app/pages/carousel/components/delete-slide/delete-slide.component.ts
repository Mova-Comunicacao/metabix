import { Component, OnInit, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { CarouselService } from '../../services';

@Component({
  selector: 'app-delete-slide',
  templateUrl: './delete-slide.component.html',
})
export class DeleteSlideComponent implements OnInit, OnDestroy {
  @Input() id!: number;
  
  isLoading?: boolean = false;

  subscriptions: Subscription[] = [];

  constructor(
    private carouselService: CarouselService, 
    public modal: NgbActiveModal,
  ) { }

  ngOnInit(): void {}

  deleteImage() {
    this.isLoading = true;
    const sb = this.carouselService.delete(this.id).pipe(
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
