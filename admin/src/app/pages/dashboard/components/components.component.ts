import { Component, OnInit } from '@angular/core';
import { DatePipe, registerLocaleData } from '@angular/common';
import { Observable } from 'rxjs';

import { DashboardService, DateRange } from '../services';


import localePT from '@angular/common/locales/pt';
registerLocaleData(localePT);
import localeEN from '@angular/common/locales/en';
registerLocaleData(localeEN);
import localeES from '@angular/common/locales/es';
registerLocaleData(localeES);

@Component({
  selector: 'app-components',
  templateUrl: './components.component.html',
  providers: [DatePipe]
})
export class ComponentsComponent implements OnInit {

  date$!: Observable<DateRange | null>;
  isLoading$?: Observable<boolean>;

  constructor(
    private dashboardService: DashboardService,
    datepipe: DatePipe
  ) { 
    this.isLoading$ = this.dashboardService.isLoading$;
  }

  ngOnInit(): void {
    this.date$ = this.dashboardService.currentDate$;
  }

}
