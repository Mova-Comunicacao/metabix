<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Carousel_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * Fetch all carousel
     *
     *
     * @return array<object>      Result set of stdClass rows.
     */   
    public function getAll($where = array())
    {       
        $columns = [
            db_prefix() .'carousel.id',
            db_prefix() .'carousel.subject',
            db_prefix() .'carousel.description',
            db_prefix() .'carousel.dateadded',
            db_prefix() .'carousel.staffid',
            db_prefix() .'carousel.file_name',
            db_prefix() .'carousel.original_file_name',
            db_prefix() .'carousel.visible_to_customer',
            db_prefix() .'carousel.external',
            db_prefix() .'carousel.type',
            db_prefix() .'carousel.order',
        ];         
        $this->db->select($columns);

        if (!empty($where)) {
            $this->db->where($where);
        }              

        $this->db->order_by(db_prefix() .'carousel.order', 'asc');
        $this->db->order_by(db_prefix() .'carousel.dateadded', 'desc');     

        $result =  $this->db->get(db_prefix() . 'carousel')->result();   
        
        // Inject a null "folder" property to keep controller compatibility
        foreach ($result as $r) {
            if (!property_exists($r, 'folder')) {
                $r->folder = 'carousel';
            }
        }

        return $result;          
    }


    /**
     * Fetch a single carousel by id or a list of carousels.
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
            db_prefix() .'carousel.id',
            db_prefix() .'carousel.subject',
            db_prefix() .'carousel.description',
            db_prefix() .'carousel.dateadded',
            db_prefix() .'carousel.staffid',
            db_prefix() .'carousel.file_name',
            db_prefix() .'carousel.original_file_name',
            db_prefix() .'carousel.visible_to_customer',
            db_prefix() .'carousel.external',
            db_prefix() .'carousel.type',
            db_prefix() .'carousel.order',
        ];         
        $this->db->select($columns);

        if (!empty($where)) {
            $this->db->where($where);
        }  
        
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'carousel.id', $id);
            $row = $this->db->get(db_prefix() . 'carousel')->row();
            if ($row) {
                // Inject a null "folder" property to keep controller compatibility
                if (!property_exists($row, 'folder')) {
                    $row->folder = 'carousel';
                }
        
                return $row;           
            }

            return null;
        }   

        // Since we filter by a single language, grouping by carousel.id is safe.
        $this->db->order_by(db_prefix() .'carousel.order', 'asc');
        $this->db->order_by(db_prefix() .'carousel.dateadded', 'desc'); 

        $result = $this->db->get(db_prefix() . 'carousel')->result();

        // Inject a null "folder" property to keep controller compatibility
        foreach ($result as $r) {
            if (!property_exists($r, 'folder')) {
                $r->folder = 'carousel';
            }
        }

        return $result;         
    }      

    /**
     * Insert a new carousel with translations (for all active languages) and categories.
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

        unset($data['null']);
        $data['subject']        = trim($data['subject'] ?? '');
        $data['description']    = nl2br($data['description'] ?? '');
        $data['dateadded']      = date('Y-m-d H:i:s');

        // defaults
        $data['visible_to_customer']         = $data['visible_to_customer'] ?? 0;

        // Hook antes da inserção
        $data = hooks()->apply_filters('before_add_carousel', $data);
     
        $this->db->insert(db_prefix() . "carousel", $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Carousel Added [ID:' . $insert_id . ', TYPE:' . $type . ']', 'create');
            hooks()->do_action('after_add_carousel', ['id' => $insert_id, 'type' => $type]);

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


        // defaults
        $data['active']         = $data['active'] ?? 0;

        $type = isset($data['type']) && in_array($data['type'], ['video','picture'], true)
            ? $data['type']
            : (!empty($data['external']) ? 'video' : 'picture');

        unset($data['type']);

        if($type === 'picture') {
            $whitelistCommon = [
                'subject','description','dateadded','staffid','visible_to_customer'
            ];
        }

        if($type === 'video') {
            $whitelistCommon = [
                'subject','description','dateadded','staffid','visible_to_customer', 'external'
            ];
        }        

        $payload = array_intersect_key($data, array_flip($whitelistCommon));

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . "carousel", $payload);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        if ($affectedRows > 0) {
            log_activity('Carousel Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_carousel', $id);
            return true;
        }

        return false;
    }  
    
    /**
     * Delete a carousel item, its translations, and its picture (file + db reference).
     * Uses a DB transaction to keep consistency.
     *
     * @param int $id
     * @return bool
     */      
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }
        
        hooks()->do_action('before_carousel_deleted', $id);
        
        $this->db->trans_begin();
        // Use the same path that uploads used (carousel)
        $pictureOk = $this->delete_picture($id);
                
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'carousel');

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
        hooks()->do_action('before_remove_carousel_pictures', $id);

        $this->db->where('id', (int)$id);
        $file = $this->db->get(db_prefix() . 'carousel')->row();
        // If there is no row or no file, just ensure DB is clean and return true
        if (!$file) {
            return true;
        }

        // Standardized path: use the same base used on upload (carousel)
        // If you truly need 'carousel', pass via $customPath when calling
        $basePath = rtrim(get_upload_path_by_type('carousel'), '/') . '/';
        $fileName = !empty($file->file_name) ? $file->file_name : null;

        // Remove physical file if local and we have a filename
        if ($fileName) {
            $fullPath = $basePath . $fileName;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
            
        // Null the file reference in DB
        $this->db->where('id', (int)$id);
        $this->db->update(db_prefix() . 'carousel', [
            'file_name' => null,
            'original_file_name' => null
        ]);

        return true;
    }
}