<?php
defined('BASEPATH') or exit('No direct script access allowed');

function is_gdpr()
{
    return get_option('enable_gdpr') === '1';
}
