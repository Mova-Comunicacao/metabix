<?php
defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Check whether the client email is verified
 * @since  2.2.0
 * @param  mixed  $id client id
 * @return boolean
 */
function is_user_email_verified($id = null)
{
    $id = !$id ? get_contact_user_id() : $id;

    if (isset($GLOBALS['contact']) && $GLOBALS['contact']->id == $id) {
        return !is_null($GLOBALS['contact']->email_verified_at);
    }

    $CI = &get_instance();

    $CI->db->select('email_verified_at');
    $CI->db->where('id', $id);
    $contact = $CI->db->get(db_prefix() . 'clients')->row();

    if (!$contact) {
        return false;
    }

    return !is_null($contact->email_verified_at);
}
/**
 * Check whether the user disabled verification emails for clients
 * @return boolean
 */
function is_email_verification()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'contact-verification-email', 'active' => 0]) == 0;
}
/**
 * Used in:
 * Search contact tickets
 * Project dropdown quick switch
 * Calendar tooltips
 * @param  [type] $userid [description]
 * @return [type]         [description]
 */
function get_company_name($userid, $prevent_empty_company = false)
{
    $_userid = get_client_user_id();
    if ($userid !== '') {
        $_userid = $userid;
    }
    $CI = &get_instance();

    $select = ($prevent_empty_company == false ? get_sql_select_client_company() : 'company');

    $client = $CI->db->select($select)
        ->where('userid', $_userid)
        ->from(db_prefix() . 'clients')
        ->get()
        ->row();
    if ($client) {
        return $client->company;
    }

    return '';
}

/**
 * Get primary contact user id for specific customer
 * @param  mixed $userid
 * @return mixed
 */
function get_primary_contact_user_id($userid)
{
    $CI = &get_instance();
    $CI->db->where('userid', $userid);
    $CI->db->where('is_primary', 1);
    $row = $CI->db->get(db_prefix() . 'contacts')->row();

    if ($row) {
        return $row->id;
    }

    return false;
}

/**
 * Return contact profile image url
 * @param  mixed $contact_id
 * @param  string $type
 * @return string
 */
function contact_profile_image_url($contact_id, $type = 'small')
{
    $url  = '';
    $CI   = &get_instance();
    $path = $CI->api_object_cache->get('contact-profile-image-path-' . $contact_id);

    if (!$path) {
        $CI->api_object_cache->add('contact-profile-image-path-' . $contact_id, $url);

        $CI->db->select('profile_image');
        $CI->db->from(db_prefix() . 'contacts');
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->get()->row();

        if ($contact && !empty($contact->profile_image)) {
            $path = 'api/uploads/contacts/' . $contact_id . '/' . $type . '_' . $contact->profile_image;
            $CI->api_object_cache->set('contact-profile-image-path-' . $contact_id, $path);
        }
    }

    if ($path) {
        $url = base_url($path);
    }

    return $url;
}
function send_customer_registered_email_to_administrators($client_id)
{
    $CI = &get_instance();
    $CI->load->model('staff_model');
    $admins = $CI->staff_model->get('', ['active' => 1, 'admin' => 1]);

    foreach ($admins as $admin) {
        $CI->load->model('emails_model');
        $api_merge_fields = new api_merge_fields();
        $merge_fields = array();
        $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($client_id, $admin['staffid']));
        $CI->emails_model->send_email_template('customer_new_registration_to_admins', $admin['email'], $merge_fields);
        //send_mail_template('customer_new_registration_to_admins', $admin['email'], $client_id, $admin['staffid']);
    }
}

/**
 * @since  2.7.0
 * Set logged in contact language
 * @return void
 */
function set_contact_language($lang, $duration = 60 * 60 * 24 * 31 * 3)
{
    set_cookie('contact_language', $lang, $duration);
}
/**
 * @since  2.7.0
 * get logged in contact language
 * @return string
 */
function get_contact_language()
{
    if (!is_null(get_cookie('contact_language'))) {
        return get_cookie('contact_language');
    }

    return '';
}
/**
 * Get client default language
 * @param  mixed $clientid
 * @return mixed
 */
function get_client_default_language($clientid = '')
{
    if (!is_numeric($clientid)) {
        $clientid = get_client_user_id();
    }
    $CI =& get_instance();
    $CI->db->select('default_language');
    $CI->db->from(db_prefix() . 'users');
    $CI->db->where('userid', $clientid);
    $client = $CI->db->get()->row();
    if ($client) {
        return $client->default_language;
    }

    return '';
}
/**
 * Load customers area language
 * @param  string $customer_id
 * @return string return loaded language
 */
function load_client_language($customer_id = '')
{
    $CI = &get_instance();
    if (!$CI->load->is_loaded('cookie')) {
        $CI->load->helper('cookie');
    }

    if (defined('CLIENTS_AREA') && get_contact_language() != '' && !is_language_disabled()) {
        $language = get_contact_language();
    } else {
        $language = get_option('active_language');

        if ((is_client_logged_in() || $customer_id != '') && !is_language_disabled()) {
            $client_language = get_client_default_language($customer_id);

            if (!empty($client_language)
                && file_exists(APPPATH . 'language/' . $client_language)) {
                $language = $client_language;
            }
        }

        // set_contact_language($language);
    }

    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];

    $CI->lang->load($language . '_lang', $language);
    load_custom_lang_file($language);

    $GLOBALS['language'] = $language;
    $GLOBALS['locale'] = get_locale_key($language);

    $CI->lang->set_last_loaded_language($language);

    hooks()->do_action('after_load_client_language', $language);

    return $language;
}
/**
 * @since  2.9.0
 *
 * Indicates whether the contact automatically
 * appended calling codes feature is enabled based on the
 * customer selected country
 *
 * @return boolean
 */
if (!function_exists('is_automatic_calling_codes_enable'))
{
    function is_automatic_calling_codes_enable()
    {
        return hooks()->apply_filters('automatic_calling_codes_enabled', true);
    }
}