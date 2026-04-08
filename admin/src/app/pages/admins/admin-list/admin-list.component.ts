import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';

import {
  ICreateAction,
  IDeleteAction,
} from '../../../shared/crud-table';

import { AdminService } from '../services';

import { DeleteAdminComponent } from '../components/delete-admin/delete-admin.component';
import { CreateAdminComponent } from '../components/create-admin/create-admin.component';

@Component({
  selector: 'app-admin-list',
  templateUrl: './admin-list.component.html',
})
export class AdminListComponent 
  implements 
  OnInit,
  OnDestroy,
  ICreateAction,
  IDeleteAction {

  isLoading?: boolean;

  private subscriptions: Subscription[] = []; // Read more: => https://brianflove.com/2016/12/11/anguar-2-unsubscribe-observables/

  constructor(
    private modalService: NgbModal,
    // Services
    public adminService: AdminService    
  ) { }

  ngOnInit(): void {
    this.adminService.fetch();
    const sb = this.adminService.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);    
  }

  // form actions
  create() {
    const modalRef = this.modalService.open(CreateAdminComponent, { size: 'lg' });
    modalRef.result.then(() =>
      this.adminService.fetch(),
      () => { }
    );    
  }  
  
  delete(id: number) {
    const modalRef = this.modalService.open(DeleteAdminComponent);
    modalRef.componentInstance.id = id;
    modalRef.result.then(() => 
      this.adminService.fetch(), 
      () => { }
    );    
  }   

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }    

}
