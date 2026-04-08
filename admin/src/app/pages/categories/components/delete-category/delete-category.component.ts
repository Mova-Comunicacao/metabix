import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { CategoriesService } from '../../services';

@Component({
  selector: 'app-delete-category',
  templateUrl: './delete-category.component.html',
})
export class DeleteCategoryComponent implements OnInit, OnDestroy {
  @Input() id!: number;

  isLoading?: boolean = false;

  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private categoriesService: CategoriesService, 
  ) { }

  ngOnInit(): void {}

  deleteCategory() {
    this.isLoading = true;
    const sb = this.categoriesService.delete(this.id).pipe(
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
