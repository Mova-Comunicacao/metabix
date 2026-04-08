<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|   http://example.com/
|
| If this is not set then CodeIgniter will try guess the protocol, domain
| and path to your installation. However, you should always configure this
| explicitly and never rely on auto-guessing, especially in production
| environments.
|
*/

define('APP_BASE_URL','http://localhost/metabix/');

/*
|--------------------------------------------------------------------------
| Encryption Key
| IMPORTANT: Don't change this EVER
|--------------------------------------------------------------------------
|
| If you use the Encryption class, you must set an encryption key.
| See the user guide for more info.
|
| http://codeigniter.com/user_guide/libraries/encryption.html
|
| Auto updated added on install
*/

$key = bin2hex(random_bytes(16));

define('APP_ENC_KEY',$key);

/* Database credentials - Auto added on install */

/* The hostname of your database server. */
define('APP_DB_HOSTNAME','localhost');
/* The username used to connect to the database */
define('APP_DB_USERNAME','root');
/* The password used to connect to the database */
define('APP_DB_PASSWORD','mova_123');
/* The name of the database you want to connect to */
define('APP_DB_NAME','metabix');

/** Database charset */
define('APP_DB_CHARSET', 'utf8');
/** Database collation */
define('APP_DB_COLLATION', 'utf8_general_ci');
/** GROQ_API_KEY */
define('STABILITY_TOKEN', 'sk-x3b6Eezgs1MMDcLrKPGuvZq24bjpDefv4my1OFB5BifliAUv');

define('HF_TOKEN', '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');
/**
 *
 * Session handler driver
 * By default the database driver will be used.
 *
 * For files session use this config:
 * define('SESS_DRIVER','files');
 * define('SESS_SAVE_PATH',NULL);
 * In case you are having problem with the SESS_SAVE_PATH consult with your hosting provider to set "session.save_path" value to php.ini
 *
 */

define('SESS_DRIVER','database');
define('SESS_SAVE_PATH','apisessions');
define('APP_SESSION_COOKIE_SAME_SITE', 'Lax');

/**
 * Enables CSRF Protection
 */
define('APP_CSRF_PROTECTION', TRUE);