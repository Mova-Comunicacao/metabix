import { Component, OnDestroy, OnInit } from '@angular/core';
import { Observable, Subscription } from 'rxjs';

import { NgbCalendar, NgbDate } from '@ng-bootstrap/ng-bootstrap';

import { DateRange, OverviewService, Charts } from '../core';

@Component({
  selector: 'app-overview',
  templateUrl: './overview.component.html',
  host: {'app-overview-technology': 'overview-technology'},
})
export class OverviewComponent implements OnInit, OnDestroy {
  date$!: Observable<DateRange | null>;

  start_date: NgbDate | any;
  end_date: NgbDate | any;
    
  metrics: any[];
  dimensions: any[];

  dimensions_filter?: string;

  chartColor = 'info';
  chartHeight = '350px';
  chartOptions: any;

  result: Charts[] = [];

  private subscriptions: Subscription[] = [];

  constructor(
    private readonly calendar: NgbCalendar, 
    // Services
    private overview: OverviewService,
    //datepipe: DatePipe
  ) { 
    this.start_date = calendar.getPrev(calendar.getToday(), 'd', 7);
    this.end_date = calendar.getPrev(calendar.getToday(), 'd', 1);      
  }

  ngOnInit(): void {
    this.date$ = this.overview.currentDate$;
    this.loadDateRange();
  }

  loadDateRange() {
    const sb = this.overview.getDateRange(this.start_date, this.end_date).pipe(    
    ).subscribe();
    this.subscriptions.push(sb);     
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }   
}
