<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_model extends Api_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTable()
    {
        $this->db->select('*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.id,' . db_prefix() . 'leads_status.name as status_name,' . db_prefix() . 'leads_sources.name as source_name');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id=' . db_prefix() . 'leads.status', 'left');
        $this->db->join(db_prefix() . 'leads_sources', db_prefix() . 'leads_sources.id=' . db_prefix() . 'leads.source', 'left');        
   
        return $this->db->get(db_prefix() . 'leads')->result();
    }

    /**
     * Get lead
     * @param  string $id Optional - leadid
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $this->db->select('*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.id,' . db_prefix() . 'leads_status.name as status_name,' . db_prefix() . 'leads_sources.name as source_name');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id=' . db_prefix() . 'leads.status', 'left');
        $this->db->join(db_prefix() . 'leads_sources', db_prefix() . 'leads_sources.id=' . db_prefix() . 'leads.source', 'left');

        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'leads.id', $id);
            $lead = $this->db->get(db_prefix() . 'leads')->row();
            /*
            if ($lead) {
                if ($lead->from_form_id != 0) {
                    $lead->form_data = $this->get_form([
                        'id' => $lead->from_form_id,
                    ]);
                }
                $lead->attachments = $this->get_lead_attachments($id);
                $lead->public_url  = leads_public_url($id);
            }
            */

            return $lead;
        }

        return $this->db->get(db_prefix() . 'leads')->result();
    }  
    
    /**
     * Get lead by given email
     *
     * @since 2.8.0
     *
     * @param  string $email
     *
     * @return \strClass|null
     */
    public function get_lead_by_email($email)
    {
        $this->db->where('email', $email);
        $this->db->limit(1);

        return $this->db->get(db_prefix() . 'leads')->row();
    }  
    
    /**
     * Add new lead to database
     * @param mixed $data lead data
     * @return mixed false || leadid
     */
    public function add($data)
    {
        if (isset($data['custom_contact_date']) || isset($data['custom_contact_date'])) {
            if (isset($data['contacted_today'])) {
                $data['lastcontact'] = date('Y-m-d H:i:s');
                unset($data['contacted_today']);
            } else {
                $data['lastcontact'] = $data['custom_contact_date'];
            }
        }

        if (isset($data['is_public']) && ($data['is_public'] == 1 || $data['is_public'] === 'on')) {
            $data['is_public'] = 1;
        } else {
            $data['is_public'] = 0;
        }  
        
        if (!isset($data['country']) || isset($data['country']) && $data['country'] == '') {
            $data['country'] = 0;
        }

        if (isset($data['custom_contact_date'])) {
            unset($data['custom_contact_date']);
        }  

        if (isset($data['description'])) {
            $data['description'] = nl2br($data['description']);
        }          
        
        $data['dateadded']   = date('Y-m-d H:i:s');

        if (!isset($data['addedfrom']) || isset($data['addedfrom']) && $data['addedfrom'] == '') {
            $data['addedfrom'] = 0;
        } else {
            $data['addedfrom'] = $data['addedfrom'] ; 
        }    
        
        $data = hooks()->apply_filters('before_lead_added', $data);

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['address'])) {
            $data['address'] = trim($data['address']);
            $data['address'] = nl2br($data['address']); 
        }           
        
        $data['email'] = trim($data['email']);

        $this->db->insert(db_prefix() . 'leads', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Lead Added [ID: ' . $insert_id . ']', 'add');
            //$this->lead_assigned_member_notification($insert_id, $data['assigned']);
            hooks()->do_action('lead_created', $insert_id);

            return $insert_id;            
        } 
        
        return false;
    }  

    /**
     * Update lead
     * @param  array $data lead data
     * @param  mixed $id   leadid
     * @return boolean
     */
    public function update($data, $id)
    {
        $current_lead_data = $this->get($id);

        $affectedRows = 0;

        if (!defined('API')) {
            if (isset($data['is_public'])) {
                $data['is_public'] = 1;
            } else {
                $data['is_public'] = 0;
            }

            if (!isset($data['country']) || isset($data['country']) && $data['country'] == '') {
                $data['country'] = 0;
            }

            if (isset($data['description'])) {
                $data['description'] = nl2br($data['description']);
            }
        }   
        
        if (isset($data['lastcontact']) && $data['lastcontact'] == '' || isset($data['lastcontact']) && $data['lastcontact'] == null) {
            $data['lastcontact'] = null;
        } elseif (isset($data['lastcontact'])) {
            $data['lastcontact'] = to_sql_date($data['lastcontact'], true);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'lead')) {
                $affectedRows++;
            }
            unset($data['tags']);
        } 
        
        if (isset($data['remove_attachments'])) {
            foreach ($data['remove_attachments'] as $key => $val) {
                $attachment = $this->get_lead_attachments($id, $key);
                if ($attachment) {
                    $this->delete_lead_attachment($attachment->id);
                }
            }
            unset($data['remove_attachments']);
        }

        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);

        $data['email'] = trim($data['email']);    
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'leads', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;

            if (isset($data['assigned'])) {
                if ($current_lead_data->assigned != $data['assigned'] && (!empty($data['assigned']) && $data['assigned'] != 0)) {
                    //$this->lead_assigned_member_notification($id, $data['assigned']);
                }
            } 
            log_activity('Lead Updated [ID: ' . $id . ']', 'update');     
            
            return true;
        }   
        if ($affectedRows > 0) {
            return true;
        }

        return false;             
    }    

    /**
     * Delete lead from database and all connections
     * @param  mixed $id leadid
     * @return boolean
     */
    public function delete($id)
    {
        $affectedRows = 0;

        hooks()->do_action('before_lead_deleted', $id);

        //$lead = $this->get($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'leads');  
        if ($this->db->affected_rows() > 0) {
            log_activity('Lead Deleted [ID: ' . $id . ']', 'deteled'); 

            hooks()->do_action('after_lead_deleted', $id);
            
            $affectedRows++;
        }   

        return true;        
    }

    
    public function lead_assigned_member_notification($lead_id, $assigned, $integration = false)
    {
        if ((!empty($assigned) && $assigned != 0)) {
            if ($integration == false) {
                if ($assigned == $assigned) {
                    return false;
                }
            }

            $name = $this->db->select('name')->from(db_prefix() . 'leads')->where('id', $lead_id)->get()->row()->name;
        
            $notification_data = [
                'description'     => ($integration == false) ? 'not_assigned_lead_to_you' : 'not_lead_assigned_from_form',
                'touserid'        => $assigned,
                'link'            => '#leadid=' . $lead_id,
                'additional_data' => ($integration == false ? serialize([
                    $name,
                ]) : serialize([])),
            ];

            if ($integration != false) {
                $notification_data['fromcompany'] = 1;
            }   
            
            /*
            if (add_notification($notification_data)) {
                pusher_trigger_notification([$assigned]);
            } 
            */   
            
            $this->db->select('email');
            $this->db->where('staffid', $assigned);
            $email = $this->db->get(db_prefix() . 'staff')->row()->email;  
            
            //send_mail_template('lead_assigned', $lead_id, $email);

            $this->db->where('id', $lead_id);
            $this->db->update(db_prefix() . 'leads', [
                'dateassigned' => date('Y-m-d'),
            ]); 
            
            $not_additional_data = [
                get_staff_full_name(),
                '<a href="' . admin_url('profile/' . $assigned) . '" target="_blank">' . get_staff_full_name($assigned) . '</a>',
            ];

            if ($integration == true) {
                unset($not_additional_data[0]);
                array_values(($not_additional_data));
            }

            $not_additional_data = serialize($not_additional_data);

            $not_desc = ($integration == false ? 'not_lead_activity_assigned_to' : 'not_lead_activity_assigned_from_form');            
        }        
    }  

    
    
    // Sources

    /**
     * Get leads sources
     * @param  mixed $id Optional - Source ID
     * @return mixed object if id passed else array
     */
    public function get_source($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'leads_sources')->row();
        }

        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'leads_sources')->result();
    }    
}