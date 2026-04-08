import { Component, OnInit, Input, OnDestroy, AfterViewInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { DashboardService } from '../../services';
import { ITableState } from '../../models';

@Component({
  selector: 'app-widget6',
  templateUrl: './widget6.component.html',
  styleUrls: ['./widget6.component.scss']
})
export class Widget6Component implements OnInit, OnDestroy, AfterViewInit {
  @Input() start_date!: string | null;
  @Input() end_date!: string | null;

  metrics: any[];
  dimensions: any[];

  channel: string[] = [];
  session: string[] = [];

  dimensionsData: string[] = ['sessionDefaultChannelGroup', 'sessionSource', 'sessionMedium', 'sessionSourceMedium'];

  result: any;
  isLoading = false;

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
      'sessionDefaultChannelGroup',
      'sessionSource',
      'sessionMedium', 
      'sessionSourceMedium',
    ];

    this.session = [
      'sessionSource'
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
    ).subscribe((res: any) => {
      this.isLoading = false;
      this.result = res.items;
      this.channel = Array.from({length: this.result.length}, 
        (value, key) =>  
        this.result[key].dimensions[0].value
      ); 
      this.session = Array.from({length: this.result.length}, 
        (value, key) =>  {
        return this.result[key].dimensions[1].value
      });         
    });
    this.subscriptions.push(sb);    
  }   

  metricChange(metrics: ITableState[]) {
    this.metrics = Array.from(metrics);
    this.loadRequestReport();
  } 

  dimensionChange(dimensionsData: ITableState[]) {  
    /*
    this.dimensions = Array.from(dimensionsData);
    */
    this.dimensions = Array.from({length: dimensionsData.length}, 
      (value, key) =>  
      this.dimensionsData[key]
    );       
    console.log(this.dimensions)
    this.loadRequestReport();
  }    

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  } 

}
