<?php
defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Default SQL select for selecting the company
 *
 * @param string $as
 *
 * @return string
 */
function get_sql_select_client_company($as = 'company')
{
    return 'CASE ' . db_prefix() . 'clients.company WHEN \' \' THEN (SELECT CONCAT(firstname, \' \', lastname) FROM ' . db_prefix() . 'contacts WHERE userid = ' . db_prefix() . 'clients.userid and is_primary = 1) ELSE ' . db_prefix() . 'clients.company END as ' . $as;
}
/**
 * Get client full name
 * @param  string $contact_id Optional
 * @return string Firstname and Lastname
 */
function get_contact_full_name($contact_id = '')
{
    $contact_id == '' ? get_contact_user_id() : $contact_id;

    $CI = &get_instance();

    $contact = $CI->api_object_cache->get('contact-full-name-data-' . $contact_id);

    if (!$contact) {
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->select('firstname,lastname')->from(db_prefix() . 'contacts')->get()->row();
        $CI->api_object_cache->add('contact-full-name-data-' . $contact_id, $contact);
    }

    if ($contact) {
        return $contact->firstname . ' ' . $contact->lastname;
    }

    return '';
}
/**
 * Function used to check if is really empty customer company
 * Can happen user to have selected that the company field is not required and the primary contact name is auto added in the company field
 * @param  mixed  $id
 * @return boolean
 */
function is_empty_customer_company($id)
{
    $CI = &get_instance();
    $CI->db->select('company');
    $CI->db->from(db_prefix() . 'clients');
    $CI->db->where('userid', $id);
    $row = $CI->db->get()->row();
    if ($row) {
        if ($row->company == '') {
            return true;
        }

        return false;
    }

    return true;
}
/**
 * Check whether the user disabled verification emails for contacts
 * @return boolean
 */
function is_email_verification_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'contact-verification-email', 'active' => 0]) == 0;
}
/**
 * @since  2.7.0
 * check if logged in customer is an individual
 * @return boolean
 */
function is_individual_client()
{
    return is_empty_customer_company(get_client_user_id())
        && total_rows(db_prefix() . 'contacts', ['userid' => get_client_user_id()]) == 1;
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
function is_automatic_calling_codes_enabled()
{
    return hooks()->apply_filters('automatic_calling_codes_enabled', true);
}
/**
 * Return and perform additional checkings for contact consent url
 * @param  mixed $contact_id contact id
 * @return string
 */
function contact_consent_url($contact_id)
{
    $CI = &get_instance();

    $consent_key = get_contact_meta($contact_id, 'consent_key');

    if (empty($consent_key)) {
        $consent_key = app_generate_hash() . '-' . app_generate_hash();
        $meta_id     = false;
        if (total_rows(db_prefix() . 'contacts', ['id' => $contact_id]) > 0) {
            $meta_id = add_contact_meta($contact_id, 'consent_key', $consent_key);
        }
        if (!$meta_id) {
            return '';
        }
    }

    return site_url('consent/contact/' . $consent_key);
}