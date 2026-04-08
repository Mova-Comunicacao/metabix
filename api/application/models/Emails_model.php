<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Emails_model extends Api_Model
{
    private $attachment = array();

    /**
     * @deprecated 2.3.0
     */
    private $client_email_templates;

    /**
     * @deprecated 2.3.0
     */
    private $staff_email_templates;

    /**
     * @deprecated 2.3.0
     */
    private $rel_id;

    /**
     * @deprecated 2.3.0
     */
    private $rel_type;

    /**
     * @deprecated 2.3.0
     */
    private $staff_id;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('email');
        $this->client_email_templates  = get_client_email_templates_slugs();
        $this->staff_email_templates  = get_staff_email_templates_slugs();
    }

    /**
     * @param  string
     * @return array
     * Get email template by type
     */
    public function get($where = array(), $result_type = 'result_array')
    {
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'emailtemplates')->{$result_type}();
    }   

    /**
     * @param  integer
     * @return object
     * Get email template by id
     */
    public function get_email_template_by_id($id)
    {
        $this->db->where('emailtemplateid', $id);

        return $this->db->get(db_prefix() . 'emailtemplates')->row();
    }    
    
    public function send_email_contact($data)
    {
        if (defined('DEMO') && DEMO) {
            return true;
        }

		$textHTML = '';		
        $textHTML .= '<p><strong>Nome: </strong>'.$data['firstname'].' '.$data['lastname'].'</p>';
        $textHTML .= '<p><strong>Fone: </strong>'.$data['phone'].'</p>';
        $textHTML .= '<p><strong>Mensagem: </strong>'.$data['message'].'</p>';

        $message = nl2br($textHTML);

        $cnf = [
            'to_email'   => get_option('company_email'),
            //'from_name'  => get_option('business_name'),
            'from_email' => $data['email'],
            'from_name'  => $data['firstname']. ' '.$data['lastname'],
            'subject'    => $data['subject'],
            'phone'      => $data['phone'],
            'message'    => $message,
        ]; 
        
        // Simulate fake template to be parsed
        $template           = new StdClass();
        $template->message  = get_option('email_header') . $cnf['message'] . get_option('email_footer');
        $template->fromname = $cnf['from_name'];
        $template->subject  = $cnf['subject'];    
        
        $template = parse_email_template($template);
        
        $cnf['message']   = $template->message;
        $cnf['from_name'] = $template->fromname;
        $cnf['subject']   = $template->subject;   
        
        $cnf['message'] = clean($cnf['message']);
        
        $cnf = hooks()->apply_filters('before_send_simple_email', $cnf); 
        
        $this->load->config('email');
        $this->email->clear(true);
        $this->email->set_newline(config_item('newline'));
        $this->email->from($cnf['from_email'], $cnf['from_name']);
        $this->email->to($cnf['to_email']);  
        
        $this->email->subject($cnf['subject']);
        $this->email->message($cnf['message']);
        
        $alt_message = strip_html_tags($cnf['message'], '<br/>, <br>, <br />');
        $this->email->set_alt_message(trim($alt_message));  
        
        if (count($this->attachment) > 0) {
            foreach ($this->attachment as $attach) {
                if (!isset($attach['read'])) {
                    $this->email->attach($attach['attachment'], 'attachment', $attach['filename'], $attach['type']);
                } else {
                    if (!isset($attach['filename']) || (isset($attach['filename']) && empty($attach['filename']))) {
                        $attach['filename'] = basename($attach['attachment']);
                    }
                    $this->email->attach($attach['attachment'], '', $attach['filename']);
                }
            }
        }
        
        $this->clear_attachments();            
        if ($this->email->send()) {
            //log_activity('Email sent to: ' . $cnf['email'] . ' Subject: ' . $cnf['subject'], 'sendemail');
            
			$data_lead = array(
				'name' => $cnf['from_name'],
				'email' => $cnf['from_email'],
				'phonenumber' => $cnf['phone'],
                'source' => 4
            );  

            $this->load->model('leads_model');
            //$this->leads_model->add($data_lead);

            return true;
        }

        return false;         
    } 
    
    
    /**
     * Send email - No templates used only simple string
     * @since Version 1.0.2
     * @param  string $email   email
     * @param  string $message message
     * @param  string $subject email subject
     * @return boolean
     */
    public function send_simple_email($email, $subject, $message)
    {
        if (defined('DEMO') && DEMO) {
            return true;
        }

        $cnf = [
            'from_email' => get_option('company_email'),
            'from_name'  => get_option('business_name'),
            'email'      => $email,
            'subject'    => $subject,
            'message'    => $message,
        ];  

        // Simulate fake template to be parsed
        $template           = new StdClass();
        $template->message  = get_option('email_header') . $cnf['message'] . get_option('email_footer');
        $template->fromname = $cnf['from_name'];
        $template->subject  = $cnf['subject'];    
        
        $template = parse_email_template($template);
        
        $cnf['message']   = $template->message;
        $cnf['from_name'] = $template->fromname;
        $cnf['subject']   = $template->subject;      
        
        $cnf['message'] = clean($cnf['message']);
        
        $cnf = hooks()->apply_filters('before_send_simple_email', $cnf);
        
        if (isset($cnf['prevent_sending']) && $cnf['prevent_sending'] == true) {
            $this->clear_attachments();
            
            return false;
        }  
        
        $this->load->config('email');
        $this->email->clear(true);
        $this->email->set_newline(config_item('newline'));
        $this->email->from($cnf['from_email'], $cnf['from_name']);
        $this->email->to($cnf['email']);   
        
        $bcc = '';
        // Used for action hooks
        if (isset($cnf['bcc'])) {
            $bcc = $cnf['bcc'];
            if (is_array($bcc)) {
                $bcc = implode(', ', $bcc);
            }
        }
        
        $systemBCC = get_option('bcc_emails');
        if ($systemBCC != '') {
            if ($bcc != '') {
                $bcc .= ', ' . $systemBCC;
            } else {
                $bcc .= $systemBCC;
            }
        }
        if ($bcc != '') {
            $this->email->bcc($bcc);
        }
        
        if (isset($cnf['cc'])) {
            $this->email->cc($cnf['cc']);
        }
        
        if (isset($cnf['reply_to'])) {
            $this->email->reply_to($cnf['reply_to']);
        }  
        
        $this->email->subject($cnf['subject']);
        $this->email->message($cnf['message']);
        
        $alt_message = strip_html_tags($cnf['message']);
        $this->email->set_alt_message(trim($alt_message));
        
        if (count($this->attachment) > 0) {
            foreach ($this->attachment as $attach) {
                if (!isset($attach['read'])) {
                    $this->email->attach($attach['attachment'], 'attachment', $attach['filename'], $attach['type']);
                } else {
                    if (!isset($attach['filename']) || (isset($attach['filename']) && empty($attach['filename']))) {
                        $attach['filename'] = basename($attach['attachment']);
                    }
                    $this->email->attach($attach['attachment'], '', $attach['filename']);
                }
            }
        }
        
        $this->clear_attachments();  
        if ($this->email->send()) {
            //log_activity('Email sent to: ' . $cnf['email'] . ' Subject: ' . $cnf['subject'], 'sendemail');

            return true;
        }

        return false;              
    } 
    
    /**
     * Send email template
     * @deprecated 2.3.0
     * @param  string $template_slug email template slug
     * @param  string $email         email to send
     * @param  array $merge_fields  merge field
     * @param  string $ticketid      used only when sending email templates linked to ticket / used for piping
     * @param  mixed $cc
     * @return boolean
     */
    public function send_email_template($template_slug, $email, $merge_fields, $ticketid = '', $cc = '')
    {
        $email = hooks()->apply_filters('send_email_template_to', $email);

        $template                     = get_email_template_for_sending($template_slug, $email);
        $staff_email_templates_slugs  = get_staff_email_templates_slugs();
        $client_email_templates_slugs = get_client_email_templates_slugs();

        $inactive_user_table_check = ''; 
        
        /**
         * Dont send email templates for non active contacts/staff
         * Do checking here
         */
        if (in_array($template_slug, $staff_email_templates_slugs)) {
            $inactive_user_table_check = db_prefix() . 'staff';
        } elseif (in_array($template_slug, $client_email_templates_slugs)) {
            $inactive_user_table_check = db_prefix() . 'contacts';
        }
        
        /**
         * Is really inactive?
         */
        if ($inactive_user_table_check != '') {
            $this->db->select('active')->where('email', $email);
            $user = $this->db->get($inactive_user_table_check)->row();
            if ($user && $user->active == 0) {
                $this->clear_attachments();
                $this->set_staff_id(null);

                return false;
            }
        }

        /**
         * Template not found?
         */
        if (!$template) {
            log_activity('Failed to send email template [Template not found]', 'failed');
            $this->clear_attachments();
            $this->set_staff_id(null);

            return false;
        }      
        
        /**
         * Template is disabled or invalid email?
         * Log activity
         */
        if ($template->active == 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->clear_attachments();

            $this->db->where('language', 'english');
            $this->db->where('slug', $template->slug);
            $tmpTemplate = $this->db->get(db_prefix() . 'emailtemplates')->row();

            if ($tmpTemplate) {
                log_activity('Failed to send email template [<a href="'.admin_url('emails/email_template/'.$tmpTemplate->emailtemplateid).'">'.$template->name.'</a>] [Reason: Email template is disabled.]', 'failed');
            }

            return false;
        }    
        
        $template = hooks()->apply_filters('before_parse_email_template_message', $template);
        $template = parse_email_template($template, $merge_fields);

        $template = hooks()->apply_filters('after_parse_email_template_message', $template);
        $template->message = get_option('email_header') . $template->message . get_option('email_footer');        

        // Parse merge fields again in case there is merge fields found in email_header and email_footer option.
        // We cant parse this in parse_email_template function because in case the template content is send via $_POST wont work
        $template = parse_email_template_merge_fields($template, $merge_fields);

        /**
         * Template is plain text?
         */
        if ($template->plaintext == 1) {
            $this->config->set_item('mailtype', 'text');
            $template->message = strip_html_tags($template->message, '<br/>, <br>, <br />');
        }     
        
        $fromemail = $template->fromemail;
        $fromname  = $template->fromname;

        if ($fromemail == '') {
            $fromemail = get_option('smtp_email');
        }

        if ($fromname == '') {
            $fromname = get_option('business_name');
        }      

        /**
         * Ticket variables
        */
        $reply_to               = false;
        $from_header_dept_email = false;
        /**
         * Tickets template
         * For tickets there is different config
         */
        if (is_numeric($ticketid) && $template->type == 'ticket') {
        }       

        $hook_data['template']    = $template;
        $hook_data['email']       = $email;
        $hook_data['attachments'] = $this->attachment;

        $hook_data['template']->message = $hook_data['template']->message;
        $hook_data = hooks()->apply_filters('before_email_template_send', $hook_data);        

        $template    = $hook_data['template'];
        $email       = $hook_data['email'];
        $attachments = $hook_data['attachments'];

        if (isset($template->prevent_sending) && $template->prevent_sending == true) {
            $this->clear_attachments();
            $this->set_staff_id(null);

            return false;
        }

        $this->load->config('email');
        $this->email->clear(true);
        $this->email->set_newline(config_item('newline'));
        $this->email->from(($from_header_dept_email ? $ticket->department_email : $fromemail), $fromname);
        $this->email->subject($template->subject);

        $this->email->message($template->message);
        $this->email->to($email);        

        $bcc = '';
        // Used for action hooks
        if (isset($template->bcc)) {
            $bcc = $template->bcc;
            if (is_array($bcc)) {
                $bcc = implode(', ', $bcc);
            }
        }

        $systemBCC = get_option('bcc_emails');
        if ($systemBCC != '') {
            if ($bcc != '') {
                $bcc .= ', ' . $systemBCC;
            } else {
                $bcc .= $systemBCC;
            }
        }

        if ($bcc != '') {
            $bcc = array_map('trim', explode(',', $bcc));
            $bcc = array_unique($bcc);
            $bcc = implode(', ', $bcc);
            $this->email->bcc($bcc);
        }


        if ($reply_to != false) {
            $this->email->reply_to($reply_to);
        } elseif (isset($template->reply_to)) {
            $this->email->reply_to($template->reply_to);
        }

        if ($template->plaintext == 0) {
            $alt_message = strip_html_tags($template->message, '<br/>, <br>, <br />');
            // Replace <br /> with \n
            $alt_message = clear_textarea_breaks($alt_message, "\r\n");
            $this->email->set_alt_message($alt_message);
        }

        if (is_array($cc) || !empty($cc)) {
            $this->email->cc($cc);
        }        

        if (count($attachments) > 0) {
            foreach ($attachments as $attach) {
                if (!isset($attach['read'])) {
                    $this->email->attach($attach['attachment'], 'attachment', $attach['filename'], $attach['type']);
                } else {
                    $this->email->attach($attach['attachment'], '', $attach['filename']);
                }
            }
        }

        $this->clear_attachments();
        $this->set_staff_id(null);        

        if ($this->email->send()) {
            log_activity('Email Send To [Email: ' . $email . ', Template: ' . $template->name . ']', 'send');
            hooks()->do_action('email_template_sent', ['template' => $template, 'email' => $email]);

            return true;
        }

        if (ENVIRONMENT !== 'production') {
            log_activity('Failed to send email template - ' . $this->email->print_debugger(), 'failed');
        }

        return false;        
    }    
    
    /**
     * @param resource
     * @param string
     * @param string (mime type)
     * @return none
     * Add attachment to property to check before an email is send
     */
    public function add_attachment($attachment)
    {
        $this->attachment[] = $attachment;
    }

    /**
     * @return none
     * Clear all attachment properties
     */
    private function clear_attachments()
    {
        $this->attachment = array();
    }    

    /**
     * @deprecated 2.3.0
     */
    public function set_rel_id($rel_id)
    {
        $this->rel_id = $rel_id;
    }

    /**
     * @deprecated 2.3.0
     */
    public function set_rel_type($rel_type)
    {
        $this->rel_type = $rel_type;
    }

    /**
     * @deprecated 2.3.0
     */
    public function get_rel_id()
    {
        return $this->rel_id;
    }

    /**
     * @deprecated 2.3.0
     */
    public function get_rel_type()
    {
        return $this->rel_type;
    }

    /**
     * @deprecated 2.3.0
     */
    public function set_staff_id($id)
    {
        $this->staff_id = $id;
    }

    /**
     * @deprecated 2.3.0
     */
    public function get_staff_id()
    {
        return $this->staff_id;
    }    
}