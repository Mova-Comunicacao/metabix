import { Component, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

import { CompanyService } from '../../services';

@Component({
  selector: 'app-delete-image',
  templateUrl: './delete-image.component.html',
})
export class DeleteImageComponent implements OnDestroy {
  @Input() id!: number;

  isLoading = false;
  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private companyService: CompanyService,
  ) { }


  deletePicture() {
    this.isLoading = true;
    const sb = this.companyService.deletePicture(this.id).pipe(
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
