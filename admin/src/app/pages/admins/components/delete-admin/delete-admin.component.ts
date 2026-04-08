import { Component, OnInit, Input, OnDestroy } from '@angular/core';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { of, Subscription } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';

import { AdminService } from '../../services';

@Component({
  selector: 'app-delete-admin',
  templateUrl: './delete-admin.component.html',
})
export class DeleteAdminComponent implements OnInit, OnDestroy {
  @Input() id: number | any;

  isLoading = false;

  hasError = false;
  errorMessage = '';

  subscriptions: Subscription[] = [];

  constructor(
    public modal: NgbActiveModal,
    // Services
    private adminService: AdminService, 
  ) { }

  ngOnInit(): void {}

  deleteAdmin() {
    this.isLoading = true;
    const sb = this.adminService.delete(this.id).pipe(
      catchError((err) => {
        this.modal.dismiss(err);
        return of(undefined);
      }),
      finalize(() => {
        this.isLoading = false;
      })
    ).subscribe((res) => {
      if(res?.alert?.type === 'error') {
        this.hasError = true;
        this.errorMessage = res?.alert?.message ?? 'Error uploading file'
      } else {
        this.hasError = false;
        this.errorMessage = '';        
        this.modal.close();
      }      
    });
    this.subscriptions.push(sb);
  }  

  ngOnDestroy(): void {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
