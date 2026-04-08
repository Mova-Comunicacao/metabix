<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['jwt_secret']   = APP_ENC_KEY;
$config['jwt_issuer']   = base_url();   // quem emite (iss)
$config['jwt_audience'] = base_url();   // quem consome (aud)
$config['jwt_ttl']      = 900;          // 15 minutos
$config['jwt_leeway']   = 60;           // tolerância de clock (segundos)