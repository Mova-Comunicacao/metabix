import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';

import { LayoutInitService } from '../../../core/layout-init.service';
import { LayoutService } from '../../../core/layout.service';
import { LayoutType } from '../../../core/configs/config';
// Menu Service
import { HeaderMenuService } from '../../config-menu/header-menu.service';

@Component({
  selector: 'app-header-menu',
  templateUrl: './header-menu.component.html',
  styleUrls: ['./header-menu.component.scss']
})
export class HeaderMenuComponent implements OnInit, OnDestroy {

  menuConfig: any;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private header: HeaderMenuService,
    private layoutInit: LayoutInitService,
    private layout: LayoutService, 
    private router: Router, 
  ) {}

  ngOnInit(): void {
    // menu load
    const menuSubscr = this.header.menuConfig$.pipe()
    .subscribe(res => {
      this.menuConfig = res;
    });
    this.subscriptions.push(menuSubscr);    
  }

  calculateMenuItemCssClass(url: string): string {
    return checkIsActive(this.router.url, url) ? 'active' : '';
  }

  setBaseLayoutType(layoutType: LayoutType) {
    this.layoutInit.setBaseLayoutType(layoutType);
  }

  setToolbar(toolbarLayout: 'classic' | 'accounting' | 'extended' | 'reports' | 'saas') {
    const currentConfig = {...this.layout.layoutConfigSubject.value};
    if (currentConfig && currentConfig.app && currentConfig.app.toolbar) {
      currentConfig.app.toolbar.layout = toolbarLayout;
      this.layout.saveBaseConfig(currentConfig)
    }
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }  
}

const getCurrentUrl = (pathname: string): string => {
  return pathname.split(/[?#]/)[0];
};

const checkIsActive = (pathname: string, url: string) => {
  const current = getCurrentUrl(pathname);
  if (!current || !url) {
    return false;
  }

  if (current === url) {
    return true;
  }

  if (current.indexOf(url) > -1) {
    return true;
  }

  return false;
};