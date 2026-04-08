<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Social_model extends Api_Model
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
            'link', 
            'active', 
            'dateadded',
            'staffid',
            'order',
        ];         
        $this->db->select($columns);
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'social')->result();            
    }

    /**
     * Get social
     * @param  string $id    optional id
     * @param  array  $where perform where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'social')->row();
        }
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'social')->result();
    }      

    /**
     * Add new social
     * @param array $data social $_POST data
     */    
	public function add($data)
	{
        unset($data['null']);
        $data['name']               = trim($data['name'] ?? '');
        $data['link']               = trim($data['link'] ?? '');
        $data['dateadded']          = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_social', $data);

        $this->db->insert(db_prefix() . 'social', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            hooks()->do_action('after_add_social', $insert_id);
            log_activity('New Social Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }  
    
    /**
     * Update social info
     * @param  array $data social data
     * @param  mixed $id   social id
     * @return boolean
     */    
    public function update($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $data['name']               = trim($data['name'] ?? '');
        $data['link']               = trim($data['link'] ?? '');

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'social', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Social Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_social', $id);
            return true;
        }

        return false;
    }  
    
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
                
        hooks()->do_action('before_social_deleted', $id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'social');   
        
        return true;
    }        
}