import { Component, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Observable, Subscription } from 'rxjs';

import { ChartComponent } from 'ng-apexcharts';

import { getCSSVariableValue } from '../../../../../layout/core/kt/_utils';
import { DateRange, OverviewService, Charts } from '../../../core';

@Component({
  selector: 'app-widget-date',
  templateUrl: './widget-date.component.html'
})
export class WidgetDateComponent implements OnInit, OnDestroy {
  @ViewChild('chart') chart!: ChartComponent;
  
  @Input() start_date: any;
  @Input() end_date: any;

  @Input() chartColor = '';
  @Input() chartHeight = '';
    
  date$!: Observable<DateRange>;
  isLoading$?: Observable<boolean>;
    
  metrics: any[];
  dimensions: any[];

  dimensions_filter: any[] = ['empresa'];
  chartOptions: any;

  charts: Charts[] = [];
  metric: any;

  private subscriptions: Subscription[] = [];

  constructor(
    // Services
    private overview: OverviewService,
  ) { 
    this.isLoading$ = this.overview.isLoading$;
  }

  ngOnInit(): void {
    this.loadAMetricData();
    this.loadFilterRequestReport();
    this.chartOptions = this.getChartOptions();
  }

  loadAMetricData() {
    this.metrics = ['activeUsers', 'newUsers', 'screenPageViews'];

    const filter: any = {
      metrics: this.metrics,
      dimensions: this.dimensions,
      dimensions_filter: this.dimensions_filter,
      start_date: this.start_date ?? null,
      end_date: this.end_date ?? null        
    };

    const sb = this.overview.getReportFilterMetricData(filter).pipe(
    ).subscribe(res => {
      this.metric = res.totals;
    });
    this.subscriptions.push(sb);    
  } 

  loadFilterRequestReport() {
    this.metrics = ['activeUsers'];
    this.dimensions = ['date'];

    const filter: any = {
      metrics: this.metrics,
      dimensions: this.dimensions,
      dimensions_filter: this.dimensions_filter,
      start_date: this.start_date ?? null,
      end_date: this.end_date ?? null      
    };

    const sb = this.overview.getFilterReportData(filter).pipe(
    ).subscribe((res) => {
      this.charts = res.items;
      
      const series = Array.from({length: this.charts.length}, 
        (value, key) => { 
          return res = this.charts[key].metrics[0].value
          //return res.replace(/[^\d]+/g, '');
      }); 
      const labels = Array.from({length: this.charts.length}, 
        (value, key) =>  
        this.charts[key].dimensions[0].date
      );  

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

  getChartOptions() {
    const labelColor = getCSSVariableValue('--bs-gray-500');
    const strokeColor = getCSSVariableValue('--bs-gray-200');
    const baseColor = getCSSVariableValue('--bs-' + this.chartColor);
    const lightColor = getCSSVariableValue('--bs-' + this.chartColor + '-light');
  
    return {
      series: [],
      chart: {
        fontFamily: 'inherit',
        type: 'area',
        height: this.chartHeight,
        toolbar: {
          show: false,
        },
      },
      plotOptions: {},
      legend: {
        show: false,
      },
      dataLabels: {
        enabled: false,
      },
      fill: {
        type: 'gradient',
        opacity: 1,
        gradient: {
          type: 'vertical',
          shadeIntensity: 0.5,
          gradientToColors: undefined,
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 0.415,
          stops: [25, 50, 100],
          colorStops: [],
        },
      },
      stroke: {
        curve: 'smooth',
        show: true,
        width: 3,
        colors: [baseColor],
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
          style: {
            colors: labelColor,
            fontSize: '12px',
          },
        },
        crosshairs: {
          position: 'front',
          stroke: {
            color: baseColor,
            width: 1,
            dashArray: 3,
          },
        },
        tooltip: {
          enabled: true,
          formatter: undefined,
          offsetY: 0,
          style: {
            fontSize: '12px',
          },
        },
      },
      yaxis: {
        labels: {
          style: {
            colors: labelColor,
            fontSize: '12px',
          },
        },
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
            return `${val}  usuários`;
          },
        },
      },
      colors: [lightColor],
      grid: {
        borderColor: strokeColor,
        strokeDashArray: 4,
        yaxis: {
          lines: {
            show: true,
          },
        },
      },
      markers: {
        strokeColors: baseColor,
        strokeWidth: 3,
      },
    }
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
  }
}
