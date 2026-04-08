<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Deprecated function error
 * @param  string $function    The function that was called
 * @param  string $version     The version that deprecated the function
 * @param  string $replacement The new function that should be called
 * @return mixed
 */
function _deprecated_function($function, $version, $replacement = null)
{
    hooks()->do_action('deprecated_function_run', $function, $replacement, $version);

    /**
     * Filters whether to trigger an error for deprecated functions.
     *
     * @since 2.3.2
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if (ENVIRONMENT != 'production' && hooks()->apply_filters('deprecated_function_trigger_error', true)) {
        if (! is_null($replacement)) {
            trigger_error(sprintf('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $function, $version, $replacement));
        } else {
            trigger_error(sprintf('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $function, $version));
        }

        _has_deprecated_errors_admin_body_class();
    }
}

function _deprecated_hook($hook, $version, $replacement = null, $message = null)
{
    hooks()->do_action('deprecated_hook_run', $hook, $replacement, $version, $message);

    /**
     * Filters whether to trigger deprecated hook errors.
     *
     * @since 2.3.1
     */
    if (ENVIRONMENT != 'production' && hooks()->apply_filters('deprecated_hook_trigger_error', true)) {
        $message = empty($message) ? '' : ' ' . $message;

        if (! is_null($replacement)) {
            /* translators: 1: Hook name, 2: version number, 3: alternative hook name */
            trigger_error(sprintf('Hook %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $hook, $version, $replacement) . $message);
        } else {
            /* translators: 1: Hook name, 2: version number */
            trigger_error(sprintf('Hook %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $hook, $version) . $message);
        }

        _has_deprecated_errors_admin_body_class();
    }
}

/**
 * @since  2.3.2
 * @private
 * Adds filter for admin body class to add has-deprecated-errors class on body
 * This is available only when the errors are thrown in php files like classes, before the VIEW loads
 * because when the errors are thrown after of <body> the menu item is hidding the errors
 * @return void
 */
function _has_deprecated_errors_admin_body_class()
{
    if (hooks()->has_filter('admin_body_class', '_add_has_deprecated_errors_admin_body_class')) {
        return;
    }

    hooks()->add_filter('admin_body_class', '_add_has_deprecated_errors_admin_body_class');
}

/**
 * @since  2.3.2
 * @private
 * Adds has-deprecated-errors class to body
 * @return array
 */
function _add_has_deprecated_errors_admin_body_class($classes)
{
    $classes[] = 'has-deprecated-errors';

    return $classes;
}