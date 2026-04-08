import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { NgApexchartsModule } from 'ng-apexcharts';
import { 
  NgbDropdownModule,
  NgbDatepickerModule,
} from '@ng-bootstrap/ng-bootstrap';

// components
import { ComponentsComponent } from './components/components.component';
import { DropdownComponent } from './components/dropdown/dropdown.component';

// Widget
import { Widget1Component } from './components/widget1/widget1.component';
import { Widget2Component } from './components/widget2/widget2.component';
import { Widget3Component } from './components/widget3/widget3.component';
import { Widget4Component } from './components/widget4/widget4.component';
import { Widget5Component } from './components/widget5/widget5.component';
import { Widget6Component } from './components/widget6/widget6.component';

import { DashboardRoutingModule } from './dashboard-routing.module';
import { DashboardComponent } from './dashboard.component';

import { 
  KeeniconModule,
  SharedModule
} from '../../shared';


@NgModule({
  declarations: [
    DashboardComponent,
    ComponentsComponent,
    DropdownComponent,
    // Widget
    Widget1Component,
    Widget2Component,
    Widget3Component,
    Widget4Component,
    Widget5Component,
    Widget6Component
  ],
  imports: [
    CommonModule,
    DashboardRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    NgbDropdownModule,
    NgbDatepickerModule,   
    NgApexchartsModule,
    InlineSVGModule,
    KeeniconModule,
    SharedModule
  ],
})
export class DashboardModule { }
