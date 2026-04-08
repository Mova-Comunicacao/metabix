export interface AiLogItems {
  id: number;
  user_id: number | null;
  client_id: number | null;
  task: 'rewrite' | 'translate' | 'seo' | 'generate' | string;
  target_lang?: 'pt' | 'es' | 'en' | string | null;
  input_chars: number;
  output_chars?: number;
  approx_tokens?: number;
  status: 'success' | 'error' | string;
  error_message?: string | null;
  dateadded: string;
}

export interface AiLogResponse {
  items: AiLogItems[];
  limit: number;
  offset: number;
}

export interface AiChangelogItem {
  version: string;
  date: string; 
  label?: string;
  items: string[];
}