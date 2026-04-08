import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { QuillModule } from 'ngx-quill';
import { 
  NgbModalModule,
  NgbTooltipModule 
} from '@ng-bootstrap/ng-bootstrap';


import { CategoriesListComponent } from './categories-list/categories-list.component';
import { CategoriesEditComponent } from './categories-edit/categories-edit.component';
// Components
import { EditCategoryComponent } from './components/edit-category/edit-category.component';
import { UploadImageComponent } from './components/upload-image/upload-image.component';
import { DeleteCategoryComponent } from './components/delete-category/delete-category.component';

import { CategoriesRoutingModule } from './categories-routing.module';
import { CategoriesComponent } from './categories.component';

import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    CategoriesComponent,
    CategoriesListComponent,
    CategoriesEditComponent,
    EditCategoryComponent,
    UploadImageComponent,
    DeleteCategoryComponent
  ],
  imports: [
    CommonModule,
    CategoriesRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    NgbModalModule,
    NgbTooltipModule,
    DragDropModule,
    KeeniconModule,
    InlineSVGModule,
    QuillModule,
    CRUDTableModule,
    SharedModule,    
  ]
})
export class CategoriesModule { }
