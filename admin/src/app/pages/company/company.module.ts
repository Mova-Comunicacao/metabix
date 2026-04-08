import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';

// 3rd-Party plugins variables
import { QuillModule } from 'ngx-quill';
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { NgbModalModule } from '@ng-bootstrap/ng-bootstrap';

import { CompanyComponent } from './company.component';
import { UploadImageComponent } from './components/upload-image/upload-image.component';
import { DeleteImageComponent } from './components/delete-image/delete-image.component';

// Goals
import { ItemsComponent } from './items/items.component';
import { EditItemComponent } from './items/edit-items/edit-item.component';
import { DeleteItemComponent } from './items/delete-items/delete-item.component';

// Goals
// import { GoalsComponent } from './goals/goals.component';
// import { EditGoalComponent } from './goals/edit-goal/edit-goal.component';
// import { DeleteGoalComponent } from './goals/delete-goal/delete-goal.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    CompanyComponent,
    UploadImageComponent,
    DeleteImageComponent,
    // Items
    ItemsComponent,
    EditItemComponent,
    DeleteItemComponent
  ],
  imports: [
    CommonModule,
    FormsModule, 
    ReactiveFormsModule,
    InlineSVGModule,
    QuillModule.forRoot(),
    NgbModalModule,  
    KeeniconModule,
    DragDropModule,
    CRUDTableModule,
    SharedModule,
    RouterModule.forChild([
      {
        path: '',
        data: {
          translate: 'nav.companyNav',
          breadcrumb: 'nav.editNav'
        },          
        component: CompanyComponent,
      },
    ]),    
  ]
})
export class CompanyModule { }
