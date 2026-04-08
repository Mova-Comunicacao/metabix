<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contacts_model extends Api_Model 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }

	public function getAll()
	{
        $columns = [
            'id',
            db_prefix() . 'clients.userid',
            'firstname',
            'lastname',
            'CONCAT(firstname, " ", lastname) as fullname',
            'email',
            db_prefix() . 'clients.phonenumber',
            db_prefix() . 'clients.datecreated',
            db_prefix() . 'clients.active',
            'profile_image',
            'is_primary',
            'last_login',
            'company'
        ]; 

        $this->db->select($columns);
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'contacts.userid', 'left');

        $this->db->order_by('company', 'asc');

        return $this->db->get(db_prefix() . 'contacts')->result();        
    }

    public function getTable()
    {
        $columns = [
            'id',
            'userid',
            'firstname',
            'lastname',
            'CONCAT(firstname, " ", lastname) as fullname',
            'email',
            'profile_image',
            'phonenumber',
            'datecreated',
            'active',
            'is_primary',
            'last_login',
        ]; 

        $this->db->select($columns);
        $this->db->order_by('is_primary', 'desc');

        return $this->db->get(db_prefix() . 'contacts')->result();             
    }    

    /**
     * Get contact object based on passed contactid if not passed contactid return array of all contacts
     * @param  mixed $id contact id
     * @param  array  $where
     * @return mixed
     */
	public function get($id)
	{
        $this->db->select('*,
            CONCAT(firstname, " ", lastname) as fullname'
        );
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'contacts')->row();
    }     

    /**
     * Add new contact
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register, convert to lead
     */
    public function add($data, $send_set_password_email, $not_manual_request = false)
    {
        $send_set_password_email = $send_set_password_email ? true : false;

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


        if (isset($data['is_primary']) && $data['is_primary'] == true) {
            $data['is_primary'] = 1;
            $this->db->where('userid', $data['userid']);
            $this->db->update(db_prefix() . 'contacts', [
                'is_primary' => 0,
            ]);
        } else {
            $data['is_primary'] = 0;
        }   

        $password_before_hash = '';
        $data['userid']       = $data['userid'];
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

        $data['email'] = trim($data['email']);

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
                $api_merge_fields = new api_merge_fields();
                $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($data['userid'], $contact_id, $password_before_hash));
                $this->emails_model->send_email_template('new-client-created', $data['email'], $merge_fields);
            }   
            
            if ($send_set_password_email) {
                $this->authentication_model->set_password_email($data['email'], false);
            }   

            log_activity('Contact Create [ID:' . $contact_id . ']', 'add');
            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;            
        }

        return false;
    } 

    /**
     * Update contact data
     * @param  array  $data           $_POST data
     * @param  mixed  $id             contact id
     * @param  boolean $client_request is request from customers area
     * @return mixed
     */ 
    public function update($data, $id, $send_set_password_email, $client_request = false)
	{  
        $affectedRows = 0;
        $contact      = $this->get($id);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password']             = app_hash_password($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }
        
        $send_set_password_email = $send_set_password_email ? true : false;
        $set_password_email_sent = false;
        
        $data['is_primary'] = isset($data['is_primary']) ? 1 : 0;
        
        // Contact cant change if is primary or not
        if ($client_request == true) {
            unset($data['is_primary']);
        }
        
        if ($client_request == false) {
            $data['invoice_emails']     = isset($data['invoice_emails']) ? 1 :0;
            $data['estimate_emails']    = isset($data['estimate_emails']) ? 1 :0;
            $data['credit_note_emails'] = isset($data['credit_note_emails']) ? 1 :0;
            $data['contract_emails']    = isset($data['contract_emails']) ? 1 :0;
            $data['task_emails']        = isset($data['task_emails']) ? 1 :0;
            $data['project_emails']     = isset($data['project_emails']) ? 1 :0;
            $data['ticket_emails']      = isset($data['ticket_emails']) ? 1 :0;
        }  
        
        $data = hooks()->apply_filters('before_update_contact', $data, $id);
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', $data);
        
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if (isset($data['is_primary']) && $data['is_primary'] == 1) {
                $this->db->where('userid', $contact->userid);
                $this->db->where('id !=', $id);
                $this->db->update(db_prefix() . 'contacts', [
                    'is_primary' => 0,
                ]);
            }            
        }  
        
        if ($client_request == false) {
            if ($send_set_password_email) {
                $set_password_email_sent = $this->authentication_model->set_password_email($data['email'], false);
            }            
        }
        
        if (($client_request == true) && $send_set_password_email) {
            $set_password_email_sent = $this->authentication_model->set_password_email($data['email'], false);
        }
        
        if ($affectedRows > 0) {
            hooks()->do_action('contact_updated', $id, $data);
        }

        if ($affectedRows > 0 && !$set_password_email_sent) {
            log_activity('Contact Updated [ID:' . $id . ']', 'update');

            return true;
        } elseif ($affectedRows > 0 && $set_password_email_sent) {
            return [
                'set_password_email_sent_and_profile_updated' => true,
            ];
        } elseif ($affectedRows == 0 && $set_password_email_sent) {
            return [
                'set_password_email_sent' => true,
            ];
        }

        return false;          
    }  

    /**
     * Delete customer contact
     * @param  mixed $id contact id
     * @return boolean
     */ 
	public function delete($id)
	{
        hooks()->do_action('before_delete_contact', $id);

        //$this->db->where('id', $id);
        //$result      = $this->db->get(db_prefix() . 'contacts')->row();
        //$customer_id = $result->userid;

        $last_activity = get_last_system_activity_id();

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'contacts');

        if ($this->db->affected_rows() > 0) {
            if (is_dir(get_upload_path_by_type('contact_profile_images') . $id)) {
                delete_dir(get_upload_path_by_type('contact_profile_images') . $id);
            }
                        
            if (is_gdpr()) {
                $contactActivityQuery = false;
                if (!empty($result->email)) {
                    $this->db->or_like('description', $result->email);
                    $contactActivityQuery = true;
                }
                if (!empty($result->firstname)) {
                    $this->db->or_like('description', $result->firstname);
                    $contactActivityQuery = true;
                }
                if (!empty($result->lastname)) {
                    $this->db->or_like('description', $result->lastname);
                    $contactActivityQuery = true;
                }

                if (!empty($result->phonenumber)) {
                    $this->db->or_like('description', $result->phonenumber);
                    $contactActivityQuery = true;
                }

                if (!empty($result->last_ip)) {
                    $this->db->or_like('description', $result->last_ip);
                    $contactActivityQuery = true;
                }

                if ($contactActivityQuery) {
                    $this->db->delete(db_prefix() . 'activity_log');
                }                
            }

           // Delete activity log caused by delete contact function
           if ($last_activity) {
                $this->db->where('id >', $last_activity->id);
                $this->db->delete(db_prefix() . 'activity_log');
            }
            
            hooks()->do_action('contact_deleted', $id, $result);

            return true;            
        }

        return true;
	}   

    public function update_status($data, $id, $client_request = false) 
    {
        $affectedRows = 0;
        $contact      = $this->get($id);

        $data['is_primary'] = isset($data['is_primary']) ? 1 : 0;

        // Contact cant change if is primary or not
        if ($client_request == true) {
            unset($data['is_primary']);
        }   
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', $data); 
        
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if (isset($data['is_primary']) && $data['is_primary'] == 1) {
                $this->db->where('userid', $contact->userid);
                $this->db->where('id !=', $id);
                $this->db->update(db_prefix() . 'contacts', [
                    'is_primary' => 0,
                ]);
            }
        }  
        
        if ($affectedRows > 0) {
            hooks()->do_action('contact_updated', $id, $data);

            return true;
        }

        return false;
    }
  
    public function delete_contact_profile_image($id)
    {
        hooks()->do_action('before_remove_contact_profile_image');
        if (file_exists(get_upload_path_by_type('contact_profile_images') . $id)) {
            delete_dir(get_upload_path_by_type('contact_profile_images') . $id);
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', [
            'profile_image' => NULL,
        ]);

        return true;
    }    
}