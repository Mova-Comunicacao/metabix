<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Carousel extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('carousel_model');
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

            $carousel = $this->carousel_model->getAll();

            // array|obj|null
            if (empty($carousel)) {
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
            foreach ($carousel as $row) {  
                $fileName = (string)($row->file_name ?? '');

                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                    }
                }      
                
                // URLs seguras (encode por arquivo)
                $fileUrl = $folderUrl && $fileName !== '' ? $folderUrl . rawurlencode($fileName) : null;
     
                // THUMB:
                // Se for vídeo com link externo → gera thumb via helper video_image()
                // Senão, usa thumb_arquivo padrão
                $thumbUrl = null;
                if (!empty($row->external)) {
                    $thumbUrl = video_image($row->external);
                } elseif ($folderUrl && $fileName !== '') {
                    $thumbUrl = $folderUrl . rawurlencode($fileName);
                }

                $data[] = [
                    'id'          => isset($row->id) ? (int)$row->id : null,
                    'type'        => (string)($row->type ?? ''),
                    'file_name'   => $fileName,
                    'name'        => (string) ($row->subject ?? ''),
                    'description' => strip_tags(character_limiter((string) ($row->description ?? ''), 50)),
                    'date'        => $toIso($row->dateadded ?? null),
                    'url'         => $fileUrl ?: (string)($row->external ?? ''),
                    'thumb'       => $thumbUrl,
                    'active'      => isset($row->visible_to_customer) ? (int)$row->visible_to_customer : 0,
                    'staff'       => $buildStaff($row->staffid ?? null),
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

            $row = $this->carousel_model->get($pid);
            if (empty($row)) {
                return $this->notFound('Carousel not found.');
            }

            $fileName = (string)($row->file_name ?? '');
            // helper folder URL segura
            $folderUrl = null;
            if (!empty($row->folder)) {
                $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                }
            }      
            
            // URLs seguras (encode por arquivo)
            $fileUrl = $folderUrl && $fileName !== '' ? $folderUrl . rawurlencode($fileName) : null;

            $data = [
                'id'          => isset($row->id) ? (int)$row->id : null,
                'type'        => (string)($row->type ?? ''),
                'name'        => (string)($row->subject ?? ''),
                'file_name'   => $fileName,
                'url'         => $fileUrl ?: (string)($row->external ?? ''),
                'description' => (string)($row->description ?? ''),
                'date'        => (string)($row->dateadded ?? ''),
                'active'      => isset($row->visible_to_customer) ? (int)$row->visible_to_customer : 0,
                'staffid'     => isset($row->staffid) ? (int)$row->staffid : null,
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
            if (array_key_exists('name', $formdata))        $data['subject']     = trim((string)$formdata['name']);
            if (array_key_exists('type', $formdata))        $data['type']        = trim((string)$formdata['type']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('external', $formdata))    $data['external']    = (string)$formdata['external'];
            if (array_key_exists('active', $formdata))      $data['visible_to_customer']     = (int)$formdata['active'];
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];

            // Insere via model
            $id = $this->carousel_model->add($data);   
    
            if (!$id) {
                return $this->unprocessable('Failed to create carousel.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'carousel');  
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

            // array
            $data = [];
            if (array_key_exists('name', $formdata))        $data['subject']     = trim((string)$formdata['name']);
            if (array_key_exists('type', $formdata))        $data['type']        = trim((string)$formdata['type']);
            if (array_key_exists('description', $formdata)) $data['description'] = trim((string)$formdata['description']);
            if (array_key_exists('external', $formdata))    $data['external']    = (string)$formdata['external'];
            if (array_key_exists('active', $formdata))      $data['visible_to_customer']     = (int)$formdata['active'];
            if (array_key_exists('staffid', $formdata))     $data['staffid']     = (int)$formdata['staffid'];
            
            if (empty($data)) {
                return $this->unprocessable('No fields to update.');
            }

            $success = $this->carousel_model->update($data, $pid);

            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'carousel');      
            }

            return $this->unprocessable('Failed to update carousel.');           
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
                    $this->db->update(db_prefix() . 'carousel', array(
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
    
    /**
     * Delete a single carousel by ID.
     *
     * @param int|string $carouselid
     *
     * Requires 'carousels:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when carousel does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */    
    public function delete($carouselid)
    {
        $this->safe(function () use ($carouselid) {
            $id = (int)$carouselid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $slide = $this->carousel_model->get($id);
            if (empty($slide)) {
                return $this->notFound('Slide not found.');
            }            

            $success = $this->carousel_model->delete($id);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'slide');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $id]);
        }); 
    }    

    /**
     * Handles upload for carousel files
     * @param  mixed $id post id
     * @return boolean
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $carousel_id        = $this->input->post('id');

            $result = handle_carousel_picture_uploads($carousel_id);

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

            $success = $this->carousel_model->delete_picture($pid);

            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.', ['id' => $pid]);           
        });
    }
}