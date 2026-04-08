<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Projects extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
    }

    public function getAll()
    {
		$ts_filter_data = array();
		$ts_filter_data['category_id'] = $this->input->get('category_id');
		$ts_filter_data['search_string'] = $this->input->get('search_string');
		$filter = array('filter' => $ts_filter_data);

		$projects = $this->projects_model->getAll($filter, ['language' => $this->load_lang()]);
		
		$data = array();
		if(!empty($projects)){
			foreach($projects as $row){ 
				$staff = $this->staff_model->get($row->staffid);	
                $admin = array(
					'staffid' => $staff->staffid,
					'firstname' => $staff->firstname,
					'lastname' => $staff->lastname,               
					'fullname' => $staff->fullname                  
                );

                $category = $this->projects_model->get_categories($row->id, ['language' => $this->load_lang()]);
                $categories = array();
                if(!empty($category)){
                    foreach($category as $c){
                        $categories[] = array(
                            'name' => $c->name,
                        );                    
                    }
                } 

				$data[] = array(
					'id' => $row->id, 
					'name' => $row->name, 
					'description' => $row->description, 
					'long_description' => $row->long_description, 
					'folder' => base_url('api/uploads/projects/'.$row->id.'/'), 
					'date' => $row->dateadded, 
					'order' => $row->order, 
					'active' => $row->active, 
					'categories' => $categories, 
					'staff' => $admin, 
				);                
            }
        }

		$response = $data;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));          
    }  
    
    public function getItemById($id = '')
    {
		$row = $this->projects_model->get($id, null, ['language' => $this->load_lang()]);

        $categories = $this->projects_model->get_categories($id, ['language' => $this->load_lang()]);
        $category = array();
        if(!empty($categories)){
            foreach($categories as $c){
                $category[] = array(
                    'category_id' => $c->category_id,
                    'name' => $c->name,
                );                    
            }
        } 

        $data = array(
            'id' => $row->id,
            'name' => $row->name,
            'description' => $row->description,
            'long_description' => $row->long_description,
            'folder' => base_url('api/uploads/projects/'.$row->id.'/'), 
            'clientid' => $row->clientid,
            'staffid' => $row->staffid,
            'slug' => $row->slug,
            'city' => $row->city,
            'year' => $row->year,
            'active' => $row->active,
            'categories' => $category,
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
            $long_description       = $formdata['long_description'];
            $categories             = $formdata['categories'];                     
            $staffid                = $formdata['staffid'];
            $city                   = $formdata['city'];
            $year                   = $formdata['year'];
            $active                 = $formdata['active'];

			$data = array(
				'name' => $name,
				'description' => $description,
				'long_description' => $long_description,
				'categories' => $categories,                             
				'staffid' => $staffid,
				'city' => $city,
				'year' => $year,
				'active' => $active,
            );  

            $id = $this->projects_model->add($data);   
            if($id) {
                $response = array(
                    'type' => 'success',
                    'message' => _l('added_successfully', _l('project')),
                    'id' => $id
                );                  
            }
            
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
            $long_description       = $formdata['long_description'];
            $categories             = $formdata['categories'];                     
            $staffid                = $formdata['staffid'];
            $slug                   = $formdata['slug'];
            $city                   = $formdata['city'];
            $year                   = $formdata['year'];            
            $active                 = $formdata['active'];

            $languageid             = $formdata['languageid'];

			$data = array(
				'name' => $name,
				'description' => $description,
				'long_description' => $long_description,
				'categories' => $categories,                            
				'staffid' => $staffid,
				'slug' => $slug,
				'city' => $city,
				'year' => $year,                
				'active' => $active,
                'languageid' => $languageid
            );  


            $success = $this->projects_model->update($data, $id);

            if(isset($success)) {
                $response = set_alert(
                    $success, 'updated_successfully', 'project'
                );	
            }

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
                foreach ($data as $pos => $project_id) {
                    $this->db->where('id', $project_id['id']);
                    $this->db->update(db_prefix() . 'projects', array(
                        'order' => $pos
                    ));                                   				                                  
                }              
            }
        }       
    }     

    public function updateStatus()
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
        if(!empty($formdata)) {
            $ids 	    = $formdata['ids'];
            $active 	= $formdata['active'];
            if (is_array($ids)) {
                foreach ($ids as $project_id) {
                    $this->db->where('id', $project_id);
                    $this->db->update(db_prefix() . 'projects', array(
                        'active' => $active
                    ));                                   
                }                 
            }
        }    
		  
        $response = array(
            'type' => 'success',
            'message' => _l('updated_successfully', _l('project'))
        );  

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));         
    } 
    
    /* Delete contact */
	public function delete($id)
	{
		$contact = $this->projects_model->get($id);
        $success = $this->projects_model->delete($id);

        if(isset($success)) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted', _l('project')),
            );         
        } else {
            $response = array(
                'type' => 'error',
                'message' => _l('problem_deleting', _l('project')),
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
                foreach ($ids as $project_id) {
                    $project = $this->projects_model->get($project_id);
                    $success = $this->projects_model->delete($project_id);                                
                }                 
            }
        }   
        
        if(isset($success)) {
            $response = array(
                'type' => 'success',
                'message' => _l('deleted', _l('project')),
            );         
        } else {
            $response = array(
                'type' => 'error',
                'message' => _l('problem_deleting', _l('project')),
            );                
        }   
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));            
    }  

    /**
     * Handles upload for projects files
     * @param  mixed $project_id project project_id
     * @return boolean
     */
	public function uploadPicture() 
	{
        $staffid        = $this->input->post('staffid');
		$project_id     = $this->input->post('project_id');

		$subject        = $this->input->post('subject');
		$description    = $this->input->post('description');

        $result = handle_project_picture_uploads($project_id, $staffid, $subject, $description);

        if (is_array($result) && isset($result['message'])) {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );
        } elseif ($result === true) {
            $response = array(
                'type' => 'success',
                'message' => _l('file_uploaded_success')
            );                
        } else {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );                
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));          
    }   

    /**
     * Handles upload for projects files
     * @param  mixed $project_id project project_id
     * @return boolean
     */
	public function uploadPictures() 
	{

        $staffid        = $this->input->post('staffid');
		$project_id     = $this->input->post('project_id');

        $result = handle_project_pictures_uploads($project_id, $staffid);
    
        if (is_array($result) && isset($result['message'])) {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );
        } elseif ($result === true) {
            $response = array(
                'type' => 'success',
                'message' => _l('file_uploaded_success')
            );                
        } else {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );                
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));             
    }    
    
    public function updatePicture($id)
    {
		$formdata = json_decode(file_get_contents('php://input'), true);
        if(!empty($formdata)) {
            $subject 	    = $formdata['subject'];
            $description 	= $formdata['description'];
            $visible_full 	= $formdata['visible_full'];

            if (is_numeric($id)) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'projects_pictures', array(
                    'subject' => $subject,
                    'description' => nl2br($description),
                    'visible_full' => $visible_full,
                ));
            }
        }    
		  
        $response = array(
            'type' => 'success',
            'message' => _l('updated_successfully', _l('project'))
        );  

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));         
    }     

	public function sortableImage() 
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		if(!empty($formdata)) {
			$data = $formdata['data'];
            if (is_array($data)) {
                foreach ($data as $pos => $picture_id) {
                    $this->db->where('id', $picture_id['id']);
                    $this->db->update(db_prefix() . 'projects_pictures', array(
                        'order' => $pos
                    ));                                   				                                  
                }              
            }
        } 
        
        $response = array(
            'type' => 'success',
            'message' => _l('updated_successfully')
        );            

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));                
    }       

	public function getPictures($project_id = '')
	{
        $pictures = $this->projects_model->get_pictures($project_id);

        $data = array();	
        if(!empty($pictures)) {  
            foreach($pictures as $row){  
                $path = 'api/uploads/projects/' . $row->project_id. '/';

                $fullPath  = $path . $row->file_name; 
                $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                $thumbPath = $fname . '_thumb.' . $fext;
                if (!empty($thumbPath)) {
                    $thumb = $thumbPath;
                }                            
                
                $data[] = array(         
                    'id' => $row->id,                    
                    'file_name' => $row->file_name,                    
                    'original_file_name' => $row->original_file_name,  
                    'visible_full' => $row->visible_full,                             
                    'subject' => $row->subject,                             
                    'description' => $row->description,  
                    'projectid' => $row->project_id,  
                    'thumb' => base_url($path . $thumb )                                
                );
            }
        }

        $response = $data;
                
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));           
	}  
    
	public function getPicturesById($id = '')
	{
        $data = $this->projects_model->get_picture($id);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));           
	}      
    
	public function deletePicture($id)
	{
        $success = $this->projects_model->delete_picture($id);

        if(isset($success)) {
            $response = set_alert(
                'success', 'deleted', ''
            ); 
        }
        
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));    
    }   

    /**
     * Handles upload for projects maps
     * @param  mixed $project_id project project_id
     * @return boolean
     */
	public function uploadMap() 
	{
        $staffid        = $this->input->post('staffid');
		$project_id     = $this->input->post('project_id');

		$subject        = $this->input->post('subject');
		$description    = $this->input->post('description');

        $result = handle_project_map_uploads($project_id, $staffid, $subject, $description);

        if (is_array($result) && isset($result['message'])) {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );
        } elseif ($result === true) {
            $response = array(
                'type' => 'success',
                'message' => _l('file_uploaded_success')
            );                
        } else {
            $response = array(
                'type' => 'error',
                'message' => $result['message']
            );                
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));          
    }    

	public function sortableMaps() 
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		if(!empty($formdata)) {
			$data = $formdata['data'];
            if (is_array($data)) {
                foreach ($data as $pos => $map_id) {
                    $this->db->where('id', $map_id['id']);
                    $this->db->update(db_prefix() . 'projects_maps', array(
                        'order' => $pos
                    ));                                   				                                  
                }              
            }
        }       
    }  

	public function getMaps($project_id = '')
	{
        $pictures = $this->projects_model->get_maps($project_id);

        $data = array();	
        if(!empty($pictures)) {  
            foreach($pictures as $row){  
                $path = 'api/uploads/projects/' . $row->project_id. '/';

                $fullPath  = $path . $row->file_name; 
                $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                $thumbPath = $fname . '_thumb.' . $fext;
                if (!empty($thumbPath)) {
                    $thumb = $thumbPath;
                }                            
                
                $data[] = array(         
                    'id' => $row->id,                    
                    'file_name' => $row->file_name,                    
                    'original_file_name' => $row->original_file_name,                            
                    'subject' => $row->subject,                             
                    'description' => $row->description,  
                    'projectid' => $row->project_id,  
                    'thumb' => base_url($path . $thumb )                                
                );
            }
        }

        $response = $data;

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));           
	}     
    
	public function deleteMap($id)
	{
        $success = $this->projects_model->delete_map($id);

        if(isset($success)) {
            $response = set_alert(
                'success', 'deleted', ''
            ); 
        }
        
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));    
    }      
    
	public function getActivity($id)
	{
        $data = $this->projects_model->get_activity($id, $limit = 10);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));          
    }    
    
}