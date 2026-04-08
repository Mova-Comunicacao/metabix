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
  NgbDropdownModule ,
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';

import { CarouselComponent } from './carousel.component';
import { CarouselListComponent } from './carousel-list/carousel-list.component';

// Components
import { EditCarouselComponent } from './components/edit-carousel/edit-carousel.component';
import { UploadImageComponent } from './components/edit-carousel/upload-image/upload-image.component';
import { DeleteSlideComponent } from './components/delete-slide/delete-slide.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    CarouselComponent,
    CarouselListComponent,
    EditCarouselComponent,
    UploadImageComponent,
    DeleteSlideComponent,
  ],
  imports: [
    CommonModule,
    FormsModule, 
    ReactiveFormsModule,
    NgbModalModule,   
    NgbDropdownModule, 
    NgbTooltipModule,
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
        component: CarouselComponent,
        children: [
          {
            path: '',       
            data: {
              translate: 'nav.slidesNav',
              breadcrumb: 'nav.listNav',              
            },                
            component: CarouselListComponent
          },                     
        ]
      },
    ]),    
  ],
  exports: [RouterModule]
})
export class CarouselModule { }
