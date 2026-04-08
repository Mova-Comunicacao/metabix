import { Component, Input, AfterViewInit, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';

import { NgbCalendar } from '@ng-bootstrap/ng-bootstrap';
import { getCSSVariableValue } from '../../../../layout/core/kt/_utils';

import { DashboardService } from '../../services';
import { Charts, DateRange, ITableState } from '../../models';

import {
  ChartComponent,
  ApexAxisChartSeries,
  ApexChart,
  ApexXAxis,
  ApexDataLabels,
  ApexTooltip,
  ApexStroke
} from "ng-apexcharts";

export interface ChartOptions {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  xaxis: ApexXAxis;
  stroke: ApexStroke;
  tooltip: ApexTooltip;
  dataLabels: ApexDataLabels;
}

@Component({
  selector: 'app-widget2',
  templateUrl: './widget2.component.html',
  styleUrls: ['./widget2.component.scss'],
})
export class Widget2Component implements OnInit, OnDestroy, AfterViewInit {
  @ViewChild('chart') chart!: ChartComponent;

  @Input() start_date!: string | null;
  @Input() end_date!: string | null;

  @Input() cssClass: any;
  @Input() symbolShape: any;
  @Input() baseColor: any;

  @Input() chartColor = '';
  @Input() chartHeight = '';

  chartOptions: any;

  fontFamily: any = '';
  colorsGrayGray500: any = '';
  colorsGrayGray200: any = '';
  colorsGrayGray300: any = '';
  colorsThemeBase: any = '';
  colorsThemeLight: any = '';
  symbolCSSClasses: any = '';
  svgCSSClasses: any = '';


  metrics: any[];
  dimensions: any[];

  isLoading = false;
  
  result: Charts[] = [];
  date: DateRange[] = [];

  activeUsers!: Charts;
  newUsers!: Charts;
  screenPageViews!: Charts;

  dimensionsData: string[] = ['date'];

  private subscriptions: Subscription[] = [];

  constructor(
    private dashboardService: DashboardService,
    private calendar: NgbCalendar,
  ) { }

  ngOnInit(): void {
    if (!this.baseColor) {
      this.baseColor = 'warning';
    }
    if (!this.symbolShape) {
      this.symbolShape = 'symbol-circle';
    }
    this.symbolCSSClasses = `symbol ${this.symbolShape} symbol-50 symbol-light-${this.baseColor} me-2`;
    this.svgCSSClasses = `svg-icon svg-icon-xl svg-icon-${this.baseColor}`;
    
    this.chartOptions = this.getChartOptions(); 
  }
  
  ngAfterViewInit(): void {    
    this.metrics = ['activeUsers'];
    this.dimensions = ['date'];

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
    ).subscribe((res) => {
      this.isLoading = false;
      this.result = res.items;
      
      const series = Array.from({length: this.result.length}, 
        (value, key) => { 
         return res = this.result[key].metrics[0].value
      }); 
      const labels = Array.from({length: this.result.length}, 
        (value, key) =>  
        this.result[key].dimensions[0].date
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

  metricChange(metrics: ITableState[]) {
    this.metrics = Array.from(metrics);
    this.loadRequestReport();
  } 

  dimensionChange(dimensionsData: ITableState[]) {
    this.dimensions = Array.from(dimensionsData);
    if(this.dimensions[0] == 'yearMonth') {
      const start_date = this.calendar.getPrev(this.calendar.getToday(), 'd', 90);
      const end_date = this.calendar.getPrev(this.calendar.getToday(), 'd', 1);
      this.start_date = start_date ? start_date.year + '-' + start_date.month + '-' + start_date.day : null;
      this.end_date = end_date ? end_date.year + '-' + end_date.month + '-' + end_date.day : null;
    }
    this.loadRequestReport();
  }  
  
  ngOnDestroy() {
    this.subscriptions.forEach(sb => sb.unsubscribe());
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

}
