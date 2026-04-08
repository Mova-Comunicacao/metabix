export interface Settings {
  main_domain: string;
  company_name: string;
  business_name: string;
  company_address: string;
  company_city: string;
  company_email: string;
  company_alt_phonenumber: string;
  company_postal_code: string;
  company_phonenumber: string;
  company_description?: string;
  zip_code: string;
  allowed_files: string;
  avatar_types: string;
  site_pic_types: string;
  array_dateformat: any[];
  dateformat: string;
  array_timezone: any[];
  default_timezone: string;  
  active_language: string;
  // SMTP
  mail_engine: string;  
  email_protocolo: string;  
  smtp_encryption: string;  
  smtp_host: string;  
  smtp_port: number;  
  smtp_email: string;  
  smtp_username: string;  
  smtp_password: string | any;  
  smtp_email_charset: string;  
  bcc_emails: string;  
  email_signature: string;  
  email_header: string | any;  
  email_footer: string | any; 
  // Google 
  google_api_key: string | any;  
  google_client_id: string | any;  
  google_property_id: string | any; 
  // Whatsapp 
  whatsapp_chat: boolean;
  whatsapp_chat_admin_area: number;
  whatsapp_chat_clients_area: number;
  whatsapp_chat_description: string;
  whatsapp_chat_clients_and_admin_area: string;
  whatsapp_access_token: string | any;  
  whatsapp_number_id: number; 
  whatsapp_business_id: number; 
  whatsapp_version: string | any;    
}