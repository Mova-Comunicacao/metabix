import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription } from 'rxjs';

import { PageInfoService, PageLink } from '../../../core/page-info.service';

@Component({
  selector: 'app-page-title',
  templateUrl: './page-title.component.html',
})
export class PageTitleComponent implements OnInit, OnDestroy {
  private unsubscribe: Subscription[] = [];

  @Input() appPageTitleDirection = '';
  @Input() appPageTitleBreadcrumb: boolean;
  @Input() appPageTitleDescription: boolean;

  // Getters
  get title$() {
    return this.pageInfo.title$;
  }

  get description$() {
    return this.pageInfo.description$;
  }    

  get breadcrumbs$() {
    return this.pageInfo.breadcrumbs$;
  }  

  constructor(
    private pageInfo: PageInfoService
  ) {}

  ngOnInit(): void { 
   }

  ngOnDestroy() {
    this.unsubscribe.forEach((sb) => sb.unsubscribe());
  }
}
