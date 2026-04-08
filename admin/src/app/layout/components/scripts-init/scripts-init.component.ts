import { Component, OnDestroy, OnInit } from '@angular/core';
import { ResolveEnd, Router } from '@angular/router';
import { Title } from '@angular/platform-browser';
import { filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';

import {
  ToggleComponent,
  ScrollTopComponent,
  DrawerComponent,
  StickyComponent,
  MenuComponent,
  ScrollComponent,
} from '../../core/kt/components';

import { LayoutService } from '../../core/layout.service';
import { PageInfoService } from '../../core/page-info.service';

@Component({
  selector: 'app-scripts-init',
  templateUrl: './scripts-init.component.html',
})
export class ScriptsInitComponent implements OnInit, OnDestroy {
  private unsubscribe: Subscription[] = [];

  constructor(
    private layout: LayoutService,
    private PageInfoService: PageInfoService
  ) {}

  ngOnInit(): void {
    this.pluginsInitialization();
    const layoutUpdateSubscription = this.layout.layoutConfigSubject
      .asObservable()
      .subscribe(() => {
        this.pluginsReInitialization();
      });
    this.unsubscribe.push(layoutUpdateSubscription);
  }

  pluginsInitialization() {
    setTimeout(() => {
      ToggleComponent.bootstrap();
      ScrollTopComponent.bootstrap();
      DrawerComponent.bootstrap();
      StickyComponent.bootstrap();
      MenuComponent.bootstrap();
      ScrollComponent.bootstrap();
    }, 200);
  }

  pluginsReInitialization() {
    setTimeout(() => {
      ToggleComponent.reinitialization();
      ScrollTopComponent.reinitialization();
      DrawerComponent.reinitialization();
      StickyComponent.bootstrap();
      MenuComponent.reinitialization();
      ScrollComponent.reinitialization();
    }, 100);
  }

  ngOnDestroy() {
    this.unsubscribe.forEach((sb) => sb.unsubscribe());
  }
}
