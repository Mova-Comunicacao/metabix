<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Company extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('company_model');
    }

    public function company()
    {
        $this->safe(function () {
            $lang = (string)($this->load_lang() ?? 'english');

            $row = $this->company_model->get(['language' => $lang]);

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
                    'language'   => (string)($row->language ?? $lang),
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

            $success = $this->company_model->update($data);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'slide');      
            }
            
            return $this->unprocessable('Failed to update slide.');      
        });
    }  
    
	public function getItems($id = '')
	{
        $this->safe(function () use ($id) {
            $pid = $id ?? '';

            $lang = (string) ($this->load_lang() ?? 'english');

            $items = $this->company_model->get_items($pid, ['language' => $lang]);
            // array|obj|null
            if (empty($items)) {
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
            
            $mapRow = function($row) use ($buildStaff, $toIso) {
                $dateIso   = $toIso($row->dateadded ?? null);

                $item = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'description'   => (string)($row->description ?? ''),
                    'date'          => $dateIso,
                    'order'         => isset($row->order) ? (int)$row->order : 0,
                    'link'          => (string)($row->link ?? ''),
                    'language'          => [
                        'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                        'language'   => (string) ($row->language ?? $lang),
                    ],                    
                ];

                // staff
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
            if (array_key_exists('name', $formdata))        $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('link', $formdata))        $data['link']        = trim((string)$formdata['link']);
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];


            $id = $this->company_model->add_items($data);  

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
            if (array_key_exists('link', $formdata))        $data['link']        = trim((string)$formdata['link']);
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))  $data['languageid']  = (int)$formdata['languageid'];

            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->company_model->update_items($data, $pid);

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
                    $this->db->update(db_prefix() . 'company_items', array(
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
    
    public function deleteItem($id)
    {
        $this->safe(function () use ($id) {
            $pid = (int) $id;
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->company_model->delete_item($pid);   
            
            if ($success) {
                // success
                return $this->ok(['id' => $pid], 'delete', 'item');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');
        });         
    }     
    
    /**
     * Upload picture(s) for a company.
     *
     * Expects multipart/form-data:
     *  - staffid (int, optional)
     *  - subject (string, optional)
     *  - description (string, optional)
     *  - file(s) in $_FILES (handled by handle_company_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $staffid        = $this->input->post('staffid');
            $subject        = $this->input->post('subject') ?? '';
            $description    = $this->input->post('description') ?? '';

            $result = handle_company_picture_uploads($staffid, $subject, $description);

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
                    $this->db->update(db_prefix() . 'company_pictures', array(
                        'order' => (int)$pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'pictures');            
        });  
    }      

	public function getPictures()
	{
        $this->safe(function () {

            $data = $this->company_model->get_pictures();

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
    
	public function deletePicture($id)
	{
        $this->safe(function () use ($id) {
            $pid = (int)$id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->company_model->delete_picture($pid);

            if ($success) {
                // 200 OK
                return $this->ok(null, 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');   
        });
    }      
}