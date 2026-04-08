<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp extends Api_Controller
{
    private $tsap;

    private $access_token;
    private $phone_number_id;

    public function __construct()
    {
        parent::__construct();

        // EAANzdUzVfZBgBAPPV0SNlBzhzoS7CPZCcWdWmOI3REU7CAF98jkSZAwJNMzOZCDqZBSyIBMWJOkeGv6ZC7ATzFJt2jy6RRyjvDYhFn1YDSAJC2tAfhVJ4peMd0X2AA19JnhR74V9A7UmbzLQAALVLMupKxTVlIOhNzcnOWqOSXuZAe1HbqlfSiSamdEeNdqmI8VDdmf1qkVkwZDZD
        $access_token = get_instance()->encryption->decrypt(get_option('whatsapp_access_token'));
        $phone_number_id = get_option('whatsapp_number_id');

        $this->load->library('api_whatsapp');
        $this->tsap = new api_whatsapp($phone_number_id, $access_token);
    }    

    /**
     * Send message
     * @param  string $message message
     * @param  string $number  number
     * @return boolean
     */    
    public function sendMessage() 
    {

        $message = 'teste';

        $success = $this->tsap->send_message($message, '5544998717383');
    
        if ($success) {
            $response = array(
                'type' => 'success',
                'title' => 'Success!',                    
                'message' => 'Seems like your SMTP settings is set correctly. Check your email now.'
            );  
            do_action('send_message_whatsapp_success');               
        }

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));    
    }

    /**
     * Send message - Templates used only simple string
     * @param  string $template template
     * @param  string $number  number
     * @return boolean
     */    
    public function sendTemplate() 
    {
        $formdata = json_decode(file_get_contents('php://input'), true);

        if(!empty($formdata)) {

            $test_number 	    = $formdata['test_number'];     

            $cnf = [
                'template'      => 'hello_world',
                'calling_code'  => '55',
                'test_number'   => $test_number,
            ];

            $success = $this->tsap->send_template($cnf['template'], $cnf['calling_code'].$cnf['test_number']);
        
            if ($success) {
                $response = array(
                    'type' => 'success',
                    'title' => 'Success!',                    
                    'message' => 'Seems like your API settings is set correctly. Check your app now.'
                );  
                do_action('send_test_whatsapp_success');               
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));    
        }
    }

    public function get_message() 
    {
        $message = 'teste';

        $response = $this->tsap->getMessage($this->phone_number_id);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));            
    }    
}