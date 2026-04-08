import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { LayoutComponent } from './layout.component';
import { Routing } from '../pages/page-routing';

const routes: Routes = [
  {
    path: '',
    component: LayoutComponent,
    children: Routing
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class LayoutRoutingModule { }
