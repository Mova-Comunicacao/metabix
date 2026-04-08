import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { getCSSVariableValue } from '../../../../layout/core/kt/_utils';

import { DashboardService } from '../../services';
import { ITableState } from '../../models';

@Component({
  selector: 'app-widget1',
  templateUrl: './widget1.component.html',
  styleUrls: ['./widget1.component.scss']
})
export class Widget1Component implements OnInit, OnDestroy {
  @Input() start_date: any;
  @Input() end_date: any;
  @Input() chartColor = 'warning';
  @Input() chartHeight = '250px';

  chartOptions: any;

  metrics: any[];
  dimensions: any[];

  isLoading = false;
  
  result: any;

  private subscriptions: Subscription[] = [];
  
  constructor(
    private dashboardService: DashboardService,
  ) {}

  ngOnInit(): void {
    this.chartOptions = this.getChartOptions();

    this.metrics = ['eventCount','activeUsers','screenPageViews','newUsers'];
    this.loadAMetricData();
  }

  loadRequestReport() {
    const filter: ITableState = {
      metrics: this.metrics,           // ex.: ['activeUsers']
      dimensions: this.dimensions,
      start_date: this.start_date ?? null,
      end_date: this.end_date ?? null
    };
    
    this.isLoading = true;
    const sb = this.dashboardService.getReportData(filter).pipe(
    ).subscribe((res) => {
      this.isLoading = false;
      this.result = res.items;
      
      const series = Array.from({length: this.result.length}, 
        (value, key) =>  
        this.result[key].metrics.value
      ); 
      const labels = Array.from({length: this.result.length}, 
        (value, key) =>  
        this.result[key].dimensions.date
      );    
      //this.chartOptions.series.data = series;        
      //this.chartOptions.labels = this.calendar.getToday(), 'd', labels;  

      this.chartOptions.series = [{
        name: '',
        data: series
      }];       
      this.chartOptions.xaxis = {
        type: "datetime",
        categories: labels,
      };
    });
    this.subscriptions.push(sb);    
  }   

  loadAMetricData() {
    const filter: ITableState = {
      metrics: this.metrics,           // ex.: ['activeUsers']
      dimensions: this.dimensions,
      start_date: this.start_date ?? null,
      end_date: this.end_date ?? null
    };

    const sb = this.dashboardService.getReportMetricData(filter).pipe(
    ).subscribe(res => {
      this.result = res.totals ?? 0;
    });
    this.subscriptions.push(sb);    
  }     

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }    

  getChartOptions() {
    const labelColor = getCSSVariableValue('--kt-gray-500');
    const borderColor = getCSSVariableValue('--kt-gray-200');
    const secondaryColor = getCSSVariableValue('--kt-gray-300');
    const baseColor = getCSSVariableValue('--kt-' + this.chartColor);
  
    return {
      series: [],
      chart: {
        fontFamily: 'inherit',
        type: 'bar',
        height: this.chartHeight,
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '50%',
          borderRadius: 5,
        },
      },
      legend: {
        show: false,
      },
      dataLabels: {
        enabled: false,
      },
      stroke: {
        show: true,
        width: 1,
        colors: ['transparent'],
      },
      xaxis: {
        categories: [],
        axisBorder: {
          show: false,
        },
        axisTicks: {
          show: false,
        },
        labels: {
          show: false,
          style: {
            colors: labelColor,
            fontSize: '12px',
          },
        },
      },
      yaxis: {
        labels: {
          show: false,
          style: {
            colors: labelColor,
            fontSize: '12px',
          },
        },
      },
      fill: {
        type: 'solid',
      },
      states: {
        normal: {
          filter: {
            type: 'none',
            value: 0,
          },
        },
        hover: {
          filter: {
            type: 'none',
            value: 0,
          },
        },
        active: {
          allowMultipleDataPointsSelection: false,
          filter: {
            type: 'none',
            value: 0,
          },
        },
      },
      tooltip: {
        style: {
          fontSize: '12px',
        },
        y: {
          formatter: function (val: number) {
            return '$' + val + ' revenue';
          },
        },
      },
      colors: [baseColor],
      grid: {
        padding: {
          top: 10,
        },
        borderColor: borderColor,
        strokeDashArray: 2,
        yaxis: {
          lines: {
            show: true,
          },
        },
      },
    };
  } 
}
