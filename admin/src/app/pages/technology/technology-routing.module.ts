import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TechnologyComponent } from './technology.component';
// 
import { OverviewComponent } from './overview/overview.component';
import { ContentComponent } from './content/content.component';
import { ItemsComponent } from './items/items.component';

const routes: Routes = [
  {
    path: '',
    data: {
      translate: 'nav.technologyNav',
    },
    component: TechnologyComponent,
    children: [
      {
        path: 'overview',
        data: {
          translate: 'nav.technologyNav',
          breadcrumb: 'nav.overviewNav',
        },         
        component: OverviewComponent,
      },    
      {
        path: 'content',
        data: {
          translate: 'nav.technologyNav',
          breadcrumb: 'nav.technologyNav',
        },            
        component: ContentComponent
      },      
      {
        path: 'solutions',
        data: {
          translate: 'nav.technologyNav',
          breadcrumb: 'nav.solutionsNav',
        },            
        component: ItemsComponent
      },
      { path: '', redirectTo: 'overview', pathMatch: 'full' },      
    ]    
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class TechnologyRoutingModule { }
