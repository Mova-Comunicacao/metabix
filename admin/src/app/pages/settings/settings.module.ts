import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { QuillModule } from 'ngx-quill';

import { 
  NgbNavModule, NgbTooltipModule 
} from '@ng-bootstrap/ng-bootstrap';

import { SettingsRoutingModule } from './settings-routing.module';
import { SettingsComponent } from './settings.component';

// Components
import { GeneralComponent } from './components/general/general.component';
import { UploadsComponent } from './components/uploads/uploads.component';
import { EmailComponent } from './components/email/email.component';
import { GoogleComponent } from './components/google/google.component';
import { WhatsappComponent } from './components/whatsapp/whatsapp.component';


import { 
  KeeniconModule,
  SharedModule
} from '../../shared';

@NgModule({
  declarations: [
    SettingsComponent,
    GeneralComponent,
    UploadsComponent,
    EmailComponent,
    GoogleComponent,
    WhatsappComponent,
  ],
  imports: [
    CommonModule,
    SettingsRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    NgbNavModule,
    NgbTooltipModule,   
    QuillModule.forRoot(),
    InlineSVGModule, 
    // Plugins
    KeeniconModule,
    SharedModule,
  ],
})
export class SettingsModule { }
