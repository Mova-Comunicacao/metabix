<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emails extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('emails_model');
    }

    /* List all email templates */
    public function index()
    {
        $langCheckings = get_option('email_templates_language_checks');
        if ($langCheckings == '') {
            $langCheckings = array();
        } else {
            $langCheckings = unserialize($langCheckings);
        }    
        
        $this->db->where('language', 'english');
        $email_templates_english = $this->db->get(db_prefix() . 'emailtemplates')->result_array();
        foreach ($this->api->get_available_languages() as $avLanguage) {
            if ($avLanguage != 'english') {
                foreach ($email_templates_english as $template) {

                    // Result is cached and stored in database
                    // This page may perform 1000 queries per request
                    if (isset($langCheckings[$template['slug'] . '-' . $avLanguage])) {
                        continue;
                    }

                    $notExists = total_rows(db_prefix() . 'emailtemplates', [
                        'slug'     => $template['slug'],
                        'language' => $avLanguage,
                    ]) == 0;

                    $langCheckings[$template['slug'] . '-' . $avLanguage] = 1;

                    if ($notExists) {
                        $data              = [];
                        $data['slug']      = $template['slug'];
                        $data['type']      = $template['type'];
                        $data['language']  = $avLanguage;
                        $data['name']      = $template['name'] . ' [' . $avLanguage . ']';
                        $data['subject']   = $template['subject'];
                        $data['message']   = '';
                        $data['fromname']  = $template['fromname'];
                        $data['plaintext'] = $template['plaintext'];
                        $data['active']    = $template['active'];
                        $data['order']     = $template['order'];
                        $this->db->insert(db_prefix() . 'emailtemplates', $data);
                    }
                }
            }
        }  
        
        $data['title'] = _l('email_templates');

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));          
    }    

    /* Since version 1.0.1 - test your smtp settings */
    public function sent_smtp_test_email()
    {
		$formdata = json_decode(file_get_contents('php://input'), true);

        $response = ['type' => 'error', 'title' => 'Error!', 'message' => 'Unknown error occurred.'];

        if($formdata) {        
            $this->load->config('email');

            // Simulate fake template to be parsed
            $template           = new StdClass();
            $template->message  = get_option('email_header') . 'This is test SMTP email. <br />If you received this message that means that your SMTP settings is set correctly.' . get_option('email_footer');
            $template->fromname = get_option('company_name') ?: 'TEST';
            $template->subject  = 'SMTP Setup Testing';

            $template = parse_email_template($template);

            hooks()->do_action('before_send_test_smtp_email');
            
            $this->email->initialize();
            if (get_option('mail_engine') === 'phpmailer') {
                $this->email->set_debug_output(function ($err) {
                    if (!isset($GLOBALS['debug'])) {
                        $GLOBALS['debug'] = '';
                    }
                    $GLOBALS['debug'] .= $err . '<br />';

                    return $err;
                });

                $this->email->set_smtp_debug(3);
            }

            $this->email->set_newline(config_item('newline'));
            $this->email->set_crlf(config_item('crlf'));

            $this->email->from('contact@metabixbiotech.com', $template->fromname);
            $this->email->to($formdata['test_email']);

            if ($systemBCC = get_option('bcc_emails')) {
                $this->email->bcc($systemBCC);
            }

            $this->email->subject($template->subject);
            $this->email->message($template->message);
            
            if ($this->email->send(true)) {
                $response = array(
                    'type' => 'success',
                    'title' => 'Success!',                    
                    'message' => 'Seems like your SMTP settings is set correctly. Check your email now.'
                );  
                hooks()->do_action('smtp_test_email_success');               
            } else {
                $response = array(
                    'type' => 'error',
                    'title' => 'Error!',                    
                    'message' => 'Your SMTP settings are not set correctly here is the debug log.' 
                    //. $this->email->print_debugger() . (isset($GLOBALS['debug']) ? $GLOBALS['debug'] : '')
                );      
                hooks()->do_action('smtp_test_email_failed');
            }
        }  
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));             
    }
}