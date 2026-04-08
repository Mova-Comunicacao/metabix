<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api_merge_fields
{

    /**
     * Codeigniter instance
     * @var object
     */
    protected $ci;
        
    public function __construct()
    {
        $this->ci = &get_instance();
    }
    
    /**
     * General merge fields not linked to any features
     * @return array
     */
    public function get_other_merge_fields()
    {
        $CI =& get_instance();
        $fields                          = array();
        $fields['{logo_url}']            = base_url('uploads/logos/' . get_option('company_logo'));

        $logo_width = hooks()->apply_filters('merge_field_logo_img_width', '');
        $fields['{logo_image_with_url}'] = '<a href="' . site_url() . '" target="_blank"><img src="' . base_url('uploads/logos/' . get_option('company_logo')) . '"'.($logo_width != '' ? ' width="'.$logo_width.'"' : '').'></a>';

        $fields['{dark_logo_image_with_url}'] = '';
        if (get_option('company_logo_dark') != '') {
            $fields['{dark_logo_image_with_url}'] = '<a href="' . site_url() . '" target="_blank"><img src="' . base_url('uploads/logos/' . get_option('company_logo_dark')) . '"' . ($logo_width != '' ? ' width="' . $logo_width . '"' : '') . '></a>';
        }

        $fields['{site_url}']            = rtrim(site_url(), '/');
        $fields['{admin_url}']           = admin_url();
        $fields['{main_domain}']         = get_option('main_domain');
        $fields['{business_name}']       = get_option('business_name');

        if (!is_staff_logged_in() || is_client_logged_in()) {
            $fields['{email_signature}'] = get_option('email_signature');
        } else {
            $this->ci->db->select('email_signature')->from(db_prefix() . 'staff')->where('staffid', get_staff_user_id());
            $signature = $this->ci->db->get()->row()->email_signature;
            if (empty($signature)) {
                $fields['{email_signature}'] = get_option('email_signature');
            } else {
                $fields['{email_signature}'] = $signature;
            }
        }

        if(!is_html($fields['{email_signature}'])) {
            $fields['{email_signature}'] = nl2br($fields['{email_signature}']);
        }    

        $hook_data['merge_fields'] = $fields;
        $hook_data['fields_to']    = 'other';
        $hook_data['id']           = '';

        $hook_data = hooks()->apply_filters('other_merge_fields', $hook_data);
        $fields    = $hook_data['merge_fields'];

        return $fields;
    }
    /**
     * Merge field for staff members
     * @param  mixed $staff_id staff id
     * @param  string $password password is used only when sending welcome email, 1 time
     * @return array
     */
    public function get_staff_merge_fields($staff_id, $password = '')
    {
        $fields = array();

        $CI =& get_instance();
        $CI->db->where('staffid', $staff_id);
        $staff = $CI->db->get(db_prefix() . 'staff')->row();

        $fields['{password}']           = '';
        $fields['{new_pass_key}']       = '';
        $fields['{staff_firstname}']    = '';
        $fields['{staff_lastname}']     = '';
        $fields['{staff_email}']        = '';
        $fields['{staff_datecreated}']  = '';

        $fields['{new_password_url}']   = '';

        if (!$staff) {
            return $fields;
        }

        if ($password != '') {
            $fields['{password}'] = $password;
        }
        

        if ($staff->two_factor_auth_code) {
            $fields['{two_factor_auth_code}'] = $staff->two_factor_auth_code;
        }

        $fields['{staff_firstname}']    = $staff->firstname;
        $fields['{staff_lastname}']     = $staff->lastname;
        $fields['{staff_email}']        = $staff->email;
        $fields['{staff_datecreated}']  = $staff->datecreated;
        $fields['{new_pass_key}']       = $staff->new_pass_key;


        $custom_fields = get_custom_fields('staff');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($staff_id, $field['id'], 'staff');
        }

        $hook_data['merge_fields'] = $fields;
        $hook_data['fields_to']    = 'staff';
        $hook_data['id']           = $staff_id;

        $hook_data = hooks()->apply_filters('staff_merge_fields', $hook_data);
        $fields    = $hook_data['merge_fields'];

        return $fields;
    }

    /**
     * Merge fields for Contacts and Customers
     * @param  mixed $client_id
     * @param  string $contact_id
     * @param  string $password   password is used when sending welcome email, only 1 time
     * @return array
     */
    public function get_client_contact_merge_fields($client_id = '', $contact_id = '', $password = '')
    {
        $fields = array();

        if ($contact_id == '') {
            $contact_id = get_primary_contact_user_id($client_id);
        }

        $fields['{company_name}']       = get_option('company_name');

        $fields['{contact_firstname}']  = '';
        $fields['{contact_lastname}']   = '';
        $fields['{contact_email}']      = '';
        $fields['{client_company}']     = '';
        $fields['{client_phonenumber}'] = '';
        //$fields['{client_country}']     = '';
        $fields['{client_city}']        = '';
        $fields['{client_zip}']         = '';
        $fields['{client_state}']       = '';
        $fields['{client_address}']     = '';
        $fields['{password}']           = '';
        $fields['{new_pass_key}']       = '';
        //$fields['{client_vat_number}']  = '';

        //$fields['{contact_public_consent_url}']        = '';
        $fields['{email_verification_url}']            = '';
        $fields['{customer_profile_files_admin_link}'] = '';    

        $CI =& get_instance();

        if ($client_id == '') {
            return $fields;
        }    
        
        //$client = $CI->clients_model->get($client_id);
        //$client = $CI->db->select()->where('userid', $client_id)->get(db_prefix() . 'clients')->row();
        //if (!$client) {
        //     return $fields;
        //}
        
        $CI->db->where('userid', $client_id);
       // $CI->db->where('id', $contact_id);
        $contact = $CI->db->get(db_prefix() . 'users')->row();
        
        if ($contact) {
            $fields['{contact_firstname}']          = $contact->firstname;
            $fields['{contact_lastname}']           = $contact->lastname;
            $fields['{contact_email}']              = $contact->email;
            $fields['{contact_phonenumber}']        = $contact->phonenumber;
            //$fields['{contact_title}']              = $contact->title;     
            $fields['{new_pass_key}']               = $contact->new_pass_key;
            
            //$fields['{contact_public_consent_url}'] = contact_consent_url($contact->id);
            $fields['{email_verification_url}']     = site_url('reset'); //site_url('verification/verify/' . $contact->id . '/' . $contact->email_verification_key);        
        }
        if (!empty($client->vat)) {
            $fields['{client_vat_number}'] = $client->vat;
        }
        
        $fields['{client_company}']     = $client->company;
        $fields['{client_phonenumber}'] = $client->phonenumber;
        $fields['{client_country}']     = get_country_short_name($client->country);
        $fields['{client_city}']        = $client->city;
        $fields['{client_zip}']         = $client->zip;
        $fields['{client_state}']       = $client->state;
        $fields['{client_address}']     = $client->address;
        $fields['{client_id}']          = $client_id;
        
        if ($password != '') {
            $fields['{password}'] = htmlentities($password);
        }
        /*
        
        */
        $custom_fields = get_custom_fields('customers');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($client_id, $field['id'], 'customers');
        }
        
        $custom_fields = get_custom_fields('contacts');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($contact_id, $field['id'], 'contacts');
        }
        
        $hook_data['merge_fields'] = $fields;
        $hook_data['fields_to']    = 'client_contact';
        $hook_data['id']           = $client_id;
        $hook_data['contact_id']   = $contact_id;
        
        $hook_data = hooks()->apply_filters('client_contact_merge_fields', $hook_data);
        $fields    = $hook_data['merge_fields'];
        
        return hooks()->apply_filters('client_contact_merge_fields', $fields, [
            'customer_id' => $client_id,
            'contact_id'  => $contact_id,
            'customer'    => $client,
            'contact'     => $contact,
        ]);
    }

    /**
     * Password merge fields
     * @param  array $data
     * @param  boolean $staff is field for staff or contact
     * @param  string $type  template type
     * @return array
     */

    function get_password_merge_field($data, $staff, $type)
    {
        $fields['{new_password_url}'] = '';
        $fields['{reset_password_url}'] = '';
        $fields['{set_password_url}']   = '';

        if ($staff == true) {
            if ($type == 'forgot') {
                $fields['{reset_password_url}'] = admin_url('auth/reset-password/' . floatval($staff) . '/' . $data['userid'] . '/' . $data['new_pass_key']);
                $fields['{new_password_url}'] = admin_url('auth/new-password/' . $data['userid'] . '/' . $data['new_pass_key']);
            }
        } else {
            if ($type == 'forgot') {
                $fields['{reset_password_url}'] = site_url('clients/reset_password/' . floatval($staff) . '/' . $data['userid'] . '/' . $data['new_pass_key']);
            } elseif ($type == 'set') {
                $fields['{set_password_url}'] = site_url('auth/set_password/' . $data['userid'] . '/' . $data['new_pass_key']);
            }
        }

        return $fields;
    }
}