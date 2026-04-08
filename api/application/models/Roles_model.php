<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles_model extends Api_Model
{
    /**
     * Get employee role by id
     * @param  mixed $id Optional role id
     * @return mixed     array if not id passed else object
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $role = $this->api_object_cache->get('role-' . $id);

            if ($role) {
                return $role;
            }

            $this->db->where('roleid', $id);

            $role = $this->db->get(db_prefix() . 'roles')->row();
            if(isset($role->permissions) && !is_null(isset($role->permissions))) {
                $role->permissions = !empty($role->permissions) ? unserialize($role->permissions) : [];
            }

            $this->api_object_cache->add('role-' . $id, $role);

            return $role;
        }

        return $this->db->get(db_prefix() . 'roles')->result();
    }   
    
    public function get_role_staff($role_id)
    {
        $this->db->where('role', $role_id);

        return $this->db->get(db_prefix() . 'staff')->result();
    }    
}