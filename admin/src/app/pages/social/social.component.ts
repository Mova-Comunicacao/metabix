import { Component, OnInit, OnDestroy } from '@angular/core';
import { catchError, finalize } from 'rxjs/operators';
import { Subscription, of } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';

import {
  IDeleteAction
} from '../../shared';

import { SocialService } from './services';
import { AuthService } from '../../modules/auth';


import { DeteleSocialComponent } from './components/detele-social/detele-social.component';
import { EditSocialComponent } from './components/edit-social/edit-social.component';
import { Social } from '../team/models/social.model';

@Component({
  selector: 'app-social',
  templateUrl: './social.component.html',
})
export class SocialComponent
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction {

  isLoading?: boolean;
  active: number;

  // Getters
  get socials$() {
    return this.socialService.items$;
  }

  private subscriptions: Subscription[] = [];

  constructor(
    private modalService: NgbModal,
    // Services
    private socialService: SocialService,    
  ) { }
 
  ngOnInit(): void {
    this.socialService.fetch();
    const sb = this.socialService.isLoading$.subscribe(res => this.isLoading = res);
    const sorting = this.socialService.sorting;
    this.socialService.patchState({ sorting });    
    sorting.column = 'order';
    this.subscriptions.push(sb); 
  }

  // form actions
  create() {
    this.edit(0);
  }

  edit(id: number) {
    const modalRef = this.modalService.open(EditSocialComponent, { size: 'lg' });
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.socialService.fetch()); 
  }

  delete(id: number) {
    const modalRef = this.modalService.open(DeteleSocialComponent);
    modalRef.componentInstance.id = id;
    modalRef.closed.subscribe(() => this.socialService.fetch());
  }

  changeStatus(ev: any){
    const ids = ev.target.value;
    const checked = ev.target.checked;
    this.active = checked == true ? 1 : 0;

    const sb = this.socialService.updateStatusForItems(Array.from(ids), +this.active).pipe(
      catchError((errorMessage) => {
        console.error('UPDATE STATUS FOR SELECTED ITEMS', ids, errorMessage);
        return of(undefined);
      }),      
    ).subscribe();
    this.subscriptions.push(sb);    
  }  
 
  drop(event: CdkDragDrop<Social[] | any>) {
    if (event.previousContainer === event.container) {
      moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
      this.sortable(event.container.data);
    }
  }  
   
  sortable(data: Social[]) {
    const sb = this.socialService.sortable(data).pipe(
      catchError((errorMessage) => {
        console.log(errorMessage);
        return of(undefined);
      }),
      finalize(() => this.isLoading = false)
    ).subscribe();
    this.subscriptions.push(sb);
  }   

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }   

}
