<?php
defined('BASEPATH') or exit('No direct script access allowed');
define('ADMIN_AREA',true);

class AdminController extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('authentication_model');
        $this->load->model('staff_model');

        hooks()->do_action('pre_admin_init');
        
        if (!is_staff_logged_in()) {}

        // Update staff last activity
        $this->db->where('staffid', get_staff_user_id());
        $this->db->update(db_prefix() . 'staff', ['last_activity' => date('Y-m-d H:i:s')]);   


        // Do not check on ajax requests
        if (!$this->input->is_ajax_request()) {
            if (ENVIRONMENT == 'production' && is_admin()) {
                if ($this->config->item('encryption_key') === '') {
                    die('<h1>Encryption key not sent in application/config/app-config.php</h1>For more info visit <a href="https://help.perfexcrm.com/encryption-key-explained/">encryption key explained</a>');
                } elseif (strlen($this->config->item('encryption_key')) != 32) {
                    die('<h1>Encryption key length should be 32 charachters</h1>For more info visit <a href="https://help.perfexcrm.com/encryption-key-explained/">encryption key explained</a>');
                }
            }
        }   


        $currentUser = $this->staff_model->get(get_staff_user_id());
        if (!$currentUser) {
            log_message('error', 'Usuário não encontrado ou sessão inválida (ID: ' . get_staff_user_id() . ')');
            
            show_error('Ops! O usuário da sessão não foi encontrado. Por favor, faça login novamente.', 500, 'Erro de autenticação');
        }    

        // Deleted or inactive but have session
        if (isset($currentUser->active) && $currentUser->active == 0) {
            log_message('error', 'Usuário inativo tentou acessar (ID: ' . get_staff_user_id() . ')');

            $this->authentication_model->logout();
            show_error('Sua conta está inativa. Entre em contato com o administrador.', 403, 'Acesso Negado');
        }

        $GLOBALS['current_user'] = $currentUser;   

        $currentLang = load_admin_language(); 
        $auto_loaded_vars = array(
            'current_user'    => $currentUser,
            'current_version' => $this->current_db_version,            
            'current_language'=> $currentLang,
        );

        /**
        * Autoloaded view variables
        * @auto_loaded_vars array
        */
        $auto_loaded_vars =  hooks()->apply_filters('before_set_auto_loaded_vars_admin_area', $auto_loaded_vars);
        $this->load->vars($auto_loaded_vars);         
    }

    /**
     * Sets view data
     * @param  array $data
     * @return core/ClientsController
     */

    /**
     * Sets view data
     * @param  array $data
     * @return core/ClientsController
     */
    public function load_lang()
    {
        return $language = load_client_language(); ;
    }       
}