import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { QuillModule } from 'ngx-quill';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { 
  NgbModalModule,
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';

import { CustomersRoutingModule } from './customers-routing.module';
import { CustomersComponent } from './customers.component';

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
    CustomersComponent,
    UploadImageComponent,
    EditItemComponent,
    DeleteItemComponent,
  ],
  imports: [
    CommonModule,
    CustomersRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    CRUDTableModule,
    InlineSVGModule,
    NgbModalModule,
    NgbTooltipModule,
    DragDropModule,
    KeeniconModule,
    QuillModule,
    CRUDTableModule,
    SharedModule, 
  ]
})
export class CustomersModule { }
