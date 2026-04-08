<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Services_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    public function getAll($where = array())
    {
        $columns = [
            db_prefix() .'services.id',
            db_prefix() .'services.folder',
            db_prefix() .'services.dateadded',
            db_prefix() .'services.staffid',
            db_prefix() .'services.file_name', 
            db_prefix() .'services.order',
            db_prefix() .'services_translation.name as name',
            db_prefix() .'services_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',        
        ];         
        $this->db->select($columns);

        $this->db->where($where);
        $this->db->join(db_prefix() . 'services_translation', db_prefix() . 'services.id = ' . db_prefix() . 'services_translation.serviceid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'services_translation.languageid', 'left');          

        $this->db->group_by(db_prefix() . 'services_translation.id');
        $this->db->order_by('order', 'asc');        

        return $this->db->get(db_prefix() . 'services')->result();           
    }

    /**
     * Get Services
     * @param  string $id    optional id
     * @param  array  $where services where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $columns = [
            db_prefix() .'services.id',
            db_prefix() .'services.folder',
            db_prefix() .'services.dateadded',
            db_prefix() .'services.staffid',
            db_prefix() .'services.file_name', 
            db_prefix() .'services.order',
            db_prefix() .'services_translation.name as name',
            db_prefix() .'services_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',            
        ];         
        $this->db->select($columns);
        $this->db->where($where);

        $this->db->join(db_prefix() . 'services_translation', db_prefix() . 'services.id = ' . db_prefix() . 'services_translation.serviceid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'services_translation.languageid', 'left');        

        $this->db->group_by(db_prefix() . 'services_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'services.id', $id);

            $service =  $this->db->get(db_prefix() . 'services')->row();
            $this->api_object_cache->set('services-' . $service->name, $service);

            return $service;
        }
        $this->db->order_by('order', 'asc');

        $services = $this->api_object_cache->get('services-data');

        if (!$services && !is_array($services)) {
            $services = $this->db->get(db_prefix() . 'services')->result();
            $this->api_object_cache->add('services-data', $services);
        }
    
        return $services; 
    }    
    
    /**
     * Add new services
     * @param array $data service $_POST data
     */    
	public function add($data)
	{
        $languages = $this->languages_model->get(null, ['active' => 1]);

        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');
        $data['description']    = nl2br($data['description']);

        $data = hooks()->apply_filters('before_add_slide', $data);

        $this->db->insert(db_prefix() . 'services', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            if(isset($languages)){
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'services_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'serviceid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_service', $insert_id);
            log_activity('New Service Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }   
    
    /**
     * Update service info
     * @param  array $data service data
     * @param  number $id   service id
     * @return boolean
     */    
    public function update($data, $id)
	{  
        $languageid = '';
        if (isset($data['languageid'])) {
            $languageid  = $data['languageid'];
            unset($data['languageid']);
        }  

        $affectedRows = 0;

        $data['description']    = nl2br($data['description']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'services', array(
            'name' => $data['name'],
        ));  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }          
        
        if(isset($languageid)){
            $this->db->where('serviceid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'services_translation', array(
                'name' => $data['name'],
                'description' => $data['description'],
            ));            
        }     
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }          

        if ($affectedRows > 0) {
            log_activity('Service Updated [ID:' . $id . ']', 'update');

            hooks()->do_action('after_update_service', $id);
            return true;
        }

        return false;
    }  
    
    public function delete($serviceid)
    {
        hooks()->do_action('before_service_deleted', $serviceid);

        $this->db->where('id', $serviceid);
        $this->db->delete(db_prefix() . 'services');      
        if ($this->db->affected_rows() > 0) {

            $this->db->where('serviceid', $serviceid);
            $this->db->delete(db_prefix() . 'services_translation');    
            
            return true;
        }  

        return false;
    }        
}