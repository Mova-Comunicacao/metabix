<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customers extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customers_model');
    }

    /**
     * Get all customers
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */         
    public function getAll()
    {
        $this->safe(function () {
		    $customers = $this->customers_model->getAll();
		
            // array|obj|null
            if (empty($customers)) {
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
			foreach($customers as $row){ 
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
     * Get a single customer by id.
     *
     * Validates the id, resolves the current language, fetches the customer
     * and returns a normalized payload.
     *
     * Responses:
     * - 200 OK with the item payload
     * - 404 Not Found when the customer does not exist
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

            $row = $this->customers_model->get($id);
            if (empty($row)) {
                return $this->notFound('Customer not found.');
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

            $data = [
                'id'            => isset($row->id) ? (int)$row->id : null,
                'name'          => (string)($row->name ?? ''),
                'description'   => (string)($row->description ?? ''),
                'external_link' => (string)($row->external_link ?? ''),
                'long_description'   => (string)($row->long_description ?? ''),
                'folder'        => $folderUrl, 
                'file_name'     => $fileImage, 
            ];
            return $this->respond($data, 200);
        });
    }      
    
    /**
     * Create a new customer.
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
            if (array_key_exists('staffid', $formdata))         $data['staffid']     = (int)$formdata['staffid'];


            $id = $this->customers_model->add($data);   
            if (!$id) {
                return $this->unprocessable('Failed to create customer.');
            }

            return $this->ok(['id' => (int)$id], 'create', 'customer');                          
        });
    }   
    
    /**
     * Update a customer.
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

            $success = $this->customers_model->update($data, $pid);
            if ($success || $success == 0) {
                return $this->ok($data, 'update', 'customer');      
            }

            return $this->unprocessable('Failed to update customer.');         
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
                    $this->db->update(db_prefix() . 'customers', array(
                        'order' => $pos
                    ));                                   				                                                       
                }    
            }         
            
            $summary = [
                'items'          => count($rows),
            ];            

            return $this->ok($summary, 'order', 'customer');            
        });      
    }  
    
    /**
     * Delete a single customer by ID.
     *
     * @param int|string $customerid
     *
     * Requires 'customers:delete' permission.
     *
     * Responses (handled via $this->safe):
     * - 200 OK ($this->ok) when deleted successfully.
     * - 404 Not Found ($this->notFound) when customer does not exist.
     * - 422 Unprocessable ($this->unprocessable) when deletion fails or id invalid.
     */        
    public function delete($customerid)
    {
        $this->safe(function () use ($customerid) {
            $id = (int)$customerid;

            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $customer = $this->customers_model->get($id);
            if (empty($customer)) {
                return $this->notFound('Customer not found.');
            }                

            $success = $this->customers_model->delete($id);   
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'customer');
            }
            
            // Fail
            return $this->unprocessable('Failed to delete customer.', ['id' => $id]);
        });             
    }   
    
    /**
     * Upload picture(s) for a customers.
     *
     * Expects multipart/form-data:
     *  - customerid (int, required)
     *  - file(s) in $_FILES (handled by handle_customers_picture_uploads)
     *
     * Responses (via $this->safe):
     *  - 200 OK: $this->ok(..., 'create', 'picture') on success
     *  - 422 Unprocessable: for validation or upload warnings (type === 'warning')
     *  - 400 Bad Request: for invalid payload or upload errors
     */
	public function uploadPicture() 
	{
        $this->safe(function () {
            $customerid      = $this->input->post('id');

            $result = handle_customers_picture_uploads($customerid);

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

            $success = $this->customers_model->delete_picture($pid);
        
            if ($success) {
                // 200 OK
                return $this->ok(['id' => $pid], 'delete', 'picture');
            }

            // Fail
            return $this->unprocessable('Failed to delete picture.', ['id' => $pid]);           
        });       
    }     
}