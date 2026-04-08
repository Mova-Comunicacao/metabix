import { Component, EventEmitter, HostBinding, Input, OnInit, Output } from '@angular/core';

import { ITableState } from '../../models';

@Component({
  selector: 'app-dropdown',
  templateUrl: './dropdown.component.html',
})
export class DropdownComponent implements OnInit {
  @HostBinding('class') class =
    'menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold w-200px pb-2';
  
  @Input() metrics!: ITableState | any;
  @Input() dimensions!: ITableState | any;

  @Input() dimensionsData!: ITableState[] | any;

  @Output() outMetrics = new EventEmitter<ITableState>();
  @Output() outDimensions = new EventEmitter<ITableState>();
  
  metricsData: string[] = ['activeUsers', 'newUsers', 'sessions'];

  constructor() { }

  ngOnInit(): void {
  }

  metricChange() {
    this.outMetrics.emit(this.metrics);
  }

  dimensionChange() {
    this.outDimensions.emit(this.dimensions);
  }  

}
