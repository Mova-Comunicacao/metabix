<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Posts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('posts_model');
    }

    /**
     * Get all posts with optional filters.
     *
     * Accepts GET params:
     * - category_id (int|null)
     * - search_string (string|null)
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */    
    public function getAll()
    {
        $this->safe(function () {
            $ts_filter_data = [];
            $category_id    = $this->input->get('category_id', true);
            $search_string  = $this->input->get('search_string', true);

            // Coerce/normalize input values
            $ts_filter_data['category_id']   = is_numeric($category_id) ? (int)$category_id : null;
            $ts_filter_data['search_string'] = is_string($search_string) ? trim($search_string) : null;

            $filter = ['filter' => $ts_filter_data];

            $lang = (string)($this->load_lang() ?? 'english');
            
            $posts = $this->posts_model->getAll($filter, ['language' => $lang]);
            
            // array|obj|null
            if (empty($posts)) {
                return $this->respond([], 200);
            }


            // helper staff
            $buildStaff = function($staffid) {
                $s = $this->staff_model->get($staffid);
                if (empty($s)) {
                    return [
                        'staffid'   => null,
                        'firstname' => null,
                        'lastname'  => null,
                        'fullname'  => null,
                    ];
                }
                return [
                    'staffid'   => (int)$s->staffid,
                    'firstname' => (string)$s->firstname,
                    'lastname'  => (string)$s->lastname,
                    'fullname'  => (string)$s->fullname
                ];
            };   

            // helper data ISO
            $toIso = function($dateStr) {
                $ts = strtotime((string)$dateStr);
                return $ts ? date('c', $ts) : (string)$dateStr;
            };   

            $data = [];
            foreach($posts as $row){ 
                
                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                    }
                }                      

                $category = $this->posts_model->get_categories($row->id, ['language' => $lang]);
                $categories = [];
                if(!empty($category)){
                    foreach($category as $c){
                        $categories[] = [
                            'name' => isset($c->name) ? (string)$c->name : '',
                        ];                
                    }
                } 

                $data[] = [
                    'id'                => isset($row->id) ? (int)$row->id : null,
                    'name'              => (string) ($row->name ?? ''),
                    'description'       => strip_tags(character_limiter((string) ($row->description ?? ''), 50)), 
                    'long_description'  => (string) ($row->long_description ?? ''), 
                    'folder'            => $folderUrl, 
                    'date'              => $toIso($row->dateadded ?? null), 
                    'order'             => isset($row->order) ? (int)$row->order : 0,
                    'active'            => isset($row->active) ? (int)$row->active : 0,
                    'external_link'     => (string)( $row->external_link ?? ''), 
                    'categories'        => $categories, 
                    'staff'             => $buildStaff($row->staffid),
                ];                
            }
		    return $this->respond($data, 200);
        });
    }
    
    /**
     * Get a single post by id.
     *
     * Validates the id, resolves the current language, fetches the post
     * and its categories in that language, and returns a normalized payload.
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
    public function getItemById($id = '')
    {
        $this->safe(function () use ($id) {
            $pid = (int) $id;

            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }        
            $lang = (string) ($this->load_lang() ?? 'english');

            $row = $this->posts_model->get($id, null, ['language' => $lang]);
            if (empty($row)) {
                return $this->notFound('Post not found.');
            }

            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                }
            }     

            $categoryRows = $this->posts_model->get_categories($id, ['language' => $lang]);
            $categories = [];
            if(!empty($categoryRows)){
                foreach($categoryRows as $c){
                    $categories[] = array(
                        'name' => isset($c->name) ? (string)$c->name : '',
                        'category_id' => isset($c->category_id) ? (int)$c->category_id : null,
                    );                    
                }
            } 

            $data = [
                'id'            => isset($row->id) ? (int)$row->id : null,
                'name'          => (string)($row->name ?? ''),
                'description'   => (string)($row->description ?? ''),
                'long_description' => (string)($row->long_description ?? ''),
                'folder'        => $folderUrl, 
                'active'        => isset($row->active) ? (int)$row->active : 0,
                'external_link' => (string)($row->external_link ?? ''),
                'staffid'     => isset($row->staffid) ? (int)$row->staffid : null,
                'categories' => $categories,
                'language'    => [
                    'languageid' => isset($row->languageid) ? (int)$row->languageid : null,
                    'language'   => (string)($row->language ?? $lang),
                ],          
            ];
            return $this->respond($data, 200);
        });      
    }   
    
    /**
     * Create a new post.
     *
     * Expects JSON body with fields:
     *  - name (string, required)
     *  - description (string, optional)
     *  - long_description (string, optional)
     *  - external_link (string|null, optional)  // normalized to null when empty; validated if present
     *  - categories (array<int>, optional)      // list of category IDs
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
        if (!has_permission('posts', '', 'create')) {
            access_denied('posts');
        }

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
            if (array_key_exists('long_description', $formdata)) $data['long_description'] = trim((string)$formdata['long_description']);
                    
            if (array_key_exists('external_link', $formdata))  {
                $ext = trim((string) $formdata['external_link']);
                if ($ext === '') {
                    $data['external_link'] = null;
                } else {
                    // basic URL validation; if fails, return 422
                    if (!filter_var($ext, FILTER_VALIDATE_URL)) {
                        return $this->unprocessable('Invalid external_link URL.', ['external_link' => 'Must be a valid URL or empty.']);
                    }
                    $data['external_link'] = $ext;
                }
            }  
            if (array_key_exists('categories', $formdata)) {
                // Ensure array<int>
                $cats = is_array($formdata['categories']) ? $formdata['categories'] : [];
                $data['categories'] = array_values(array_filter(
                    array_map('intval', $cats),
                    static fn($v) => $v > 0
                ));                
            }
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];

            // Insere post via model
            $id = $this->posts_model->add($data);   
            if (!$id) {
                return $this->unprocessable('Failed to create post.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'post');                         
        });
    }  
    
    /**
     * Update a post.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - description (string)
     *  - long_description (string)
     *  - external_link (string|null) - normalized to null when empty; validated if present
     *  - categories (array<int>)     - list of category IDs to attach
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
        if (!has_permission('posts', '', 'edit')) {
            access_denied('posts');
        }

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
            if (array_key_exists('long_description', $formdata)) $data['long_description'] = trim((string)$formdata['long_description']);
           
            if (array_key_exists('external_link', $formdata))  {
                $ext = trim((string) $formdata['external_link']);
                if ($ext === '') {
                    $data['external_link'] = null;
                } else {
                    // basic URL validation; if fails, return 422
                    if (!filter_var($ext, FILTER_VALIDATE_URL)) {
                        return $this->unprocessable('Invalid external_link URL.', ['external_link' => 'Must be a valid URL or empty.']);
                    }
                    $data['external_link'] = $ext;
                }
            }  
            if (array_key_exists('categories', $formdata)) {
                // Ensure array<int>
                $cats = is_array($formdata['categories']) ? $formdata['categories'] : [];
                $data['categories'] = array_values(array_filter(
                    array_map('intval', $cats),
                    static fn($v) => $v > 0
                ));                
            }

            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];
            if (array_key_exists('languageid', $formdata))      $data['languageid']  = (int)$formdata['languageid'];

            // If no updatable fields were provided
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->posts_model->update($data, $pid);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'post');      
            }

            return $this->unprocessable('Failed to update post.'); 
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
            $this->db->update(db_prefix() . 'posts', ['active' => (int)$active]);

            $affected = (int) $this->db->affected_rows();

            return $this->ok(['ids' => $ids, 'active' => (int)$active, 'affected' => $affected], 'update', 'post');
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
	public function delete($postid)
	{
        if (!has_permission('posts', '', 'delete')) {
            access_denied('posts');
        }

        $this->safe(function () use ($postid) {
            $id = (int)$postid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $post = $this->posts_model->get($id);
            if (empty($post)) {
                return $this->notFound('Post not found.');
            }            

            $success = $this->posts_model->delete($id);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'post');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $id]);
        });        
    }   
    
    /**
     * Bulk delete posts by IDs.
     *
     * Expects JSON body:
     * {
     *   "ids": number[]   // required, array of post IDs
     * }
     *
     * Responses:
     * - 200 OK with {type, message, details}:
     *   - type: "success" when all deleted
     *   - type: "warning" when partially deleted
     *   - type: "error" when none deleted
     *   details: { deleted: int[], failed: int[], not_found: int[] }
     * - 400 when payload is missing/invalid
     */    
    public function deleteItems()
    {
        if (!has_permission('posts', '', 'delete')) {
            access_denied('posts');
        }

		$this->safe(function () {
            $formdata = $this->readJson();

            if (empty($formdata) || !is_array($formdata) || !isset($formdata['ids'])) {
                return $this->unprocessable('Empty or invalid payload.');
            }            
            
            // Normalize ids: unique, int > 0
            $ids = $formdata['ids'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), static function ($v) {
                return $v > 0;
            }));
    
            if (empty($ids)) {
                return $this->badRequest('Nenhum ID válido informado.', ['ids' => $ids]);
            }
    
            $deleted   = [];
            $failed    = [];
            $notFound  = [];        
    
            foreach ($ids as $post_id) {
                // Optionally check existence (faster UX feedback)
                $exists = $this->posts_model->get($post_id);
                if (empty($exists)) {
                    $notFound[] = $post_id;
                    continue;
                }          
    
                $success = $this->posts_model->delete($post_id);
                if ($success) {
                    $deleted[] = $post_id;
                } else {
                    $failed[] = $post_id;
                }
            }

            $total = count($ids);
            $ok    = count($deleted);                
    
            $details = [
                'deleted'   => $deleted,
                'failed'    => $failed,
                'not_found' => $notFound,
                'total'     => $total,
            ];      
            
            if ($ok === $total) {
                return $this->ok($details, 'delete', 'post');
            }     
            
            if ($ok === 0) {
                if (count($notFound) === $total) {
                    return $this->notFound('No posts found to delete.', $details);
                }
                // All attempted but failed
                return $this->unprocessable('Failed to delete items.', $details);
            }
                
            // Partial success
            $details['status'] = 'partial';
            return $this->ok($details, 'delete', 'post');                
        });
    }  

    /**
     * Upload picture(s) for a post.
     *
     * Expects multipart/form-data:
     *  - post_id (int, required)
     *  - staffid (int, optional)
     *  - subject (string, optional)
     *  - description (string, optional)
     *  - file(s) in $_FILES (handled by handle_post_picture_uploads)
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
            $post_id        = $this->input->post('post_id');

            $subject        = $this->input->post('subject');
            $description    = $this->input->post('description');

            $result = handle_post_picture_uploads($post_id, $staffid, $subject, $description);

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
     * List pictures for a given post.
     *
     * Query:
     * - $post_id (int, required): the post ID
     *
     * Responses:
     * - 200 OK with an array of pictures (possibly empty) or an informational payload
     * - 422 Unprocessable when post_id is missing/invalid
     * - 500 on unexpected errors (handled by $this->safe())
     */    
	public function getPictures($post_id = '')
	{
        $this->safe(function () use ($post_id) {
            $id = (int) $post_id; 

            $data = $this->posts_model->get_pictures($id);

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

            $success = $this->posts_model->delete_picture($pid);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $pid]);           
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
                    $this->db->update(db_prefix() . 'posts_pictures', array(
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
    
	public function getActivity($id)
	{
        $data = $this->posts_model->get_activity($id, $limit = 10);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));          
    }    
}