import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { DashboardComponent } from './dashboard.component';
import { ComponentsComponent } from './components/components.component';

const routes: Routes = [
  {
    path: '',
    data: {
      translate: 'nav.dashboardNav',
    },       
    component: DashboardComponent,
    children: [
      {
        path: '',
        data: {
          translate: 'nav.dashboardNav',
          breadcrumb: 'nav.overviewNav'
        },          
        component: ComponentsComponent
      },
      { path: '', redirectTo: '', pathMatch: 'full' },
      { path: '**', redirectTo: '', pathMatch: 'full' },       
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DashboardRoutingModule { }
