<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check if there is dot in database name and throws warning message.
 * @return void
 */
function _maybe_dot_in_database_name()
{
    if (defined('APP_DB_NAME') && strpos(APP_DB_NAME, '.') !== false) {
        ?>
        <div class="col-md-12">
            <div class="alert alert-warning">
                <h4>Database name (<?php echo APP_DB_NAME; ?>) change required.</h4>
                The system indicated that your database name contains <b>. (dot)</b>, you can encounter upgrading errors when your database name contains dot, it's highly recommended to change your database name to be without dot as example: <?php echo str_replace('.', '', APP_DB_NAME); ?>
                <hr />
                <ul>
                    <li>1. Change the name to be without dot via cPanel/Command line or contact your hosting provider/server administrator to change the name. (use the best method that is suitable for you)</li>
                    <li>2. After the name is changed navigate via ftp or cPanel to application/config/app-config.php and change the database name config constant to your new database name.</li>
                    <li>3. Save the modified app-config.php file.</li>
                </ul>
                <br />
                <small>This message will disappear automatically once the database name won't contain dot.</small>
         </div>
     </div>
     <?php
    }
}

/**
 * Notice for Cloudflare rocket loader usage
 * The application wont work good if cloudflare rocket loader is enabled
 * @return null
 */
function _maybe_using_cloudflare_rocket_loader()
{
    $CI = &get_instance();
    $header = $CI->input->get_request_header('Cf-Ray');

    if ($header && !empty($header) && get_option('show_cloudflare_notice') == '1' && is_admin()) {
        ob_start(); ?>
            <div class="col-md-12">
            <div class="alert alert-warning font-medium">
            <div class="mtop15"></div>
            <h4><strong>Cloudflare usage detected</strong></h4><hr />
            <ul>
                <li>When using Cloudflare with the application <strong>you must disable ROCKET LOADER</strong> feature from Cloudflare options in order everything to work properly. <br /><strong><small>NOTE: The script can't check if Rocket Loader is enabled/disabled in your Cloudflare account. If Rocket Loader is already disabled you can ignore this warning.</small></strong></li>
            <li>
            <br />
                <ul>
                    <li><strong>&nbsp;&nbsp;- Disable Rocket Loader for whole domain name</strong></li>
                    <li>&nbsp;&nbsp;&nbsp;&nbsp;Login to your Cloudflare account and click on the <strong>Speed</strong> tab from the top dashboard, search for Rocket Loader and <strong>set to Off</strong>.</li>
                    <br />
                    <li><strong>&nbsp;&nbsp;- Disable Rocket Loader with page rule for application installation url</strong></li>
                    <li>
                        &nbsp;&nbsp;&nbsp;&nbsp;If you do not want to turn off Rocket Loader for the whole domain you can add <a href="https://support.cloudflare.com/hc/en-us/articles/200168306-Is-there-a-tutorial-for-Page-Rules-" target="_blank">page rule</a> that will disable the Rocket Loader only for the application, follow the steps below in order to achieve this.
                        <br /><br />
                        <p class="no-margin">&nbsp;&nbsp;- Login to your Cloudflare account and click on the <strong>Page Rules</strong> tab from the top dashboard</p>
                        <p class="no-margin">&nbsp;&nbsp;- Click on <strong>Create Page Rule</strong></p>
                        <p class="no-margin">&nbsp;&nbsp;- In the url field add the following url: <strong><?php echo rtrim(site_url(), '/').'/'; ?>*</strong></p>
                        <p class="no-margin">&nbsp;&nbsp;- Click <strong>Add Setting</strong> and search for <strong>Rocket Loader</strong></p>
                        <p class="no-margin">&nbsp;&nbsp;- After you select Rocket Loader <strong>set value to Off</strong></p>
                        <p class="no-margin">&nbsp;&nbsp;- Click <strong>Save and Deploy</strong></p>
                    </li>
                </ul>
            </li>
            </ul>
            <br /><br /><a href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>" class="alert-link">Got it! Don't show this message again</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo admin_url('misc/dismiss_cloudflare_notice'); ?>" class="alert-link">Rocket loader is already disabled</a>
            </div>
            </div>
    <?php
    $contents = ob_get_contents();
        ob_end_clean();
        echo $contents;
    }
}

/**
 * Check few timezones statements
 * @return void
 */
function _maybe_timezone_not_set()
{
    if (get_option('default_timezone') == '') {
        echo '<div class="col-md-12">';
        echo '<div class="alert alert-danger">';
        echo '<strong>Default timezone not set. Navigate to Setup->Settings->Localization to set default system timezone.</strong>';
        echo '</div>';
        echo '</div>';
    } else {
        if (!in_array(get_option('default_timezone'), array_flatten(get_timezones_list()))) {
            echo '<div class="col-md-12">';
            echo '<div class="alert alert-danger">';
            echo '<strong>We updated the timezone logic for the app. Seems like your previous timezone do not fit with the new logic. Navigate to Setup->Settings->Localization to set new proper timezone.</strong>';
            echo '</div>';
            echo '</div>';
        }
    }
}

/**
 * Show message on dashboard when environment is set to development or testing
 * @return void
 */
function _show_development_mode_message()
{
    if (ENVIRONMENT == 'development' || ENVIRONMENT == 'testing') {
        if (is_admin()) {
            echo '<div class="col-md-12">';
            echo '<div class="alert alert-warning">';
            echo 'Environment set to <b>' . ENVIRONMENT . '</b>. Don\'t forget to set back to <b>production</b> in the main index.php file after finishing your tests.';
            echo '</div>';
            echo '</div>';
        }
    }
}

function show_pdf_unable_to_get_image_size_error()
{
    ?>
   <div style="font-size:17px;">
   <hr />
    <p>This error can be shown if the <b>PDF library can't read the image from your server</b>.</p>
    <p>Very often this is happening <b>when you are using custom PDF logo url in Setup -> Settings -> PDF</b>, first make sure that the url you added in Setup->Settings->PDF for the custom pdf logo is valid and the image exists if the problem still exists you will need to use a <b>direct path</b> to the image to include in the PDF documents. Follow the steps mentioned below:</p>
    <p><strong>Method 1 (easy)</strong></p>
    <ul>
        <li>Upload the logo image in the installation directory eq. <?php echo FCPATH; ?>mylogo.jpg</li>
        <li><a href="<?php echo admin_url('settings?group=pdf'); ?>" target="_blank">Navigate to Setup -> Settings -> PDF</a> -> Custom PDF Company Logo URL and only add the filename like: <b>mylogo.jpg</b>, now Custom PDF Company Logo URL should be only filename not full URL.</li>
        <li>Try to re-generate PDF document again.</li>
    </ul>
     <p><strong>Method 2 (advanced)</strong></p>
     <small>Try this method if method 1 is still not working.</small>
    <ul>
        <li>Consult with your hosting provider to confirm that the server is able to use PHP's <a href="http://php.net/manual/en/function.file-get-contents.php" target="_blank">file_get_contents</a> or <a href="http://php.net/manual/en/curl.examples-basic.php" target="_blank">cUrl</a> to download the file. </li>
        <li>Try to re-generate PDF document again.</li>
    </ul>
   </div>
<?php
}