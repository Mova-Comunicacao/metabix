<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Authentication extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        hooks()->do_action('clients_authentication_constructor', $this);

        $this->load->helper(['url', 'security']);
        $this->load->library(['session']);
        
        $this->load->model('users_model');
    }

    /**
     * POST /auth/login
     * Body:
     *  - email (string, required)
     *  - password (string, required)
     *  - remember (bool, optional)
     *  - staff (bool, optional) -> true = staff; false = cliente/contato
     *
     * Resposta 200:
     *  {
     *    "user": {...},
     *    "accessToken": "jwt",
     *    "refreshToken": "plain"
     *  }
     *
     * Se staff com 2FA habilitado:
     *  { "require_2fa": true }
     */
    public function login()
    {
        $formdata = json_decode(file_get_contents('php://input'), true);

        if (empty($formdata)) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['type' => 'error', 'message' => 'Bad request']));
        }            

        $this->load->model('authentication_model');
        $result = $this->authentication_model->login(
            trim((string)($formdata['email'] ?? '')),
            (string)($formdata['password'] ?? ''),
            true,
            true
        );

        if (is_array($result['user']) && isset($result['user']['memberinactive'])) {
            $response = ['type' => 'error', 'message' => _l('admin_auth_inactive_account')];
            return $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }
    
        if ($result['user'] === false) {
            $response = ['type' => 'error', 'message' => _l('admin_auth_invalid_email_or_password')];
            return $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }

        // Se o staff tiver 2FA ativo, sua model mantém a sessão *após* 2FA; aqui você pode optar:
        // Detectar necessidade de 2FA e NÃO emitir tokens até confirmar o TOTP/SMS.
        if (true && isset($result['user']->two_factor_auth_enabled) && (int)$result['user']->two_factor_auth_enabled === 1) {
            // Neste fluxo, não emitimos tokens aqui. O controller específico de 2FA (ex.: /auth/2fa-verify)
            // chamará o finish_login() abaixo após validação do código.
            // Você pode guardar na sessão um "pending_2fa_user".
            $this->session->set_userdata([
                'pending_2fa_user' => $result['user'],
                'pending_2fa_is_staff' => true,
            ]);
            // Limpa tokens retornados pela model para evitar uso indevido
            return $this->respond(200, ['require_2fa' => true]);
        }

        hooks()->do_action('after_staff_login');
                    
        $response = [
            'type'          => 'success',
            'message'       => 'ok',
            'token'         => $result['accessToken'],
            'refreshToken'  => $result['refreshToken'],
            //'expiresIn'     => $result['expiresIn'],   // ISO 8601
            //'expiresInSec'  => $accessTtlSec, // opcional
        ];

        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));                 
    }    

    /**
     * (Opcional) POST /auth/2fa-verify
     * Body:
     *  - code (string)   -> seu verificador TOTP/SMS
     * Fluxo:
     *  - valida code externo (não incluso aqui)
     *  - emite tokens, finalizando login do staff com 2FA
     */
    public function two_fa_verify()
    {
        // Exemplo minimalista: supondo que você já validou o code em outro helper.
        $code = trim((string)$this->input->post('code', true));
        if ($code === '') {
            return $this->respond(422, ['error' => 'Código 2FA é obrigatório.']);
        }

        $pending = $this->session->userdata('pending_2fa_user');
        $isStaff = $this->session->userdata('pending_2fa_is_staff') === true;

        if (!$pending || !$isStaff) {
            $response = ['type' => 'error', 'message' => 'Nenhuma sessão pendente de 2FA'];
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }

        $this->load->model('authentication_model');

        // TODO: validar $code (TOTP/SMS) conforme sua implementação

        // Emite tokens agora
        $sub  = 'staff:' . $pending->staffid;
        $role = 'staff';
        [$accessToken] = $this->authentication_model->verify_jwt('dummy') === null // só para silenciar analisadores
            ? $this->authentication_model->makeJwt($sub, $role) // método é private; se preferir exponha um wrapper público
            : $this->authentication_model->makeJwt($sub, $role);

        // Como makeJwt é private na model fornecida, crie um wrapper público:
        // ex.: public function issue_access_for($sub, $role) { return $this->makeJwt($sub, $role); }

        // Vamos usar um wrapper público para evitar mexer no escopo:
        if (!method_exists($this->authentication_model, 'issue_access_for')) {
            $response = ['type' => 'error', 'message' => 'Método issue_access_for não encontrado na model. Crie um wrapper público para makeJwt.'];
            return $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }
        [$accessToken] = $this->authentication_model->issue_access_for($sub, $role);

        $refresh = $this->authentication_model->create_refresh_token((int)$pending->staffid, true, null, $this->input->user_agent(), $this->input->ip_address());
        // Finaliza sessão de pendência
        $this->session->unset_userdata('pending_2fa_user');
        $this->session->unset_userdata('pending_2fa_is_staff');

        $response = [            
            'user'         => $pending,
            'accessToken'  => $accessToken,
            'refreshToken' => $refresh['plain'],
        ];

        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }


    /**
     * Token
     *
     * @return void
     */
    public function me() 
    {

        $this->load->model('authentication_model');
        $this->load->library('Jwt_service');

        $staffId = $this->session->userdata('staff_user_id')
            ?: $this->session->userdata('staff_logged_in')
            ?: $this->session->userdata('staffid')
            ?: $this->session->userdata('user_id')
            ?: $this->session->userdata('userid')
            ?: null;

        if (!$staffId) {
            $payload = $this->jwt_service->requirePayloadFromBearer(); // 401 se inválido
            $staffId = (int)($payload->sub ?? 0);
        }       

        /*
        if (!$staffId) {
            $authHeader = $this->input->get_request_header('Authorization', true);
            if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
                $token = trim(substr($authHeader, 7));
                $isValidToken = $this->authentication_model->check_token($token, true);
                if ($isValidToken && !empty($isValidToken->staffid)) {
                    $staffId = (int) $isValidToken->staffid;
                }
            }
        }
        */

        if (!$staffId) {
            $response = ['type' => 'error', 'message' => 'Unauthorized'];
            return $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                
        }

        $user = $this->authentication_model->staff($staffId, true);
        if (!$user) {
            $response = ['type' => 'error', 'message' => 'User not found'];
            return $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
        }        

        /*
        $isValidToken = $this->authentication_model->check_token($token, true);
        if (!$isValidToken) {
            return $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['type' => 'error', 'message' => 'Unauthorized']));
        }
        */

        $response = [
            'staffid'          => (int) $user->staffid,
            'firstname'        => (string) $user->firstname,
            'lastname'         => (string) $user->lastname,
            'email'            => (string) $user->email,
            'phone'            => (string) $user->phone,
            'avatar'           => staff_profile_image_url($user->staffid, 'small'),
            'username'         => (string) $user->username,
            'default_language' => (get_staff_default_language($user->staffid) !== '')
                ? get_staff_default_language($user->staffid)
                : get_option('active_language'),
            'role'             => $user->role,
            'admin'            => (int) $user->admin,
            'address'          => $user->address,
            'website'          => $user->website,
            'active'           => (int) $user->active,
        ];
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));          

    }    

    /**
     * POST /auth/logout
     * Body:
     *  - refreshToken (string, optional – se enviado, revoga-o)
     *  - staff (bool, optional)
     */
    public function logout()
    {
        hooks()->do_action('after_admin_logout');
        
        $this->load->model('authentication_model');
        $success = $this->authentication_model->logout(true, $refreshPlain = '');
        
        if($success == true) {
            $response = array(
                'type' => 'success',
                'message' => 'ok'
            );  
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));  
    }    

    public function register()
    {

        $honeypot = get_option('enable_honeypot_spam_validation') == 1;

        $fields = [
        'firstname' => $honeypot ? 'firstnamemjxw' : 'firstname',
        'lastname'  => $honeypot ? 'lastnamemjxw' : 'lastname',
        'email'     => $honeypot ? 'emailmjxw' : 'email',
        'username'  => $honeypot ? 'usernamemjxw' : 'username',
        'company'   => $honeypot ? 'companymjxw' : 'company', // ADICIONE isto se for usar
        ];

        $formdata = json_decode(file_get_contents('php://input'), true);

        if (!empty($formdata)) {
            if ($honeypot) {

                return true;
            }  

            $plainPassword = $formdata['password'];
            
            $countryId = is_numeric($formdata['country']) ? $formdata['country'] : 0;
            if (is_automatic_calling_codes_enable()) {
                $customerCountry = get_country($countryId);
                
                if ($customerCountry) {
                    $callingCode = '+' . ltrim($customerCountry->calling_code, '+');
                    
                    if (startsWith($formdata['contact_phonenumber'], $customerCountry->calling_code)) { // with calling code but without the + prefix
                        $formdata['contact_phonenumber'] = '+' . $formdata['contact_phonenumber'];
                    } elseif (!startsWith($formdata['contact_phonenumber'], $callingCode)) {
                        $formdata['contact_phonenumber'] = $callingCode . $formdata['contact_phonenumber'];
                    }
                }                
            }

            $this->db->where('email', $formdata['email']);
            $email = $this->db->get(db_prefix() . 'users')->row();
            
            if ($email) {
                $response = array(
                    'type' => 'error',
                    'message' => 'E-mail já existe',
                );
                
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));                 
            }            
            
            define('CONTACT_REGISTERING', true);
            
            $clientid = $this->users_model->add([      
                'firstname'           => $formdata[$fields['firstname']],
                'lastname'            => $formdata[$fields['lastname']],
                'email'               => $formdata[$fields['email']],
                'password'            => $formdata['rPassword'],             
                'username'            => $formdata[$fields['username']],
                'description'         => $formdata['description'],
                'billing_street'      => $formdata['address'],
                'billing_city'        => $formdata['city'],
                'billing_state'       => $formdata['state'],
                'billing_zip'         => $formdata['zip'],
                'billing_country'     => $countryId,     
                'contact_phonenumber' => $formdata['contact_phonenumber'] ,   
                'company'             => $formdata[$fields['company']],
                //'vat'                 => isset($formdata['vat']) ? $data['vat'] : '',
                'phone'                 => $formdata['phone'],
                'country'             => $formdata['country'],
                'city'                => $formdata['city'],
                'address'             => $formdata['address'],
                'zip'                 => $formdata['zip'],
                'state'               => $formdata['state'],                        
                'default_language'    => get_option('active_language') //(get_contact_language() != '') ? get_contact_language() : get_option('active_language'),
            ], false);   
            

            if ($clientid) {
                hooks()->do_action('after_client_register', $clientid);
            
                if (get_option('customers_register_require_confirmation') == '1') {
                    send_customer_registered_email_to_administrators($clientid);

                    $this->clients_model->require_confirmation($clientid);
                    $response = array(
                        'type' => 'success',
                        'message' => _l('customer_register_account_confirmation_approval_notice'),
                    );	 
                }                

                $this->load->model('authentication_model');

                $logged_in = $this->authentication_model->login(
                    $formdata['email'],
                    $plainPassword,
                    false,
                    false
                );     
                
                if ($logged_in) {
                    hooks()->do_action('after_client_register_logged_in', $clientid);
                    $response = array(
                        'type' => 'success',
                        'message' => _l('clients_successfully_registered'),
                    );	                    
                } else {
                    $response = array(
                        'type' => 'warning',
                        'message' => _l('clients_account_created_but_not_logged_in'),
                    );	                     
                }

                send_customer_registered_email_to_administrators($clientid);                
            }      
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                 
        }        
    }    
    

    public function forgotPassword() 
    {
        $formdata = json_decode(file_get_contents('php://input'), true);

        if (!empty($formdata)) {
            $email = $formdata['email'];

            $this->load->model('authentication_model');
            $success = $this->authentication_model->forgot_password($email, true);

            if (is_array($success) && isset($success['memberinactive'])) {
                $response = array(
                    'type' => 'error',
                    'message' => _l('inactive_account')
                );
            } elseif ($success == true) {
                $response = array(
                    'type' => 'success',
                    'message' => _l('check_email_for_resetting_password')
                );                
            } else {
                $response = array(
                    'type' => 'error',
                    'message' => _l('error_setting_new_password_key')
                );                
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));              
        }        
    }  

    public function newPassword() 
    {
        $formdata = json_decode(file_get_contents('php://input'), true);

        if ($formdata) {
            $new_pass_key = $formdata['currentPassword'];
            $password = $formdata['password'];

            $this->load->model('authentication_model');
            if (!$this->authentication_model->can_reset_password(true, $new_pass_key)) {
                $response = array(
                    'type' => 'error',
                    'message' => _l('password_reset_key_expired')
                ); 
            }
            $success = $this->authentication_model->new_password(true, $new_pass_key, $password);
            if (is_array($success) && isset($success['expired'])) {
                $response = array(
                    'type' => 'error',
                    'message' => _l('password_reset_key_expired')
                );
            } elseif ($success == true){
                $response = array(
                    'type' => 'success',
                    'message' => _l('password_reset_message')
                );                
            } else {
                $response = array(
                    'type' => 'error',
                    'message' => _l('password_reset_message_fail')
                );                
            }   

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                    

        }
    }  

    public function setPassword() 
    {
        $formdata = json_decode(file_get_contents('php://input'), true);

        if ($formdata) {
            $new_pass_key = $formdata['currentPassword'];
            $password = $formdata['password'];

            //$admin = $this->admin_model->get($currentPassword);
            $success = $this->authentication_model->set_password($staff = false, $userid, $new_pass_key, $password);
            if (is_array($success) && isset($success['expired'])) {
                $response = array(
                    'type' => 'error',
                    'message' => _l('password_reset_key_expired')
                );
            } else {
                $response = array(
                    'type' => 'success',
                    'message' => _l('password_reset_message')
                );                
            }   

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));                    

        }
    }    
       
}
