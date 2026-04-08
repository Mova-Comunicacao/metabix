<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Misc_model extends Api_Model
{
    public $notifications_limit;

    public function __construct()
    {
        parent::__construct();
        $this->notifications_limit = 15;
    }    

    public function _search_staff($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'staff',
            'search_heading' => _l('staff_members'),
        ];

        if (has_permission('staff', '', 'view')) {
            // Staff
            $this->db->select();
            $this->db->from(db_prefix() . 'staff');
            $this->db->like('firstname', $q);
            $this->db->or_like('lastname', $q);
            $this->db->or_like("CONCAT(firstname, ' ', lastname)", $q, false);
            $this->db->or_like("CONCAT(lastname, ' ', firstname)", $q, false);
            //$this->db->or_like('facebook', $q);
            //$this->db->or_like('linkedin', $q);
            //$this->db->or_like('phonenumber', $q);
            //$this->db->or_like('email', $q);
            //$this->db->or_like('skype', $q);

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('firstname', 'ASC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }    
}