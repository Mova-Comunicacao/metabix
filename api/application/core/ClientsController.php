<?php
defined('BASEPATH') or exit('No direct script access allowed');
define('CLIENTES_AREA',true);

class ClientsController extends Api_Controller
{

    public $output = [];

    function __construct()
    {
        parent::__construct();      
        $this->load->library('api_clients_area_constructor');
    }    

    /**
     * Sets view data
     * @param  array $data
     * @return core/ClientsController
     */
    public function data($data)
    {
        if (!is_array($data)) {
            //return false;
        }

		$this->output
			->set_status_header(200)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data)); 

        return $this;
    }   
    
    /**
     * Sets view title
     * @param  string $title
     * @return core/ClientsController
     */
    public function title($title)
    {
        $this->data['title'] = $title;

        return $this;
    }      
}