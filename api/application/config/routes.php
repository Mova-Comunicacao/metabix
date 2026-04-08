<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['translate_uri_dashes'] = FALSE;

$route['default_controller'] = 'welcome';
$route['404_override'] = '';

// Aliases for short routes
$route['auth/resetPassword']         = 'authentication/resetPassword';
$route['auth/forgotPassword']        = 'authentication/forgotPassword';
$route['auth/newPassword']           = 'authentication/newPassword';
$route['auth/register']              = 'authentication/register';

$route['auth/me']                    = 'authentication/me';

$route['auth/login']                 = 'authentication/login';
$route['auth/logout']                = 'authentication/logout';

/**
 * Admin
 */
$route['dashboard']                  = "admin/dashboard";
$route['settings']                   = "admin/settings";


/**
 * Misc controller routes
 */
$route['admin/access_denied']        = 'admin/misc/access_denied';
$route['admin/not_found']            = 'admin/misc/not_found';

/**
 * Terms and conditions and Privacy Policy routes
 */
$route['terms-and-conditions'] = 'terms_and_conditions';
$route['privacy-policy']       = 'privacy_policy';

/* Site links and routes */
// // In case if site access directly to url without the arguments redirect to site url
$route['/']  = "site";

/**
 * CRM
 */

 // Contacts
$route['contacts']  = "crm/contacts";
$route['contacts/(:num)']  = "crm/contacts/getItemById/$1";

// clients
$route['clients']  = "crm/clients";
$route['clients/(:num)']  = "crm/clients/getItemById/$1";


if (file_exists(APPPATH . 'config/my_routes.php')) {
    include_once(APPPATH . 'config/my_routes.php');
}