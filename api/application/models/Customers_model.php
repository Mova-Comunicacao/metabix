<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customers_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * Get All
     *
     * @param array $where
     * @return void
     */    
    public function getAll()
    {
        $columns = [
            'id',
            'name',
            'description',
            'external_link',
            'file_name', 
            'folder',
            'dateadded',
            'staffid',
            'order',
        ];         
        $this->db->select($columns);
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'customers')->result();            
    }

    /**
     * Get customers
     * @param  string $id    optional id
     * @param  array  $where customers where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return  $this->db->get(db_prefix() . 'customers')->row();

        }
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'customers')->result();
    }  
    
    /**
     * Add new customer
     * @param array $data customer $_POST data
     */    
	public function add($data)
	{
        unset($data['null']);
        $data['name']               = trim($data['name'] ?? '');
        $data['description']        = nl2br($data['description'] ?? '', false);
        $data['external_link']      = trim($data['external_link'] ?? '');
        
        $data['dateadded']          = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_customer', $data);

        $this->db->insert(db_prefix() . 'customers', $data);
        $insert_id = $this->db->insert_id();    

        if ($insert_id) {
            hooks()->do_action('after_add_customer', $insert_id);
            log_activity('New Customers Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }  
    
    /**
     * Update customer info
     * @param  array $data customer data
     * @param  mixed $id   customer id
     * @return boolean
     */    
    public function update($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $data['name']               = trim($data['name'] ?? '');
        $data['description']        = nl2br($data['description'] ?? '', false);
        $data['external_link']      = trim($data['external_link'] ?? '');
       // $data['long_description']   = html_purify($data['long_description'], true);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'customers', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('customer Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_customer', $id);
            return true;
        }

        return false;
    }  
    
    /**
     * Permanently delete a customer and all related data.
     *
     * Deletes:
     * - files/pictures (via delete_picture)
     * - customers (row itself)
     *
     * Hooks:
     * - before_customer_deleted($customer_id)
     * - after_customer_deleted($customer_id)
     *
     * @param int $customer_id
     * @return bool True if the customer was deleted, false otherwise.
     */        
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        hooks()->do_action('before_customer_deleted', $id);

        $this->db->trans_begin();
        // Use the same path that uploads used (teams/icons)
        $pictureOk = $this->delete_picture($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'customers');   

        $rows = $this->db->affected_rows();

        // Commit or rollback
        if ($this->db->trans_status() === false || $rows <= 0 || !$pictureOk) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }      
    
    /**
     * Delete a single picture (file + DB row) for team.
     *
     * @param int  $id            Picture row id (teams.id)
     * @param string|null $customPath Optional override path (if you keep different stores)
     * @return bool               True if deleted, false otherwise
     */        
    public function delete_picture($id, $customPath = null)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
              
        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'customers')->row();
        // If there is no row or no file, just ensure DB is clean and return true
        if (!$file) {
            return false;
        }
        
        // Build base path
        $path = $customPath ?: rtrim(get_upload_path_by_type('customers'), '/\\');     

        // Current avatar file (if any)
        $fileName = !empty($file->file_name) ? (string)$file->file_name : null;        

        if ($fileName) {
            $fullPath = $path . '/' . $fileName;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Null the file reference in DB
        $this->db->where('id', (int)$id);
        $this->db->update(db_prefix() . 'customers', ['file_name' => null]);        

        hooks()->do_action('after_remove_customer_file', $file->id, $fileName);

        return true;
    }    
}