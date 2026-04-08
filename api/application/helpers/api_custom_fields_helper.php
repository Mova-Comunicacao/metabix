<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get custom fields
 * @param  string  $field_to
 * @param  array   $where
 * @param  boolean $exclude_only_admin
 * @return array
 */
function get_custom_fields($field_to, $where = array(), $exclude_only_admin = false)
{
    $is_admin = is_admin();
    $CI =& get_instance();
    $CI->db->where('fieldto', $field_to);
    if (count($where) > 0) {
        $CI->db->where($where);
    }
    if (!$is_admin || $exclude_only_admin == true) {
        $CI->db->where('only_admin', 0);
    }
    $CI->db->where('active', 1);
    $CI->db->order_by('field_order', 'asc');

    $results = $CI->db->get(db_prefix() . 'customfields')->result_array();

    foreach ($results as $key => $result) {
        $results[$key]['name'] = _l('cf_translate_' . $result['slug'], '', false) != 'cf_translate_' . $result['slug'] ? _l('cf_translate_' . $result['slug'], '', false) : $result['name'];
    }

    return $results;
}

function _maybe_translate_custom_field_name($name, $slug)
{
    return _l('cf_translate_' . $slug, '', false) != 'cf_translate_' . $slug ? _l('cf_translate_' . $slug, '', false) : $name;
}
/**
 * Get custom field value
 * @param  mixed $rel_id              the main ID from the table, e.q. the customer id, invoice id
 * @param  mixed $field_id_or_slug    field id, the custom field ID or custom field slug
 * @param  string $field_to           belongs to e.q leads, customers, staff
 * @param  string $format             format date values
 * @return string
 */
function get_custom_field_value($rel_id, $field_id, $field_to, $format = true)
{
    $CI =& get_instance();
    $CI->db->where('relid', $rel_id);
    $CI->db->where('fieldid', $field_id);
    $CI->db->where('fieldto', $field_to);
    $row    = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
    $result = '';
    if ($row) {
        $result = $row->value;
        if ($format == true) {
            $CI->db->where('id', $field_id);
            $_row = $CI->db->get(db_prefix() . 'customfields')->row();
            if ($_row->type == 'date_picker') {
                $result = _d($result);
            } elseif ($_row->type == 'date_picker_time') {
                $result = _dt($result);
            }
        }
    }

    return $result;
}