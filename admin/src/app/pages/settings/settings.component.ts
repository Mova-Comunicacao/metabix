import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { AuthService, StaffModel } from '../../modules/auth';

@Component({
  selector: 'app-settings',
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.scss']
})
export class SettingsComponent implements OnInit, OnDestroy {

  currentAdmin: StaffModel;
  activeTabId = 1;

  private subscriptions: Subscription[] = [];
  
  constructor(
    // Services
    private authService: AuthService,
  ) { }

  ngOnInit(): void { }


  setActiveTab(tabId: number) {
    this.activeTabId = tabId;
  }

  getActiveTabCSSClass(tabId: number) {
    if (tabId !== this.activeTabId) {
      return '';
    }

    return 'active';
  }  


  reset() {
    //this.settings = Object.assign({}, this.firstSettingsState);
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }

}
