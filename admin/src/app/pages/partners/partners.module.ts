import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { NgSelectModule } from '@ng-select/ng-select';
import { QuillModule } from 'ngx-quill';
import { 
  NgbModalModule,
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';

import { PartnersRoutingModule } from './partners-routing.module';
import { PartnersComponent } from './partners.component';

// Components
import { EditItemComponent } from './components/edit-items/edit-item.component';
import { DeleteItemComponent } from './components/delete-items/delete-item.component'
import { UploadImageComponent } from './components/upload-image/upload-image.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    PartnersComponent,
    UploadImageComponent,
    EditItemComponent,
    DeleteItemComponent,
  ],
  imports: [
    CommonModule,
    PartnersRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    CRUDTableModule,
    InlineSVGModule,
    NgbModalModule,
    NgbTooltipModule,
    NgSelectModule,
    DragDropModule,
    KeeniconModule,
    QuillModule,
    CRUDTableModule,
    SharedModule, 
  ]
})
export class PartnersModule { }
