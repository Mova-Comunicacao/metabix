import { Component, OnInit, Input, OnDestroy, AfterViewInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { DashboardService } from '../../services';
import { Charts, ITableState } from '../../models';

@Component({
  selector: 'app-widget4',
  templateUrl: './widget4.component.html',
  styleUrls: ['./widget4.component.scss']
})
export class Widget4Component implements OnInit, OnDestroy, AfterViewInit {
  @Input() start_date!: string | null;
  @Input() end_date!: string | null;

  metrics: any[];
  dimensions: any[];
  
  dimensionsData: string[] = ['country', 'region', 'city'];

  result: any;
  flags = '';
    
  isLoading?: boolean = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private dashboardService: DashboardService, 
  ) { }

  ngOnInit(): void {}

  ngAfterViewInit(): void {
    this.metrics = [
      'activeUsers'
    ];
    this.dimensions = [
      'country',
    ];

    this.loadRequestReport();
  }  

  loadRequestReport() {
    const filter: ITableState = {
      metrics: this.metrics,
      dimensions: this.dimensions,
      start_date: this.start_date ?? null,
      end_date: this.end_date ?? null
    };
    
    this.isLoading = true;
    const sb = this.dashboardService.getReportData(filter).pipe(
    ).subscribe(res => {
      this.isLoading = false;
      this.result = res.items;
    });
    this.subscriptions.push(sb);    
  }  

  metricChange(metrics: ITableState[]) {
    this.metrics = Array.from(metrics);
    this.loadRequestReport();
  } 

  dimensionChange(dimensionsData: ITableState[]) {
    this.dimensions = Array.from(dimensionsData);
    this.loadRequestReport();
  }  

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 

}
