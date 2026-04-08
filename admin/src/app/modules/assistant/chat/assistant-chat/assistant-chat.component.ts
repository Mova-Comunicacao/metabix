import { Component } from '@angular/core';
import { AiImagePreset, AiTextPreset } from '../../core';


@Component({
  selector: 'app-assistant-chat',
  templateUrl: './assistant-chat.component.html'
})
export class AssistantChatComponent {
  
  currentImagePresetId: string = 'square';
  currentImagePreset: AiImagePreset | null = null;

  currentTextPreset: AiTextPreset = {
    temperature: 1,
    max_tokens: 1024,
    top_p: 0.95,
    stream: false,
    json_mode: false,
    moderation: 'none',
  };


  onImagePresetChange(preset: AiImagePreset): void {
    this.currentImagePresetId = preset.id;
    this.currentImagePreset   = preset;
  }

  onTextPresetChange(preset: AiTextPreset): void {
    this.currentTextPreset   = preset;
  }  
  
}
