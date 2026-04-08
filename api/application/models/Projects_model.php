<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Projects_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    public function getAll($filter = null, $where = array())
    {
        unset($filter['null']);
        if(is_array($filter)) {
            $filter_category = ($filter['filter']['category_id'] == 0) ? db_prefix() . 'projects_categories.category_id = '. db_prefix() . 'projects_categories.category_id' : db_prefix() . 'projects_categories.category_id = ' .  $filter['filter']['category_id'];
        }

        $columns = [
            db_prefix() .'projects.id as id',
            db_prefix() .'projects.name as name',
            db_prefix() .'projects.dateadded as dateadded',
            db_prefix() .'projects.active as active',
            db_prefix() .'projects.order as order',
            db_prefix() .'projects.staffid as staffid',
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',
            db_prefix() .'projects_translation.description as description',
            db_prefix() .'projects_translation.long_description as long_description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language as language',    
        ];         
        $this->db->select($columns);

        $this->db->where($where);
        $this->db->join(db_prefix() . 'projects_translation', db_prefix() . 'projects.id = ' . db_prefix() . 'projects_translation.projectid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'projects_translation.languageid', 'left');  
        $this->db->join(db_prefix() . 'projects_categories', db_prefix() . 'projects_categories.project_id = ' . db_prefix() . 'projects.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'projects_categories.category_id', 'left'); 
       
        $this->db->where($filter_category);
        
        $this->db->group_by(db_prefix() . 'projects_translation.id');
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'projects')->result();            
    }

    /**
     * Get Product
     * @param  string $slug  optional slug
     * @param  array  $where perform where
     * @return mixed
     */
    public function get($id = '', $filter = null, $where = array())
    {
        unset($filter['null']);
        if(is_array($filter)) {
            $filter_category = ($filter['filter']['category_id'] == 0) ? db_prefix() . 'projects_categories.category_id = '. db_prefix() . 'projects_categories.category_id' : db_prefix() . 'projects_categories.category_id = ' .  $filter['filter']['category_id'];
        }

        $columns = [
            db_prefix() .'projects.id as id',
            db_prefix() .'projects.name as name',
            db_prefix() .'projects.dateadded as dateadded',
            db_prefix() .'projects.active as active',
            db_prefix() .'projects.order as order',
            db_prefix() .'projects.staffid',
            db_prefix() .'projects.clientid',
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',
            db_prefix() .'projects_translation.description as description',
            db_prefix() .'projects_translation.long_description as long_description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',  
            'city',             
            'year',             
            'slug',
        ];   
        $this->db->select($columns);
        
        $this->db->where($where);
        $this->db->join(db_prefix() . 'projects_translation', db_prefix() . 'projects.id = ' . db_prefix() . 'projects_translation.projectid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'projects_translation.languageid', 'left');  
        $this->db->join(db_prefix() . 'projects_categories', db_prefix() . 'projects_categories.project_id = ' . db_prefix() . 'projects.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'projects_categories.category_id', 'left'); 

        $this->db->group_by(db_prefix() . 'projects_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'projects.id', $id);
            $project = $this->db->get(db_prefix() . 'projects')->row();
            if ($project) {
                $project            = hooks()->apply_filters('project_get', $project);
                $GLOBALS['project'] = $project;

                return $project;                
            }

            return null;
        }
        
        $this->db->where($filter_category);
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'projects')->result();
    }  
    
    /**
     * Get Product
     * @param  string $slug    optional slug
     * @param  array  $where perform where
     * @return mixed
     */
    public function slug($slug = '', $where = array())
    {
        $columns = [
            db_prefix() .'projects.id as id',
            db_prefix() .'projects.name as name',
            db_prefix() .'projects.dateadded as dateadded',
            db_prefix() .'projects.active as active',
            db_prefix() .'projects.order as order',
            db_prefix() .'projects.staffid as staffid',
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',
            db_prefix() .'projects_translation.description as description',
            db_prefix() .'projects_translation.long_description as long_description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',  
            'city',             
            'year',                             
            'slug',
        ];  
        $this->db->select($columns);
        $this->db->where($where);

        $this->db->join(db_prefix() . 'projects_translation', db_prefix() . 'projects.id = ' . db_prefix() . 'projects_translation.projectid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'projects_translation.languageid', 'left');  
        $this->db->join(db_prefix() . 'projects_categories', db_prefix() . 'projects_categories.project_id = ' . db_prefix() . 'projects.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'projects_categories.category_id', 'left'); 

        $this->db->group_by(db_prefix() . 'projects_translation.id');

        if (!empty($slug)) {
            $this->db->where(db_prefix() . 'projects.slug', $slug);
            $project = $this->db->get(db_prefix() . 'projects')->result();
            if ($project) {
                $project            = hooks()->apply_filters('project_get', $project);
                $GLOBALS['project'] = $project;

                return $project;                
            }
        }
        
        return null;
    } 

    /**
     * Add new projects
     * @param array $data projects $_POST data
     */    
	public function add($data)
	{
        $languages = $this->languages_model->get(null, ['active' => 1]);

        unset($data['null']);
        $data['dateadded']          = date('Y-m-d H:i:s');
        $data['description']        = nl2br($data['description']);
        $data['long_description']   = html_purify($data['long_description'], true);
        $data['slug']               = slug_it($data['name']);

        $project_categories = '';
        if (isset($data['categories'])) {
            $project_categories = $data['categories'];
            unset($data['categories']);
        }    

        $staff_id = '';
        if (isset($data['staffid'])) {
            $staff_id = $data['staffid'];
        }            

        $data = hooks()->apply_filters('before_add_project', $data);

        $this->db->insert(db_prefix() . 'projects', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            if(isset($languages)){
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'projects_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'long_description' => $data['long_description'],
                        'languageid' => $l->languageid,
                        'projectid' => $insert_id,
                    ));            
                }
            }

            if(isset($project_categories)){
                foreach ($project_categories as $category_id) {
                    $this->db->insert(db_prefix() . 'projects_categories', array(
                        'project_id' => $insert_id,
                        'category_id' => $category_id
                        )
                    ); 
                }             
            }  
             
            $this->log_activity($insert_id, $staff_id, '', 'project_activity_created');

            hooks()->do_action('after_add_project', $insert_id);
            log_activity('New Project Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }   

    /**
     * Update project info
     * @param  array $data project data
     * @param  mixed $id   project id
     * @return boolean
     */    
    public function update($data, $id)
	{  
        $languageid = '';
        if (isset($data['languageid'])) {
            $languageid  = $data['languageid'];
            unset($data['languageid']);
        }   

        $data['description']        = nl2br($data['description']);
        $data['long_description']   = html_purify($data['long_description'], true);
        $data['slug']               = slug_it($data['slug']);
        
        $original_project = $this->get($id);
        $affectedRows = 0;
        
        $project_categories = '';
        if (isset($data['categories'])) {
            $project_categories = $data['categories'];
            unset($data['categories']);
        }   
        
        $staff_id = '';
        if (isset($data['staffid'])) {
            $staff_id = $data['staffid'];
            unset($data['staffid']);
        }           
                

        if (is_array($project_categories)) {
            $this->db->where('project_id', $id);
            $this->db->delete(db_prefix() . 'projects_categories');  

            foreach ($project_categories as $category_id) {
                $this->db->insert(db_prefix() . 'projects_categories', array(
                    'project_id' => $id,
                    'category_id' => $category_id
                    )
                );   
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }                    
            }          
        }   

        $data = hooks()->apply_filters('before_update_project', $data, $id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'projects', array(
            'city' => $data['city'],
            'year' => $data['year'],
            'slug' => $data['slug'],
        ));  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }  

        if(isset($languageid)){
            $this->db->where('projectid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'projects_translation', array(
                'name' => $data['name'],
                'long_description' => $data['long_description'],
                'description' => $data['description'],
            ));            
        }     
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }                
        
        if ($affectedRows > 0) {
            $this->log_activity($id, $staff_id, '', 'project_activity_updated');
            log_activity('Project Updated [ID:' . $id . ']', 'update');

            hooks()->do_action('after_update_project', $id);

            return true;            
        }

        return false;
    }    
    
    public function delete($project_id)
    {
        hooks()->do_action('before_project_deleted', $project_id);

        $project_name = get_project_name_by_id($project_id);

        $this->db->where('id', $project_id);
        $this->db->delete(db_prefix() . 'projects');      
        if ($this->db->affected_rows() > 0) {
            // Delete the custom field values
            $this->db->where('relid', $project_id);
            $this->db->where('fieldto', 'projects');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('project_id', $project_id);
            $this->db->delete(db_prefix() . 'projects_categories');

            $this->db->where('projectid', $project_id);
            $this->db->delete(db_prefix() . 'projects_translation');            

            $files = $this->get_pictures($project_id);
            foreach ($files as $file) {
                $this->delete_picture($file->id);
            }  

            $maps = $this->get_maps($project_id);
            foreach ($maps as $map) {
                $this->delete_map($map->id);
            }              
            
            log_activity('Project Deleted [ID: ' . $project_id . ', Name: ' . $project_name . ']', 'deleted');
            
            hooks()->do_action('after_project_deleted', $project_id);   
            
            return true;
        }  

        return false;
    } 


    public function get_categories($project_id = '', $where = array())
    {
        $columns = [
            db_prefix() .'categories.id',
            db_prefix() .'categories.order',
            db_prefix() .'projects_categories.category_id',
            db_prefix() .'projects_categories.project_id',
            db_prefix() .'categories_translation.name as name',
            db_prefix() .'categories_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',
            db_prefix() .'languages.language as language',            
        ];         
        $this->db->select($columns);
        $this->db->where($where);
        $this->db->where(db_prefix() . 'projects_categories.project_id = ' . db_prefix() . 'projects_categories.project_id');

        $this->db->join(db_prefix() . 'projects_categories', db_prefix() . 'projects_categories.category_id = ' . db_prefix() . 'categories.id', 'left'); 
        $this->db->join(db_prefix() . 'categories_translation', db_prefix() . 'categories.id = ' . db_prefix() . 'categories_translation.categoryid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'categories_translation.languageid', 'left');   
        $this->db->join(db_prefix() . 'projects',  db_prefix() . 'projects.id = ' . db_prefix() . 'projects_categories.project_id', 'left'); 

        $this->db->group_by(db_prefix() . 'categories_translation.id');

        if (is_numeric($project_id)) {
            $this->db->where(db_prefix() . 'projects_categories.project_id', $project_id);
        }   
        
        $categories = $this->db->get(db_prefix() . 'categories')->result();
       
        if ($categories) {
            return $categories;
        }

        return false;        
    }


    public function get_pictures($project_id = '', $where = array())
    {
        $this->db->where($where);
        $this->db->order_by('order', 'asc');
        
        if (is_numeric($project_id)) {
            $this->db->where('project_id', $project_id);

            return $this->db->get(db_prefix() . 'projects_pictures')->result();
        }

        return $this->db->get(db_prefix() . 'projects_pictures')->result();
    }

    public function get_picture($id, $project_id = false)
    {
        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'projects_pictures')->row();

        if ($file && $project_id) {
            if ($file->project_id != $project_id) {
                return false;
            }
        }

        return $file;
    }  

    public function delete_picture($id, $log_activity = true)
    {
        hooks()->do_action('before_remove_project_file', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'projects_pictures')->row();
        if ($file) {
            if (empty($file->external)) {
                $path     = get_upload_path_by_type('projects') . $file->project_id . '/';
                $fullPath = $path . $file->file_name;     
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                    $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                    $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $thumbPath = $path . $fname . '_thumb.' . $fext;

                    if (file_exists($thumbPath)) {
                        @unlink($thumbPath);
                    }                    
                }           
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'projects_pictures');  
            if ($log_activity) {
                $this->log_activity($file->project_id, '', '', 'project_activity_project_file_removed', $file->originalFilename, $file->visible_to_customer);
            }

            if (is_dir(get_upload_path_by_type('projects') . $file->project_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('projects') . $file->project_id);
                if (count($other_attachments) == 0) {
                    delete_dir(get_upload_path_by_type('projects') . $file->project_id);
                }
            }

            return true;
        }  
        
        return false;
    }       

    public function get_maps($project_id = '', $where = array())
    {
        $this->db->where($where);
        $this->db->order_by('order', 'asc');
        
        if (is_numeric($project_id)) {
            $this->db->where('project_id', $project_id);

            return $this->db->get(db_prefix() . 'projects_maps')->result();
        }

        return $this->db->get(db_prefix() . 'projects_maps')->result();
    }   
    
    public function get_map($id, $project_id = false)
    {
        $this->db->where('project_id', $id);
        $file = $this->db->get(db_prefix() . 'projects_maps')->row();

        if ($file && $project_id) {
            if ($file->project_id != $project_id) {
                return false;
            }
        }

        return $file;
    }      
    
    public function delete_map($id, $log_activity = true)
    {
        hooks()->do_action('before_remove_project_file', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'projects_maps')->row();
        if ($file) {
            if (empty($file->external)) {
                $path     = get_upload_path_by_type('projects') . $file->project_id . '/';
                $fullPath = $path . $file->file_name;     
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                    $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                    $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $thumbPath = $path . $fname . '_thumb.' . $fext;

                    if (file_exists($thumbPath)) {
                        @unlink($thumbPath);
                    }                    
                }           
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'projects_maps');  
            if ($log_activity) {
                $this->log_activity($file->project_id, '', '', 'project_activity_project_file_removed', $file->originalFilename, $file->visible_to_customer);
            }

            if (is_dir(get_upload_path_by_type('projects') . $file->project_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('projects') . $file->project_id);
                if (count($other_attachments) == 0) {
                    delete_dir(get_upload_path_by_type('projects') . $file->project_id);
                }
            }

            return true;
        }  
        
        return false;
    }    

    /**
     * Save Wish product
     * @param  mixed $id id
     * @param  array $data product data
     * @return boolean
     */
    public function saveWishList($id, $data)
    {
        if ($this->user_wish_project($id, $data)) {
            return true;
        }

        $this->db->insert(db_prefix() . 'products_whish', [
            'projectid' => $id,
            'userid'    => $data['userid'],
            'dateliked' => date('Y-m-d H:i:s'),
        ]);
        $likeid = $this->db->insert_id();
        if ($likeid) {
            $project = $this->get_project($id);
            /*
            if ($post->creator != $user_id) {
                $notified = add_notification([
                    'description'     => 'not_liked_your_post',
                    'touserid'        => $post->creator,
                    'link'            => '#projectid=' . $id,
                    'additional_data' => serialize([
                        get_staff_full_name($user_id),
                        strip_tags(mb_substr($post->content, 0, 50)),
                    ]),
                ]);
                if ($notified) {
                    pusher_trigger_notification([$post->creator]);
                }
            }
            */

            return true;
        }

        return false;
    }

    /**
     * Check if current user liked product
     * @param  mixed $id product id
     * @param  array $data product data
     * @return mixed
     */
    public function user_wish_project($id, $data)
    {
        $this->db->where('userid', $data['userid']);
        $this->db->where('projectid', $id);

        return $this->db->get(db_prefix() . 'products_whish')->row();
    }  
    
    /**
     * Get project single project by id
     * @param  mixed $id projectid
     * @return object
     */
    public function get_project($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'projects')->row();
    }    

    public function log_activity($project_id, $staff_id = '', $contact_id = '', $description_key, $additional_data = '', $visible_to_customer = 1)
    {
        if (!DEFINED('CRON')) {
            if (is_numeric($contact_id)) {
                $data['contact_id'] = $contact_id;
                $data['staff_id']   = 0;
                $data['fullname']   = get_contact_full_name($contact_id);
            } elseif (is_numeric($staff_id)) {
                $data['contact_id'] = 0;
                $data['staff_id']   = $staff_id;
                $data['fullname']   = get_staff_full_name($staff_id);
            }
        } else {
            $data['contact_id'] = 0;
            $data['staff_id']   = 0;
            $data['fullname']   = '[CRON]';
        }
        $data['description_key']     = $description_key;
        $data['additional_data']     = $additional_data;
        $data['visible_to_customer'] = $visible_to_customer;
        $data['project_id']          = $project_id;
        $data['dateadded']           = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_log_project_activity', $data);

        $this->db->insert(db_prefix() . 'project_activity', $data);
    }   
    
    public function get_activity($id = '', $limit = '', $only_project_members_activity = false)
    {
        if (is_numeric($id)) {
            $this->db->where('project_id', $id);
        }   
        if (is_numeric($limit)) {
            $this->db->limit($limit);
        }   
        $this->db->order_by('dateadded', 'desc'); 
        $activities = $this->db->get(db_prefix() . 'project_activity')->result_array();
        $i          = 0;    
        foreach ($activities as $activity) {
            $seconds          = get_string_between($activity['additional_data'], '<seconds>', '</seconds>');
            $other_lang_keys  = get_string_between($activity['additional_data'], '<lang>', '</lang>');
            $_additional_data = $activity['additional_data'];  
            
            if ($seconds != '') {
                $_additional_data = str_replace('<seconds>' . $seconds . '</seconds>', seconds_to_time_format($seconds), $_additional_data);
            }

            if ($other_lang_keys != '') {
                $_additional_data = str_replace('<lang>' . $other_lang_keys . '</lang>', _l($other_lang_keys), $_additional_data);
            }  
            
            if (strpos($_additional_data, 'project_status_') !== false) {
                $_additional_data = get_project_status_by_id(strafter($_additional_data, 'project_status_'));

                if (isset($_additional_data['name'])) {
                    $_additional_data = $_additional_data['name'];
                }
            }  
            
            $activities[$i]['description']     = _l($activities[$i]['description_key']);
            $activities[$i]['additional_data'] = $_additional_data;
            $activities[$i]['project_name']    = get_project_name_by_id($activity['project_id']);
            unset($activities[$i]['description_key']);
            $i++;            
        } 
        
        return $activities;
    }

    public function get_project_statuses()
    {
        $statuses = hooks()->apply_filters('before_get_project_statuses', [
            [
                'id'             => 1,
                'color'          => '#475569',
                'name'           => _l('project_status_1'),
                'order'          => 1,
                'filter_default' => true,
            ],
            [
                'id'             => 2,
                'color'          => '#2563eb',
                'name'           => _l('project_status_2'),
                'order'          => 2,
                'filter_default' => true,
            ],
            [
                'id'             => 3,
                'color'          => '#f97316',
                'name'           => _l('project_status_3'),
                'order'          => 3,
                'filter_default' => true,
            ],
            [
                'id'             => 4,
                'color'          => '#16a34a',
                'name'           => _l('project_status_4'),
                'order'          => 100,
                'filter_default' => false,
            ],
            [
                'id'             => 5,
                'color'          => '#94a3b8',
                'name'           => _l('project_status_5'),
                'order'          => 4,
                'filter_default' => false,
            ],
        ]);

        usort($statuses, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $statuses;
    }    
}