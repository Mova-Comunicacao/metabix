import { Component, OnInit, Input, OnDestroy, AfterViewInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';

import { DashboardService, DateRange } from '../../services';
import { ITableState } from '../../models';

import {
  ApexNonAxisChartSeries,
  ApexPlotOptions,
  ApexChart,
  ApexLegend,
  ApexResponsive,
  ChartComponent
} from "ng-apexcharts";

export interface ChartOptions {
  series: ApexNonAxisChartSeries;
  chart: ApexChart;
  labels: string[];
  colors: string[];
  legend: ApexLegend;
  plotOptions: ApexPlotOptions;
  responsive: ApexResponsive | ApexResponsive[];
}


@Component({
  selector: 'app-widget3',
  templateUrl: './widget3.component.html',
  styleUrls: ['./widget3.component.scss']
})
export class Widget3Component implements OnInit, OnDestroy, AfterViewInit {
  @ViewChild('chart') chart!: ChartComponent;
  
  @Input() start_date!: string | null;
  @Input() end_date!: string | null;

  @Input() baseColor: any;
  @Input() symbolShape: any;
  @Input() chartColor = '';
  @Input() chartHeight = '320';

  metrics: any[];
  dimensions: any[];
  
  dimensionsData: string[] = ['deviceCategory', 'browser'];

  result: any;
  isLoading = false;

  chartOptions: any;

  private subscriptions: Subscription[] = [];

  constructor(
    private dashboardService: DashboardService,
  ) { }

  ngOnInit(): void {
    if (!this.baseColor) {
      this.baseColor = 'warning';
    }
    if (!this.symbolShape) {
      this.symbolShape = 'symbol-circle';
    }

    this.chartOptions = this.getChartOptions();     
  }

  ngAfterViewInit(): void {
    this.metrics = [
      'activeUsers'
    ];
    this.dimensions = [
      'deviceCategory',
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
      const series = Array.from({length: this.result.length}, 
        (value, key) => { 
        return res = this.result[key].metrics[0].value
      }); 
      const labels = Array.from({length: this.result.length}, 
        (value, key) =>  
        this.result[key].dimensions[0].value
      );   
       
      this.chartOptions.series = series;        
      this.chartOptions.labels = labels;  
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

  getChartOptions() {
    return {
      series: [],
      chart: {
        height: this.chartHeight,
        type: "radialBar"
      },
      plotOptions: {
        radialBar: {
          offsetY: 0,
          startAngle: 0,
          endAngle: 270,
          hollow: {
            margin: 5,
            size: "25%",
            background: "transparent",
            image: undefined
          },
          dataLabels: {
            name: {
              show: false
            },
            value: {
              show: false
            }
          }
        }
      },
      colors: ["#2DA44A", "#006536", "#1a3428", "#0c291c"],
      labels: [],
      legend: {
        show: true,
        floating: true,
        fontSize: "12px",
        position: "left",
        offsetX: 0,
        offsetY: 0,
        labels: {
          useSeriesColors: true
        },
        formatter: function(seriesName: any, opts: any) {
          return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex];
        },
        itemMargin: {
          horizontal: 1
        }
      },
      responsive: [
        {
          breakpoint: 480,
          options: {
            legend: {
              show: false
            }
          }
        }
      ]      
    }
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }

}
