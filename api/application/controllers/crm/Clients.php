<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients extends CRMController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');
    }

    /* List all clients */
    public function getAll() {}
    
    /* List clients */
    public function getTable()
    {
        $clients = $this->clients_model->getTable();

        $data = array();

		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($clients));          
    }    
 
    
    /* Get contact id */
    public function getItemById($id)
    {
        $client = $this->clients_model->get($id);

        if(!empty($client->userid)) {
            $contacts = $this->clients_model->get_contacts($client->userid);
            $contact = array();
            if(!empty($contacts)){
                foreach($contacts as $c){
                    $contact[] = array(
                        'id' => $c->id,
                        'firstname' => $c->firstname,
                        'lastname' => $c->lastname,
                        'fullname' => $c->fullname,
                        'email' => $c->email,
                        'phonenumber' => $c->phonenumber,
                        'date' => $c->datecreated,
                        'last_login' => $c->last_login,
                        'is_primary' => $c->is_primary,
                    );                         
                }
            }  
            
            if (!empty($client->company)) {
                // Check if is realy empty client company so we can set this field to empty
                // The query where fetch the client auto populate firstname and lastname if company is empty
                if (is_empty_customer_company($client->userid)) {
                    $client->company = '';
                }
            }

            $response = array(
                'id' => $client->userid,
                'userid' => $client->userid,
                'company' => $client->company,
                'phonenumber' => $client->phonenumber,
                'website' => $client->website, 
                'description' => $client->description, 
                'address' => $client->address,
                'folder' => base_url('api/uploads/clients/' . $client->userid . '/'),
                'logo_image' => $client->logo_image,
                'city' => $client->city,               
                'zip' => $client->zip,               
                'state' => $client->state,                    
                'date' => $client->datecreated,
                'staffid' => $client->addedfrom,
                'active' => $client->active,
                'contacts' => $contact
            );
        } else {
            $response = array(
                'type' => 'info',
                'message' => 'Could not find contact for specified ID',
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

            $phonenumber    = $formdata['phonenumber'];
            $address 	    = $formdata['address']; 
            $company 	    = $formdata['company']; 
            $website 	    = $formdata['website']; 
            $description 	= $formdata['description']; 
            $city 	        = $formdata['city']; 
            $zip 	        = $formdata['zip']; 
            $state 	        = $formdata['state']; 
            $staffid 	    = $formdata['staffid']; 
            $active 	    = $formdata['active']; 

            $data = array(
                'phonenumber' => $phonenumber,
                "address" => $address,
                "company" => $company,
                "website" => $website,    
                "description" => $description,               
                "city" => $city,               
                "zip" => $zip,               
                "state" => $state,               
				'addedfrom' => $staffid,		
				'is_primary' => 1,		
				'active' => $active			
            );
            
            $save_and_add_contact = false;
            if (isset($formdata['save_and_add_contact']) && $formdata['save_and_add_contact'] == true) {
                unset($formdata['save_and_add_contact']);
                $save_and_add_contact = true;
            }

            $id = $this->clients_model->add($data, $save_and_add_contact);
            if ($id) {
				$response = array(
					'type' => 'success',
					'message' => _l('added_successfully', _l('client')),
                    'userid' => $id
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

            $phonenumber    = $formdata['phonenumber'];
            $address 	    = $formdata['address']; 
            $company 	    = $formdata['company']; 
            $website 	    = $formdata['website']; 
            $description 	= $formdata['description']; 
            $city 	        = $formdata['city']; 
            $zip 	        = $formdata['zip']; 
            $state 	        = $formdata['state']; 
            $staffid 	    = $formdata['staffid']; 
            $active 	    = $formdata['active']; 

            $data = array(
                'phonenumber' => $phonenumber,
                "address" => $address,
                "company" => $company,
                "website" => $website,               
                "description" => $description,               
                "city" => $city,               
                "zip" => $zip,               
                "state" => $state,               
				'addedfrom' => $staffid,			
				'active' => $active			
            );

            $success = $this->clients_model->update($data, $id);
            $response = set_alert(
                $success, 'updated_successfully', 'client'
            ); 

            $this->output
				->set_content_type('application/json')
				->set_output(json_encode($response));                 
        }        
    }    

    /* Delete client */
    public function delete($id)
    {
        $success = $this->clients_model->delete($id);
        if($success) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted', _l('client')),
            );         
        } else {
            $response = array(
                'type' => 'warning',
                'message' => _l('problem_deleting', _l('client_lowercase')),
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
                foreach ($ids as $client_id) {
                    $client = $this->clients_model->get($client_id);
                    $success = $this->clients_model->delete($client_id);   
                    if($success) {
                        $response = array(
                            'type' => 'success',
                            'message' => _l('deleted', _l('client')),
                        );         
                    } else {
                        $response = array(
                            'type' => 'warning',
                            'message' => _l('problem_deleting', _l('client_lowercase')),
                        );                
                    }                                                  
                }                 
            }
        }      

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));         
    }   
    
    public function getSocial($id = '')
	{
        $data = $this->clients_model->get_social($id);

        $response = $data;
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }

	public function addSocial()
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		
        if(!empty($formdata)) {
            $name                   = $formdata['name'];
            $link                   = $formdata['link'];  
            $clientid               = $formdata['clientid'];
            $staffid                = $formdata['staffid'];

            $data = array(
                'name' => $name,
                'link' => $link,
                'clientid' => $clientid,
                'staffid' => $staffid,
            );  

            $success = $this->clients_model->add_social($data);   
            $response = set_alert(
                $success, 'added_successfully', ''
            );	
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                          
        }
    }     
    
	public function updateSocial($id) 
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		
		if(!empty($formdata)) {
            $name                   = $formdata['name'];
            $link                   = $formdata['link']; 
            $clientid               = $formdata['clientid'];
            $staffid                = $formdata['staffid'];

			$data = array(
				'name' => $name,
                'link' => $link,
                'clientid' => $clientid,
                'staffid' => $staffid,                
            );  

            $success = $this->clients_model->update_social($data, $id);
            $response = set_alert(
                $success, 'updated_successfully', ''
            );	
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));         
        }
    }  

    public function deleteSocial($id)
    {
        $social = $this->clients_model->get_social($id);
        $success = $this->clients_model->delete_social($id);   
        
        if ($success) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted'),
            );         
        } else {
            $response = array(
                'type' => 'error',
                'message' => _l('problem_deleting'),
            );   
        }

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    } 

    /**
     * Handles upload for category files
     * @param  mixed $categoryid post id
     * @return boolean
     */
	public function uploadPicture() 
	{
		$userid = $this->input->post('id');

        $success = handle_client_file_uploads($userid);

        if (is_array($success) && isset($success['message'])) {
            $response = array(
                'type' => 'error',
                'message' => $success['message']
            );
        } elseif ($success == true) {
            $response = array(
                'type' => 'success',
                'message' => _l('file_uploaded_success')
            );                
        } else {
            $response = array(
                'type' => 'error',
                'message' => $success['message']
            );                
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));          
    }  
    
	public function deletePicture($userid)
	{
        $picture = $this->clients_model->get($userid);     
        $success = $this->clients_model->delete_picture($userid);
        
        if ($success) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted'),
            );         
        } else {
            $response = array(
                'type' => 'error',
                'message' => _l('problem_deleting'),
            );   
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));         
    }      
}