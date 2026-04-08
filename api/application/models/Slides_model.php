<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Slides_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * Fetch posts with optional and language scoping.
     *
     * @param array      $where   Extra conditions. Supports:
     *                            - 'language'  => string (e.g. 'english')
     *                            - 'languageid'=> int
     *
     * @return array<object>      Result set of stdClass rows.
     */   
    public function getAll($where = array())
    {       
        $columns = [
            db_prefix() .'slides.id',
            db_prefix() .'slides.dateadded',
            db_prefix() .'slides.staffid',
            db_prefix() .'slides.active as active',
            db_prefix() .'slides_translation.name as name',
            db_prefix() .'slides_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',
            db_prefix() .'languages.language as language',   
            'order', 
            'link', 
            'mask', 
            'folder',       
        ];         
        $this->db->select($columns);

        if (!empty($where)) {
            $this->db->where($where);
        }      

        $this->db->join(db_prefix() . 'slides_translation', db_prefix() . 'slides.id = ' . db_prefix() . 'slides_translation.slideid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'slides_translation.languageid', 'left');          

        $this->db->group_by(db_prefix() . 'slides_translation.id');
        $this->db->order_by('order', 'asc');        

        return $this->db->get(db_prefix() . 'slides')->result();            
    }

    /**
     * Fetch a single slide by id or a list of slides.
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
            db_prefix() .'slides.id as id',
            db_prefix() .'slides.dateadded',
            db_prefix() .'slides.staffid',
            db_prefix() .'slides.active',
            db_prefix() .'slides_translation.name as name',
            db_prefix() .'slides_translation.description as description',
            db_prefix() .'languages.languageid as languageid',  
            db_prefix() .'languages.language_cod as language_cod',
            db_prefix() .'languages.language as language',  
            'order', 
            'link', 
            'mask', 
            'folder',       
        ];     
        $this->db->select($columns);        
        
        $this->db->where($where);
        $this->db->join(db_prefix() . 'slides_translation', db_prefix() . 'slides.id = ' . db_prefix() . 'slides_translation.slideid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'slides_translation.languageid', 'left');  

        $this->db->group_by(db_prefix() . 'slides_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'slides.id', $id);
            
            return $this->db->get(db_prefix() . 'slides')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'slides')->result();
    }      

    /**
     * Insert a new slide with translations (for all active languages) and categories.
     *
     * Expected $data keys:
     *  - name (string, required)
     *  - description (string, optional)
     *  - link (string|null, optional)
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

        // defaults
        $data['mask']           = $data['mask']   ?? 1;
        $data['active']         = $data['active'] ?? 1;

        // Hook antes da inserção
        $data = hooks()->apply_filters('before_add_slide', $data);

        $this->db->insert(db_prefix() . 'slides', $data);
        $insert_id = $this->db->insert_id();    

        if ($insert_id) {
            if (!empty($languages)) {
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'slides_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'slideid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_slide', $insert_id);
            log_activity('New Slide Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }

    /**
     * Update slide main data and translation.
     *
     * @param array $data Normalized payload. Accepts:
     *  - name (string)
     *  - description (string)
     *  - link (string|null)
     *  - staffid (int)                      // optional: used only for activity log
     *  - languageid (int)                   // required when updating translation fields
     *  - mask (int|bool)                    // optional
     *  - active (int|bool)                  // optional
     * @param int   $id
     *
     * @return bool  True if any row changed, false otherwise
     */
    public function update($data, $id)
	{  
        if (empty($data) || !$id) {
            return false;
        }

        $affectedRows = 0;
        
        // Se houver tradução específica
        $languageid = $data['languageid'] ?? null;
        unset($data['languageid']);

        $data['description']    = nl2br($data['description'] ?? '');

        // defaults
        $data['mask']           = $data['mask']   ?? 1;

        $updateMain = [
            'link' => $data['link'] ?? null,
            'mask' => $data['mask'] ?? 1,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'slides', $updateMain);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }          
        
        if($languageid) {
            $updateTranslation = [
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
            ];

            $this->db->where('slideid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'slides_translation', $updateTranslation);

            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }     
        }     

        if ($affectedRows > 0) {
            log_activity('Slide Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_slide', $id);
            return true;
        }

        return false;
    }  
    
    /**
     * Permanently delete a slide and all related data.
     *
     * Deletes:
     * - slides_translation (all languages)
     * - files/pictures (via delete_picture)
     * - slide (row itself)
     *
     * Hooks:
     * - before_slide_deleted($slide_id)
     * - after_slide_deleted($slide_id)
     *
     * @param int $slide_id
     * @return bool True if the slide was deleted, false otherwise.
     */      
    public function delete($slide_id)
    {
        $slide_id = (int)$slide_id;
        if ($slide_id <= 0) {
            return false;
        }

        hooks()->do_action('before_slide_deleted', $slide_id);

        $this->db->trans_start();

        // Translations
        $this->db->where('slideid', $slide_id);
        $this->db->delete(db_prefix() . 'slides_translation');     
        
        // Files/pictures (fetch first; delete one by one using model helper)
        $files = $this->get_pictures($slide_id);
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                // Defensive: ensure object has id
                if (isset($file->id)) {
                    // Ignore individual failures, keep trying others
                    try {
                        $this->delete_picture((int)$file->id);
                    } catch (\Throwable $e) {
                        // Optionally: log_message('error', 'delete_picture failed: '.$e->getMessage());
                    }
                }
            }
        }           

        // Delete the slide row
        $this->db->where('id', $slide_id);
        $this->db->delete(db_prefix() . 'slides');   
        $deletedSlideRows = $this->db->affected_rows();

        $this->db->trans_complete();

        if ($this->db->trans_status() === false || $deletedSlideRows <= 0) {
            // Transaction failed or no post row deleted
            return false;
        }        

        log_activity('Slide Deleted [ID: ' . $slide_id . ']', 'deleted');
        hooks()->do_action('after_slide_deleted', $slide_id);

        return true;
    }    

    public function upload_picture($data)
	{  
		$this->db->insert(db_prefix() . 'slides_pictures', $data);  
        $insert_id = $this->db->insert_id(); 
        if ($insert_id) {
        
            return $insert_id;
        }
        
        return false;        
    }      

    /**
     * Get pictures for a slide (optionally filtered).
     *
     * @param int|string|null $slide_id  If numeric (>0), filters by slide_id
     * @param array           $where    Extra where conditions (optional)
     * @return array<object>            Result set (possibly empty)
     */        
    public function get_pictures($slide_id, $where = array())
    {
        // Extra filters (apply only if non-empty array)
        if (is_array($where) && !empty($where)) {
            $this->db->where($where);
        }

        if (is_numeric($slide_id) && (int)$slide_id > 0) {
            $this->db->where('slideid', $slide_id);
        }

        // Predictable ordering (adjust as needed)
        $this->db->order_by('dateadded', 'DESC');
        $this->db->order_by('id', 'DESC');            

        return $this->db->get(db_prefix() . 'slides_pictures')->result();
    }

    /**
     * Get a single picture by its id (and optionally ensure it belongs to a given slide).
     *
     * @param int              $id        Picture id (required)
     * @param int|string|null  $slide_id  Optional slide id to enforce ownership
     * @return object|false               Row object if found (and matches slide), false otherwise
     */         
    public function get_picture($id, $slide_id = false)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }   

        // Always filter by picture id
        $this->db->where('slideid', $id);
        // If a slide_id is provided, enforce ownership
        if ($slide_id !== null && is_numeric($slide_id) && (int)$slide_id > 0) {
            $this->db->where('slideid', (int)$slide_id);
        }        

        $file = $this->db->get(db_prefix() . 'slides_pictures')->row();

        // If $slide_id was not provided above, we still return the row if found
        return $file ?: false;
    }    

    /**
     * Delete a single picture (file + DB row) for posts.
     *
     * @param int  $id            Picture row id (id)
     * @param bool $log_activity  Whether to log activity
     * @return bool               True if deleted, false otherwise
     */       
    public function delete_picture($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        hooks()->do_action('before_remove_slide_picture', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'slides_pictures')->row();
        if (!$file) {
            return false;
        }
        
        // Build base path
        $basePath = rtrim(get_upload_path_by_type('slides'), '/\\') . '/';
        $path     = $basePath . '/';              
        
        // Remove physical files if not marked as external (when such column exists)
        $isExternal = property_exists($file, 'external') ? (bool)$file->external : false;
        if (!$isExternal) {
            $fullPath = $path . $file->file_name;
            if (is_file($fullPath)) {
                // Delete original
                @unlink($fullPath);

                // Compute thumb path: CI appende "_thumb"
                $fname = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext  = pathinfo($fullPath, PATHINFO_EXTENSION);
                
                $thumbPath = $fext !== ''
                    ? ($path . $fname . '_thumb.' . $fext)
                    : ($path . $fname . '_thumb');

                if (is_file($thumbPath)) {
                    @unlink($thumbPath);
                }                
            }             
        }

        // Delete DB row
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'slides_pictures');  
        if ($this->db->affected_rows() <= 0) {
            // If DB delete failed, stop here (files may have been removed already)
            return false;
        }

        hooks()->do_action('after_remove_slide_file', $id, (int)$file->slideid);

        return true;
    }
}