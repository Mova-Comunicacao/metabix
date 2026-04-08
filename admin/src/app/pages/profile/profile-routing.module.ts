import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ProfileComponent } from './profile.component';
import { SettingsComponent } from './components/settings/settings.component';
import { OverviewComponent } from './components/overview/overview.component';


const routes: Routes = [
  {
    path: '',   
    component: ProfileComponent,
    children: [
      {
        path: 'overview',
        data: {
          title: 'Settings',
          translate: 'nav.personalInfo'
        },          
        component: OverviewComponent,
      },      
      {
        path: 'settings',
        data: {
          title: 'Settings',
          translate: 'nav.personalInfo'
        },         
        component: SettingsComponent,
      },            
      { path: '', redirectTo: 'overview', pathMatch: 'full' },
      { path: '**', redirectTo: 'overview', pathMatch: 'full' },      
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class ProfileRoutingModule { }
