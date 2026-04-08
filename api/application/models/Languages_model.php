<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Languages_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get language
     * @param  string $id    optional id
     * @param  array  $where perform where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $this->db->select('*,
            language_cod as code'
        );        
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'languages')->row();
        }
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'languages')->result();
    }       
}