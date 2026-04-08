<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Languages extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->languages();
    }

	public function languages()
	{
        $response = $this->languages_model->get(null, ['active' => 1]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));  
	}        
}