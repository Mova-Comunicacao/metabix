<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Slides extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('slides_model');
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

            $slides = $this->slides_model->getAll(['language' => $lang]);

            // array|obj|null
            if (empty($slides)) {
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
            foreach ($slides as $row) {         
                $shortDesc = strip_tags(character_limiter((string)($row->description), 25));

                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                    }
                }      
                
                $data[] = [
                    'id'          => isset($row->id) ? (int)$row->id : null,
                    'name'        => (string) ($row->name ?? ''),
                    'description' => sanitize_html_input($shortDesc),
                    'folder'      => $folderUrl,
                    'link'        => (string) ($row->link ?? ''),
                    'date'        => $toIso($row->dateadded ?? null),
                    'active'      => isset($row->active) ? (int)$row->active : 0,
                    'staff'       => $buildStaff($row->staffid),
                ];            
            }        
            return $this->respond($data, 200);
        });
    }   

    /**
     * Get a single slide by id.
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

            $row = $this->slides_model->get($pid, ['language' => $lang]);
            if (empty($row)) {
                return $this->notFound('Slide not found.');
            }

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                }
            }

            $data = [
                'id'          => isset($row->id) ? (int)$row->id : null,
                'name'        => (string)($row->name ?? ''),
                'description' => (string)($row->description ?? ''),
                'link'        => (string)($row->link ?? ''),
                'folder'      => $folderUrl,
                'mask'        => isset($row->mask) ? (int)$row->mask : 0,
                'date'        => (string)($row->dateadded ?? ''),
                'active'      => isset($row->active) ? (int)$row->active : 0,
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
     * Create a new slide.
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
            if (array_key_exists('link', $formdata))        $data['link']        = trim((string)$formdata['link']);
            if (array_key_exists('mask', $formdata))        $data['mask']        = (int)$formdata['mask'];
            if (array_key_exists('active', $formdata))      $data['active']      = (int)$formdata['active'];
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];

            // Insere via model
            $id = $this->slides_model->add($data);   
    
            if (!$id) {
                return $this->unprocessable('Failed to create slide.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'slide');  
        });                       
    }    

    /**
     * Update a slide.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - description (string)
     *  - link (string|null) - normalized to null when empty; validated if present
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
            if (array_key_exists('name', $formdata))        $data['name']        = trim((string)$formdata['name']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('link', $formdata))        $data['link']        = trim((string)$formdata['link']);
            if (array_key_exists('mask', $formdata))        $data['mask']        = (int)$formdata['mask'];
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))  $data['languageid']  = (int)$formdata['languageid'];
            
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->slides_model->update($data, $pid);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'slide');      
            }

            return $this->unprocessable('Failed to update slide.');           
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
                    $this->db->update(db_prefix() . 'slides', array(
                        'order' => (int)$pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'slide');            
        });   
    }     

    public function updateStatus()
    {
        $this->safe(function () {
            $formdata = $this->readJson();

            // valida payload
            $idsRaw  = $formdata['ids']   ?? null;
            $active  = $formdata['active'] ?? null;
            
            if (!is_array($idsRaw)) {
                return $this->badRequest('Invalid payload: ids must be an array.');
            }
            if (!in_array((int)$active, [0, 1], true)) {
                return $this->unprocessable('Invalid active flag.', ['active' => 'Must be 0 or 1']);
            }     

            $ids = array_values(array_unique(array_filter(array_map('intval', $idsRaw), fn($v) => $v > 0)));
            if (empty($ids)) {
                return $this->unprocessable('No valid ids provided.', ['ids' => 'Provide at least one valid id']);
            }            
            
            $this->db->where_in('id', $ids);
            $this->db->update(db_prefix() . 'slides', ['active' => (int)$active]);

            $affected = (int) $this->db->affected_rows();

            return $this->ok(['ids' => $ids, 'active' => (int)$active, 'affected' => $affected], 'update', 'slides');
        });
    }    
    
    /**
     * Delete a single post by ID.
     *
     * @param int|string $postid
     *
     * Requires 'posts:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when post does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */    
    public function delete($slideid)
    {
        $this->safe(function () use ($slideid) {
            $id = (int)$slideid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $slide = $this->slides_model->get($id);
            if (empty($slide)) {
                return $this->notFound('Slide not found.');
            }            

            $success = $this->slides_model->delete($id);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'slide');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $id]);
        }); 
    }    

    /**
     * Handles upload for slide files
     * @param  mixed $slideid post id
     * @return boolean
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $staffid        = $this->input->post('staffid');
            $slide_id        = $this->input->post('slideid');

            $subject        = $this->input->post('subject') ?? '';
            $description    = $this->input->post('description') ?? '';

            $result = handle_slide_picture_uploads($slide_id, $staffid, $subject, $description);

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

    /**
     * List pictures for a given slide.
     *
     * Query:
     * - $slide_id (int, required): the slide ID
     *
     * Responses:
     * - 200 OK with an array of pictures (possibly empty) or an informational payload
     * - 422 Unprocessable when slide_id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     */    
	public function getPictures($slide_id = '')
	{
        $this->safe(function () use ($slide_id) {
            $id = $slide_id ?? '';        

            $data = $this->slides_model->get_pictures($id);

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

            $success = $this->slides_model->delete_picture($pid);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $pid]);           
        });
    }
}