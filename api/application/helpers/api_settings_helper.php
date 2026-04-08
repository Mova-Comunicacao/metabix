<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add option
 *
 * @since  Version 1.0.1
 *
 * @param string  $name      Option name (required|unique)
 * @param string  $value     Option value
 * @param integer $autoload  Whether to autoload this option
 *
 */
function add_option($name, $value = '', $autoload = 1)
{
    if (!option_exists($name)) {
        $CI = & get_instance();

        $newData = [
                'name'  => $name,
                'value' => $value,
            ];

        if ($CI->db->field_exists('autoload', db_prefix() . 'options')) {
            $newData['autoload'] = $autoload;
        }

        $CI->db->insert(db_prefix() . 'options', $newData);

        $insert_id = $CI->db->insert_id();

        if ($insert_id) {
            return true;
        }

        return false;
    }

    return false;
}

/**
 * Get option value
 * @param  string $name Option name
 * @return mixed
 */
function get_option($name)
{
    $CI = & get_instance();

    if (!class_exists('api', false)) {
        $CI->load->library('api');
    }

    return $CI->api->get_option($name);
}

/**
 * Updates option by name
 *
 * @param  string $name     Option name
 * @param  string $value    Option Value
 * @param  mixed $autoload  Whether to update the autoload
 *
 * @return boolean
 */
function update_option($name, $value, $autoload = null)
{
    /**
     * Create the option if not exists
     * @since  2.3.3
     */
    if (!option_exists($name)) {
        return add_option($name, $value, $autoload === null ? 1 : 0);
    }

    $CI = & get_instance();

    $CI->db->where('name', $name);
    $data = ['value' => $value];

    if ($autoload) {
        $data['autoload'] = $autoload;
    }

    $CI->db->update(db_prefix() . 'options', $data);

    if ($CI->db->affected_rows() > 0) {
        return true;
    }

    return false;
}

/**
 * Delete option
 * @since  Version 1.0.4
 * @param  mixed $name option name
 * @return boolean
 */
function delete_option($name)
{
    $CI = &get_instance();
    $CI->db->where('name', $name);
    $CI->db->delete(db_prefix() . 'options');

    return (bool) $CI->db->affected_rows();
}

/**
 * @since  2.3.3
 * Check whether an option exists
 *
 * @param  string $name option name
 *
 * @return boolean
 */
function option_exists($name)
{
    return total_rows(db_prefix() . 'options', [
        'name' => $name,
    ]) > 0;
}