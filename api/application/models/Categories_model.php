<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Categories_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    public function getAll($where = array())
    {
        $columns = [
            db_prefix() .'categories.id',
            db_prefix() .'categories.file_name', 
            db_prefix() .'categories.folder',
            db_prefix() .'categories.dateadded',
            db_prefix() .'categories.staffid',
            db_prefix() .'categories.order',
            db_prefix() .'categories_translation.name as name',
            db_prefix() .'categories_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language as language',            
        ]; 
        $this->db->select($columns);
        
        $this->db->where($where);
        $this->db->join(db_prefix() . 'categories_translation', db_prefix() . 'categories.id = ' . db_prefix() . 'categories_translation.categoryid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'categories_translation.languageid', 'left');        

        $this->db->group_by(db_prefix() . 'categories_translation.id');  
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'categories')->result();            
    }

    /**
     * Fetch a single category by id or a list of categorys.
     *
     * Behavior:
     * - When $id is numeric, returns a single stdClass row or null if not found.
     * - When $id is empty/non-numeric, returns an array of stdClass rows.
     *
     * Language scoping via $where:
     * - $where['languageid'] (int) OR $where['language'] (string) will be applied.
     *   Any other key/value pairs in $where are also applied as standard where clauses.
     *
     * @param mixed      $id
     * @param array      $where
     * @return object|array<object>|null
     */
    public function get($id = '', $where = array())
    {
        $columns = [
            db_prefix() .'categories.id',
            db_prefix() .'categories.file_name', 
            db_prefix() .'categories.folder',
            db_prefix() .'categories.dateadded',
            db_prefix() .'categories.staffid',
            db_prefix() .'categories.order',
            db_prefix() .'categories_translation.name as name',
            db_prefix() .'categories_translation.description as description',
            db_prefix() .'languages.languageid as languageid',             
            db_prefix() .'languages.language as language',            
        ];         
        $this->db->select($columns);
        
        $this->db->where($where);
        $this->db->join(db_prefix() . 'categories_translation', db_prefix() . 'categories.id = ' . db_prefix() . 'categories_translation.categoryid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'categories_translation.languageid', 'left');        

        $this->db->group_by(db_prefix() . 'categories_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'categories.id', $id);

            $category =  $this->db->get(db_prefix() . 'categories')->row();
            $this->api_object_cache->set('categories-' . $category->name, $category);

            return $category;
        }
        $this->db->order_by('order', 'asc');

        $categories = $this->api_object_cache->get('categories-data');

        if (!$categories && !is_array($categories)) {
            $categories = $this->db->get(db_prefix() . 'categories')->result();
            $this->api_object_cache->add('categories-data', $categories);
        }
    
        return $categories;  
    }  
    
    /**
     * Insert a new category with translations (for all active languages) and categories.
     *
     * Expected $data keys:
     *  - name (string, required)
     *  - description (string, optional)
     *  - staffid (int, optional)
     *
     * Returns:
     *  - int insert_id on success
     *  - false on failure
     */
	public function add($data)
	{
        $languages = $this->languages_model->get(null, ['active' => 1]);

        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');
        $data['description']    = nl2br($data['description'] ?? '');

        $data = hooks()->apply_filters('before_add_category', $data);

        $this->db->insert(db_prefix() . 'categories', $data);
        $insert_id = $this->db->insert_id();  

        if ($insert_id) {
            if (!empty($languages)) {
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'categories_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'categoryid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_category', $insert_id);
            log_activity('New Category Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }  
    
    /**
     * Update category main data and translation.
     *
     * @param array $data Normalized payload. Accepts:
     *  - name (string)
     *  - description (string)
     *  - staffid (int)                      // optional: used only for activity log
     *  - languageid (int)                   // required when updating translation fields
     * @param int   $id
     *
     * @return bool  True if any row changed, false otherwise
     */
    public function update($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $languageid = $data['languageid'] ?? null;
        unset($data['languageid']);

        $data['description']    = nl2br($data['description'] ?? '');

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'categories', $data);  
        
        if($languageid){
            $this->db->where('categoryid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'categories_translation', array(
                'name' => $data['name'],
                'description' => $data['description'],
            ));            
        }  

        if ($this->db->affected_rows() > 0) {
            log_activity('Category Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_categories', $id);
            return true;
        }

        return false;
    }  
    
    /**
     * Permanently delete a category and all related data.
     *
     * Deletes:
     * - categorys_translation (all languages)
     * - files/pictures (via delete_picture)
     * - category (row itself)
     *
     * Hooks:
     * - before_category_deleted($category_id)
     * - after_category_deleted($category_id)
     *
     * @param int $category_id
     * @return bool True if the category was deleted, false otherwise.
     */       
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        hooks()->do_action('before_category_deleted', $id);

        $this->db->trans_begin();
        // Use the same path that uploads used (technology/icons)
        $pictureOk = $this->delete_picture($id, rtrim(get_upload_path_by_type('technology'), '/') . '/icons/');


        // Translations
        $this->db->where('categoryid', $id);
        $this->db->delete(db_prefix() . 'categories_translation');     

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'categories');

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
     * Delete only the picture file for a technology item and null the DB field.
     *
     * @param int $id
     * @param string|null $customPath Optional override path (if you keep different stores)
     * @return bool
     */    
    public function delete_picture($id, $customPath = null)
    {
        hooks()->do_action('before_remove_category_pictures', $id);

        $this->db->where('id', (int)$id);
        $file = $this->db->get(db_prefix() . 'categories')->row();
        // If there is no row or no file, just ensure DB is clean and return true
        if (!$file) {
            return true;
        }


        // If you truly need 'technology/icons', pass via $customPath when calling
        $basePath = $customPath ?: (rtrim(get_upload_path_by_type('technology'), '/') . '/icons/');
        $fullPath = $basePath . $file->file_name;   
        $fileName = !empty($file->file_name) ? $file->file_name : null;

        if ($fileName) {
            $fullPath = $basePath . $fileName;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Null the file reference in DB
        $this->db->where('id', (int)$id);
        $this->db->update(db_prefix() . 'categories', ['file_name' => null]);

        return true;
    }    
}