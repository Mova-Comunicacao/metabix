import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import {
  GroupingState,
  SortState,
  IDeleteAction,
  IGroupingView,
} from '../../../../shared';

import { SocialService } from '../../services';

import { Social } from '../../models/social.model';
import { Teams } from '../../models/teams.model';

import { EditSocialComponent } from './edit-social/edit-social.component';
import { DeleteSocialComponent } from './delete-social/delete-social.component';

@Component({
  selector: 'app-social',
  templateUrl: './social.component.html',
})
export class SocialComponent 
  implements 
  OnInit, 
  OnDestroy,
  IDeleteAction,
  IGroupingView {

  @Input() team!: Teams;

  isLoading = false;

  sorting!: SortState;
  grouping!: GroupingState;

  active: number;

  private subscriptions: Subscription[] = [];
    
  constructor(
    private modalService: NgbModal,
    // Services
    public socialService: SocialService
  ) { }

  ngOnInit(): void {
    const sb = this.socialService.isLoading$.subscribe(res => this.isLoading = res);
    this.subscriptions.push(sb);    
    this.socialService.patchState({ entityId: this.team.id });
    this.grouping = this.socialService.grouping
    this.sorting = this.socialService.sorting;
    this.sorting.column = 'order';    
    this.socialService.fetch();    
  }

  create() {
    this.edit(0);
  }

  edit(id: number): void {
    const modalRef = this.modalService.open(EditSocialComponent);
    modalRef.componentInstance.id = id;
    modalRef.componentInstance.teamid = this.team.id;
    modalRef.result.then(() =>
      this.socialService.fetch(),
      () => { }
    );
  }    

  delete(id: number) {
    const modalRef = this.modalService.open(DeleteSocialComponent);
    modalRef.componentInstance.id = id;
    modalRef.result.then(
      () => this.socialService.fetch(),
      () => { }
    );
  }  

  ngOnDestroy() {
    this.subscriptions.forEach((sb) => sb.unsubscribe());
  }     
}
