<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @since  2.3.0
 * Module libraries path
 * e.q. modules/module_name/libraries
 * @param  string $module module name
 * @param  string $concat append additional string to the path
 * @return string
 */
function module_libs_path($module, $concat = '')
{
    return module_dir_path($module) . 'libraries/' . $concat;
}

/**
 * @since  2.3.0
 * Module directory absolute path
 * @param  string $module module system name
 * @param  string $concat append additional string to the path
 * @return string
 */
function module_dir_path($module, $concat = '')
{
    return APP_MODULES_PATH . $module . '/' . $concat;
}