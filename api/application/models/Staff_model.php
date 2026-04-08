<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Staff_model extends Api_Model
{
    public function getAll()
	{
        $this->db->select('
            staffid,
            firstname,
            lastname,
            CONCAT(firstname,\' \',lastname) as fullname,
            email,
            phone,
            altphone,
            address,
            website,
            avatar,
            admin,
            role,
            datecreated,
            username,
            default_language,
            token,
            active                         
        ');     
        $this->db->order_by('staffid', 'desc');        

        return $this->db->get(db_prefix() . 'staff')->result();
    }

    /**
     * Get admin member/s
     * @param  mixed $id Optional - admin id
     * @param  mixed $where where in query
     * @return mixed if id is passed return object else array
     */
    public function get($id = '', $active = '', $where = array())
    {
        $select_str = '*,CONCAT(firstname,\' \',lastname) as fullname';

        $this->db->select($select_str);
        $this->db->where($where);

        if (is_int($active)) {
            $this->db->where('active', $active);
        }   

        if (is_numeric($id)) {
            $this->db->where('staffid', $id); 
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if ($staff) {
                $staff->permissions = $this->get_staff_permissions($id);
            }
                        
            return $staff;
        }

        $this->db->order_by('firstname', 'desc');

        return $this->db->get(db_prefix() . 'staff')->result();
    }  

    /**
     * Add new staff member
     *
     * Notes:
     * - Expects sanitized/validated $data in the controller (email unique, role exists, etc.)
     * - Hashes password and sets creation timestamp
     * - Normalizes admin flag (compatible with legacy "administrator"/"admin" fields)
     * - Optionally sends welcome email when requested
     * - Returns the new staff ID or false on failure
     *
     * @param  array $data Staff POST data
     * @return int|false
     */
	public function add($data)
	{
        // Allow external filters to modify payload before insert
        $data = hooks()->apply_filters('before_create_staff_member', $data);        

        // Admin flag normalization (supports "admin" or legacy "administrator")
        // Only allow current admins to set admin flag; otherwise force 0
        $isAdminCaller = function_exists('is_admin') ? is_admin() : false;
        $requestedAdmin = 0;

        if (isset($data['admin'])) {
            $requestedAdmin = !empty($data['admin']) ? 1 : 0;
            unset($data['admin']);
        } elseif (isset($data['administrator'])) {
            $requestedAdmin = !empty($data['administrator']) ? 1 : 0;
            unset($data['administrator']);
        }

        $data['admin'] = $isAdminCaller ? $requestedAdmin : 0;


        //  Welcome email flag (supports "send_welcome_email" or "send_mail")
        $sendWelcome = false;
        if (isset($data['send_welcome_email'])) {
            $sendWelcome = !empty($data['send_welcome_email']);
            unset($data['send_welcome_email']);
        } elseif (isset($data['send_mail'])) {
            $sendWelcome = !empty($data['send_mail']);
            unset($data['send_mail']);
        }

        $original_password = isset($data['password']) ? (string)$data['password'] : '';
                  
        $data['password']    = app_hash_password($original_password);
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['token']       = function_exists('app_generate_hash') ? app_generate_hash() : md5(uniqid('token', true));    

        if (empty($data['default_language'])) {
            if (function_exists('get_staff_default_language')) {
                $lang = get_staff_default_language();
                $data['default_language'] = $lang !== '' ? $lang : get_option('active_language');
            } else {
                $data['default_language'] = get_option('active_language');
            }
        }        

        $this->db->insert(db_prefix() . 'staff', $data);
        $staffid = (int)$this->db->insert_id();
        if ($staffid > 0) {
            $slug = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
            if ($slug === '') {
                $slug = 'unknown-' . $staffid;
            }
            
            if ($sendWelcome && !empty($data['email'])) {
                $this->load->model('emails_model');
                
                $merge_fields = [];
                if (class_exists('api_merge_fields')) {
                    $api_merge_fields = new api_merge_fields();
                    $merge_fields = array_merge($merge_fields, $api_merge_fields->get_staff_merge_fields($staffid, $original_password));
                } else {
                    $merge_fields = [
                        '{staff_firstname}' => $data['firstname'] ?? '',
                        '{staff_lastname}'  => $data['lastname'] ?? '',
                        '{staff_email}'     => $data['email'] ?? '',
                        '{staff_password}'  => $original_password,
                    ];
                }
                $this->emails_model->send_email_template('new-staff-created', $data['email'], $merge_fields);
            }    

            log_activity('New Staff Member Added [ID: ' . $staffid . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']', 'insert');
            hooks()->do_action('staff_member_created', $staffid);

            return $staffid;
        }

        return false;        
	}    

    /**
     * Update staff member information
     *
     * This method handles staff data updates, including:
     * - Password hashing and last change date
     * - Permissions updates
     * - Admin flag normalization
     * - Hook actions before and after update
     * - Activity logging
     *
     * @param  int   $id   Staff ID
     * @param  array $data Staff data
     * @return bool         True if one or more fields were updated, false otherwise
     */
    public function update($id, $data)
	{  
        $data = hooks()->apply_filters('before_update_staff_member', $data, $id);

        // Admin handling logic
        // Only admins can update the "admin" flag
        if (is_admin()) {
            if (isset($data['admin'])) {
                $data['admin'] = 1;
                unset($data['admin']); // Prevent direct update if restricted elsewhere
            } else {
                $data['admin'] = 0;
            }
        }

        $affectedRows = 0;

        // Handle password hashing and last change timestamp
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = app_hash_password($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }

        // Extract permissions array if provided
        $permissions = [];
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }

        // Determine if user is not staff
        if (isset($data['is_not_staff'])) {
            $data['is_not_staff'] = 1;
        } else {
            $data['is_not_staff'] = 0;
        }

        // If user is admin, always mark as staff (is_not_staff = 0)
        if (isset($data['admin']) && $data['admin'] == 1) {
            $data['is_not_staff'] = 0;
        }

        // Update main staff table
        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'staff', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        // Update permissions (only if not admin)
        $isAdmin = isset($data['admin']) && $data['admin'] == 1;
        if ($this->update_permissions($isAdmin ? [] : $permissions, $id)) {
            $affectedRows++;
        }

        // If anything changed, log activity and fire hook
        if ($affectedRows > 0) {
            log_activity('Staff Member Updated [ID: ' . $id . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']', 'update');
            hooks()->do_action('staff_member_updated', $id);

            return true;            
        }

        return false;          
    }   
    
    /**
     * Permanently delete a staff member.
     *
     * @param  int $id
     * @return bool True on success, false otherwise
     */    
	public function delete($id)
	{
        $sid = (int)$id;
        if ($sid <= 0) {
            return false;
        }

        // Fire a "before" hook with context (id only; row may not exist)
        hooks()->do_action('before_delete_staff_member', ['id' => $sid]);

        // Fetch row first; if not found, nothing to delete
        $this->db->where('staffid', $sid);
        $row = $this->db->get(db_prefix() . 'staff')->row();
        if (!$row) {
            return false;
        }

        // Keep name for logging (safe fallback)
        $name = get_staff_full_name($sid);
        if ($name === '' || $name === null) {
            $name = trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')) ?: ('ID: ' . $sid);
        }
        
        // Begin transaction to keep things consistent
        $this->db->trans_begin();        
        
        //  Remove avatar (safe, ignores if not present)
        $this->deleteAvatar($sid);

        // Delete related/child records (adjust to your schema)
        // Permissions table
        if ($this->db->table_exists(db_prefix().'staff_permissions')) {
            $this->db->where('staff_id', $sid);
            $this->db->delete(db_prefix().'staff_permissions');
        }

        $this->db->where('staffid', $sid);
        $this->db->delete(db_prefix() . 'staff');

        // Check transaction status and affected rows
        if ($this->db->trans_status() === false || $this->db->affected_rows() <= 0) {
            // Something went wrong: rollback and bail
            $this->db->trans_rollback();
            return false;
        }

        // Commit changes
        $this->db->trans_commit();        

        // Log and fire "after" hook
        log_activity('Staff Member Deleted [ID: ' . $sid . ', Name: ' . $name . ']', 'deleted');
        hooks()->do_action('staff_member_deleted', ['id' => $sid]);

        return true;
	}     
    
	public function upload($id, $data)
	{
		$this->db->where('staffid', $id);
		$this->db->update(db_prefix() . 'staff', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;        
	}    

    /**
     * Delete staff avatar (file + thumbnails) and null the DB field.
     *
     * @param int $id Staff ID
     * @return bool
 */    
	public function deleteAvatar($id)
	{

        $sid = (int) $id;
        if ($sid <= 0) {
            return false;
        }

        // Fetch staff row
        $this->db->where('staffid', $sid);
        $row = $this->db->get(db_prefix() . 'staff')->row();
        if (!$row || empty($row->avatar)) {
            // Nothing to delete
            return false;
        }

        // Base path: /uploads/staff/{staffid}/
        $basePath = rtrim(get_upload_path_by_type('staff'), '/').'/'.$row->staffid.'/';

        // Fire "before" hook so listeners can react (audit, etc.)
        hooks()->do_action('before_remove_staff_profile_image', $sid);

        // Main avatar full path
        $avatarFile = $basePath . $row->avatar;

        // Delete main avatar if present
        if (is_file($avatarFile)) {
            @unlink($avatarFile);

            // Build thumb names from original basename
            $fname = pathinfo($avatarFile, PATHINFO_FILENAME);
            $fext  = pathinfo($avatarFile, PATHINFO_EXTENSION);

            // Known thumb variants (explicit)
            $thumbs = [
                $basePath . 'small_' . $fname . '.' . $fext,
                $basePath . 'thumb_' . $fname . '.' . $fext,
            ];

            // Delete known thumbnails if present
            foreach ($thumbs as $t) {
                if (is_file($t)) {
                    @unlink($t);
                }
            }

            // Optional: delete any other derivative with pattern if your stack creates more
            // Example: small_*, thumb_*, or any "*_{$fname}.*"
            foreach (['small_', 'thumb_'] as $prefix) {
                foreach (glob($basePath . $prefix . $fname . '.*') ?: [] as $g) {
                    if (is_file($g)) {
                        @unlink($g);
                    }
                }
            }
        }  
        
        // Null avatar field in DB
        $this->db->where('staffid', $sid);
        $this->db->update(db_prefix() . 'staff', ['avatar' => null]);

        // Clean up directory if empty
        $dir = rtrim(get_upload_path_by_type('staff'), '/').'/'.$row->staffid;
        if (is_dir($dir)) {
            $files = list_files($dir); // project helper
            if (is_array($files) && count($files) === 0) {
                delete_dir($dir); // project helper
            }
        }

        // Fire "after" hook for listeners
        hooks()->do_action('after_remove_staff_profile_image', $sid);

        return true;
    }   
 
    /**
     * Persist a new password for a staff member
     * - Hashes the password with app_hasher()
     * - Updates last_password_change timestamp
     * - (Optional) invalidates sessions/tokens if your app supports it
     *
     * @param int    $id
     * @param string $password Plain-text new password
     * @return bool
     */
	public function change_password($id, $password)
	{   

        $sid = (int) $id;
        if ($sid <= 0 || $password === '') {
            return false;
        }

        $data = [
            'password'             => app_hash_password($password),
            'last_password_change' => date('Y-m-d H:i:s'),
        ];        

        $this->db->where('staffid', $sid);
        $this->db->update(db_prefix() . 'staff', $data);

        if ($this->db->affected_rows() > 0) {
            // Optional: invalidate tokens/sessions here if you have such tables
            // $this->session_model->invalidate_by_staff($sid);
            log_activity('Staff Password Changed [Admin: ' . get_staff_full_name($id) . ']', 'update', $id);

            return true;
        }   
        
        return false;
	}   
    
    /**
     * Get staff permissions
     * @param  mixed $id staff id
     * @return array
     */
    public function get_staff_permissions($id)
    {
        // Fix for version 2.3.1 tables upgrade
        if (defined('DOING_DATABASE_UPGRADE')) {
            return [];
        }

        $permissions = $this->api_object_cache->get('staff-' . $id . '-permissions');

        if (!$permissions && !is_array($permissions)) {
            $this->db->where('staff_id', $id);
            $permissions = $this->db->get('staff_permissions')->result_array();

            $this->api_object_cache->add('staff-' . $id . '-permissions', $permissions);
        }

        return $permissions;
    } 

    public function update_permissions($permissions, $id)
    {
        
        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'staff_permissions');

        $is_staff_member = is_staff_member($id);
        
        foreach ($permissions as $feature => $capabilities) {
            //foreach ($capabilities['capability'] as $capability) {

                // Maybe do this via hook.
                if ($capabilities['feature'] == 'leads' && !$is_staff_member) {
                    continue;
                }

                $this->db->insert(db_prefix() . 'staff_permissions', ['staff_id' => $id, 'feature' => $capabilities['feature'], 'capability' => $capabilities['capability']]);

            //}
        }

        return true;
    }

    public function get_logged_time_data($id = '', $filter_data = array())
    {
        if ($id == '') {
            $id = get_staff_user_id();
        }
    } 
}