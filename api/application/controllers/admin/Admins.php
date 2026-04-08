<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admins extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin_model');
    }

    public function getAll()
    {
		$admins = $this->admin_model->getAll();
		
		$admin = array();
		if(!empty($admins)){
			foreach($admins as $row){

                if(empty($row->adminWebsite)) {
                    $email  = $row->adminEmail;
                    $parts = explode("@",$email);
                    // Get the website by slicing string
                    $website = $parts[1];
                } else {
                    $website  = $row->adminWebsite;
                }                

				$admin[] = array(
					'id' => $row->staffid,
                    'staffid' => $row->staffid,
                    'adminFirstName' => $row->adminFirstName,
                    'adminLastName' => $row->adminLastName,
                    'adminEmail' => $row->adminEmail,
                    'adminPhone' => $row->adminPhone,
                    'avatarFolder' => base_url('api/uploads/avatars/'),
                    'adminAvatar' => $row->adminAvatar,
                    'accessToken' => $row->token,
                    'adminRole' => $row->adminRole,
                    'username' => $row->username,
                    'language' => $row->language,
                    'date' => $row->createDate,
                    'superuser' => $row->superuser,
                    'adminAddress' => $row->adminAddress,
                    'website' => $website,
                    'status' => $row->isActive,
				);
			}
		}

		$response = $admin;
		
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response));  
	} 

    public function getItemById($id)
    {

        $admin = $this->admin_model->get($id);
        $password = app_hash_password($admin->password);

        if(empty($admin->adminWebsite)) {
            $email  = $admin->adminEmail;
            $parts = explode("@",$email);
            // Get the website by slicing string
            $website = $parts[1];
        } else {
            $website  = $admin->adminWebsite;
        }   

        if ($admin) {
            $response = array(
                'id' => $admin->staffid,
                'staffid' => $admin->staffid,
                'adminFirstName' => $admin->adminFirstName,
                'adminLastName' => $admin->adminLastName,
                'adminEmail' => $admin->adminEmail,
                'adminPhone' => $admin->adminPhone,
                'avatarFolder' => base_url('api/uploads/avatars/'),
                'adminAvatar' => $admin->adminAvatar,
                'accessToken' => $admin->token,
                'adminRole' => $admin->adminRole,
                'username' => $admin->username,
                'language' => $admin->language,
                'date' => $admin->createDate,
                'superuser' => $admin->superuser,            
                'adminAddress' => $admin->adminAddress,
                'password' => $admin->password,
                'website' => $website,
                'status' => $admin->isActive,
            );
        } else {
            $response = array(
                'type' => 'error'
            );	
        }
    
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));        
                
    }   

	public function create()
	{
		$formdata = json_decode(file_get_contents('php://input'), true);
		
		if(!empty($formdata)) {

            $dupEmail = false;

            $this->db->where('adminEmail', $formdata['adminEmail']);
            $admin = $this->db->get('admins')->row();
            if ($admin) {
                $dupEmail = true;
            }
    
            if (!$dupEmail) {

                $adminFirstName 	= $formdata['adminFirstName'];
                $adminLastName 	    = $formdata['adminLastName'];
                $adminEmail 	    = $formdata['adminEmail'];
                $adminRole 	        = $formdata['adminRole'];
                $password 	        = $formdata['password'];
                $status 	        = $formdata['status'];

                $send_mail 	        = $formdata['send_mail'];

                //$data['createDate'] = date('Y-m-d');
                //$data['password'] = md5('password');
                //$data['token'] = md5(uniqid('token'));

                $data = array(
                    'adminFirstName' => $adminFirstName,
                    'adminLastName' => $adminLastName,
                    'adminEmail' => $adminEmail,
                    'adminRole' => $adminRole,					
                    'password' => $password,
                    'createDate' =>	'',	
                    'token' => '',			
                    'isActive' => $status,		
                    //'send_created_email' => $send_mail,		
                );

                $id = $this->admin_model->insert($data);
                if ($id) {
                    if ($data['send_created_email']) {
                        $this->load->model('emails_model');
                        $this->emails_model->send_admin_email($adminEmail, $password, 'Você recebeu acesso a uma propriedade', 'access');
                    }   
        
                    return $insert_id;
                }
                $response = array(
                    'type' => $id
                );
            }
            else {
                $response = array(
                    'type' => 'error',
                    'message' => 'Este e-mail já está cadastrado.'
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

			$adminFirstName 	= $formdata['adminFirstName'];
			$adminLastName 	    = $formdata['adminLastName'];
			$adminPhone 	    = $formdata['adminPhone'];
			$adminEmail 	    = $formdata['adminEmail'];
			$adminAddress 	    = $formdata['adminAddress'];

			$username 	        = $formdata['username'];
			$adminRole 	        = $formdata['adminRole'];
			$language 	        = $formdata['language'];
			$superuser 	        = $formdata['superuser'];

			$data = array(
				'adminFirstName' => $adminFirstName,
				'adminLastName' => $adminLastName,
				'adminPhone' => $adminPhone,
				'adminEmail' => $adminEmail,
				'adminAddress' => $adminAddress,
				'username' => $username,
				'adminRole' => $adminRole,
				'language' => $language,
				'superuser' => $superuser,
			);	
			
			$success = $this->admin_model->update($id, $data);
            if ($success) {
                $response = set_alert($success, 'updated_successfully', 'staff_member');	
            }
			
			$this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response)); 
		}		
	}    
    
    
	public function uploadAvatar($id) 
	{
        $admin = $this->admin_model->get($id);
        $filename = $admin->adminAvatar;

		//$filename = 'adminDefault.png';
		$isUploadError = false;	
		
		if ($_FILES && $_FILES['adminAvatar']['name']) {
            do_action('before_upload_admin_avatar');
            
            $settings = $this->settings_model->settings();
            // Get the Max Upload Size allowed
            $maxUpload = (int)(ini_get('upload_max_filesize'));
            // Getting file size
            $sizeFile = pathinfo($_FILES["file"]["size"]);            
			// Get the File Types allowed
			$fileExt = $settings->avatarTypes;
			$ftypes_data = explode( ',', $fileExt );
            $allowed_extensions = $fileExt;   

            // Getting file extension
            $path_parts = pathinfo($_FILES['adminAvatar']['name'], PATHINFO_EXTENSION);
            $extension = strtolower($path_parts);

            if ($sizeFile > $maxUpload) {
                $file_uploaded = false;                    

                $response = array(
                    'type' => 'error',
                    'message' => 'Maximum size: ' . $maxUpload . 'MB.'
                ); 
            } else {
                $file_uploaded = true;
            }            
            if (!in_array($extension, $ftypes_data)) {
                $file_uploaded = false;                    

                $response = array(
                    'type' => 'error',
                    'message' => 'Image extension not allowed. Extensions: ' . $allowed_extensions
                ); 
            } else {
                $file_uploaded = true;				
            }

            if ($file_uploaded == true) {

                $path = AVATAR_ATTACHMENTS_FOLDER;
				if($admin->adminAvatar && file_exists($path.$admin->adminAvatar)) {
					unlink($path.$admin->adminAvatar);
				}					
                // Get the temp file path
                $tmpFilePath = $_FILES['adminAvatar']['tmp_name'];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {

                    $filename = unique_filename($path, $_FILES["adminAvatar"]["name"]); 
                    $newFilePath = $path . $filename;				
                    // Upload the file into the temp dir
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $file_uploaded = true;  
                        $data = array(
                            'adminAvatar' => $filename,
                        ); 
                        $success = $this->admin_model->upload($id, $data);  
            
                        $response = array(
                            'type' => 'success'
                        );					
					} else {
						$response = array(
							'type' => 'error',
							'message' => 'Houve um erro ao carregar a Imagem, por favor, verifique o tipo de arquivo e tente novamente.'
						);						
					}				
				}								
			} else {
                $response = array(
                    'type' => 'error',
                    'message' => 'Houve um erro ao carregar a Imagem, por favor, verifique o tipo de arquivo e tente novamente.'
                ); 				
			}	
		}					
	
		$this->output
			->set_status_header(200)
			->set_content_type('application/json')
			->set_output(json_encode($response)); 			      			
	}  
    
	public function deleteAvatar($id)
	{
        $success = $this->admin_model->deleteAvatar($id);
        
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

	public function delete($id)
	{
        $isDeleteError = false;

        $admin = $this->admin_model->get($id);
        if ($admin->staffid == 1 || $admin->superuser == 1) {
            $isDeleteError = true;

            $response = array(
                'type' => 'error',
                'message' => 'Busted, you can\'t delete administrators'
            );            
        }

        if (!$isDeleteError) {        
            $success = $this->admin_model->deleteAdmin($id);
            if ($success) {
                set_alert('success', _l('deleted', _l('staff_member')));
            }
        }

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($response)); 		
	}	       
    
    public function changePassword($id) 
	{
        $isError = false;
		$formdata = json_decode(file_get_contents('php://input'), true);

		if(!empty($formdata)) {
            $currentPassword 	= $formdata['currentPassword'];
            $password 	        = $formdata['password'];

            $admin = $this->admin_model->get($id);
            //$passwordOld = $admin->password;            

            if(!app_hasher()->CheckPassword($currentPassword, $admin->password)) {
                $isError = true;
                $response = array(
                    'type' => 'error',
                    'message' => 'Senha atual incorreta.'
                );
            } else {
                $isError = false;
            }

            if(!$isError) {
			    $success = $this->admin_model->set_password($id, $password);
                if($success) {
                    $response = array(
                        'type' => 'success'
                    );	
                } else {
                    $response = array(
                        'type' => 'error'
                    );					
                }
            }
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));              
        }         
    } 
}