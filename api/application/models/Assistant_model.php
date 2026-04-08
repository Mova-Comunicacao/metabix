<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Assistant_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
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
    public function get($filters = [], $limit = 50, $offset = 0, $where = array())
    {
        if (!empty($filters['client_id'])) {
            $this->db->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['task'])) {
            $this->db->where('task', $filters['task']);
        }     
        
        if (!empty($filters['date_from'])) {
            $this->db->where('dateadded >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where('dateadded <=', $filters['date_to'] . ' 23:59:59');
        }        

        if (!empty($where)) {
            $this->db->where($where);
        }     

        $this->db->order_by('dateadded', 'DESC');
        $this->db->limit($limit, $offset);     

        $result = $this->db->get(db_prefix() . 'assistant_log')->result();
        
        return $result;
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
        unset($data['null']);

        $data['user_id']           = $data['user_id'] ?? null;
        $data['client_id']         = $data['client_id'] ?? null;

        $data['input_chars']       = $data['input_chars'] ?? 0;
        $data['status']            = $data['status'] ?? 'success';
        $data['dateadded']         = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'assistant_log', $data);
        $insert_id = $this->db->insert_id();       
        
        if ($insert_id) {
            hooks()->do_action('after_add_assistant_log', $insert_id);
            log_activity('New Log Created [ID: ' . $insert_id . ']', 'add');  

            return $insert_id;
        }

        return false;
    }    
}