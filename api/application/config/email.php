<?php

// =======================
// SMTP / Email Config
// =======================

$config['useragent']    = get_option('mail_engine') ; // phpmailer or codeigniter
$config['protocol']     = get_option('email_protocol');
$config['mailpath']     = '/usr/bin/sendmail'; // or "/usr/sbin/sendmail"


$config['smtp_host']    = trim(get_option('smtp_host'));
$config['smtp_user']    = trim(get_option('smtp_username') ?: get_option('smtp_email'));
$config['smtp_pass']    = get_instance()->encryption->decrypt(get_option('smtp_password'));
$config['smtp_port']    = trim(get_option('smtp_port'));
$config['smtp_timeout'] = 30;
$config['smtp_crypto']  = get_option('smtp_encryption');

// 0 = Sem debug | 1 = comandos | 2 = comandos + dados | 3 = com status da conexão | 4 = nível baixo
$config['smtp_debug'] = 0;                       

// Saída do debug: 'html', 'echo', 'error_log'
$config['debug_output'] = 'html';                    

// TLS automático mesmo sem definir 'smtp_crypto = tls'
$config['smtp_auto_tls'] = false;

// Opções adicionais de conexão
$config['smtp_conn_options'] = [];          

$config['wordwrap']     = true;
$config['mailtype']     = 'html';

// Charset
$charset = strtoupper(trim(get_option('smtp_email_charset')));
$config['charset'] = ($charset == '' || stripos($charset, 'utf8') !== false) ? 'utf-8' : $charset;

$config['validate']     = false;
$config['priority']     = 3; // 1 (alta) - 5 (baixa)

// Quebras de linha
$config['newline']        = "\r\n";
$config['crlf']           = "\r\n";

// BCC batch
$config['bcc_batch_mode'] = false;
$config['bcc_batch_size'] = 200;

// The body encoding. For CodeIgniter: '8bit' or '7bit'. For PHPMailer: '8bit', '7bit', binary', 'base64', or 'quoted-printable'.
$config['encoding']       = '8bit';                   

// =======================
// XOAUTH2 (Opcional)
// =======================

// XOAUTH2 mechanism for authentication.
// See https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2
$config['oauth_type']          = 'xoauth2_google';      // XOAUTH2 authentication mechanism:
                                                        // ''                  - disabled;
                                                        // 'xoauth2'           - custom implementation;
                                                        // 'xoauth2_google'    - Google provider;
                                                        // 'xoauth2_yahoo'     - Yahoo provider;
                                                        // 'xoauth2_microsoft' - Microsoft provider.
$config['oauth_instance']      = null;                  // Initialized instance of \PHPMailer\PHPMailer\OAuth (OAuthTokenProvider interface) that contains a custom token provider. Needed for 'xoauth2' custom implementation only. 
$config['oauth_user_email']    = '';                    // If this option is an empty string or null, $config['smtp_user'] will be used.
$config['oauth_client_id']     = '237644427849-g8d0pnkd1jh3idcjdbopvkse2hvj0tdp.apps.googleusercontent.com';
$config['oauth_client_secret'] = 'mklHhrns6eF-qjwuiLpSB4DL';
$config['oauth_refresh_token'] = '1/7Jt8_RHX86Pk09VTfQd4O_ZqKbmuV7HpMNz-rqJ4KdQMEudVrK5jSpoR30zcRFq6';

// =======================
// DKIM (Opcional)
// =======================

// DKIM Signing
// See https://yomotherboard.com/how-to-setup-email-server-dkim-keys/
// See http://stackoverflow.com/questions/24463425/send-mail-in-phpmailer-using-dkim-keys
// See https://github.com/PHPMailer/PHPMailer/blob/v5.2.14/test/phpmailerTest.php#L1708
$config['dkim_domain']         = '';                       // DKIM signing domain name, for exmple 'example.com'.
$config['dkim_private']        = '';                       // DKIM private key, set as a file path.
$config['dkim_private_string'] = '';                    // DKIM private key, set directly from a string.
$config['dkim_selector']       = '';                       // DKIM selector.
$config['dkim_passphrase']     = '';                       // DKIM passphrase, used if your key is encrypted.
$config['dkim_identity']       = '';                       // DKIM Identity, usually the email address used as the source of the email.

if (file_exists(APPPATH . 'config/my_email.php')) {
    include_once(APPPATH . 'config/my_email.php');
}
