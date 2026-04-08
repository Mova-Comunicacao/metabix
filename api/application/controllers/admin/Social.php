<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Social extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('social_model');
    }

    /**
     * Get all socials
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */          
    public function getAll()
    {
        $this->safe(function () {
		    $socials = $this->social_model->getAll();
		
            // array|obj|null
            if (empty($socials)) {
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
			foreach($socials as $row){ 
				$data[] = [
                    'id'                => isset($row->id) ? (int)$row->id : null,
                    'name'              => (string) ($row->name ?? ''),
                    'link'              => (string) ($row->link ?? ''),
                    'active'            => isset($row->active) ? (int)$row->active : 0,
					'date'              => $toIso($row->dateadded ?? null), 
					'order'             => isset($row->order) ? (int)$row->order : 0,
					'staff'             => $buildStaff($row->staffid), 
                ];
			}
            return $this->respond($data, 200);
		});    
    }

    /**
     * Get a single social by id.
     *
     * Validates the id, resolves the current language, fetches the social
     * and returns a normalized payload.
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the social does not exist
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

            $row = $this->social_model->get($id);
            if (empty($row)) {
                return $this->notFound('Social not found.');
            }    

            $data = array(
                'id'                => isset($row->id) ? (int)$row->id : null,
                'name'              => (string) ($row->name ?? ''),
                'link'              => (string) ($row->link ?? ''),
                'active'            => isset($row->active) ? (int)$row->active : 0,
            );
            return $this->respond($data, 200);
        });       
    }   
    
    
    /**
     * Create a new social.
     *
     * Expects JSON body with fields:
     *  - name (string, required)
     *  - link (string, required)
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
            if (array_key_exists('link', $formdata))            $data['link'] = trim((string)$formdata['link']);
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];


            $id = $this->social_model->add($data);   
            if (!$id) {
                return $this->unprocessable('Failed to create social.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'social');                          
        });
    }   
    
    /**
     * Update a social.
     *
     * Expects JSON body with any of the fields:
     *  - name (string)
     *  - link (string)
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
            if (array_key_exists('link', $formdata))            $data['link'] = trim((string)$formdata['link']);

            $success = $this->social_model->update($data, $id);
            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'social');      
            }

            return $this->unprocessable('Failed to update social.');         
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
                    $this->db->update(db_prefix() . 'social', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'social');            
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
            $this->db->update(db_prefix() . 'social', ['active' => (int)$active]);

            $affected = (int) $this->db->affected_rows();

            return $this->ok(['ids' => $ids, 'active' => (int)$active, 'affected' => $affected], 'update', 'social');
        });      
    } 

    /**
     * Delete a single social by ID.
     *
     * @param int|string $socialid
     *
     * Requires 'socials:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when social does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */       
    public function delete($socialid)
    {
        $this->safe(function () use ($socialid) {
            $id = (int)$socialid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $social = $this->social_model->get($id);
            if (empty($social)) {
                return $this->notFound('Social not found.');
            }     

            $success = $this->social_model->delete($id);   
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'social');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete social.', ['id' => $id]);
        });        
    }      
}