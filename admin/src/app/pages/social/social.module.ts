import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { NgbDropdownModule, NgbTooltipModule, NgbModalModule } from '@ng-bootstrap/ng-bootstrap';

import { SocialRoutingModule } from './social-routing.module';
import { SocialComponent } from './social.component';

import { DeteleSocialComponent } from './components/detele-social/detele-social.component';
import { EditSocialComponent } from './components/edit-social/edit-social.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';


@NgModule({
  declarations: [
    SocialComponent,
    DeteleSocialComponent,
    EditSocialComponent
  ],
  imports: [
    CommonModule,
    SocialRoutingModule,
    FormsModule, 
    ReactiveFormsModule,
    DragDropModule,
    InlineSVGModule,
    KeeniconModule,
    CRUDTableModule,   
    NgbDropdownModule, 
    NgbTooltipModule, 
    NgbModalModule,     
    SharedModule,
  ]
})
export class SocialModule { }
