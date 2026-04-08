import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { NgSelectModule } from '@ng-select/ng-select';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { QuillModule } from 'ngx-quill';
import { 
  NgbModalModule,
  NgbDropdownModule,
  NgbAccordionModule
} from '@ng-bootstrap/ng-bootstrap';

import { PostsRoutingModule } from './posts-routing.module';
import { PostsComponent } from './posts.component';

import { PostsEditComponent } from './posts-edit/posts-edit.component';
import { PostsListComponent } from './posts-list/posts-list.component';

// Components
import { DeletePostComponent } from './posts-list/components/delete-post/delete-post.component';
import { DeletePostsComponent } from './posts-list/components/delete-posts/delete-posts.component';
import { UpdateStatusComponent } from './posts-list/components/update-status/update-status.component';
// Images
import { PostImageComponent } from './posts-image/post-image.component';
import { UploadImageComponent } from './posts-image/components/upload-image/upload-image.component';
import { DeletePostImageComponent } from './posts-image/components/delete-image/delete-post-image.component';

import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule
} from '../../shared';

@NgModule({
  declarations: [
    PostsComponent,
    PostsEditComponent,
    // Image
    PostImageComponent,
    UploadImageComponent,
    DeletePostImageComponent,
    // Components
    PostsListComponent,
    DeletePostComponent,
    DeletePostsComponent,
    UpdateStatusComponent,
  ],
  imports: [
    CommonModule,
    PostsRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    QuillModule.forRoot({
      theme: 'bubble'
    }),   
    NgbModalModule,   
    NgbDropdownModule, 
    NgbAccordionModule,
    KeeniconModule,
    DragDropModule,     
    InlineSVGModule,
    NgSelectModule,
    CRUDTableModule,    
    SharedModule,
  ] 
})
export class PostsModule { }
