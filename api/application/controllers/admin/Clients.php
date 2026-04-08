<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');
    }

    /* List all clients */
    public function getAll()
    {
        $clients = $this->clients_model->get();

        $data = array();
		if(!empty($clients)){
			foreach($clients as $row){              
				$data[] = array(
					'id' => $row->userid,
					'userid' => $row->userid,
					'company' => $row->company,
					'phonenumber' => $row->phonenumber,
					'date' => $row->datecreated,
					'active' => $row->active,
                );                 
            }
        }        
		$response = $data;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }
}