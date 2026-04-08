<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Technology extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('technology_model');
    }

    public function index()
    {
        $this->safe(function () {
            $lang = (string) ($this->load_lang() ?? 'english');

            $row = $this->technology_model->get(['language' => $lang]);

            // array|obj|null
            if (empty($row)) {
                return $this->respond([], 200);
            }

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    // rawurlencode evita problemas com espaços/acentos
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder) . '/'), '/') . '/';
                }
            }            

            $data = [
                'name'              => (string) ($row->name ?? ''),
                'description'       => (string) ($row->description ?? ''),
                'long_description'  => (string) ($row->long_description ?? ''),
                'folder'            => $folderUrl,
                'date'              => strtotime((string)$row->dateupdated),
                'staffid'           => isset($row->staffid) ? (int)$row->staffid : null,
                'language'          => [
                    'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                    'language'   => (string) ($row->language ?? $lang),
                ],
            ];
            
            $this->respond($data, 200);    
        });
    }

	public function update() 
	{
        $this->safe(function () {
            $formdata = $this->readJson();         
		
            // languageid
            $languageid = isset($formdata['languageid']) ? (int)$formdata['languageid'] : 0;
            if ($languageid <= 0) {
                return $this->unprocessable('Missing or invalid languageid.', ['languageid' => 'Required and must be > 0']);
            }

            // array
            $data = [];
            if (array_key_exists('name', $formdata))             $data['name'] = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata))      $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('long_description', $formdata)) $data['long_description'] = trim((string)$formdata['long_description']);
            if (array_key_exists('staffid', $formdata))          $data['staffid'] = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))       $data['languageid'] = (int)$formdata['languageid'];
            
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->technology_model->update($data);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'technology');      
            }
            
            return $this->unprocessable('Failed to update slide.');  
            
        });
    }   

    /******* Technology Videos *****/   

    /**
     * List videos for a given slide.
     *
     * Responses:
     * - 200 OK with an array of videos (possibly empty) or an informational payload
     * - 422 Unprocessable when slide_id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     */    
	public function getVideos()
	{
        $this->safe(function () {

            $videos = $this->technology_model->get_videos();

            $data = [];
            foreach($videos as $row){
                $data[] = [                                                    
                    'id' => isset($row->id) ? (int)$row->id : null,                          
                    'subject' => (string) ($row->subject ?? ''),                         
                    'description' => (string) ($row->description ?? ''),  
                    'video_id' => getVideoLocation($row->external),
                    'visible_to_customer' => isset($row->visible_to_customer) ? (int)$row->visible_to_customer : 0,
                    'thumb' => $row->external ? video_image($row->external) : $v->external                                                
                ];                     
            }

            if (empty($data)) {
                $response = array(
                    'type' => 'info',
                    'message' => 'No Videos'
                );                   
                return $this->respond($response, 200);
            }

            return $this->respond($data, 200); 
        });
    }  
      
    /**
     * Get a single video by id.
     *
     * Validates the id
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the video does not exist
     * - 422 Unprocessable when id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     *
     * @param mixed $id
     * @return void (echo JSON)
     */    
    public function getVideoById($id = '')
    {
        $this->safe(function () use ($id) {
            $pid = (int) $id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }   

            $row = $this->technology_model->get_video($pid);     
            if (empty($row)) {
                return $this->notFound('Video not found.');
            }    
            
            $data = [                                                    
                'id' => isset($row->id) ? (int)$row->id : null,                          
                'subject' => (string) ($row->subject ?? ''),                         
                'description' => (string) ($row->description ?? ''),  
                'external' =>(string) ($row->external ?? ''),
                'video_id' => getVideoLocation($row->external),
                'visible_to_customer' => isset($row->visible_to_customer) ? (int)$row->visible_to_customer : 0,                                           
            ]; 
            return $this->respond($data, 200);             
        });
    }

	public function addVideo()
	{
        $this->safe(function () {
            $formdata = $this->readJson();
                    
            $data = [];
            if (array_key_exists('subject', $formdata))         $data['subject']        = trim((string)$formdata['subject']);
            if (array_key_exists('description', $formdata))     $data['description']    = trim((string)$formdata['description']);   
            if (array_key_exists('external', $formdata))        $data['external']    = trim((string)$formdata['external']); 
            if (array_key_exists('visible_to_customer', $formdata))         $data['visible_to_customer']        = (int)$formdata['visible_to_customer'];   
            if (array_key_exists('staffid', $formdata))          $data['staffid']        = (int)$formdata['staffid']; 

            $id = $this->technology_model->add_video($data);  

            if (!$id) {
                return $this->unprocessable('Failed to create video.');
            }

           return $this->ok(['id' => $id], 'create', 'video');             
        });
    }

	public function updateVideo($id) 
	{
        $this->safe(function () use ($id) {
            $pid = (int) $id;
            
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }
		
            $formdata = $this->readJson();  

            if (array_key_exists('subject', $formdata))         $data['subject']        = trim((string)$formdata['subject']);
            if (array_key_exists('description', $formdata))     $data['description']    = trim((string)$formdata['description']);   
            if (array_key_exists('external', $formdata))        $data['external']    = trim((string)$formdata['external']); 
            if (array_key_exists('visible_to_customer', $formdata))         $data['visible_to_customer']        = (int)$formdata['visible_to_customer'];   
            if (array_key_exists('staffid', $formdata))          $data['staffid']        = (int)$formdata['staffid'];   
            
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->technology_model->update_video($data, $pid);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'video');      
            }

            return $this->unprocessable('Failed to update video.');               
        });
    }    

	public function sortableVideos() 
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
                    $this->db->update(db_prefix() . 'technology_videos', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'videos');            
        });   
    }     
    
	public function deleteVideo($videoid)
	{
        $this->safe(function () use ($videoid) {
            $id = (int)$videoid;  

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->technology_model->delete_video($id);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'video');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $id]);

        });
    }         
            
    /******* Technology Pictures *****/   

    /**
     * List pictures for a given slide.
     *
     * Responses:
     * - 200 OK with an array of pictures (possibly empty) or an informational payload
     * - 422 Unprocessable when slide_id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     */    
	public function getPictures()
	{
        $this->safe(function () {

            $data = $this->technology_model->get_pictures();

            if (empty($data)) {
                $response = array(
                    'type' => 'info',
                    'message' => 'No Pictures'
                );                   
                return $this->respond($response, 200);
            }

            return $this->respond($data, 200);   
        });       
	}      

    /**
     * Upload picture(s) for a technology.
     *
     * Expects multipart/form-data:
     *  - staffid (int, optional)
     *  - subject (string, optional)
     *  - description (string, optional)
     *  - file(s) in $_FILES (handled by handle_technology_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $staffid        = (int)$this->input->post('staffid');
            $subject        = $this->input->post('subject');
            $description    = $this->input->post('description');

            $result = handle_technology_picture_uploads($staffid, $subject, $description);

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

            return $this->ok($result['data'] ?? null, 'create', 'picture');
        });        
    } 
    
	public function sortablePictures() 
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
                    $this->db->update(db_prefix() . 'technology_pictures', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'pictures');            
        });   
    }      
    
	public function deletePicture($id)
	{
        $this->safe(function () use ($id) {
            $pid = (int)$id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->technology_model->delete_picture($pid);

            if ($success) {
                // 200 OK
                return $this->ok(null, 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');   
        });
    } 

    /******* Technology Items *****/

	public function getItems($id = '')
	{
        $this->safe(function () use ($id) {
            $pid = $id ?? '';

            $lang = (string) ($this->load_lang() ?? 'english');

            $items = $this->technology_model->get_items($pid, ['language' => $lang]);
            // array|obj|null
            if (empty($items)) {
                return $this->respond([], 200);
            }

            // helper URL de pasta
            $buildFolderUrl = function($folder) {
                $f = trim((string)$folder, "/ \t\n\r\0\x0B");
                if ($f === '') return null;
                return rtrim(base_url('api/uploads/' . rawurlencode($f)), '/') . '/icons/';
            };       
            
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
            
            $mapRow = function($row) use ($buildFolderUrl, $buildStaff, $toIso) {
                $folderUrl = $buildFolderUrl($row->folder ?? '');
                $dateIso   = $toIso($row->dateadded ?? null);

                $item = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'description'   => (string)($row->description ?? ''),
                    'folder'        => $folderUrl,
                    'file_name'     => (string)($row->file_name ?? ''),
                    'date'          => $dateIso,
                    'visible_draft' => isset($row->visible_draft) ? (int)$row->visible_draft : 0,
                    'external_link' => (string)($row->external_link ?? ''),
                    'order'         => isset($row->order) ? (int)$row->order : 0,
                    'language'          => [
                        'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                        'language'   => (string) ($row->language ?? $lang),
                    ],                    
                ];

                // staff (quando existir)
                if (isset($row->staffid)) {
                    $item['staff'] = $buildStaff($row->staffid);
                }

                return $item;
            };  
            
            if (is_array($items)) {
                $data = [];
                foreach ($items as $row) {
                    $data[] = $mapRow($row);
                }
                return $this->respond($data, 200);
            } else {
                if ($pid <= 0) {
                    return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
                }                    
                $data = $mapRow($items);
                return $this->respond($data, 200);
            }  
        });          
	} 

    
	public function addItems()
	{
        $this->safe(function () {
            $formdata = $this->readJson();
		
            $data = [];
            if (array_key_exists('name', $formdata))          $data['name'] = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata))   $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('link', $formdata))          $data['link'] = trim((string)$formdata['link']);
            if (array_key_exists('visible_draft', $formdata)) $data['visible_draft'] = (int)!empty($formdata['visible_draft']);
            if (array_key_exists('staffid', $formdata))       $data['staffid'] = (int)$formdata['staffid'];
            
            // Insere slide via model
            $id = $this->technology_model->add_items($data);   

            if (!$id) {
                return $this->unprocessable('Failed to create item.');
            }

           return $this->ok(['id' => $id], 'create', 'item');
        });
    }   
    
    /**
     * update Items
     *
     * @param [type] $id
     * @return void
     */    
	public function updateItems($id) 
	{
        $this->safe(function () use ($id) {
            $pid = (int) $id;
            
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $formdata = $this->readJson();
		
            // languageid
            $languageid = isset($formdata['languageid']) ? (int)$formdata['languageid'] : 0;
            if ($languageid <= 0) {
                return $this->unprocessable('Missing or invalid languageid.', ['languageid' => 'Required and must be > 0']);
            }            

            // array
            $data = [];
            if (array_key_exists('name', $formdata))        $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('link', $formdata))        $data['link'] = trim((string)$formdata['link']);
            if (array_key_exists('visible_draft', $formdata)) $data['visible_draft'] = (int)!empty($formdata['visible_draft']);
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))  $data['languageid']  = (int)$formdata['languageid'];

            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->technology_model->update_items($data, $id);
            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'item');      
            }

            return $this->unprocessable('Failed to update item.');  
        });
    }   
    
	public function sortableItems() 
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
                    $this->db->update(db_prefix() . 'technology_items', array(
                        'order' => (int)$pos
                    ));                                   				                                                       
                }    
            }

            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'item');
        });    
    }  
    
    /**
     * Delete a single technology by ID.
     *
     * @param int|string $technologyid
     *
     * Requires 'technology items:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when technology does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */    
    public function deleteItem($id)
    {
        $this->safe(function () use ($id) {
            $pid = (int) $id;
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->technology_model->delete_item($pid);   
            
            if ($success) {
                // success
                return $this->ok(['id' => $pid], 'delete', 'item');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');
        });       
    }     

    /**
     * Upload picture(s) for a item.
     *
     * Expects multipart/form-data:
     *  - item_id (int, required)
     *  - file(s) in $_FILES (handled by handle_item_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicturesItems() 
	{
        $this->safe(function () {
            $id = $this->input->post('id');

            $result = handle_technology_item_picture_uploads($id);

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
	public function deletePictureItem($id)
	{ 
        $this->safe(function () use ($id) {
            $pid = (int)$id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->technology_model->delete_picture_items($pid);

            if ($success) {
                // 200 OK
                return $this->ok(null, 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');   
        }); 
    }  
    
    /******* Technology Diagnosis *****/

	public function getDiagnosis($id = '')
	{
        $diagnosis = hooks()->apply_filters('before_get_tech_diagnosis', [
            [
                'id'             => 1,
                'color'          => '#475569',
                'name'           => _l('post_status_1'),
                'order'          => 1,
                'filter_default' => true,
            ],
        ]);
        
        usort($diagnosis, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $diagnosis;        
    }
}