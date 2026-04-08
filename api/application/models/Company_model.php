<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Company_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * @param  integer ID (optional)
     * @return mixed
     * Get company object based on passed id if not passed id return array of all currencies
     */
    public function get($where = array())
    {

        $columns = [
            db_prefix() .'company.name',
            db_prefix() .'company.description',
            db_prefix() .'company.folder',
            db_prefix() .'company.staffid',
            db_prefix() .'company.long_description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language', 
            'dateupdated',
        ];         
        $this->db->select($columns);

        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'company.languageid', 'left'); 

        if (isset($where['language']) && $where['language'] !== '') {
            $this->db->where(db_prefix() . 'languages.language', (string)$where['language']);
            return $this->db->get(db_prefix() . 'company')->row();
        }

        if (!empty($where)) {
            $this->db->where($where);
        }   

        return  $this->db->get(db_prefix() . 'company')->result();
    }  

    /**
     * Update company info
     * @param  array $data company data
     * @return boolean
     */    
    public function update($data)
	{  
        $data['description']         = nl2br($data['description'] ?? '');
        $data['long_description']    = html_purify($data['long_description'] ?? '', true);

        $data['dateupdated']         = date('Y-m-d H:i:s');

        $this->db->where('languageid', $data['languageid']);
        $this->db->update(db_prefix() . 'company', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Company Updated', 'update');
            hooks()->do_action('after_update_company', $data);

            return true;
        }

        return false;
    }  
    
    public function get_items($id = '', $where = array())
    {

        $columns = [
            db_prefix() .'company_items.id',
            db_prefix() .'company_items.dateadded',
            db_prefix() .'company_items.staffid',
            db_prefix() .'company_items.order',
            db_prefix() .'company_items_translation.name as name',
            db_prefix() .'company_items_translation.description as description',            
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language', 
        ];           
        $this->db->select($columns);

        $this->db->where($where);
        $this->db->join(db_prefix() . 'company_items_translation', db_prefix() . 'company_items.id = ' . db_prefix() . 'company_items_translation.itemid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'company_items_translation.languageid', 'left');  

        $this->db->group_by(db_prefix() . 'company_items_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'company_items.id', $id);

            return $this->db->get(db_prefix() . 'company_items')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'company_items')->result();        
    }

    public function add_items($data)
    {
        $languages = $this->languages_model->get(null, ['active' => 1]);

        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');
        $data['description']    = nl2br($data['description'] ?? '');

        $data = hooks()->apply_filters('before_add_items', $data);

        $this->db->insert(db_prefix() . 'company_items', $data);
        $insert_id = $this->db->insert_id();  

        if ($insert_id) {
            if(isset($languages)){
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'company_items_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'itemid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_items', $insert_id);
            log_activity('New Items Company Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;        
    }

    public function update_items($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $affectedRows = 0;

        // Se houver tradução específica
        $languageid = $data['languageid'] ?? null;
        unset($data['languageid']);

        $data['description']    = nl2br($data['description'] ?? '');

        $updateMain = [
            'link' => $data['link'] ?? null,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'company_items', $updateMain);  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }          
        
        if($languageid){
            $updateTranslation = [
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
            ];

            $this->db->where('itemid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'company_items_translation', $updateTranslation);            
        }   

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }  

        if ($affectedRows > 0) {
            log_activity('Company Items Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_company_items', $id);

            return true;
        }

        return false;
    }  
    
    public function delete_item($id)
    {
        hooks()->do_action('before_company_items_deleted', $id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'company_items');    
        if ($this->db->affected_rows() > 0) {

            $this->db->where('itemid', $id);
            $this->db->delete(db_prefix() . 'company_items_translation'); 
            
            return true;
        }     

        return false;
    }       
    
    public function upload_picture($data)
	{  
		$this->db->insert(db_prefix() . 'company_pictures', $data);  
        $insert_id = $this->db->insert_id(); 
        if ($insert_id) {
        
            return $insert_id;
        }
        
        return false;        
    } 

    public function get_seals($id = '', $where = array())
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'company_seals')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'company_seals')->result();
    }      

    public function get_pictures($id = '', $limit = false,  $where = array())
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'company_pictures')->row();
        }
        if ($limit) {
            $this->db->limit(3);
        }
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'company_pictures')->result();
    }  
    
    public function get_picture($id, $slideid = false)
    {
        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'company_pictures')->row();

        if ($file && $slideid) {
            if ($file->slideid != $slideid) {
                return false;
            }
        }

        return $file;
    }     
    
    public function delete_picture($id)
    {
        hooks()->do_action('before_remove_company_picture', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'company_pictures')->row();
        if ($file) {
            if (empty($file->external)) {
                $path     = get_upload_path_by_type('company');
                $fullPath = $path . $file->file_name;     
                if (file_exists($fullPath)) {
                    @unlink($fullPath);                 
                }           
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'company_pictures');  

        }  
        
        return true;
    }    
}