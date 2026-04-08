import { Component, Input, OnDestroy } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { PartnerService } from '../../services';

@Component({
  selector: 'app-delete-item',
  templateUrl: './delete-item.component.html',
})
export class DeleteItemComponent implements OnDestroy {
  @Input() id!: number;
  
  isLoading = false;

  private subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private partnerService: PartnerService, 
  ) { }


  deletePartner() {
    this.isLoading = true;

    const sb = this.partnerService.delete(this.id).pipe(
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
