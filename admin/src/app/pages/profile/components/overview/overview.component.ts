import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { first } from 'rxjs/operators';

import { AuthService, StaffModel } from '../../../../modules/auth';

@Component({
  selector: 'app-overview',
  templateUrl: './overview.component.html',
})
export class OverviewComponent implements OnInit, OnDestroy {

  staff!: StaffModel;

  subscriptions: Subscription[] = [];
  
  constructor(
    private authService: AuthService,
  ) {}

  ngOnInit(): void {
    this.loadProfile();
  }

  loadProfile() {
    const sb = this.authService.currentUserSubject.asObservable().pipe(
      first(staff => !!staff)
    ).subscribe(staff => {
      this.staff = staff as StaffModel;
      this.staff = { ... this.staff }
    });
    this.subscriptions.push(sb);    
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}
