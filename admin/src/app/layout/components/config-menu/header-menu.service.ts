import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

import { HeaderMenuConfig } from './header-menu.config';

export class emptyMenuConfig {
  items: []
};

@Injectable({
  providedIn: 'root'
})
export class HeaderMenuService {
  private menuConfigSubject = new BehaviorSubject<any>(emptyMenuConfig);
  menuConfig$: Observable<any>;

  constructor() {
    this.menuConfig$ = this.menuConfigSubject.asObservable();
    this.loadMenu();
  }

  // Here you able to load your menu from server/data-base/localeStorage
  // Default => from HeaderMenuConfig
  private loadMenu() {
    this.setMenu(HeaderMenuConfig);
  }

  private setMenu(menuConfig: any) {
    this.menuConfigSubject.next(menuConfig);
  }

  private getMenu(): any {
    return this.menuConfigSubject.value;
  }
}
