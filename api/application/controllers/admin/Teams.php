<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Teams extends AdminController
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('teams_model');
    }

    /**
     * Get all team
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */        
    public function getAll()
    {
        $this->safe(function () {
            $lang = (string)($this->load_lang() ?? 'english');

		    $teams = $this->teams_model->getAll(['language' => $lang]);
		
            // array|obj|null
            if (empty($teams)) {
                return $this->respond([], 200);
            }

            // helper staff
            $buildStaff = function($staffid) {
                $s = $this->staff_model->get($staffid);
                if (empty($s)) {
                    return ['staffid' => null, 'fullname' => null];
                }
                return [
                    'staffid'  => (int)$s->staffid,
                    'fullname' => (string)$s->fullname
                ];
            };   
            
            // helper data ISO
            $toIso = function($dateStr) {
                $ts = strtotime((string)$dateStr);
                return $ts ? date('c', $ts) : (string)$dateStr;
            };               

            $data = [];
			foreach($teams as $row){ 
                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                    }
                }         

                $fileAvatar = null;
                if (property_exists($row, 'file_avatar') && !empty($row->file_avatar)) {
                    $fileAvatar = 'small_' . (string)$row->file_avatar;
                }

				$data[] = [
                    'id'                => isset($row->id) ? (int)$row->id : null,
                    'name'              => (string) ($row->name ?? ''),
                    'description'       => strip_tags(character_limiter((string) ($row->description ?? ''), 50)), 
                    'phonenumber'       => isset($row->phonenumber) ? (string)$row->phonenumber : null,
                    'email'             => isset($row->email) ? (string)$row->email : null,
					'folder'            => $folderUrl, 
					'file_avatar'       => $fileAvatar, 
					'date'              => $toIso($row->dateadded ?? null), 
					'type'              => isset($row->type) ? (int)$row->type : 1, 
					'order'             => isset($row->order) ? (int)$row->order : 0, 
					'staff'             => $buildStaff($row->staffid), 
                ];
			}
            return $this->respond($data, 200);
		});       
    }

    /**
     * Get a single team by id.
     *
     * Validates the id, resolves the current language, fetches the team
     * and its language, and returns a normalized payload.
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the team does not exist
     * - 422 Unprocessable when id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     *
     * @param mixed $id
     * @return void (echo JSON)
     */      
    public function getItemById($id)
    {
        $this->safe(function () use ($id) {
            $pid = (int) $id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }        
            $lang = (string) ($this->load_lang() ?? 'english');

		    $row = $this->teams_model->get($id, ['languages.language' => $lang]);
            if (empty($row)) {
                return $this->notFound('Team not found.');
            }

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                }
            }      

            $fileAvatar = null;
            if (property_exists($row, 'file_avatar') && !empty($row->file_avatar)) {
                $fileAvatar = 'small_' . (string)$row->file_avatar;
            }

            $data = [
                'id'            => isset($row->id) ? (int)$row->id : null,
                'name'          => (string)($row->name ?? ''),
                'description'   => (string)($row->description ?? ''),
                'phonenumber'   => isset($row->phonenumber) ? (string)$row->phonenumber : null,
                'email'         => isset($row->email) ? (string)$row->email : null,          
                'folder'        => $folderUrl, 
                'file_avatar'   => $fileAvatar, 
                'type'          => isset($row->type) ? (int)$row->type : 1, 
                'language'      => [
                    'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                    'language'   => (string)($row->language ?? $lang),
                ],  
            ];
		    return $this->respond($data, 200);
        });
    }
    
    /**
     * Create a new team.
     *
     * Expects JSON body with fields:
     *  - name (string, required)
     *  - description (string, optional)
     *  - email (string, optional)
     *  - phonenumber (string, optional)
     *  - staffid (int, optional)
     *  - languageid (int, required)             // target translation language
     *
     * Responses:
     * - 200 OK with { id } on success
     * - 422 Unprocessable on validation errors
     * - 500 on unexpected errors (handled by $this->safe())
     */      
	public function create()
	{
        $this->safe(function () {
            // Load payload (expects JSON body)
            $formdata = $this->readJson();

            if (empty($formdata) || !is_array($formdata)) {
                return $this->unprocessable('Empty or invalid payload.');
            }

            // array
            $data = [];
            if (array_key_exists('name', $formdata))            $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata))     $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('phonenumber', $formdata))     $data['phonenumber'] = trim((string)$formdata['phonenumber']);
            if (array_key_exists('email', $formdata))           $data['email']       = trim((string)$formdata['email']);
            if (array_key_exists('type', $formdata))            $data['type']        = trim((string)$formdata['type']);
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];

            // Insere via model
            $id = $this->teams_model->add($data);   
            if (!$id) {
                return $this->unprocessable('Failed to create team.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'team');                              
        });
    }   
    
    /**
     * Update a team.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - description (string)
     *  - email (string|null) - normalized to null when empty; validated if present
     *  - phonenumber (string|null) - normalized to null when empty; validated if present
     *  - staffid (int)
     *  - languageid (int)            - required (> 0) to target the translation row
     *
     * Responses:
     * - 200 OK on success (even if 0 affected rows)
     * - 422 Unprocessable on validation errors or empty payload
     * - 500 on unexpected errors (handled by $this->safe())
     */     
	public function update($id) 
	{
        $this->safe(function () use ($id) {
            $pid = (int) $id;
            
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            // Load payload (expects JSON body)
            $formdata = $this->readJson();       

            // languageid
            $languageid = isset($formdata['languageid']) ? (int)$formdata['languageid'] : 0;
            if ($languageid <= 0) {
                return $this->unprocessable('Missing or invalid languageid.', ['languageid' => 'Required and must be > 0']);
            }

            // array
            $data = [];
            if (array_key_exists('name', $formdata))            $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata))     $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('phonenumber', $formdata))     $data['phonenumber'] = trim((string)$formdata['phonenumber']);
            if (array_key_exists('email', $formdata))           $data['email'] = trim((string)$formdata['email']);
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('type', $formdata))            $data['type'] = trim((string)$formdata['type']);
            if (array_key_exists('languageid', $formdata))      $data['languageid']  = (int)$formdata['languageid'];
            // If no updatable fields were provided
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }
            
            $success = $this->teams_model->update($data, $pid);
            
            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'team');      
            }

            return $this->unprocessable('Failed to update team.');         
        });
    }  
    
	public function sortable() 
	{
        $this->safe(function () {
            $formdata = $this->readJson();

            $rows = $formdata['data'] ?? null;
            if (!is_array($rows) || empty($rows)) {
                return $this->unprocessable('Payload inválido.', ['data' => 'Required and must be a non-empty array of items.']);
            }

            if (is_array($rows)) {
                foreach ($rows as $pos => $item) {
                    $id = (int)$item['id'];
                    if ($id <= 0) {
                        return $this->unprocessable('Item inválido na lista.', [
                            "data[$pos].id" => 'Required and must be > 0'
                        ]);
                    }                    
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'teams', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'team');            
        });       
    }  
    
    /**
     * Delete a single team by ID.
     *
     * @param int|string $teamid
     *
     * Requires 'teams:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when team does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */     
    public function delete($teamid)
    {
        $this->safe(function () use ($teamid) {
            $id = (int)$teamid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $team = $this->teams_model->get($id);
            if (empty($team)) {
                return $this->notFound('Team not found.');
            }    

            $success = $this->teams_model->delete($id);   
        
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'team');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete team.', ['id' => $id]);
        });         
    }   
    
    /**
     * Upload picture(s) for a teams.
     *
     * Expects multipart/form-data:
     *  - team_id (int, required)
     *  - file(s) in $_FILES (handled by handle_teams_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $teamid        = $this->input->post('id');

            $result = handle_teams_avatar_uploads($teamid);

            // if invalid or erro
            if (!$result || !is_array($result)) {
                $result = set_alert(false, 'unexpected_error', 'Empty response from upload helper.');
            }

            $ok   = (bool)($result['ok'] ?? false);
            $type = $result['alert']['type'] ?? ($ok ? 'success' : 'error');

            $status = $ok ? 200 : ($type === 'warning' ? 422 : 400);

            if (!$ok) {
                return $this->respond($result, $status);
            }

            return $this->ok($result['data'] ?? null, 'upload', 'picture');
        });       
    }  
    
    /**
     * Delete a single picture by its ID.
     *
     * Params:
     * - $id (int, required): the picture ID
     *
     * Responses:
     * - 200 OK ($this->ok) when deleted successfully (returns the deleted id)
     * - 422 Unprocessable when id is missing/invalid or delete fails
     * - 500 on unexpected errors (handled by $this->safe())
     */        
	public function deletePicture($id)
	{
        $this->safe(function () use ($id) {
            $pid = (int)$id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->teams_model->delete_picture($pid);
            
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete picture.', ['id' => $pid]);           
        });    
    }  
    
    public function getSocial($id = '')
	{
        $data = $this->teams_model->get_social($id);

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
            $teamid                 = $formdata['teamid'];
            $staffid                = $formdata['staffid'];

            $data = array(
                'name' => $name,
                'link' => $link,
                'teamid' => $teamid,
                'staffid' => $staffid,
            );  

            $id = $this->teams_model->add_social($data);   
            if($id) {
                $success = true;
                $response = set_alert(
                    $success, 'added_successfully', ''
                );	
            }
            
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
            $teamid                 = $formdata['teamid'];
            $staffid                = $formdata['staffid'];

			$data = array(
				'name' => $name,
                'link' => $link,
                'teamid' => $teamid,
                'staffid' => $staffid,                
            );  

            $success = $this->teams_model->update_social($data, $id);
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
        $social = $this->teams_model->get_social($id);
        $success = $this->teams_model->delete_social($id);   
        
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