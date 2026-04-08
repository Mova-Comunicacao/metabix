import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';

// 3rd-Party plugins variables
import { DragDropModule } from '@angular/cdk/drag-drop';
import { InlineSVGModule } from 'ng-inline-svg-2';
import { QuillModule } from 'ngx-quill';
import { 
  NgbModalModule,
  NgbDropdownModule 
} from '@ng-bootstrap/ng-bootstrap';

import { SlidesComponent } from './slides.component';
import { SlideEditComponent } from './slide-edit/slide-edit.component';
import { SlideListComponent } from './slide-list/slide-list.component';

import { CreateSlideComponent } from './components/create-slide/create-slide.component';
import { DeleteSlideComponent } from './components/delete-slide/delete-slide.component';

import { DeleteImageComponent } from './slide-image/components/delete-image/delete-image.component';
import { UploadImageComponent } from './slide-image/components/upload-image/upload-image.component';
import { SlideImageComponent } from './slide-image/slide-image.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    SlidesComponent,
    SlideListComponent,
    SlideEditComponent,
    CreateSlideComponent,
    UploadImageComponent,
    DeleteImageComponent,
    DeleteSlideComponent,
    SlideImageComponent
  ],
  imports: [
    CommonModule,
    FormsModule, 
    ReactiveFormsModule,
    NgbModalModule,   
    NgbDropdownModule, 
    DragDropModule,
    CRUDTableModule,   
    KeeniconModule,
    InlineSVGModule,
    QuillModule.forRoot(),   
    SharedModule,
    RouterModule.forChild([
      {
        path: '',
        data: {
          translate: 'nav.slidesNav',
        },       
        component: SlidesComponent,
        children: [
          {
            path: '',       
            data: {
              translate: 'nav.slidesNav',
              breadcrumb: 'nav.listNav',              
            },                
            component: SlideListComponent
          },          
          {
            path: 'edit',
            data: {
              translate: 'nav.slidesNav',
              breadcrumb: 'nav.createNav'
            },             
            component: SlideEditComponent
          },
          {
            path: 'edit/:id',
            data: {
              translate: 'nav.slidesNav',
              breadcrumb: 'nav.editNav',
              breadcrumbParam: 'id'
            },             
            component: SlideEditComponent
          },            
        ]
      },
    ]),    
  ],
  exports: [RouterModule]
})
export class SlidesModule { }
