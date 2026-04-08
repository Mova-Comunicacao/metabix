export type AlertType = 'success' | 'info' | 'warning' | 'error';

export interface ApiAlert {
  type: AlertType;
  title: string; 
  message: string;
  code?: string;
  fields?: Record<string, string> | null;
}

export interface ApiModel<T = any>  {
  id?: any; 
  ok?: boolean;
  alert?: ApiAlert;
  data?: T | null;
}