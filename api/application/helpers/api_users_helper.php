<?php
defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Return user profile image url
 * @param  mixed $user_id
 * @param  string $type
 * @return string
 */
function user_profile_image_url($user_id, $type = '')
{
    $url  = '';
    $CI   = &get_instance();
    $path = $CI->api_object_cache->get('user-profile-image-path-' . $user_id);

    if (!$path) {
        $CI->api_object_cache->add('user-profile-image-path-' . $user_id, $url);

        $CI->db->select('avatar');
        $CI->db->from(db_prefix() . 'users');
        $CI->db->where('userid', $user_id);
        $user = $CI->db->get()->row();

        if ($user && !empty($user->avatar)) {
            $path = 'api/uploads/users/' . $user_id . '/' . $type . '_' . $user->avatar;
            $CI->api_object_cache->set('user-profile-image-path-' . $user_id, $path);
        }
    }

    if ($path) {
        $url = base_url($path);
    }

    return $url;
}

/**
 * Return user prodyct image url
 * @param  mixed $product_id
 * @param  string $type
 * @return string
 */
function product_image_url($product_id)
{
    $url  = '';
    $CI   = &get_instance();
    $path = $CI->api_object_cache->get('product-image-path-' . $product_id);

    if (!$path) {
        $CI->api_object_cache->add('product-image-path-' . $product_id, $url);

        $CI->db->select('file_name');
        $CI->db->from(db_prefix() . 'projects_pictures');
        $CI->db->where('project_id', $product_id);
        $product = $CI->db->get()->row();

        $fullPath  = 'api/uploads/products/' . $product_id . '/' . $product->file_name; 
        $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
        $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);

        if ($product && !empty($product->file_name)) {
            $path = 'api/uploads/products/' . $product_id . '/' . $fname . '_thumb.' . $fext;
            $CI->api_object_cache->set('product-image-path-' . $product_id, $path);
        }
    }

    if ($path) {
        $url = base_url($path);
    }

    return $url;
}

/**
 * Get client full name
 * @param  string $contact_id Optional
 * @return string Firstname and Lastname
 */
function get_user_full_name($contact_id = '')
{
    $contact_id == '' ? get_contact_user_id() : $contact_id;

    $CI = &get_instance();

    $contact = $CI->api_object_cache->get('contact-full-name-data-' . $contact_id);

    if (!$contact) {
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->select('firstname,lastname')->from(db_prefix() . 'users')->get()->row();
        $CI->api_object_cache->add('contact-full-name-data-' . $contact_id, $contact);
    }

    if ($contact) {
        return $contact->firstname . ' ' . $contact->lastname;
    }

    return '';
}