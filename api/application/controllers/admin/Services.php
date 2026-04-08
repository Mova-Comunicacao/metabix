<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Services extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('services_model');
    }

    public function getAll()
    {
		$services = $this->services_model->getAll(['language' => $this->load_lang()]);
		
		$data = array();
		if(!empty($services)){
			foreach($services as $row){ 

				$staff = $this->staff_model->get($row->staffid);	
                $admin = array(
					'staffid' => $staff->staffid,
					'firstname' => $staff->firstname,
					'lastname' => $staff->lastname,               
					'fullname' => $staff->fullname                  
                );

				$data[] = array(
					'id' => $row->id, 
					'name' => $row->name, 
					'description' => strip_tags(character_limiter($row->description, 50)), 
					'folder' => base_url('api/uploads/'.$row->folder.'/icons\/'), 
					'file_name' => $row->file_name, 
					'date' => $row->dateadded, 
					'order' => $row->order, 
					'staff' => $admin, 
				);
			}
		}

		$response = $data;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }

    public function getItemById($id)
    {
		$row = $this->services_model->get($id, ['language' => $this->load_lang()]);

        $data = array(
            'id' => $row->id, 
            'name' => $row->name, 
            'description' => $row->description, 
            'folder' => base_url('api/uploads/'.$row->folder.'/icons\/'), 
            'file_name' => $row->file_name, 
            'date' => $row->dateadded, 
            'order' => $row->order, 
            'languageid' => $row->languageid,
            'language' => $row->language,               
        );

		$response = $data;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }      
    
	public function create()
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		
        if(!empty($formdata)) {
            $name                   = $formdata['name'];
            $description            = $formdata['description'];  
            $staffid                = $formdata['staffid'];

            $data = array(
                'name' => $name,
                'description' => $description,
                'staffid' => $staffid,
            );  

            $success = $this->services_model->add($data);   
            $response = set_alert(
                $success, 'added_successfully', 'project'
            );	
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                          
        }
    }   
    
	public function update($id) 
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		
		if(!empty($formdata)) {
            $name                   = $formdata['name'];
            $description            = $formdata['description'];  

            $languageid             = $formdata['languageid'];

			$data = array(
				'name' => $name,
				'description' => $description,
                'languageid' => $languageid
            );  

            $success = $this->services_model->update($data, $id);
            $response = set_alert(
                $success, 'updated_successfully', 'project'
            );	
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));         
        }
    }  
    
	public function sortable() 
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		if(!empty($formdata)) {
			$data = $formdata['data'];
            if (is_array($data)) {
                foreach ($data as $pos => $category_id) {
                    $this->db->where('id', $category_id['id']);
                    $this->db->update(db_prefix() . 'services', array(
                        'order' => $pos
                    ));                                   				                                  
                }              
            }
        }       
    }  
    
    public function delete($id)
    {
        $success = $this->services_model->delete($id);   
        
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
}