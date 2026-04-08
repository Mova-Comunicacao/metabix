import { Component, EventEmitter, HostBinding, Input, Output } from '@angular/core';

import { AiImagePreset, AiTextPreset } from '../../core';

interface BasePreset {
  id: string;
  label: string;
  baseWidth: number;
  baseHeight: number;
}

interface SizeStep {
  id: string;
  label: string;
  factor: number;
}

@Component({
  selector: 'app-assistant-config',
  templateUrl: './assistant-config.component.html'
})
export class AssistantConfigComponent {
  @HostBinding('class') class =
    'menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold w-250px w-lg-325px';

  @Input() currentPresetId: string | null = null;
  @Input() textPreset: AiTextPreset | null = null;

  @Output() imagePresetChange = new EventEmitter<AiImagePreset>();
  @Output() textPresetChange = new EventEmitter<AiTextPreset>();

  temperature = 1.0;          // slider 0–2 (ou 0–1)
  maxTokens   = 1024;         // slider 1–4096
  topP        = 0.95;         // slider 0–1
  stream      = false;
  jsonMode    = false;
  moderationEnabled = false;


  /** Base presets (aspect ratio + default size) */
  basePresets: BasePreset[] = [
    { id: 'square',    label: 'Quadrado 1:1',          baseWidth: 1024, baseHeight: 1024 },
    { id: 'story',     label: 'Story 9:16',            baseWidth: 1080, baseHeight: 1920 },
    { id: 'feed-vert', label: 'Feed 4:5',              baseWidth: 1080, baseHeight: 1350 },
    { id: 'landscape', label: 'Landscape 16:9',        baseWidth: 1920, baseHeight: 1080 },
  ];

  /** Range steps (multipliers) */
  sizeSteps: SizeStep[] = [
    { id: 'xs', label: 'XS', factor: 0.5 },
    { id: 'sm', label: 'SM', factor: 0.75 },
    { id: 'md', label: 'MD', factor: 1 },
    { id: 'lg', label: 'LG', factor: 1.5 },
    { id: 'xl', label: 'XL', factor: 2 },
  ];

  /** Slider index (0..sizeSteps.length-1) */
  sizeIndex = 2; // md (1x) default

  // helper getters
  get maxSizeIndex(): number {
    return this.sizeSteps.length - 1;
  }

  get selectedBasePreset(): BasePreset | undefined {
    const id = this.currentPresetId || this.basePresets[0].id;
    return this.basePresets.find(p => p.id === id);
  }  

  /** Called when user clicks on a preset inside dropdown */
  onSelectPreset(preset: BasePreset): void {
    this.currentPresetId = preset.id;
  }

  /** Called when slider moves */
  onSizeRangeChange(index: number): void {
    this.emitCurrentTextPreset();
  }  

  /** Handlers simples pra ser chamado nos (change)/(ngModelChange) do template */
  onTemperatureChange(value: number): void {
    this.temperature = +value;
    this.emitCurrentTextPreset();
  }

  onMaxTokensChange(value: number): void {
    this.maxTokens = +value;
    this.emitCurrentTextPreset();
  }

  onTopPChange(value: number): void {
    this.topP = +value;
    this.emitCurrentTextPreset();
  }

  onToggleStream(value: boolean): void {
    this.stream = value;
    this.emitCurrentTextPreset();
  }

  onToggleJsonMode(value: boolean): void {
    this.jsonMode = value;
    this.emitCurrentTextPreset();
  }

  onToggleModeration(value: boolean): void {
    this.moderationEnabled = value;
    this.emitCurrentTextPreset();
  }

  /** Core: emit to parent */
  private emitCurrentTextPreset(): void {
    const preset: AiTextPreset = {
      temperature: this.temperature,
      max_tokens: this.maxTokens,
      top_p: this.topP,
      stream: this.stream,
      json_mode: this.jsonMode,
      moderation: this.moderationEnabled ? 'llamaguard' : 'none',
    };

    this.textPresetChange.emit(preset);

    /*
    const step  = this.sizeSteps[this.sizeIndex] ?? this.sizeSteps[2];
    const factor = step.factor;

    let width  = Math.round(base.baseWidth  * factor);
    let height = Math.round(base.baseHeight * factor);

    // Common-safe: make them multiples of 8
    width  = width  - (width  % 8);
    height = height - (height % 8);

    const preset: AiImagePreset = {
      id: base.id,
      label: `${base.label} – ${step.label}`,
      width,
      height,
    };

    this.imagePresetChange.emit(preset);
    */
  }


  /** For template: check if base preset is active */
  isActivePreset(preset: BasePreset): boolean {
    const id = this.currentPresetId || this.basePresets[0].id;
    return id === preset.id;
  }
}
