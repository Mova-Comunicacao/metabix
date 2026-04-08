<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Contacts extends CRMController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contacts_model');
    }

    /* List all clients */
    public function getAll()
    {
        $contacts = $this->contacts_model->getAll();

        $contact = array();
		if(!empty($contacts)){
			foreach($contacts as $row){              
				$contact[] = array(
					'id' => $row->id,
					'userid' => $row->userid,
                    'firstname' => $row->firstname,
                    'lastname' => $row->lastname,
                    'fullname' => $row->fullname,
                    'company' => $row->company,
					'phonenumber' => $row->phonenumber,
                    'profile_image' => contact_profile_image_url($row->id, 'small'),
                    'email' => $row->email,
					'date' => $row->datecreated,
					'active' => $row->active,
                    'last_login' => $row->last_login,
                    'is_primary' => $row->is_primary,                    
                );                 
            }
        }        
		$response = $contact;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));        
    }    

    /* List Contacts */
    public function getClientTable()
    {
        $contacts = $this->contacts_model->getTable();

        $contact = array();
		if(!empty($contacts)){
			foreach($contacts as $row){              
				$contact[] = array(
					'id' => $row->id,
					'userid' => $row->userid,
                    'firstname' => $row->firstname,
                    'lastname' => $row->lastname,
                    'fullname' => $row->fullname,
					'phonenumber' => $row->phonenumber,
                    'profile_image' => contact_profile_image_url($row->id, 'small'),
                    'email' => $row->email,
					'date' => $row->datecreated,
					'active' => $row->active,
                    'last_login' => $row->last_login,
                    'is_primary' => $row->is_primary,                    
                );                 
            }
        }        
		$response = $contact;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    } 

    /* Get contact id */
    public function getItemById($id)
    {

		$contact = $this->contacts_model->get($id);

        if(!empty($id)) {
            $response = array(
                'id' => $contact->id,
                'userid' => $contact->userid,
                'firstname' => $contact->firstname,
                'lastname' => $contact->lastname,
                'fullname' => $contact->fullname,
                'profile_image' => contact_profile_image_url($contact->id, 'small'),
                'phonenumber' => $contact->phonenumber,
                'email' => $contact->email,
                'active' => $contact->active,
                'is_primary' => $contact->is_primary,  
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

    /* Add new contact*/
    public function create()
    {
        $formdata = json_decode(file_get_contents('php://input'), true);  

        if(!empty($formdata)) {

            $firstname 	    = $formdata['firstname']; 
            $lastname 	    = $formdata['lastname']; 
            $phonenumber 	= $formdata['phonenumber']; 
            $email 	        = $formdata['email']; 
            $password 	    = $formdata['password']; 
            $active 	    = $formdata['active'];  
            $userid 	    = $formdata['userid'];
            $is_primary 	= $formdata['is_primary'];

            $send_set_password_email 	= $formdata['send_set_password_email'];

            if (is_automatic_calling_codes_enabled()) {
                $clientCountryId = $this->db->select('country')
                    ->where('userid', $userid)
                    ->get(db_prefix() . 'clients')->row()->country ?? null;
    
                $clientCountry = get_country($clientCountryId);
                $callingCode   = $clientCountry ? '+' . ltrim($clientCountry->calling_code, '+') : null;
            } else {
                $callingCode = null;
            }
                        
            if ($callingCode && !empty($phonenumber) && $phonenumber == $callingCode) {
                $phonenumber = '';
            }  

            $data = array(
                "firstname" => $firstname,
                "lastname" => $lastname,
                "password" => $password,
                "phonenumber" => $phonenumber,
                "email" => $email,
                'active' => $active,			
                'userid' => $userid,			
                'is_primary' => $is_primary		
            );
            

            $id = $this->contacts_model->add($data, $send_set_password_email);
            if ($id) {
                $success = true;
                $response = set_alert(
                    $success, 'added_successfully', 'contact'
                ); 
            }

            $this->output
				->set_content_type('application/json')
				->set_output(json_encode($response)); 
        }
    }

    /* Edit contact*/
    public function update($id)
    {
		$formdata = json_decode(file_get_contents('php://input'), true);

        if(!empty($formdata)) {
            $firstname 	    = $formdata['firstname']; 
            $lastname 	    = $formdata['lastname']; 
            $phonenumber 	= $formdata['phonenumber']; 
            $email 	        = $formdata['email']; 
            $password 	    = $formdata['password']; 
            $active 	    = $formdata['active'];  
            $userid 	    = $formdata['userid'];
            $is_primary 	= $formdata['is_primary'];

            $send_set_password_email 	= $formdata['send_set_password_email'];
            
            if (is_automatic_calling_codes_enabled()) {
                $clientCountryId = $this->db->select('country')
                    ->where('userid', $userid)
                    ->get(db_prefix() . 'clients')->row()->country ?? null;
    
                $clientCountry = get_country($clientCountryId);
                $callingCode   = $clientCountry ? '+' . ltrim($clientCountry->calling_code, '+') : null;
            } else {
                $callingCode = null;
            }
                        
            if ($callingCode && !empty($phonenumber) && $phonenumber == $callingCode) {
                $phonenumber = '';
            }            

			$data = array(
                "firstname" => $firstname,
                "lastname" => $lastname,
                "password" => $password,
                "phonenumber" => $phonenumber,
                "email" => $email,
                'active' => $active,			
                'userid' => $userid,			
                'is_primary' => $is_primary			
            );            
            
            $original_contact   = $this->contacts_model->get($id);
            $success            = $this->contacts_model->update($data, $id, $send_set_password_email);
            $message            = '';
            $proposal_warning   = false;
            $original_email     = '';
            $updated            = false; 
            if (is_array($success)) {
                if (isset($success['set_password_email_sent'])) {
                    $response = array(
                        'type' => 'success',
                        'message' => _l('set_password_email_sent_to_client'),
                    );   
                } elseif (isset($success['set_password_email_sent_and_profile_updated'])) {
                    $updated = true;
                    $response = array(
                        'type' => 'success',
                        'message' => _l('set_password_email_sent_to_client_and_profile_updated'),
                    );                  
                }            
            } else {
                if ($success) {
                    $updated = true;
                    $response = array(
                        'type' => 'success',
                        'message' => _l('updated_successfully', _l('contact')),
                    );                       
                } elseif ($success == 0) {
                    $response = array(
                        'type' => 'info',
                        'message' => _l('updated_successfully', _l('contact')),
                    );  
                }
            }   

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));         
        }
    }

    /* Delete contact */
	public function delete($id)
	{
		$contact = $this->contacts_model->get($id);
        $success = $this->contacts_model->delete($id);

        if($success) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted', _l('contact')),
            );         
        } else {
            $response = array(
                'type' => 'error',
                'message' => _l('problem_deleting', _l('contact')),
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
                foreach ($ids as $contact_id) {
                    $project = $this->contacts_model->get($contact_id);
                    $success = $this->contacts_model->delete($contact_id);                                
                }                 
            }
        }          
    }     

    public function updateStatus()
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
		
        if(!empty($formdata)) {
            $ids 	        = $formdata['ids'];
            $is_primary 	= $formdata['active'];
            
            $data = array(
                "is_primary" => $is_primary,
            );

            if (is_array($ids)) {
                foreach ($ids as $contact_id) {
                    $success = $this->contacts_model->update_status($data, $contact_id);   
                    if($success) {
                        $response = array(
                            'type' => 'success',
                            'message' => _l('updated_successfully', _l('contact')),
                        );         
                    }            
                }                 
            }

        }   

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));     
    }     

    /**
     * Handles upload for contact files
     * @param  mixed $contactid post id
     * @return boolean
     */
	public function uploadPicture() 
	{
		$id = $this->input->post('id');

        $success = handle_contact_profile_image_upload($id);

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
    
	public function deletePicture($id)
	{
        $picture = $this->contacts_model->get($id);     
        $success = $this->contacts_model->delete_contact_profile_image($id);
        
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