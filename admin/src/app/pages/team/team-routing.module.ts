import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TeamComponent } from './team.component';
import { TeamContentComponent } from './team-content/team-content.component';
import { TeamEditComponent } from './team-edit/team-edit.component';

const routes: Routes = [
  {
    path: '',
    data: {
      translate: 'nav.teamNav',
    },       
    component: TeamComponent,
    children: [
      {
        path: '',
        data: {
          translate: 'nav.teamNav',
          breadcrumb: 'nav.listNav',              
        },                
        component: TeamContentComponent
      },
      {
        path: 'add',
        data: {
          translate: 'nav.teamNav',
          breadcrumb: 'nav.createNav',              
        },         
        component: TeamEditComponent
      },
      {
        path: 'edit/:id',
        data: {
          translate: 'nav.teamNav',
          breadcrumb: 'nav.editNav',              
        },          
        component: TeamEditComponent
      },      
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class TeamRoutingModule { }
