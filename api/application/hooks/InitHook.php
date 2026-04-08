<?php
defined('BASEPATH') or exit('No direct script access allowed');

function _api_init_load()
{
    $ci = &get_instance();

    $ci->load->library([
        //'mails/api_mail_template',
        'merge_fields/api_merge_fields',
        'api_object_cache',
    ]);

    $ci->load->helper([
        //'api_merge_fields',
    ]);    
}

function _api_init()
{
    $ci = &get_instance();

    _api_init_load();
}
