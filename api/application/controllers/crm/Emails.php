<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emails extends CRMController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('emails_model');
    }

    // Send email - No templates used only simple string
    public function send_email()
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
		
        if(!empty($formdata)) { 

            $send_email 	= $formdata['send_email']; 
            $subject 	    = $formdata['subject']; 
            $message 	    = $formdata['message']; 

            $message = nl2br($message);
            $success = $this->emails_model->send_simple_email($send_email, $subject, $message);

            if ($success == true) {
                $response = array(
                    'type' => 'success',
                    'title' => 'Success!',   
                    'message' => _l('custom_file_success_send')
                );
            } else {
                $response = array(
                    'type' => 'warning',
                    'title' => 'Warning!', 
                    'message' => _l('custom_file_fail_send')
                );            
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));             
        }
    }    
}