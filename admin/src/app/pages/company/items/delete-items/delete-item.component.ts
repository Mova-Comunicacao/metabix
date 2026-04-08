import { Component, OnInit, Input, OnDestroy } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { ItemService } from '../../services';

@Component({
  selector: 'app-delete-item',
  templateUrl: './delete-item.component.html',
})
export class DeleteItemComponent implements OnInit, OnDestroy {
  @Input() id!: number;
  isLoading = false;

  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private itemService: ItemService, 
  ) { }

  ngOnInit(): void {
  }

  deleteItem() {
    this.isLoading = true;
    const sb = this.itemService.deleteItem(this.id).pipe(
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
