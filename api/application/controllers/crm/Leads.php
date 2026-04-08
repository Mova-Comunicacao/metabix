<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Content-Type: text/html; charset=utf-8');

class Leads extends CRMController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
    }

    public function index($id = '')
    {

    }  

    /* List all leads */
    public function getTable()
    {
		$leads = $this->leads_model->getTable();

        $data = array();
		if(!empty($leads)){
			foreach($leads as $row){              
				$data[] = array(
					'id' => $row->id,
                    'name' => $row->name,
					'company' => $row->company,
					'phonenumber' => $row->phonenumber,
                    'email' => $row->email,
                    'status' => $row->status,
                    'source' => $row->source,                    
                    'status_name' => $row->status_name,
                    'source_name' => $row->source_name,
					'date' => $row->dateadded,
                );                 
            }
        }        
		$response = $data;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));           
    }    

    /* Get leads id */
    public function getItemById($id)
    {
        $lead = $this->leads_model->get($id);

        if(!empty($id)) {
            $response = array(
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'phonenumber' => $lead->phonenumber,
                'description' => $lead->description,
                'state' => $lead->state,
                'city' => $lead->city,
                'zip' => $lead->zip,
                'address' => $lead->address,
                'email' => $lead->email,
                'status' => $lead->status,
                'source' => $lead->source,                    
                'status_name' => $lead->status_name,
                'source_name' => $lead->source_name,
                'staffid' => $lead->addedfrom,
                'date' => $lead->dateadded,
            );
        }

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }    

    /* Add new client*/
    public function create()
    {
        $formdata = json_decode(file_get_contents('php://input'), true);  
                        
        if(!empty($formdata)) {

            $name           = $formdata['name'];
            $email          = $formdata['email'];
            $company        = $formdata['company'];
            $state          = $formdata['state'];
            $city           = $formdata['city'];
            $address        = $formdata['address'];
            $zip            = $formdata['zip'];
            $description    = $formdata['description'];
            $phonenumber    = $formdata['phonenumber'];
            $status         = $formdata['status'];
            $source         = $formdata['source'];
            $staffid 	    = $formdata['staffid'];

            $data = array(
                'name' => $name,
                'email' => $email,
                "company" => $company,
                "state" => $state,               
                "city" => $city,               
                "address" => $address,
                "zip" => $zip,               
                'phonenumber' => $phonenumber,
                "description" => $description,               
				'status' => $status,		
				'source' => $source,		
				'addedfrom' => $staffid,		
            );  
            
            $id = $this->leads_model->add($data);
            if ($id) {
				$response = array(
					'type' => 'success',
					'message' => _l('added_successfully', _l('lead')),
				);	                
            }

            $this->output
				->set_content_type('application/json')
				->set_output(json_encode($response));                 
        }
    }   

    /* Edit client*/
    public function update($id)
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
    
        if(!empty($formdata)) {
            $name           = $formdata['name'];
            $email          = $formdata['email'];
            $company        = $formdata['company'];
            $state          = $formdata['state'];
            $city           = $formdata['city'];
            $address        = $formdata['address'];
            $zip            = $formdata['zip'];
            $description    = $formdata['description'];
            $phonenumber    = $formdata['phonenumber'];
            $status         = $formdata['status'];
            $source         = $formdata['source'];
            $staffid 	    = $formdata['staffid'];

            $data = array(
                'name' => $name,
                'email' => $email,
                "company" => $company,
                "state" => $state,               
                "city" => $city,               
                "address" => $address,
                "zip" => $zip,               
                'phonenumber' => $phonenumber,
                "description" => $description,               
				'status' => $status,		
				'source' => $source,		
				'addedfrom' => $staffid,		
            );  
            
            $success = $this->leads_model->update($data, $id);
            $response = set_alert(
                $success, 'updated_successfully', 'lead'
            ); 

            $this->output
				->set_content_type('application/json')
				->set_output(json_encode($response));               
        }
    }    
    
    /* Delete lead from database */
    public function delete($id)
    {
        $success = $this->leads_model->delete($id);
        if ($success == true) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted', _l('lead')),
            );         
        } else {
            $response = array(
                'type' => 'warning',
                'message' => _l('problem_deleting', _l('lead_lowercase')),
            );                
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));             
    }

    public function deleteItems()
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
		
        if(!empty($formdata)) {
            $ids = $formdata['ids'];
            if (is_array($ids)) {
                foreach ($ids as $lead_id) {
                    $lead = $this->leads_model->get($lead_id);
                    $success = $this->leads_model->delete($lead_id);   
                    if($success) {
                        $response = array(
                            'type' => 'success',
                            'message' => _l('deleted', _l('lead')),
                        );         
                    } else {
                        $response = array(
                            'type' => 'warning',
                            'message' => _l('problem_deleting', _l('lead_lowercase')),
                        );                
                    }                                                  
                }                 
            }
        }      

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));         
    }       

    // Sources
    /* Manage leads sources */
    public function sources()
    {
        $data = $this->leads_model->get_source();

        $response = $data;
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }    
}