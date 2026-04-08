<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends Api_Model 
{
    private $encrypted_fields = ['smtp_password'];
    private $encrypted_token_fields = ['whatsapp_access_token'];

    public function options()
	{
        return $this->db->get('apioptions')->result();      
    }

	public function settings()
	{
        $this->db->select('value, name');
        $options = $this->db->get(db_prefix() . 'options')->result();

        return $options;
    }

    /**
     * Update all settings
     * @param  array $data all settings
     * @return integer
     */    
    public function update($data)
	{  
        $affectedRows = 0;
        $data         = hooks()->apply_filters('before_settings_updated', $data);
        
        $original_encrypted_fields = array();
        foreach ($this->encrypted_fields as $ef) {
            $original_encrypted_fields[$ef] = get_option($ef);
        } 

        $original_encrypted_token_fields = array();
        foreach ($this->encrypted_token_fields as $ef) {
            $original_encrypted_token_fields[$ef] = get_option($ef);
        }         

        foreach ($data as $name => $val) {
            $hook_data['name']  = $name;
            $hook_data['value'] = $val;   
            $hook_data          = hooks()->apply_filters('before_single_setting_updated_in_loop', $hook_data);         
            $name               = $hook_data['name'];
            $val                = $hook_data['value'];

            if ($name == 'email_signature') {
                $val = html_entity_decode($val);

                if ($val == strip_tags($val)) {
                    // not contains HTML, add break lines
                    $val = nl2br_save_html($val);
                }
            } elseif ($name == 'email_header' || $name == 'email_footer') {
                $val = html_entity_decode($val);
            } elseif (in_array($name, $this->encrypted_fields)) {
                // Check if not empty $val password
                // Get original
                // Decrypt original
                // Compare with $val password
                // If equal unset
                // If not encrypt and save
                if (!empty($val)) {
                    $or_decrypted = $this->encryption->decrypt($original_encrypted_fields[$name]);
                    if ($or_decrypted == $val) {
                        continue;
                    }
                    $val = $this->encryption->encrypt($val);
                }                
            } elseif (in_array($name, $this->encrypted_token_fields)) {
                // Check if not empty $val token
                // Get original
                // Decrypt original
                // Compare with $val token
                // If equal unset
                // If not encrypt and save
                if (!empty($val)) {
                    $or_decrypted = $this->encryption->decrypt($original_encrypted_token_fields[$name]);
                    if ($or_decrypted == $val) {
                        continue;
                    }
                    $val = $this->encryption->encrypt($val);
                }                
            }

            if (update_option($name, $val)) {
                $affectedRows++;
            }
        }        
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }  
        
        return $affectedRows;
    }     
}