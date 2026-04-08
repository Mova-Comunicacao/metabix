<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Users_model extends Api_Model
{
    private $contact_columns;

    public function __construct()
    {
        parent::__construct();

        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails', 'is_primary']);
        //$this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }

    /**
     * Update user data
     * @param  array  $data           $_POST data
     * @param  mixed  $id             user id
     * @param  boolean $client_request is request from customers area
     * @return mixed
     */ 
    public function update($data, $id, $send_set_password_email, $client_request = false)
	{  
        $affectedRows   = 0;
        $user           = $this->get($id);

        $send_set_password_email = $send_set_password_email ? true : false;
        $set_password_email_sent = false;   
        
        $data = hooks()->apply_filters('before_update_user', $data, $id);
        
        $this->db->where('userid', $id);
        $this->db->update(db_prefix() . 'users', $data);  
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;

            if ($affectedRows > 0) {
                hooks()->do_action('user_updated', $id, $data);
            } 
            
            return $affectedRows;
        }  
        
        return false;
    }

    /**
     * Get user member/s
     * @param  mixed $id Optional - user id
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
            $this->db->where('userid', $id); 
            $user = $this->db->get(db_prefix() . 'users')->row();

            return $user;
        }

        $this->db->order_by('firstname', 'desc');

        return $this->db->get(db_prefix() . 'users')->result_array();
    }   

    
    public function delete_user_profile_image($userid)
    {
        hooks()->do_action('before_remove_user_profile_image');
        if (file_exists(get_upload_path_by_type('users') . $userid)) {
            delete_dir(get_upload_path_by_type('users') . $userid);
        }
        $this->db->where('userid', $userid);
        $this->db->update(db_prefix() . 'users', [
            'avatar' => NULL,
        ]);

        return true;
    }   
    
    /**
     * @param array $_POST data
     * @param save_and_add_contact
     *
     * @return integer Insert ID
     *
     * Add new user to database
     */
    public function add($data, $save_and_add_contact = false)
    {
        $contact_data = [];
        // From Lead Convert to client
        if (isset($data['send_set_password_email'])) {
            $contact_data['send_set_password_email'] = true;
        }
        
        if (isset($data['donotsendwelcomeemail'])) {
            $contact_data['donotsendwelcomeemail'] = true;
        }

        $send_welcome_email = true; 

        //$data = $this->check_zero_columns($data);
        $data = hooks()->apply_filters('before_user_added', $data);
        
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
        
        /*
        $contact_data['email_verified_at'] = date('Y-m-d H:i:s');

        if (is_email_verification() && !empty($data['email'])) {
            // Verification is required on register
            $contact_data['email_verified_at']      = null;
            $contact_data['email_verification_key'] = app_generate_hash();
        }       
        */ 

        $contact_data['email']            = trim($contact_data['email']);
        $password_before_hash = '';
        if (isset($contact_data['password'])) {
            $password_before_hash         = $contact_data['password'];
            $contact_data['password']     = app_hash_password($contact_data['password']);
        }

        $this->db->insert(db_prefix() . 'users', array_merge($contact_data, [
            'datecreated' => date('Y-m-d H:i:s'),
            'token' => md5(uniqid('token')),
        ]));
        
        $client_id = $this->db->insert_id(); 
        
        if ($client_id) {

            /**
             * Used in Import, Lead Convert, Register
            */
            if ($save_and_add_contact == true) {
                $contact_id = $this->add_contact($contact_data, $client_id);
            }      

            if ($send_welcome_email == true) {
                $send = $this->send_welcome_email($contact_data, $client_id);
             }   

            $log = 'ID: ' . $client_id;
            if ($log == '' && isset($contact_id)) {
                $log = get_user_full_name($contact_id);
            }    
            
            $isStaff = null;
            
            if ($data['addedfrom']) {
                $log .= ', From Staff: ' . $data['addedfrom'];
                $isStaff = $data['addedfrom'];
            }     
            
            hooks()->do_action('after_user_created', [
                'id'            => $contact_id,
                'data'          => $data,
                'contact_data'  => $contact_data,
                //'custom_fields' => $custom_fields,
                //'groups_in'     => $groups_in,
                'with_contact'  => $save_and_add_contact,
            ]);  
            
            log_activity('New User Created [' . $log . ']', 'insert', $isStaff); 

            return $client_id;
        }

        return false;
    } 
    
    /**
     * Add new user
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register, convert to lead
     */
    public function add_contact($data, $customer_id)
    {
        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;

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
    
        $data['userid']         = $customer_id;
        
        $data['description']    = nl2br($data['description']);
        $data['datecreated']    = date('Y-m-d H:i:s'); 
        
        $data = $this->check_zero_columns($data);
        $data = hooks()->apply_filters('before_create_contact', $data);
        
        $this->db->insert(db_prefix() . 'contacts', $data);
        $contact_id = $this->db->insert_id();

        if ($contact_id) {
            
            if ($send_welcome_email == true && !empty($data['email'])) {
                $this->load->model('emails_model');
                $merge_fields = array();

                $api_merge_fields = new api_merge_fields();
                $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($data['userid'], $contact_id, $password_before_hash));
                $this->emails_model->send_email_template('customer_created_welcome_mail', $data['email'], $merge_fields);
            }   
            
            if ($send_set_password_email) {
                $this->authentication_model->set_password_email($data['email'], 0);
            }     
                        
            log_activity('Contact Created [ID: ' . $contact_id . ']', 'insert');
            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;            
        }   
        
        return false;
    }   

    public function send_welcome_email($data, $client_id)
    {
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
        }

        if ($send_welcome_email == true && !empty($data['email'])) {
            $this->load->model('emails_model');
            $merge_fields = array();

            $api_merge_fields = new api_merge_fields();
            $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($client_id));
            $send = $this->emails_model->send_email_template('customer_created_welcome_mail', $data['email'], $merge_fields);
        
            if ($send) {
                return true;
            }          
        }  

        return false;
        
    }    
    
    public function send_verification_email($id)
    {
        $contact = $this->get_contact($id);

        if (empty($contact->email)) {
            return false;
        }

        $success = send_mail_template('customer_contact_verification', $contact);

        if ($success) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'contacts', ['email_verification_sent_at' => date('Y-m-d H:i:s')]);
        }

        return $success;
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
}