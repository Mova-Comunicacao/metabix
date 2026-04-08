<?php
defined('BASEPATH') or exit('No direct script access allowed');
define('CRM_AREA',true);

class CRMController extends Api_Controller
{

    public function __construct()
    {
        parent::__construct();
 
        // Model  
        $this->load->model('settings_model'); 

        $language = load_admin_language(); 

        $auto_loaded_vars = array(
            'app_language'=> $language,
        );

        $auto_loaded_vars =  hooks()->apply_filters('before_set_auto_loaded_vars_admin_area', $auto_loaded_vars);

        $this->load->vars($auto_loaded_vars);         
    }
     
}