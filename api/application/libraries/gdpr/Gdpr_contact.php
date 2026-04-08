<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_contact
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($contact_id)
    {
        define('GDPR_EXPORT', true);
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', 360);

        // $lead = $CI->leads_model->get($id);
        $this->ci->load->library('zip');

        $tmpDir     = get_temp_dir();
        $valAllowed = get_option('gdpr_contact_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $json = array();

        $contactFields = $this->ci->db->list_fields(db_prefix().'contacts');

        if ($passwordKey = array_search('password', $contactFields)) {
            unset($contactFields[$passwordKey]);
        }   
        
        $this->ci->db->select(implode(',', $contactFields));
        $this->ci->db->where('id', $contact_id);
        $contact = $this->ci->db->get(db_prefix().'contacts')->row_array();
        $slug    = slug_it($contact['firstname'] . ' ' . $contact['lastname']);

        $isIndividual = is_empty_customer_company($contact['userid']);
        $json         = array();   
        
        $this->ci->db->where('show_on_client_portal', 1)
        ->where('fieldto', 'contacts')
        ->order_by('field_order', 'asc');

        $contactsCustomFields = $this->ci->db->get(db_prefix().'customfields')->result_array();  
        
        if (in_array('profile_data', $valAllowed)) {
            $contact['additional_fields'] = [];

            foreach ($contactsCustomFields as $field) {
                $contact['additional_fields'][] = [
                'name'  => $field['name'],
                'value' => get_custom_field_value($contact['id'], $field['id'], 'contacts'),
            ];
            }

            $json = $contact;
        }   
        
        if (in_array('customer_profile_data', $valAllowed)
        && $contact['is_primary'] == '1'
        && !$isIndividual) {
            $this->ci->db->where('userid', $contact['userid']);
            $customer = $this->ci->db->get(db_prefix().'clients')->row_array();

            $customer['country']          = get_country($customer['country']);
            $customer['billing_country']  = get_country($customer['billing_country']);
            $customer['shipping_country'] = get_country($customer['shipping_country']);

            $this->ci->db->where('show_on_client_portal', 1)
              ->where('fieldto', 'customers')
              ->order_by('field_order', 'asc');

            $custom_fields                 = $this->ci->db->get(db_prefix().'customfields')->result_array();
            $customer['additional_fields'] = array(); 
            
            $json['company'] = $customer;
        }  
        
        // Contacts
        if (in_array('contacts', $valAllowed) && $contact['is_primary'] == '1' && !$isIndividual) {
            $this->ci->db->where('id !=', $contact['id']);
            $this->ci->db->where('userid', $contact['userid']);
            $otherContacts = $this->ci->db->get(db_prefix().'contacts')->result_array();

            foreach ($otherContacts as $keyContact => $otherContact) {
                $otherContacts[$keyContact]['additional_fields'] = array();

                foreach ($contactsCustomFields as $field) {
                    $otherContacts[$keyContact]['additional_fields'][] = array(
                    'name'  => $field['name'],
                    'value' => get_custom_field_value($otherContact['id'], $field['id'], 'contacts'),
                    );
                }
            }
        }  
        
        $tmpDirContactData = $tmpDir . '/' . $contact['id'] . time() . '-contact';
        mkdir($tmpDirContactData, 0755);

        $fp = fopen($tmpDirContactData . '/data.json', 'w');
        fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->ci->zip->read_file($tmpDirContactData . '/data.json');

        if (is_dir($tmpDirContactData)) {
            @delete_dir($tmpDirContactData);
        }

        $this->ci->zip->download($slug . '-data.zip');        
    }    
}