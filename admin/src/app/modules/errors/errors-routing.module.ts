import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { LocalizeRouterModule } from '@gilsdav/ngx-translate-router';

import { Error404Component } from './error404/error404.component';
import { Error500Component } from './error500/error500.component';
import { ErrorsComponent } from './errors.component';

const routes: Routes = [
  {
    path: '',
    component: ErrorsComponent,
    children: [
      {
        path: '404',
        title: '404',
        component: Error404Component,
      },
      {
        path: '500',
        title: '500',
        component: Error500Component,
      },
      { path: '', redirectTo: '404', pathMatch: 'full' },
      { path: '**', redirectTo: '404', pathMatch: 'full' },
    ],
  },
];

@NgModule({
  imports: [
    RouterModule.forChild(routes),
    LocalizeRouterModule.forChild(routes)
  ],
  exports: [RouterModule, LocalizeRouterModule],
})
export class ErrorsRoutingModule {}
