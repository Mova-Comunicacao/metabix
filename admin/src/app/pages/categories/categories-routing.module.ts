import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { CategoriesComponent } from './categories.component';
import { CategoriesListComponent } from './categories-list/categories-list.component';
import { CategoriesEditComponent } from './categories-edit/categories-edit.component';

const routes: Routes = [
  {
    path: '',    
    data: {
      translate: 'nav.categoriesNav',
    },       
    component: CategoriesComponent,
    children: [
      {
        path: '',
        data: {
          translate: 'nav.categoriesNav',
          breadcrumb: 'nav.listNav',              
        },          
        component: CategoriesListComponent
      },
      {
        path: 'add',
        data: {
          translate: 'nav.categoriesNav',
          breadcrumb: 'nav.createNav',              
        },         
        component: CategoriesEditComponent
      },
      {
        path: 'edit/:id',
        data: {
          translate: 'nav.categoriesNav',
          breadcrumb: 'nav.editNav',              
        },         
        component: CategoriesEditComponent
      },       
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class CategoriesRoutingModule { }
