<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients_model extends Api_Model
{
    private $contact_columns;

    public function __construct()
    {
        parent::__construct();
        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails', 'is_primary']);
    }    

    public function getAll()
    {
    }

    public function getTable()
    {
        $columns = [
            db_prefix() . 'clients.userid as userid',
            db_prefix() . 'clients.phonenumber as phonenumber',
            db_prefix() . 'clients.active',
            db_prefix() . 'clients.datecreated as datecreated',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'customer_groups JOIN ' . db_prefix() . 'customers_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'customers_groups.id WHERE customer_id = ' . db_prefix() . 'clients.userid ORDER by name ASC) as customerGroups',
            'CONCAT(firstname, " ", lastname) as fullname',
            'company',
            'email',
        ]; 

        $this->db->select('*');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid', 'left');        

        $this->db->order_by('company', 'asc');

        return $this->db->get(db_prefix() . 'clients')->result();        
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($client_id = '', $where = array())
    {
        $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'clients')) . ',' . get_sql_select_client_company());

        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($client_id)) {
            $this->db->where(db_prefix() . 'clients.userid', $client_id);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            if ($client && get_option('company_requires_vat_number_field') == 0) {
                $client->vat = null;
            }

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('company', 'asc');

        return $this->db->get(db_prefix() . 'clients')->result();        
    }   

    /**
     * Get customers contacts
     * @param  mixed $customer_id
     * @param  array  $where       perform where in query
     * @return array
     */
    public function get_contacts($customer_id = '', $where = array('active' => 1))
    {
        $this->db->select('*,
            CONCAT(firstname, " ", lastname) as fullname'
        );
        $this->db->where($where);
        if ($customer_id != '') {
            $this->db->where('userid', $customer_id);
        }

        $this->db->order_by('is_primary', 'DESC');

        return $this->db->get(db_prefix() . 'contacts')->result();
    }    

    /**
     * Get unique staff id's of customer admins
     * @return array
     */
    public function get_customers_admin_unique_ids()
    {
        return $this->db->query('SELECT DISTINCT(staff_id) FROM ' . db_prefix() . 'customer_admins')->result();
    }    
    
    /**
     * @param array $_POST data
     * @param save_and_add_contact
     *
     * @return integer Insert ID
     *
     * Add new client to database
     */
    public function add($data, $save_and_add_contact = false)
    {
        $contact_data = array();
        // From Lead Convert to client
        if (isset($data['send_set_password_email'])) {
            $contact_data['send_set_password_email'] = true;
        }

        if (isset($data['donotsendwelcomeemail'])) {
            $contact_data['donotsendwelcomeemail'] = true;
        }

        if (isset($data['description'])) {
            $contact_data['description'] = nl2br($data['description']);
            unset($data['description']);
        }          

        $data = $this->check_zero_columns($data);
        $data = hooks()->apply_filters('before_client_added', $data);    
        
        foreach ($this->contact_columns as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $contact_data[$field] = $data[$field];

            // Phonenumber is also used for the company profile
            if ($field != 'phonenumber') {
                unset($data[$field]);
            }
        }  
        
        // From customer profile register
        if (isset($data['contact_phonenumber'])) {
            $contact_data['phonenumber'] = $data['contact_phonenumber'];
            unset($data['contact_phonenumber']);
        }   
          
        
        $this->db->insert(db_prefix() . 'clients', array_merge($data, [
            'datecreated' => date('Y-m-d H:i:s'),
        ]));
        $client_id = $this->db->insert_id();    
        if ($client_id) {
            /**
             * Used in Import, Lead Convert, Register
             */
            if ($save_and_add_contact == true) {
                $contact_id = $this->add_contact($contact_data, $client_id, $save_and_add_contact);
            } 
            
            $log = 'ID: ' . $client_id;
            if ($log == '' && isset($contact_id)) {
                $log = get_contact_full_name($contact_id);
            }

            $isStaff = null;
            
            if ($data['addedfrom']) {
                $log .= ', From Staff: ' . $data['addedfrom'];
                $isStaff = $data['addedfrom'];
            }            
            
            hooks()->do_action('after_client_created', [
                'id'            => $client_id,
                'data'          => $data,
                'contact_data'  => $contact_data,
                //'custom_fields' => $custom_fields,
                //'groups_in'     => $groups_in,
                'with_contact'  => $save_and_add_contact,
            ]);  
            
            log_activity('New Client Created [' . $log . ']', 'insert', $isStaff);
        }

        return $client_id;
    }  

    /**
     * Add new contact
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register, convert to lead
     */
    public function add_contact($data, $customer_id, $not_manual_request = false)
    {
        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;

        $data['email_verified_at'] = date('Y-m-d H:i:s');
        
        $send_welcome_email = true;
        if (isset($data['donotsendwelcomeemail'])) {
            $send_welcome_email = false;
        }    
        
        if (defined('CONTACT_REGISTERING')) {
            $send_welcome_email = true;
            
            // Do not send welcome email if confirmation for registration is enabled
            if (get_option('customers_register_require_confirmation') == '1') {
                $send_welcome_email = false;
            }   
            
            // If client register set this contact as primary
            $data['is_primary'] = 1;
            
            if (is_email_verification_enabled() && !empty($data['email'])) {
                // Verification is required on register
                $data['email_verified_at']      = null;
                $data['email_verification_key'] = app_generate_hash();
            }            
        }   

        
        if (isset($data['is_primary'])) {
            $data['is_primary'] = 1;
            $this->db->where('userid', $customer_id);
            $this->db->update(db_prefix() . 'contacts', [
                'is_primary' => 0,
            ]);
        } else {
            $data['is_primary'] = 0;
        }     
        
        $password_before_hash = '';
        $data['userid']       = $customer_id;  
        if (isset($data['password'])) {
            $password_before_hash = $data['password'];
            $data['password']     = app_hash_password($data['password']);
        }  
        
        $data['datecreated'] = date('Y-m-d H:i:s');

        if (!$not_manual_request) {
            $data['invoice_emails']     = isset($data['invoice_emails']) ? 1 :0;
            $data['estimate_emails']    = isset($data['estimate_emails']) ? 1 :0;
            $data['credit_note_emails'] = isset($data['credit_note_emails']) ? 1 :0;
            $data['contract_emails']    = isset($data['contract_emails']) ? 1 :0;
            $data['task_emails']        = isset($data['task_emails']) ? 1 :0;
            $data['project_emails']     = isset($data['project_emails']) ? 1 :0;
            $data['ticket_emails']      = isset($data['ticket_emails']) ? 1 :0;            
        }

        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }

        $data = hooks()->apply_filters('before_create_contact', $data);   

        $this->db->insert(db_prefix() . 'contacts', $data);
        $contact_id = $this->db->insert_id();

        if ($contact_id) {

            if ($not_manual_request == true) {
                // update all email notifications to 0
                $this->db->where('id', $contact_id);
                $this->db->update(db_prefix() . 'contacts', [
                    'invoice_emails'     => 0,
                    'estimate_emails'    => 0,
                    'credit_note_emails' => 0,
                    'contract_emails'    => 0,
                    'task_emails'        => 0,
                    'project_emails'     => 0,
                    'ticket_emails'      => 0,
                ]);
            } 
            
            if ($send_welcome_email == true && !empty($data['email'])) {
                $this->load->model('emails_model');
                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($data['userid'], $contact_id, $password_before_hash));
                $this->emails_model->send_email_template('new-client-created', $data['email'], $merge_fields);
            }   

            
            log_activity('Contact Created [ID: ' . $contact_id . ']', 'insert');
            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;            
        }

        return false;
    } 

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */
    public function update($data, $id, $client_request = false)
    {
        $updated = false;
        $data['description']    = nl2br($data['description']);

        $data    = $this->check_zero_columns($data);

        $data = hooks()->apply_filters('before_client_updated', $data, $id);

        $this->db->where('userid', $id);
        $this->db->update(db_prefix() . 'clients', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        hooks()->do_action('client_updated', [
            'id'                            => $id,
            'data'                          => $data,
            //'update_all_other_transactions' => $update_all_other_transactions,
            //'update_credit_notes'           => $update_credit_notes,
            //'custom_fields'                 => $custom_fields,
            //'groups_in'                     => $groups_in,
            //'updated'                       => &$updated,
        ]);

        if ($updated) {
            log_activity('Client Info Updated [ID: ' . $id . ']', 'update');
        }

        return $updated;        
    }  
    
    /**
     * @param  integer ID
     * @return boolean
     * Delete client, also deleting rows from, dismissed client announcements, ticket replies, tickets, autologin, user notes
     */
    public function delete($id)
    {

        hooks()->do_action('before_client_deleted', $id);

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'clients');
        if ($this->db->affected_rows() > 0) {
            if (is_dir(get_upload_path_by_type('client_logo_images') . $id)) {
                delete_dir(get_upload_path_by_type('client_logo_images') . $id);
            }

            // Delete all user contacts
            $this->db->where('userid', $id);
            $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();
            foreach ($contacts as $c) {
                $this->delete_contact($c['id']);
            }  
            
            // Delete all user social
            $this->db->where('clientid', $id);
            $social = $this->db->get(db_prefix() . 'clients_social')->result_array();
            foreach ($social as $s) {
                $this->delete_social($s['id']);
            }           
            
            hooks()->do_action('after_client_deleted', $id);
    
            log_activity('Client Deleted [ID: ' . $id . ']', 'deleted');

        }  
        
        return true;            
    }   
    
    /**
     * Delete customer contact
     * @param  mixed $id contact id
     * @return boolean
     */
    public function delete_contact($id)
    {
        hooks()->do_action('before_delete_contact', $id);

        $this->db->where('id', $id);
        $result      = $this->db->get(db_prefix() . 'contacts')->row();
        $customer_id = $result->userid;     
        
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'contacts'); 
        
        if ($this->db->affected_rows() > 0) {
            
            hooks()->do_action('contact_deleted', $id, $result);

            return true;            
        }

        return false;
    }   
    
    public function delete_picture($userid)
    {
        $this->db->where('userid', $userid);
        $file = $this->db->get(db_prefix() . 'clients')->row();
        if ($file) {
            $path     = get_upload_path_by_type('client_logo_images') . $userid . '/';
            $fullPath = $path . $file->logo_image;     
            if ($fullPath && file_exists($fullPath)) {
                @unlink($fullPath);                 
            }

            $this->db->where('userid', $userid);
            $this->db->update(db_prefix() . 'clients', array(
                'logo_image' => NULL
            ));            
        }  

        return true;
    }     
   
    public function get_social($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'clients_social')->row();
        }

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'clients_social')->result();        
    }

    public function get_social_proejcts($client_id = '')
    {
        $this->db->where('clientid', $client_id);

        $this->db->order_by('order', 'asc');

        return $this->db->get(db_prefix() . 'clients_social')->result();        
    }    

    public function add_social($data)
    {
        unset($data['null']);
        $data['dateadded']      = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_add_social', $data);

        $this->db->insert(db_prefix() . 'clients_social', $data);
        $insert_id = $this->db->insert_id();     
        if ($insert_id) {
            hooks()->do_action('after_add_social', $insert_id);
            log_activity('New Social Client Created [ID: ' . $insert_id . ']', 'add');

            return $insert_id;
        }   

        return false;        
    }  
    
    public function update_social($data, $id)
	{  
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'clients_social', $data);  
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Client Social Updated [ID:' . $id . ']', 'update');

            hooks()->do_action('after_update_client_social', $id);
            return true;
        }

        return false;
    } 
    
    public function delete_social($id)
    {
        hooks()->do_action('before_clients_social_deleted', $id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'clients_social');         
        
        return true;
    }      
    
    private function check_zero_columns($data)
    {
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }

        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }

        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }

        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        return $data;        
    }

    public function get_clients_distinct_countries()
    {
        return $this->db->query('SELECT DISTINCT(country_id), short_name FROM ' . db_prefix() . 'clients JOIN ' . db_prefix() . 'countries ON ' . db_prefix() . 'countries.country_id=' . db_prefix() . 'clients.country')->result_array();
    }    
}