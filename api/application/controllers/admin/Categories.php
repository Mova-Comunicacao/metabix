<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Categories extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('categories_model');
    }

    /**
     * Get all slides
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */       
    public function getAll()
    {
        $this->safe(function () {
            $lang = (string)($this->load_lang() ?? 'english');  

		    $categories = $this->categories_model->getAll(['language' => $lang]);
		
            // array|obj|null
            if (empty($categories)) {
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
			foreach($categories as $row){ 
                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/icons/';
                    }
                }     

				$data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'description'   => (string)($row->description ?? ''),
                    'folder'        => $folderUrl,
                    'file_name'     => (string)($row->file_name ?? ''),
                    'date'          => $toIso($row->dateadded ?? null),
					'order'         => isset($row->order) ? (int)$row->order : 0, 
					'staff'         => $buildStaff($row->staffid), 
                    'language'      => [
                        'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                        'language'   => (string) ($row->language ?? $lang),
                    ],                     
                ];
			}
            return $this->respond($data, 200);
		});
    }

    /**
     * Get a single category by id.
     *
     * Validates the id, resolves the current language, fetches the post
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the post does not exist
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
                        
            $row = $this->categories_model->get($id, ['language' => $lang]);
            if (empty($row)) {
                return $this->notFound('Slide not found.');
            }

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/icons/';
                }
            }

            $data = [
                'id'          => isset($row->id) ? (int)$row->id : null,
                'name'        => (string)($row->name ?? ''),
                'description' => (string)($row->description ?? ''),
                'link'        => (string)($row->link ?? ''),
                'folder'      => $folderUrl,
                'file_name'   => (string)($row->file_name ?? ''),
                'date'        => (string)($row->dateadded ?? ''), 
                'order'       => isset($row->order) ? (int)$row->order : 0,  
                'staffid'     => isset($row->staffid) ? (int)$row->staffid : null,
                'language'    => [
                    'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                    'language'   => (string)($row->language ?? $lang),
                ],         
            ];
            return $this->respond($data, 200);
        });    
    }      
    
    /**
     * Create a new category.
     *
     * Expects JSON body with fields:
     *  - name (string, required)
     *  - description (string, optional)
     *  - link (string|null, optional)  // normalized to null when empty; validated if present
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

            $data = [];
            if (array_key_exists('name', $formdata))        $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];

            // Insere via model
            $id = $this->categories_model->add($data);  
            
            if (!$id) {
                return $this->unprocessable('Failed to create category.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'category');                         
        });
    }   
    
    /**
     * Update a category.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - description (string)
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

            $name                   = $formdata['name'];
            $description            = $formdata['description'];  
            $staffid                = $formdata['staffid'];

            $languageid             = $formdata['languageid'];
            
            // array
            $data = [];
            if (array_key_exists('name', $formdata))        $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))  $data['languageid']  = (int)$formdata['languageid'];

            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->categories_model->update($data, $pid);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'category');      
            }

            return $this->unprocessable('Failed to update category.');        
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
                    $this->db->update(db_prefix() . 'categories', array(
                        'order' => (int)$pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'category');            
        });      
    }  
    
    /**
     * Delete a single category by ID.
     *
     * @param int|string $categoryid
     *
     * Requires 'categorys:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when category does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */      
    public function delete($categoryid)
    {
        $this->safe(function () use ($categoryid) {
            $id = (int)$categoryid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $category = $this->categories_model->get($id);
            if (empty($category)) {
                return $this->notFound('Category not found.');
            }                 

            $success = $this->categories_model->delete($id);   

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'category');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $id]);
        });         
    }   
    
    /**
     * Handles upload for category files
     * @param  mixed $categoryid post id
     * @return boolean
     */
	public function uploadPicture() 
	{
		$id = $this->input->post('id');

        $success = handle_category_file_uploads($id);

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
        $picture = $this->categories_model->get($id);     
        $success = $this->categories_model->delete_picture($id);
        
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