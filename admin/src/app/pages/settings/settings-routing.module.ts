import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { SettingsComponent } from './settings.component';
import { GeneralComponent } from './components/general/general.component';
import { UploadsComponent } from './components/uploads/uploads.component';
import { EmailComponent } from './components/email/email.component';
import { GoogleComponent } from './components/google/google.component'
import { WhatsappComponent } from './components/whatsapp/whatsapp.component';

const routes: Routes = [
  {
    path: '',     
    component: SettingsComponent,
    children: [
      {
        path: 'general',        
        data: {
          translate: 'nav.settingsNav',
          breadcrumb: 'nav.overviewNav'       
        },          
        component: GeneralComponent
      },
      {
        path: 'uploads',
        data: {
          translate: 'nav.settingsNav',
          breadcrumb: 'Files',
        },          
        component: UploadsComponent
      },  
      {
        path: 'email',
        data: {
          translate: 'nav.settingsNav',
          breadcrumb: 'Email',
        },         
        component: EmailComponent
      },
      {
        path: 'google',
        data: {
          translate: 'nav.settingsNav',
          breadcrumb: 'Google',
        },         
        component: GoogleComponent
      },  
      {
        path: 'whatsapp',
        data: {
          translate: 'nav.settingsNav',
          breadcrumb: 'Whatsapp',
        },         
        component: WhatsappComponent
      },                     
      { 
        path: '', 
        redirectTo: 'general', 
        pathMatch: 'full' 
      },
    ]
  },
  { path: '', redirectTo: '', pathMatch: 'full' },
  { path: '**', redirectTo: '', pathMatch: 'full' },   
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SettingsRoutingModule { }
