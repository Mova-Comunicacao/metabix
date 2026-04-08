import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

// 3rd-Party plugins variables
import { InlineSVGModule } from 'ng-inline-svg-2';
import { 
  NgbDropdownModule,
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';

// Components
import { AssistantConfigComponent } from './components/assistant-config/assistant-config.component';
// Chat
import { AssistantChatComponent } from './chat/assistant-chat/assistant-chat.component';
import { AssistantChatInnerComponent } from './chat/assistant-chat-inner/assistant-chat-inner.component';

import { 
  KeeniconModule,
  SharedModule
} from '../../shared';

@NgModule({
  declarations: [
    AssistantChatComponent,
    AssistantChatInnerComponent,
    AssistantConfigComponent
  ],
  imports: [
    CommonModule,
    FormsModule, 
    ReactiveFormsModule,
    NgbDropdownModule,
    NgbTooltipModule,    
    InlineSVGModule,
    KeeniconModule,
    SharedModule    
  ],
  exports: [
    AssistantChatComponent
  ]
})
export class AssistantModule { }
