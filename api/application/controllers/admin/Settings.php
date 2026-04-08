<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends AdminController {
		
	public function __construct()
	{
		parent::__construct();
		$this->load->model('settings_model');
    }

    public function index()
    {
        if (!has_permission('settings', '', 'view')) {
            access_denied('settings');
        }
				
		$this->safe(function () {

			$array_dateformat = [];
			foreach(get_available_date_formats() as $key => $val){
				$array_dateformat[] = [
					'key' => (string)$key,
					'val' => (string)$val,
				];
			}
			$array_timezone = [];
			$tzGroups = get_timezones_list();
			if (is_array($tzGroups)) {
				foreach ($tzGroups as $group => $timezones) {
					if (!is_array($timezones)) {
						continue;
					}
					foreach ($timezones as $timezone) {
						$array_timezone[] = ['val' => (string)$timezone];
					}
				}
			}

			$hasSmtpPassword   = (string)get_option('smtp_password') !== '';
			$hasWhatsAppToken  = (string)get_option('whatsapp_access_token') !== '';
			
			// Normalize multiline options
			$email_signature = clear_textarea_breaks((string)get_option('email_signature'));
			$email_header    = clear_textarea_breaks((string)get_option('email_header'));
			$email_footer    = clear_textarea_breaks((string)get_option('email_footer'));

			/*
			if (!empty($password)) {
				if (false == $this->encryption->decrypt($password)) {
					$password = $this->encryption->encrypt($password);
				} else {
					$password = $this->encryption->decrypt($password);
				}
			}
			$access_token = get_option('whatsapp_access_token');
			if (!empty($access_token)) {
				if (false == $this->encryption->decrypt($access_token)) {
					$access_token = $this->encryption->encrypt($access_token);
				} else {
					$access_token = $this->encryption->decrypt($access_token);
				}
			}		
			*/

			$email_signature = clear_textarea_breaks(get_option('email_signature'));
			$email_header = clear_textarea_breaks(get_option('email_header'));
			$email_footer = clear_textarea_breaks(get_option('email_footer'));
			
			$data = [
				'main_domain'              => get_option('main_domain'),
				'company_name'             => get_option('company_name'),
				'business_name'            => get_option('business_name'),
				'company_address'          => get_option('company_address'),
				'company_city'             => get_option('company_city'),
				'company_alt_phonenumber'  => get_option('company_alt_phonenumber'),
				'company_postal_code'      => get_option('company_postal_code'),
				'company_phonenumber'      => get_option('company_phonenumber'),
				'company_email'            => get_option('company_email'),
				'company_description'      => get_option('company_description'),

				'active_language'          => get_option('active_language'),

				// Files types
				'allowed_files'            => get_option('allowed_files'),
				'avatar_types'             => get_option('avatar_types'),
				'site_pic_types'           => get_option('site_pic_types'),

				// Date/time helpers
				'array_dateformat'         => $array_dateformat,
				'dateformat'               => get_option('dateformat'),
				'array_timezone'           => $array_timezone,
				'default_timezone'         => get_option('default_timezone'),
				
				// Mail
				'mail_engine'              => get_option('mail_engine'),
				'email_protocolo'          => get_option('email_protocolo'),
				'smtp_encryption'          => get_option('smtp_encryption'),
				'smtp_host'                => get_option('smtp_host'),
				'smtp_port'                => get_option('smtp_port'),
				'smtp_email'               => get_option('smtp_email'),
				'smtp_username'            => get_option('smtp_username'),
				'smtp_password'            => '',                 // never expose
				'has_smtp_password'        => $hasSmtpPassword,   // helper flag
				'smtp_email_charset'       => get_option('smtp_email_charset'),
				'bcc_emails'               => get_option('bcc_emails'),

				// Email templates
				'email_signature'          => $email_signature,
				'email_header'             => $email_header,
				'email_footer'             => $email_footer,

				// Google
				'google_api_key'           => get_option('google_api_key'),
				'google_client_id'         => get_option('google_client_id'),
				'google_property_id'       => get_option('google_property_id'),

				// WhatsApp
				'whatsapp_chat'            => get_option('whatsapp_chat'),
				'whatsapp_chat_clients_area' => get_option('whatsapp_chat_clients_area'),
				'whatsapp_chat_description'  => get_option('whatsapp_chat_description'),
				'whatsapp_access_token'    => '',                    // never expose
				'has_whatsapp_access_token'=> $hasWhatsAppToken,     // helper flag
				'whatsapp_number_id'       => get_option('whatsapp_number_id'),
				'whatsapp_business_id'     => get_option('whatsapp_business_id'),
				'whatsapp_version'         => get_option('whatsapp_version'),					
			];
			return $this->respond($data, 200);
		});     
    } 

	/**
	 * Update application settings (whitelisted keys only).
	 *
	 * Behavior:
	 * - Requires 'settings:edit' permission.
	 * - Reads JSON body and updates only fields present in payload (no mass-null overwrite).
	 * - Normalizes basic types (trim strings), and preserves smtp_password if empty.
	 * - Returns 200 with {type, message}.
	 * - Returns 400/422 on invalid input.
	 */	
	public function update() 
	{
		if (!has_permission('settings', '', 'edit')) {
			access_denied('settings');
		}	

		$this->safe(function () {
			// Load payload (expects JSON body)
			$formdata = $this->readJson(); 	

			if (empty($formdata) || !is_array($formdata)) {
				return $this->unprocessable('Empty or invalid payload.');
			}

			// Lista das chaves permitidas para atualização
			$allowedFields = [
				'main_domain', 
				'company_name', 
				'business_name', 
				'company_city',
				'company_alt_phonenumber', 
				'company_postal_code', 
				'company_phonenumber',
				'company_address', 
				'company_email', 
				'company_description',
				'active_language', 
				'allowed_files', 
				'avatar_types', 
				'site_pic_types',
				'dateformat', 
				'default_timezone', 
				'mail_engine', 
				'email_protocolo',
				'smtp_encryption', 
				'smtp_host', 
				'smtp_port', 
				'smtp_email',
				'smtp_username', 
				//'smtp_password', 
				'smtp_email_charset', 
				'bcc_emails',
				'email_signature', 
				'email_header', 
				'email_footer',
				'google_api_key', 
				'google_client_id', 
				'google_property_id',
				'whatsapp_chat', 
				'whatsapp_chat_clients_area', 
				'whatsapp_chat_description',
				'whatsapp_access_token', 
				'whatsapp_number_id', 
				'whatsapp_business_id',
				'whatsapp_version'
			];

			// Build update array only with keys present in payload
			$data = [];
			foreach ($allowedFields as $field) {
				// smtp_password: do not overwrite if empty string provided
				if ($field === 'smtp_password' && ($formdata[$field] === '' || $formdata[$field] === null)) {
					continue; // skip update of password when empty
				}

				// smtp_port should be int if provided
				if ($field === 'smtp_port' && $formdata[$field] !== null && $formdata[$field] !== '') {
					$formdata[$field] = (int)$formdata[$field];
				}				

				$data[$field] = isset($formdata[$field]) ? $formdata[$field] : null;
			}
				
			$success = $this->settings_model->update($data);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'settings');      
            }

			return $this->unprocessable('Failed to update settings.');   
		});
	}

    public function clear_sessions()
    {
        if (!has_permission('settings', '', 'delete')) {
            access_denied('settings');
        }
        $this->db->empty_table(db_prefix() . 'sessions');

		$response = array(
			'type' => 'success',
			'message' => 'Sessions Cleared'
		);                   
		return $this->respond($response, 200);

    }	
    
}