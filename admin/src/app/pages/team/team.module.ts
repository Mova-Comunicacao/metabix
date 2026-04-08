import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { QuillModule } from 'ngx-quill';
import { InlineSVGModule } from 'ng-inline-svg-2';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { 
  NgbModalModule,
  NgbDropdownModule, 
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';

import { TeamRoutingModule } from './team-routing.module';
import { TeamComponent } from './team.component';

import { TeamContentComponent } from './team-content/team-content.component';

import { TeamCardComponent } from './team-content/team-card/team-card.component';
import { TeamListComponent } from './team-content/team-list/team-list.component';

import { TeamEditComponent } from './team-edit/team-edit.component';
// social
import { SocialComponent } from './team-edit/social/social.component';
import { EditSocialComponent } from './team-edit/social/edit-social/edit-social.component';
import { DeleteSocialComponent } from './team-edit/social/delete-social/delete-social.component';
// Components
import { DeleteTeamComponent } from './components/delete-team/delete-team.component';
import { DeleteAvatarComponent } from './components/delete-avatar/delete-avatar.component';


import { 
  CRUDTableModule,
  KeeniconModule,
  SharedModule 
} from '../../shared';

@NgModule({
  declarations: [
    TeamComponent,
    TeamListComponent,
    TeamEditComponent,
    DeleteTeamComponent,
    TeamCardComponent,
    DeleteAvatarComponent,
    TeamContentComponent,
    // Social
    SocialComponent,
    EditSocialComponent,
    DeleteSocialComponent,
  ],
  imports: [
    CommonModule,
    FormsModule, 
    ReactiveFormsModule,
    TeamRoutingModule,
    InlineSVGModule,
    NgbModalModule,
    NgbDropdownModule,
    NgbTooltipModule,
    DragDropModule,
    QuillModule,
    KeeniconModule,
    CRUDTableModule,
    SharedModule,
  ]
})
export class TeamModule { }
