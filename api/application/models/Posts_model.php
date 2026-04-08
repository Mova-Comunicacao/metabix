<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Posts_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }    

    /**
     * Fetch posts with optional filters and language scoping.
     *
     * @param array|null $filter  Expected shape: ['filter' => ['category_id' => int|0, 'search_string' => string|null]]
     * @param array      $where   Extra conditions. Supports:
     *                            - 'language'  => string (e.g. 'english')
     *                            - 'languageid'=> int
     *
     * @return array<object>      Result set of stdClass rows.
     */    
    public function getAll($filter = null, $where = array())
    {
        $categoryId   = null;
        $searchString = null;

        if (is_array($filter) && isset($filter['filter']) && is_array($filter['filter'])) {
            $categoryId   = isset($filter['filter']['category_id']) ? (int)$filter['filter']['category_id'] : null;
            $searchString = isset($filter['filter']['search_string']) ? trim((string)$filter['filter']['search_string']) : null;
        }      
           
        
        $columns = [
            db_prefix() .'posts.id',
            db_prefix() .'posts.dateadded',
            db_prefix() .'posts.active',
            db_prefix() .'posts.order',
            db_prefix() .'posts.staffid',
            db_prefix() .'posts.external_link',
            db_prefix() .'posts_translation.name as name',
            db_prefix() .'posts_translation.description as description',
            db_prefix() .'posts_translation.long_description as long_description',  
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',             
            db_prefix() .'languages.languageid as languageid',           
            db_prefix() .'languages.language as language',                      
        ];    

        $this->db->select($columns);

        $this->db->join(db_prefix() . 'posts_translation', db_prefix() . 'posts.id = ' . db_prefix() . 'posts_translation.postid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'posts_translation.languageid', 'left');          
        $this->db->join(db_prefix() . 'posts_categories', db_prefix() . 'posts_categories.post_id = ' . db_prefix() . 'posts.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'posts_categories.category_id', 'left');

        if (isset($where['languageid']) && is_numeric($where['languageid'])) {
            $this->db->where(db_prefix() . 'languages.languageid', (int)$where['languageid']);
            unset($where['languageid']);
        } elseif (isset($where['language']) && $where['language'] !== '') {
            $this->db->where(db_prefix() . 'languages.language', (string)$where['language']);
            unset($where['language']);
        }

        if (!empty($where)) {
            $this->db->where($where);
        }        

        // If category_id == 0 (or null), do not restrict by category.
        if (!empty($categoryId)) {
            // Proper, parameterized filter (no raw string)
            $this->db->where(db_prefix() . 'posts_categories.category_id', (int)$categoryId);
        }   
        
        // Search filter (name/description/long_description)
        if (!empty($searchString)) {
            $this->db->group_start();
            $this->db->like(db_prefix() . 'posts_translation.name', $searchString);
            $this->db->or_like(db_prefix() . 'posts_translation.description', $searchString);
            $this->db->or_like(db_prefix() . 'posts_translation.long_description', $searchString);
            $this->db->group_end();
        }
        
        // Since we filter by a single language, grouping by posts.id is safe.
        $this->db->group_by(db_prefix() .'posts.id');
        $this->db->order_by(db_prefix() .'posts.order', 'ASC');
        $this->db->order_by(db_prefix() .'posts.dateadded', 'DESC');        

        $result = $this->db->get(db_prefix() . 'posts')->result();

        // Inject a null "folder" property to keep controller compatibility
        foreach ($result as $r) {
            if (!property_exists($r, 'folder')) {
                $r->folder = 'posts';
            }
        }

        return $result;              
    }

    /**
     * Fetch a single post by id or a list of posts with optional filters.
     *
     * Behavior:
     * - When $id is numeric, returns a single stdClass row or null if not found.
     * - When $id is empty/non-numeric, returns an array of stdClass rows.
     *
     * Filters:
     * - $filter['filter']['category_id'] (int|0|null): if > 0, restricts to that category.
     *
     * Language scoping via $where:
     * - $where['languageid'] (int) OR $where['language'] (string) will be applied.
     *   Any other key/value pairs in $where are also applied as standard where clauses.
     *
     * Notes:
     * - There is no 'folder' column in DB. A null 'folder' property is injected on results
     *   to keep controller compatibility (which may access $row->folder).
     *
     * @param mixed      $id
     * @param array|null $filter
     * @param array      $where
     * @return object|array<object>|null
     */
    public function get($id = '', $filter = null, $where = array())
    {
        $categoryId   = null;
        if (is_array($filter) && isset($filter['filter']) && is_array($filter['filter'])) {
            $categoryId   = isset($filter['filter']['category_id']) ? (int)$filter['filter']['category_id'] : null;
        }    

        $columns = [
            db_prefix() .'posts.id',
            db_prefix() .'posts.dateadded',
            db_prefix() .'posts.active',
            db_prefix() .'posts.order',
            db_prefix() .'posts.staffid',
            db_prefix() .'posts.external_link',
            db_prefix() .'posts.slug',
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',
            db_prefix() .'posts_translation.name as name',
            db_prefix() .'posts_translation.description as description',
            db_prefix() .'posts_translation.long_description as long_description',
            db_prefix() .'languages.languageid as languageid',             
            db_prefix() .'languages.language_cod as language_cod',             
            db_prefix() .'languages.language as language',  
        ];   
        $this->db->select($columns);

        $this->db->join(db_prefix() . 'posts_translation', db_prefix() . 'posts.id = ' . db_prefix() . 'posts_translation.postid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'posts_translation.languageid', 'left');  
        $this->db->join(db_prefix() . 'posts_categories', db_prefix() . 'posts_categories.post_id = ' . db_prefix() . 'posts.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'posts_categories.category_id', 'left'); 

        if (isset($where['languageid']) && is_numeric($where['languageid'])) {
            $this->db->where(db_prefix() . 'languages.languageid', (int)$where['languageid']);
            unset($where['languageid']);
        } elseif (isset($where['language']) && $where['language'] !== '') {
            $this->db->where(db_prefix() . 'languages.language', (string)$where['language']);
            unset($where['language']);
        }

        if (!empty($where)) {
            $this->db->where($where);
        }     

        // If category_id == 0 (or null), do not restrict by category.
        if (!empty($categoryId)) {
            // Proper, parameterized filter (no raw string)
            $this->db->where(db_prefix() . 'posts_categories.category_id', (int)$categoryId);
        }   

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'posts.id', (int)$id);
            $row = $this->db->get(db_prefix() . 'posts')->row();
            if ($row) {
                // Inject null folder for controller compatibility
                if (!property_exists($row, 'folder')) {
                    $row->folder = 'posts';
                }

                $row = hooks()->apply_filters('post_get', $row);
                $GLOBALS['post'] = $row;

                return $row;              
            }

            return null;
        }
        
        // Since we filter by a single language, grouping by posts.id is safe.
        $this->db->group_by(db_prefix() .'posts_translation.id');
        $this->db->order_by(db_prefix() .'posts.order', 'asc');
        $this->db->order_by(db_prefix() .'posts.dateadded', 'desc'); 

        $result = $this->db->get(db_prefix() . 'posts')->result();

        // Inject a null "folder" property to keep controller compatibility
        foreach ($result as $r) {
            if (!property_exists($r, 'folder')) {
                $r->folder = 'posts';
            }
        }

        return $result;             
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
            db_prefix() .'posts.id as id',
            db_prefix() .'posts.name as name',
            db_prefix() .'posts.dateadded',
            db_prefix() .'posts.active',
            db_prefix() .'posts.order',
            db_prefix() .'posts.staffid',
            db_prefix() .'categories.id as category_id',
            db_prefix() .'categories.name as category_name',
            db_prefix() .'posts_translation.description as description',
            db_prefix() .'posts_translation.long_description as long_description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',    
            'external_link',           
            'slug',
        ];  
        $this->db->select($columns);

        $this->db->join(db_prefix() . 'posts_translation', db_prefix() . 'posts.id = ' . db_prefix() . 'posts_translation.postid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'posts_translation.languageid', 'left');  
        $this->db->join(db_prefix() . 'posts_categories', db_prefix() . 'posts_categories.post_id = ' . db_prefix() . 'posts.id', 'left'); 
        $this->db->join(db_prefix() . 'categories', db_prefix() . 'categories.id = ' . db_prefix() . 'posts_categories.category_id', 'left'); 

        if (!empty($where)) {
            $this->db->where($where);
        }     

        if (!empty($slug)) {
            $this->db->where(db_prefix() . 'posts.slug', $slug);
            $post = $this->db->get(db_prefix() . 'posts')->result();

            // Inject a null "folder" property to keep controller compatibility
            foreach ($post as $r) {
                if (!property_exists($r, 'folder')) {
                    $r->folder = 'posts';
                }
            }

            if ($post) {
                $post            = hooks()->apply_filters('post_get', $post);
                $GLOBALS['post'] = $post;

                return $post;                
            }

            return null;
        }
    } 

    /**
     * Insert a new post with translations (for all active languages) and categories.
     *
     * Expected $data keys:
     *  - name (string, required)
     *  - description (string, optional)
     *  - long_description (string, optional)
     *  - external_link (string|null, optional)
     *  - categories (array<int>, optional)
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
        
        $data['name']               = trim($data['name'] ?? '');
        $data['description']        = nl2br($data['description'] ?? '', false);
        $data['long_description']   = html_purify($data['long_description'] ?? '', true);
        $data['external_link']      = $data['external_link'] ?? null;
        $slug                       = slug_it($data['name'] ?? '');

        $data['dateadded']          = date('Y-m-d H:i:s');

        $post_categories = [];
        if (isset($data['categories'])) {
            $post_categories = $data['categories'];
            unset($data['categories']);
        }   

        $staff_id = isset($data['staffid']) ? $data['staffid'] : 0;
        unset($data['staffid']);     

        // Required: name
        if ($data['name'] === '') {
            return false;
        }

        // Base post payload
        $postPayload = [
            'name'             => $data['name'],               
            'description'      => $data['description'],     
            'long_description' => $data['long_description'],
            'external_link'    => $data['external_link'],
            'dateadded'        => $data['dateadded'],
            'slug'             => $slug,
            'active'           => isset($data['active']) ? $data['active'] : 1,
        ];            

        // Preserve any extra allowed fields that may belong to posts table
        $extra = array_diff_key($data, [
            'name' => true,
            'description' => true,
            'long_description' => true,
            'external_link' => true,
            'active' => true,
        ]);
        if (!empty($extra)) {
            $postPayload = array_merge($postPayload, $extra);
        }
                
        // Hooks before insert
        $postPayload = hooks()->apply_filters('before_add_post', $postPayload);

        $this->db->trans_start();

        $this->db->insert(db_prefix() . 'posts', $postPayload);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Translations for all active languages (batch)
            if (is_array($languages) && !empty($languages)) {
                $translations = [];
                foreach($languages as $l) {
                    $langId = isset($l->languageid) ? $l->languageid : 0;
                    if ($langId <= 0) {
                        continue;
                    }     
                    $translations[] = [
                        'postid'          => (int)$insert_id,
                        'languageid'      => $langId,
                        'name'             => $data['name'],               
                        'description'      => $data['description'],     
                        'long_description' => $data['long_description'],
                    ];
                }
                if (!empty($translations)) {
                    $this->db->insert_batch(db_prefix() . 'posts_translation', $translations);
                }         
            }

            if(!empty($post_categories)){
                $rels = [];
                foreach ($post_categories as $category_id) {
                    $rels[] = [
                        'post_id'     => (int)$insert_id,
                        'category_id' => (int)$category_id,
                    ];
                }             
                if (!empty($rels)) {
                    $this->db->insert_batch(db_prefix() . 'posts_categories', $rels);
                }
            }  
            
             // Activity logs & hooks
            $this->log_activity($insert_id, $staff_id ?? '', '', 'project_activity_created');
            hooks()->do_action('after_add_post', $insert_id);
            log_activity('New Post Created [ID: ' . $insert_id . ']', 'add');
        }   

        $this->db->trans_complete();

        if ($this->db->trans_status() === false || !$insert_id) {
            return false;
        }

        return $insert_id;
    }   

    /**
     * Update post main data, categories, and translation.
     *
     * @param array $data Normalized payload. Accepts:
     *  - name (string)
     *  - description (string)
     *  - long_description (string)
     *  - external_link (string|null)
     *  - categories (int[])                 // optional: full replacement of relations
     *  - staffid (int)                      // optional: used only for activity log
     *  - languageid (int)                   // required when updating translation fields
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

        $data['name']               = trim($data['name'] ?? '');
        $data['description']        = nl2br($data['description'] ?? '', false);
        $data['long_description']   = nl2br($data['long_description'] ?? '', false);
        $slug                       = slug_it($data['name'] ?? '');

        // defaults
        $data['active']             = $data['active']   ?? 1;

        $post_categories = [];
        if (isset($data['categories'])) {
            $post_categories = $data['categories'];
            unset($data['categories']);
        }   
        
        $staff_id = '';
        if (isset($data['staffid'])) {
            $staff_id = $data['staffid'];
            unset($data['staffid']);
        }           
        
        $this->db->trans_start();

        if (!empty($post_categories) || is_array($post_categories)) {
            // remove all current relations for this post
            $this->db->where('post_id', $id);
            $this->db->delete(db_prefix() . 'posts_categories');  
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }   
            
            if (!empty($post_categories)) {
                // batch insert
                $rows = [];   
                foreach ($post_categories as $cid) {
                    $rows[] = [
                        'post_id'     => $id,
                        'category_id' => $cid,
                    ];
                } 
                $this->db->insert_batch(db_prefix() . 'posts_categories', $rows); 
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }                                                          
            }          
        }   

        $data = hooks()->apply_filters('before_update_post', $data, $id);

        // Update main 'posts' row
        $updateMain = [
            'external_link' => $data['external_link'] ?? null,
            'active'        => $data['active'] ?? 1,
            'slug'          => $slug,
        ];        

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'posts', $updateMain);  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }  

        if($languageid) {
            $updateTranslation = [
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
                'long_description' => $data['long_description'] ?? '',
            ];

            // First try update
            $this->db->where('postid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'posts_translation', $updateTranslation);  
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }  
            /*
            if ($this->db->affected_rows() === 0) {
                // No row to update -> insert
                $updateTranslation['postid']     = $id;
                $updateTranslation['languageid'] = $languageid;   
                
                $this->db->insert(db_prefix() . 'posts_translation', $updateTranslation);
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }                 
            } else {
                $affectedRows++;
            }   
            */             
        }   
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === false) {
            // Transaction failed
            return false;
        }
                
        if ($affectedRows > 0) {
            $this->log_activity($id, $staff_id, '', 'project_activity_updated');
            log_activity('Post Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_post', $id);
            return true;            
        }

        return false;
    }    
    
    /**
     * Permanently delete a post and all related data.
     *
     * Deletes:
     * - customfieldsvalues (relid = post_id, fieldto = 'posts')
     * - posts_categories (relations)
     * - posts_translation (all languages)
     * - files/pictures (via delete_picture)
     * - posts (row itself)
     *
     * Hooks:
     * - before_post_deleted($post_id)
     * - after_post_deleted($post_id)
     *
     * @param int $post_id
     * @return bool True if the post was deleted, false otherwise.
     */    
    public function delete($post_id)
    {
        $post_id = (int)$post_id;
        if ($post_id <= 0) {
            return false;
        }

        hooks()->do_action('before_post_deleted', $post_id);

        // Cache name for logging (avoid querying after deletion)
        $post_name = get_post_name_by_id($post_id);

        $this->db->trans_start();

        // Custom fields
        $this->db->where('relid', $post_id);
        $this->db->where('fieldto', 'posts');
        $this->db->delete(db_prefix() . 'customfieldsvalues');

        // Categories relations
        $this->db->where('post_id', $post_id);
        $this->db->delete(db_prefix() . 'posts_categories');

        // Translations
        $this->db->where('postid', $post_id);
        $this->db->delete(db_prefix() . 'posts_translation');   
        
        // Files/pictures (fetch first; delete one by one using model helper)
        $files = $this->get_pictures($post_id);
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
        
        // Delete the post row
        $this->db->where('id', $post_id);
        $this->db->delete(db_prefix() . 'posts');
        $deletedPostRows = $this->db->affected_rows();

        $this->db->trans_complete();

        if ($this->db->trans_status() === false || $deletedPostRows <= 0) {
            // Transaction failed or no post row deleted
            return false;
        }        

        log_activity('Post Deleted [ID: ' . $post_id . ', Name: ' . $post_name . ']', 'deleted');
        hooks()->do_action('after_post_deleted', $post_id);

        return true;
    } 


    public function get_categories($post_id = '', $where = array())
    {
        $columns = [
            db_prefix() .'categories.id as id',
            db_prefix() .'posts_categories.category_id',
            db_prefix() .'posts_categories.post_id',
            db_prefix() .'categories_translation.name as name',
            db_prefix() .'categories_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',
            db_prefix() .'languages.language as language',            
        ];         
        $this->db->select($columns);
        
        $this->db->join(db_prefix() . 'posts_categories', db_prefix() . 'posts_categories.category_id = ' . db_prefix() . 'categories.id', 'left'); 
        $this->db->join(db_prefix() . 'categories_translation', db_prefix() . 'categories.id = ' . db_prefix() . 'categories_translation.categoryid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'categories_translation.languageid', 'left');   
        $this->db->join(db_prefix() . 'posts',  db_prefix() . 'posts.id = ' . db_prefix() . 'posts_categories.post_id', 'left'); 

        $this->db->where(db_prefix() . 'posts_categories.post_id = ' . db_prefix() . 'posts_categories.post_id');
       
        if (!empty($where)) {
            $this->db->where($where);
        }          

        $this->db->group_by(db_prefix() . 'categories_translation.id');

        if (is_numeric($post_id)) {
            $this->db->where(db_prefix() . 'posts_categories.post_id', $post_id);
        }   
        
        $categories = $this->db->get(db_prefix() . 'categories')->result();
       
        if ($categories) {
            return $categories;
        }

        return false;        
    }


    /**
     * Get pictures for a post (optionally filtered).
     *
     * @param int|string|null $post_id  If numeric (>0), filters by post_id
     * @param array           $where    Extra where conditions (optional)
     * @return array<object>            Result set (possibly empty)
     */    
    public function get_pictures($post_id = '', $where = array())
    {

        // Extra filters (apply only if non-empty array)
        if (is_array($where) && !empty($where)) {
            $this->db->where($where);
        }

        if (is_numeric($post_id) && (int)$post_id > 0) {
            $this->db->where('post_id', $post_id);
        }

        // Predictable ordering (adjust as needed)
        $this->db->order_by('order', 'desc');
        $this->db->order_by('id', 'desc');        

        return $this->db->get(db_prefix() . 'posts_pictures')->result();
    }

    /**
     * Get a single picture by its id (and optionally ensure it belongs to a given post).
     *
     * @param int              $id        Picture id (required)
     * @param int|string|null  $post_id   Optional post id to enforce ownership
     * @return object|false               Row object if found (and matches post), false otherwise
     */    
    public function get_picture($id, $post_id = false)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }        

        // Always filter by picture id
        $this->db->where('post_id', $id);
        // If a post_id is provided, enforce ownership
        if ($post_id !== null && is_numeric($post_id) && (int)$post_id > 0) {
            $this->db->where('post_id', (int)$post_id);
        }

        $file = $this->db->get(db_prefix() . 'posts_pictures')->row();

        // If $post_id was not provided above, we still return the row if found
        return $file ?: false;
    }  
 

    /**
     * Delete a single picture (file + DB row) for posts.
     *
     * @param int  $id            Picture row id (posts_pictures.id)
     * @param bool $log_activity  Whether to log activity
     * @return bool               True if deleted, false otherwise
     */    
    public function delete_picture($id, $log_activity = false)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        hooks()->do_action('before_remove_post_file', $id);

        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . 'posts_pictures')->row();
        if (!$file) {
            return false;
        }

        // Build base path
        $basePath = rtrim(get_upload_path_by_type('posts'), '/\\') . '/';
        $path     = $basePath . $file->post_id . '/';        

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
        $this->db->delete(db_prefix() . 'posts_pictures');           
        if ($this->db->affected_rows() <= 0) {
            // If DB delete failed, stop here (files may have been removed already)
            return false;
        }

        // Optional activity log
        if ($log_activity) {
            // Use existing columns safely
            $originalName = property_exists($file, 'original_file_name') ? $file->original_file_name : '';

            $this->log_activity(
                $file->post_id,
                '',                      // staff
                '',                      // extra data
                'post_activity_post_file_removed',
                $originalName,
                null
            );
        }

        // Remove folder if empty
        $postDir = $basePath . $file->post_id;
        if (is_dir($postDir)) {
            $other = list_files($postDir);
            if (is_array($other) && count($other) === 0) {
                delete_dir($postDir);
            }
        }        
                
        hooks()->do_action('after_remove_post_file', $id, $file->post_id);

        return true;
    }


    /**
     * Get project single project by id
     * @param  mixed $id postid
     * @return object
     */
    public function get_post($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'posts')->row();
    }    

    public function log_activity($post_id, $staff_id = '', $contact_id = '', $description_key, $additional_data = '', $visible_to_customer = 1)
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
        $data['post_id']             = $post_id;
        $data['dateadded']           = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_log_post_activity', $data);

        $this->db->insert(db_prefix() . 'post_activity', $data);
    }   
    
    public function get_activity($id = '', $limit = '', $only_post_members_activity = false)
    {
        if (is_numeric($id)) {
            $this->db->where('post_id', $id);
        }   
        if (is_numeric($limit)) {
            $this->db->limit($limit);
        }   
        $this->db->order_by('dateadded', 'desc'); 
        $activities = $this->db->get(db_prefix() . 'post_activity')->result_array();
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
            
            if (strpos($_additional_data, 'post_status_') !== false) {
                $_additional_data = get_post_status_by_id(strafter($_additional_data, 'post_status_'));

                if (isset($_additional_data['name'])) {
                    $_additional_data = $_additional_data['name'];
                }
            }  
            
            $activities[$i]['description']     = _l($activities[$i]['description_key']);
            $activities[$i]['additional_data'] = $_additional_data;
            $activities[$i]['post_name']    = get_post_name_by_id($activity['post_id']);
            unset($activities[$i]['description_key']);
            $i++;            
        } 
        
        return $activities;
    }

    public function get_post_statuses()
    {
        $statuses = hooks()->apply_filters('before_get_post_statuses', [
            [
                'id'             => 1,
                'color'          => '#475569',
                'name'           => _l('post_status_1'),
                'order'          => 1,
                'filter_default' => true,
            ],
            [
                'id'             => 2,
                'color'          => '#2563eb',
                'name'           => _l('post_status_2'),
                'order'          => 2,
                'filter_default' => true,
            ],
            [
                'id'             => 3,
                'color'          => '#f97316',
                'name'           => _l('post_status_3'),
                'order'          => 3,
                'filter_default' => true,
            ],
            [
                'id'             => 4,
                'color'          => '#16a34a',
                'name'           => _l('post_status_4'),
                'order'          => 100,
                'filter_default' => false,
            ],
            [
                'id'             => 5,
                'color'          => '#94a3b8',
                'name'           => _l('post_status_5'),
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