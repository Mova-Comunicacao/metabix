<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Partners extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('partners_model');
    }

    /**
     * Get all partners
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */         
    public function getAll()
    {
        $this->safe(function () {
		    $partners = $this->partners_model->getAll();
		
            // array|obj|null
            if (empty($partners)) {
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
			foreach($partners as $row){ 
                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                    }
                }    

				$data[] = [
                    'id'                => isset($row->id) ? (int)$row->id : null,
                    'name'              => (string) ($row->name ?? ''),
                    'description'       => strip_tags(character_limiter((string) ($row->description ?? ''), 50)), 
					'folder'            => $folderUrl,  
					'file_name'         => isset($row->file_name) ? (string)$row->file_name : null,
					'date'              => $toIso($row->dateadded ?? null), 
					'order'             => isset($row->order) ? (int)$row->order : 0,
					'staff'             => $buildStaff($row->staffid), 
                ];
			}
            return $this->respond($data, 200);
		});  
    }

    /**
     * Get a single partner by id.
     *
     * Validates the id, resolves the current language, fetches the partner
     * and returns a normalized payload.
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the partner does not exist
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

            $row = $this->partners_model->get($id);
            if (empty($row)) {
                return $this->notFound('Team not found.');
            }            

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                }
            }    

            $fileImage = null;
            if (property_exists($row, 'file_name') && !empty($row->file_name)) {
                $fileImage = (string)$row->file_name;
            }         

            $partnercountry = get_country($row->countryid);
            $country = [];
            if(!empty($partnercountry)){
                $country = [
                    'country_id'    => (int) $partnercountry->country_id,
                ];                
            } 

            $data = [
                'id'            => isset($row->id) ? (int)$row->id : null,
                'name'          => (string)($row->name ?? ''),
                'description'   => (string)($row->description ?? ''),
                'external_link' => (string)($row->external_link ?? ''),
                'long_description'   => (string)($row->long_description ?? ''),
                'type'          => isset($row->type) ? (int)$row->type : 0,
                'folder'        => $folderUrl, 
                'file_name'     => $fileImage, 
                'countries'     => $country, 
            ];
            return $this->respond($data, 200);
        });
    }      
    
    /**
     * Create a new partner.
     *
     * Expects JSON body with fields:
     *  - name (string, required)
     *  - description (string, optional)
     *  - staffid (int, optional)
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
            if (array_key_exists('external_link', $formdata))   $data['external_link'] = trim((string)$formdata['external_link']);
            if (array_key_exists('countries', $formdata))       $data['countryid']   = (int)$formdata['countries'];
            if (array_key_exists('type', $formdata))            $data['type']        = (int)$formdata['type'];
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];


            $id = $this->partners_model->add($data);   
            if (!$id) {
                return $this->unprocessable('Failed to create partner.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'partner');                          
        });
    }   
    
    /**
     * Update a partner.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - description (string)
     *  - external_link (string)
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

            // array
            $data = [];
            if (array_key_exists('name', $formdata))            $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata))     $data['description'] = trim((string)$formdata['description']);     
            if (array_key_exists('external_link', $formdata))   $data['external_link'] = trim((string)$formdata['external_link']);
            if (array_key_exists('countries', $formdata))       $data['countryid']   = (int)$formdata['countries'];
            if (array_key_exists('type', $formdata))            $data['type']        = (int)$formdata['type'];
            
            $success = $this->partners_model->update($data, $pid);
            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'partner');      
            }

            return $this->unprocessable('Failed to update partner.');         
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
                    $this->db->update(db_prefix() . 'partners', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'partner');            
        });      
    }  
    
    /**
     * Delete a single partner by ID.
     *
     * @param int|string $partnerid
     *
     * Requires 'partners:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when partner does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */        
    public function delete($partnerid)
    {
        $this->safe(function () use ($partnerid) {
            $id = (int)$partnerid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $partner = $this->partners_model->get($id);
            if (empty($partner)) {
                return $this->notFound('Partner not found.');
            }                

            $success = $this->partners_model->delete($id);   
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'partner');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete partner.', ['id' => $id]);
        });             
    }   
    
    /**
     * Upload picture(s) for a partners.
     *
     * Expects multipart/form-data:
     *  - partnerid (int, required)
     *  - file(s) in $_FILES (handled by handle_partners_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $partnerid      = $this->input->post('id');

            $result = handle_partners_picture_uploads($partnerid);

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

            $success = $this->partners_model->delete_picture($pid);
        
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete picture.', ['id' => $pid]);           
        });       
    }     
}