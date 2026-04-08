<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin_model extends APIModel
{
    public function getAll()
	{
        $this->db->select('
            staffid,
            adminFirstName,
            adminLastName,
            adminEmail,
            adminPhone,
            adminAltPhone,
            adminAddress,
            adminWebsite,
            adminAvatar,
            admin,
            adminRole,
            superuser,
            createDate,
            username,
            language,
            token,
            isActive                         
        ');     
        $this->db->order_by('staffid', 'desc');        

        return $this->db->get('admins')->result();
    }

    /**
     * Get admin member/s
     * @param  mixed $id Optional - admin id
     * @param  mixed $where where in query
     * @return mixed if id is passed return object else array
     */
    public function get($id = '', $where = array())
    {
        $this->db->select('
            staffid,
            adminFirstName,
            adminLastName,
            adminEmail,
            adminPhone,
            adminAltPhone,
            adminAddress,
            adminWebsite,
            adminAvatar,
            admin,
            adminRole,
            superuser,
            createDate,
            username,
            password,
            language,
            token,
            isActive      
        ');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('staffid', $id); 
            $admin = $this->db->get('admins')->row();
        }
        
        return $admin;
    }  

	public function insert($data)
	{

        $data['createDate'] = date('Y-m-d');
        $data['password'] = md5('password');
        $data['token'] = md5(uniqid('token'));

        $this->db->insert('admins', $data);
		$insert_id = $this->db->insert_id();
        if ($insert_id) {
            return $insert_id;
        }

        return false;        
	}    

    public function update($id, $data)
	{  
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = app_hash_password($data['password']);
            //$data['last_password_change'] = date('Y-m-d H:i:s');
        }

		$this->db->where('staffid', $id);
		$this->db->update('admins', $data); 
        if ($this->db->affected_rows() > 0) {
            //log_activity('Admin Profile Updated [Admin: ' . $insert_id . ', Nome: ' . $data['categoryName'] . ']', 'insert', $cat->staffid);

            return true;
        }

        return false;          
    }   
    
	public function deleteAdmin($id)
	{
		$this->db->where('staffid', $id);
		$this->db->delete('admins');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('staffid', $id);
            $this->db->delete('assignedprojects');
        }
	}     
    
	public function upload($id, $data)
	{
		$this->db->where('staffid', $id);
		$this->db->update('admins', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;        
	}    

	public function deleteAvatar($id)
	{
        $this->db->where('staffid', $id);
        $file = $this->db->get('admins')->row();
        if ($file) {
            //$cat = $this->get($file->staffid);
            $path = 'uploads/avatars'; 
            $fullPath = $path.$file->adminAvatar; 
            if ($file->adminAvatar && file_exists($fullPath)) {
                unlink($fullPath);
            } 
            
            $this->db->where('staffid', $id);
            $this->db->update('admins', array(
                'adminAvatar' => NULL
            )); 
            
            return true;
        }
        
        return false;
	}   
 
    /**
     * @param  boolean Is Admin or User
     * @param  integer ID
     * @param  string
     * @param  string
     * @return boolean
     * User reset password after successful validation of the key
     */    
	public function set_password($id, $password)
	{   
        $password = app_hash_password($password);

		$this->db->where('staffid', $id);
		$this->db->update('admins', array(
            'password' => $password
        )); 
        if ($this->db->affected_rows() > 0) {
            log_activity('Admin Profile Updated [Admin: ' . get_admin_full_name($id) . ']', 'edit', $id);

            return true;
        }       
	}     
}