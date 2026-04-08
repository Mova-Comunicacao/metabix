type Task = 'rewrite' | 'translate' | 'generic' | 'seo' | 'generate';
type Lang = 'pt' | 'es' | 'en';

export interface AiMessage {
  type: 'in' | 'out';
  text: string;
  lang?: Lang;
  tone?: 'formal' | 'neutro' | 'educativo' | 'corporativo' | 'tecnico' | string;
  isImage?: boolean;
  template?: boolean;
}

export interface AiRequest {
  task: Task | string;
  text: string;
  targetLang?: string;
  tone?: string;
  model?: string;
  usage?: {
    prompt_tokens?: number;
    completion_tokens?: number;
    total_tokens?: number;
  };  
  history?: AiMessage[];
  options?: AiTextPreset;
}

export interface AiTextPreset {
  temperature?: number;
  max_tokens?: number;
  top_p?: number;
  stream?: boolean;
  json_mode?: boolean;
  moderation?: 'llamaguard' | 'none';
}

export interface AiImagePreset {
  id: string;
  label: string;
  width: number;
  height: number;
}

