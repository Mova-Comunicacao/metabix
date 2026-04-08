<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get admin url
 * @param string url to append (Optional)
 * @return string admin url
 */
function admin_url($url = '')
{
    $adminURI = get_admin_uri();

    if ($url == '' || $url == '/') {
        if ($url == '/') {
            $url = '';
        }

        return site_url($adminURI) . '/';
    }

    return site_url($adminURI . '/' . $url);
}

/**
 * @since  1.0.0
 * Load language in admin area
 * @param  string $staff_id
 * @return string return loaded language
 */
function load_admin_language($staff_id = '')
{
    $CI = & get_instance();

    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];

    $language = get_option('active_language');
    if ((is_staff_logged_in() || $staff_id != '') && !is_language_disabled()) {
        $staff_language = get_staff_default_language($staff_id);
        if (!empty($staff_language)
            && file_exists(APPPATH . 'language/' . $staff_language)) {
            $language = $staff_language;
        }
    }

    $CI->lang->load($language . '_lang', $language);
    load_custom_lang_file($language);
    $GLOBALS['language'] = $language;
    $GLOBALS['locale']   = get_locale_key($language);

    hooks()->do_action('after_load_admin_language', $language);

    return $language;
}

function get_admin_uri()
{
    return ADMIN_URI;
}

/**
 * Check if current user is admin
 * @param  mixed $staffid
 * @return boolean if user is not admin
 */
function is_admin($staffid = '')
{
    /**
     * Checking for current user?
     */
    if (!is_numeric($staffid)) {
        if (isset($GLOBALS['current_user'])) {
            return $GLOBALS['current_user']->admin === '1';
        }

        $staffid = get_staff_user_id();
    }

    $CI = & get_instance();

    if ($cache = $CI->api_object_cache->get('is-admin-' . $staffid)) {
        return $cache === 'yes';
    }

    $CI->db->select('1')
    ->where('admin', 1)
    ->where('staffid', $staffid);

    $result = $CI->db->count_all_results(db_prefix() . 'staff') > 0 ? true : false;
    $CI->api_object_cache->add('is-admin-' . $staffid, $result ? 'yes' : 'no');

    return is_admin($staffid);
}
/**
 * @since  2.3.3
 * Helper function for checking staff capabilities, this function should be used instead of has_permission
 * Can be used e.q. staff_can('view', 'invoices');
 *
 * @param  string $capability         e.q. view | create | edit | delete | view_own | can_delete
 * @param  string $feature            the feature name e.q. invoices | estimates | contracts | my_module_name
 *
 *    NOTE: The $feature parameter is available as optional, but it's highly recommended always to be passed
 *    because of the uniqueness of the capability names.
 *    For example, if there is a capability "view" for feature "estimates" and also for "invoices" a capability "view" exists too
 *    In this case, if you don't pass the feature name, there may be inaccurate results
 *    If you are certain that your capability name is unique e.q. my_prefixed_capability_can_create , you don't need to pass the $feature
 *    and you can use this function as e.q. staff_can('my_prefixed_capability_can_create')
 *
 * @param  mixed $staff_id            staff id | if not passed, the logged in staff will be checked
 *
 * @return boolean
 */
function staff_can($capability, $feature = null, $staff_id = '')
{
    $staff_id = empty($staff_id) ? get_staff_user_id() : $staff_id;

    /**
     * Maybe permission is function?
     * Example is_admin or is_staff_member
     */
    if (function_exists($capability) && is_callable($capability)) {
        return call_user_func($capability, $staff_id);
    }

    /**
     * If user is admin return true
     * Admins have all permissions
     */
    if (is_admin($staff_id)) {
        return true;
    }

    $CI = &get_instance();

    $permissions = null;
    /**
     * Stop making query if we are doing checking for current user
     * Current user is stored in $GLOBALS including the permissions
     */
    if ((string) $staff_id === (string) get_staff_user_id() && isset($GLOBALS['current_user'])) {
        $permissions = $GLOBALS['current_user']->permissions;
    }

    /**
     * Not current user?
     * Get permissions for this staff
     * Permissions will be cached in object cache upon first request
     */
    if (!$permissions) {
        if (!class_exists('staff_model', false)) {
            $CI->load->model('staff_model');
        }

        $permissions = $CI->staff_model->get_staff_permissions($staff_id);
    }

    if (!$feature) {
        $retVal = in_array_multidimensional($permissions, 'capability', $capability);

        return hooks()->apply_filters('staff_can', $retVal, $capability, $feature, $staff_id);
    }

    foreach ($permissions as $permission) {
        if ($feature == $permission['feature']
            && $capability == $permission['capability']) {
            return hooks()->apply_filters('staff_can', true, $capability, $feature, $staff_id);
        }
    }

    return hooks()->apply_filters('staff_can', false, $capability, $feature, $staff_id);
}
/**
 * @since  2.3.3
 * Check whether a role has specific permission applied
 * @param  mixed  $role_id    role id
 * @param  string  $capability e.q. view|create|read
 * @param  string  $feature    the feature, e.q. invoices|estimates etc...
 * @return boolean
 */
function has_role_permission($role_id, $capability, $feature)
{
    $CI          = &get_instance();
    $permissions = $CI->roles_model->get($role_id)->permissions;

    foreach ($permissions as $appliedFeature => $capabilities) {
        if ($feature == $appliedFeature && in_array($capability, $capabilities)) {
            return true;
        }
    }

    return false;
}
/**
 * @since 1.0.0
 * NOTE: This function will be deprecated in future updates, use staff_can($do, $feature = null, $staff_id = '') instead
 *
 * Check if staff user has permission
 * @param  string  $permission permission shortname
 * @param  mixed  $staffid if you want to check for particular staff
 * @return boolean
 */
function has_permission($permission, $staffid = '', $can = '')
{
    return staff_can($can, $permission, $staffid);
}