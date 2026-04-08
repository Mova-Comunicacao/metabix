import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { PostsComponent } from './posts.component';
import { PostsListComponent } from './posts-list/posts-list.component';
import { PostsEditComponent } from './posts-edit/posts-edit.component';

const routes: Routes = [
  {
    path: '',
    data: {
      translate: 'nav.postsNav',
    },       
    component: PostsComponent,
    children: [
      {
        path: '',
        data: {
          translate: 'nav.postsNav',
          breadcrumb: 'nav.listNav',              
        },              
        component: PostsListComponent,
      },      
      {
        path: 'add',
        data: {
          translate: 'nav.postsNav',
          breadcrumb: 'nav.createNav'
        },           
        component: PostsEditComponent
      },
      {
        path: 'edit/:id',
        data: {
          translate: 'nav.postsNav',
          breadcrumb: 'nav.editNav'
        },         
        component: PostsEditComponent
      },      
    ]    
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PostsRoutingModule { }
