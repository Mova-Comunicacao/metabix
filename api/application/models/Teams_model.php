<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Teams_model extends Api_Model
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
    public function getAll($where = array())
    {
        $columns = [
            db_prefix() .'teams.id as id',
            db_prefix() .'teams.folder',
            db_prefix() .'teams.dateadded',
            db_prefix() .'teams.staffid',
            db_prefix() .'teams.phonenumber', 
            db_prefix() .'teams.email', 
            db_prefix() .'teams.type', 
            db_prefix() .'teams.file_avatar', 
            db_prefix() .'teams.order',
            db_prefix() .'teams_translation.name as name',
            db_prefix() .'teams_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language as language',  
        ];         
        $this->db->select($columns);
        
        $this->db->where($where);
        $this->db->join(db_prefix() . 'teams_translation', db_prefix() . 'teams.id = ' . db_prefix() . 'teams_translation.teamid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'teams_translation.languageid', 'left');        

        $this->db->group_by(db_prefix() . 'teams_translation.id');        
        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'teams')->result();            
    }

    /**
     * Get teams
     * @param  string $id    optional id
     * @param  array  $where teams where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $columns = [
            db_prefix() .'teams.id',
            db_prefix() .'teams.folder',
            db_prefix() .'teams.dateadded',
            db_prefix() .'teams.staffid',
            db_prefix() .'teams.phonenumber', 
            db_prefix() .'teams.email', 
            db_prefix() .'teams.type', 
            db_prefix() .'teams.file_avatar', 
            db_prefix() .'teams.order',
            db_prefix() .'teams_translation.name as name',
            db_prefix() .'teams_translation.description as description',
            db_prefix() .'languages.languageid as languageid',
            db_prefix() .'languages.language_cod as language_cod',               
            db_prefix() .'languages.language as language',            
        ];         
        $this->db->select($columns);
        $this->db->where($where);

        $this->db->join(db_prefix() . 'teams_translation', db_prefix() . 'teams.id = ' . db_prefix() . 'teams_translation.teamid', 'left');           
        $this->db->join(db_prefix() . 'languages',  db_prefix() . 'languages.languageid = ' . db_prefix() . 'teams_translation.languageid', 'left');        

        $this->db->group_by(db_prefix() . 'teams_translation.id');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'teams.id', $id);

            $team =  $this->db->get(db_prefix() . 'teams')->row();
            $this->api_object_cache->set('teams-' . $team->name, $team);

            return $team;
        }
        $this->db->order_by('order', 'asc');

        $teams = $this->api_object_cache->get('teams-data');

        if (!$teams && !is_array($teams)) {
            $teams = $this->db->get(db_prefix() . 'teams')->result();
            $this->api_object_cache->add('teams-data', $teams);
        }
    
        return $teams; 
    }  
    
    /**
     * Insert a new team with translations (for all active languages) and categories.
     *
     * Expected $data keys:
     *  - name (string, required)
     *  - description (string, optional)
     *  - email (string, optional)
     *  - phonenumber (string|null, optional)
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
        
        $data['dateadded']          = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_team', $data);

        $this->db->insert(db_prefix() . 'teams', $data);
        $insert_id = $this->db->insert_id();     

        if ($insert_id) {
            if (!empty($languages)) {
                foreach($languages as $l) {
                    $this->db->insert(db_prefix() . 'teams_translation', array(
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'languageid' => $l->languageid,
                        'teamid' => $insert_id,
                    ));            
                }
            }

            hooks()->do_action('after_add_team', $insert_id);
            log_activity('New Team Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;
    }  
    
    /**
     * Update team main data, categories, and translation.
     *
     * @param array $data Normalized payload. Accepts:
     *  - name (string)
     *  - description (string)
     *  - email (string|null)
     *  - phonenumber (string|null)
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

        $affectedRows = 0;

        // Se houver tradução específica
        $languageid = $data['languageid'] ?? null;
        unset($data['languageid']);

        $data['name']               = trim($data['name'] ?? '');
        $data['description']        = nl2br($data['description'] ?? '', false);

        $updateMain = [
            'name'          => $data['name'] ?? null,
            'phonenumber'   => $data['phonenumber'] ?? null,
            'email'         => $data['email'] ?? null,
            'type'          => $data['type'] ?? 1,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'teams', $updateMain);  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }   

        if($languageid) {
            $updateTranslation = [
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
            ];

            $this->db->where('teamid', $id);
            $this->db->where('languageid', $languageid);
            $this->db->update(db_prefix() . 'teams_translation', $updateTranslation);  
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }                       
        }   

        if ($affectedRows > 0) {
            log_activity('Team Updated [ID:' . $id . ']', 'update');
            hooks()->do_action('after_update_team', $id);
            return true;
        }

        return false;
    }  
    
    /**
     * Permanently delete a team and all related data.
     *
     * Deletes:
     * - teams_translation (all languages)
     * - files/pictures (via delete_picture)
     * - teams (row itself)
     *
     * Hooks:
     * - before_team_deleted($team_id)
     * - after_team_deleted($team_id)
     *
     * @param int $team_id
     * @return bool True if the team was deleted, false otherwise.
     */      
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        hooks()->do_action('before_team_deleted', $id);

        $this->db->trans_begin();
        // Use the same path that uploads used (teams/icons)
        $pictureOk = $this->delete_picture($id);

        $this->db->where('teamid', $id);
        $this->db->delete(db_prefix() . 'teams_translation');

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'teams');        
        
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
        $file = $this->db->get(db_prefix() . 'teams')->row();
        // If there is no row or no file, just ensure DB is clean and return true
        if (!$file) {
            return false;
        }

        // Build base path
        $basePath = $customPath ?: rtrim(get_upload_path_by_type('teams'), '/\\');
        $path     = $basePath . '/' . $file->id . '/'; 

        // Current avatar file (if any)
        $fileName = !empty($file->file_avatar) ? (string)$file->file_avatar : null;

        if ($fileName) {
            $fullPath = $path . $fileName;
            if (is_file($fullPath)) {
                @unlink($fullPath);

            }

            // Thumb (prefix small_)
            $thumbPath = $path . 'small_' . $fileName;
            if (is_file($thumbPath)) {
                @unlink($thumbPath);
            }               
        }

        // Null the file reference in DB
        $this->db->where('id', (int)$id);
        $this->db->update(db_prefix() . 'teams', ['file_avatar' => null]);

        // Remove folder if empty
        if (is_dir($path)) {
            $other = list_files($path);
            if (is_array($other) && count($other) === 0) {
                delete_dir($path);
            }
        }    

        hooks()->do_action('after_remove_team_file', $file->id, $fileName);

        return true;
    }  
    
    public function get_social($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'teams_social')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'teams_social')->result();        
    }

    public function get_social_teams($team_id = '')
    {
        $this->db->where('teamid', $team_id);

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'teams_social')->result();        
    }    

    public function add_social($data)
    {
        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_social', $data);

        $this->db->insert(db_prefix() . 'teams_social', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            hooks()->do_action('after_add_social', $insert_id);
            log_activity('New Social Team Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;        
    }  
    
    public function update_social($data, $id)
	{  
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'teams_social', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Client Social Updated [ID:' . $id . ']', 'update');

            hooks()->do_action('after_update_client_social', $id);

            return true;
        }

        return false;
    } 
    
    public function delete_social($id)
    {
        hooks()->do_action('before_teams_social_deleted', $id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'teams_social');  
        if ($this->db->affected_rows() > 0) {
            
            return true;
        }       
        
        return false;
    }          
}