<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

/**
 * This function must be used for all hooks
 * @return object|Hooks Hooks instance
 */
function hooks()
{
    global $hooks;

    return $hooks;
}

$hook['pre_system'][] = [
  'class'    => '',
  'function' => 'cors',
  'filename' => 'Cors.php',
  'filepath' => 'hooks',
  'params'   => []
];

$hook['pre_system'][] = [
    'class'    => 'EnhanceSecurity',
    'function' => 'protect',
    'filename' => 'EnhanceSecurity.php',
    'filepath' => 'hooks',
    'params'   => [],
];

$hook['pre_system'][] = [
    'class'    => 'Api_Autoloader',
    'function' => 'register',
    'filename' => 'Api_Autoloader.php',
    'filepath' => 'hooks',
    'params'   => [],
];

$hook['pre_controller_constructor'][] = [
    'class'    => '',
    'function' => '_api_init',
    'filename' => 'InitHook.php',
    'filepath' => 'hooks',
];

if (file_exists(APPPATH . 'config/my_hooks.php')) {
    include_once(APPPATH . 'config/my_hooks.php');
}
