<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Authentication_model extends Api_Model
{

    private $jwtSecret;
    private $jwtIss;
    private $jwtAud;
    private $accessTtl;
    private $refreshTtl;
    private $pepper;

    public function __construct()
    {
        parent::__construct();
        
        // Ajuste estes valores no config.php ou .env
        $this->jwtSecret = config_item('JWT_SECRET');
        $this->jwtIss    = config_item('JWT_ISS') ?? 'app';
        $this->jwtAud    = config_item('JWT_AUD') ?? 'app-web';
        $this->accessTtl = (int) (config_item('ACCESS_TTL') ?? 900);       // 15 min
        $this->refreshTtl= (int) (config_item('REFRESH_TTL') ?? 2592000);  // 30 dias
        $this->pepper    = (string) (config_item('TOKEN_PEPPER') ?? '');

        if (!$this->jwtSecret) {
            log_message('error', 'JWT_SECRET não definido em config.');
        }        
    }

    /**
     * Login: mantém sua sessão + retorna tokens (access JWT + refresh plain)
     *
     * @param  string  $email
     * @param  string  $password
     * @param  boolean $remember (mantive assinatura, mas refresh cobre isso)
     * @param  boolean $staff
     * @return array|false  Em caso de sucesso retorna:
     *   ['user' => $userObj, 'accessToken' => 'jwt', 'refreshToken' => 'plain']
     */
    public function login($email, $password, $remember, $staff)
    {

        if (empty($email) || empty($password)) {
            return false;
        }

        $table  = db_prefix() . ($staff ? 'staff' : 'users');
        $_id    = $staff ? 'staffid' : 'userid';

        $this->db->where('email', $email);
        $user = $this->db->get($table)->row();

        if (!$user) {
            hooks()->do_action('non_existent_user_login_attempt', [
                'email'           => $email,
                'is_staff_member' => $staff,
            ]);
            log_activity(
                'Non Existing User Tried to Login [Email:' . $email . ', Is Staff Member:' . ($staff ? 'Yes' : 'No') . ' ' . $this->input->ip_address() .  ']',
                'failed'
            );
            return false;
        }

        // Email is okey lets check the password now
        if (!app_hasher()->CheckPassword($password, $user->password)) {
            hooks()->do_action('failed_login_attempt', [
                'user' => $user,
                'is_staff_member' => $staff,
            ]);
            log_activity(
                'Failed Login Attempt [Email:' . $email . ', Is Staff Member:' . ($staff ? 'Yes' : 'No') . ' ' . $this->input->ip_address() . ']',
                'failed',
                $user->{$_id}
            );
            return false;
        }
        
        if ((int)$user->active === 0) {
            hooks()->do_action('inactive_user_login_attempt', [
                'user'            => $user,
                'is_staff_member' => $staff,
            ]);
            log_activity(
                'Inactive User Tried to Login [Email:' . $email . ', Is Staff Member:' . ($staff ? 'Yes' : 'No') . ' ' . $this->input->ip_address() . ']',
                'failed',
                $user->{$_id}
            );
            // Mantive retorno compatível com o seu código:
            return ['memberinactive' => true];
        }

        // 2FA (somente staff)
        $twoFactorAuth = false;
        if ($staff === true) {
            $twoFactorAuth = (isset($user->two_factor_auth_enabled) && (int)$user->two_factor_auth_enabled === 1);
        }

        // Hooks + sessão
        if ($staff) {
            if (!$twoFactorAuth) {
                hooks()->do_action('before_staff_login', [
                    'email' => $email,
                    'staffid' => $user->{$_id}
                ]);
                $this->session->set_userdata([
                    'staff_user_id'   => $user->{$_id},
                    'staff_logged_in' => true,
                ]);
            }
        } else {
            hooks()->do_action('before_client_login', [
                'email'           => $email,
                'userid'          => $user->userid,      // id do cliente (empresa)
                'contact_user_id' => $user->{$_id},      // id do contato
            ]);
            $this->session->set_userdata([
                'client_user_id'   => $user->userid,
                'contact_user_id'  => $user->{$_id},
                'client_logged_in' => true,
            ]);
        }

        if (!$twoFactorAuth) {
            // remember antigo era cookie/token; com refresh rotativo não é necessário,
            // mas mantemos a chamada de update de login.
            $this->update_login_info($user->{$_id}, $staff);
        }

        // Token
        $sub  = ($staff ? 'staff:' : 'user:') . $user->{$_id};
        $role = $staff ? 'staff' : 'user';
        [$accessToken/*, $jti*/, $expTs] = $this->makeJwt($sub, $role);

        // refresh com família
        [$refreshPlain, $family] = $this->generateRefresh();
        $this->storeRefreshHash(
            $refreshPlain,
            $staff ? (int)$user->{$_id} : (int)$user->{$_id}, // id do contato (lado client)
            $staff,
            $family,
            $this->input->user_agent(),
            $this->input->ip_address()
        );

        return [
            'user'         => $user,
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshPlain,     
            //'expiresIn'    => gmdate('c', $expTs),  // ISO-8601 para o front (ex.: "2025-09-17T12:34:56Z")     
        ];

    }

    /**
     * @param  integer ID
     * @param  boolean Is Client or Staff
     * @return none
     * Update login info on autologin
     */
    private function update_login_info($user_id, $staff)
    {
        $table = db_prefix() . ($staff ? 'staff' : 'users');
        $_id   = $staff ? 'staffid' : 'userid';

        $this->db->set('last_ip', $this->input->ip_address());
        $this->db->set('last_login', date('Y-m-d H:i:s'));
        $this->db->where($_id, $user_id)->update($table);

       log_activity('User Successfully Logged In [User Id: ' . $user_id . ', Is Staff Member: ' . ($staff ? 'Yes' : 'No') . ']', 'update');
    }    

    /**
     * @param  boolean If Client or Admin
     * @return none
     */
    public function logout($staff = true, $refreshTokenPlain = '')
    {
        if ($refreshTokenPlain) {
            $this->revoke_refresh_by_plain($refreshTokenPlain, $staff);
        }

        // Mantive seus hooks e variáveis de sessão
        if (is_client_logged_in()) {
            hooks()->do_action('before_admin_logout', get_admin_id());
            $this->session->unset_userdata('client_user_id');
            $this->session->unset_userdata('client_logged_in');
            $this->session->unset_userdata('contact_user_id');
        } else {
            hooks()->do_action('before_staff_logout', get_staff_user_id());
            $this->session->unset_userdata('staff_user_id');
            $this->session->unset_userdata('staff_logged_in');
        }

        $this->session->sess_destroy();
        
        return true;
    } 
 
    
    /**
     * GET staff
     *
     * @param string $id
     * @param [type] $staff
     * @param array $where
     * @return void
     */    
    public function staff($id = '', $staff,  $where = array())
    {
        $table = db_prefix() . ($staff ? 'staff' : 'users');
        $_id   = $staff ? 'staffid' : 'userid';

        $this->db->select('*')->where($where)->where($_id, $id);
        return $this->db->get($table)->row();
    }    

    /**
     * Refresh Session
     *
     * @param string $refreshTokenPlain
     * @param boolean $isStaff
     * @param string|null $userAgent
     * @param string|null $ip
     * @return void
     */
    public function refresh_session(string $refreshTokenPlain, bool $isStaff, ?string $userAgent = null, ?string $ip = null)
    {
        $row = $this->findRefreshRowByPlain($refreshTokenPlain, $isStaff);
        if (!$row) {
            return [null, null, 'Refresh token inválido'];
        }
        if ((int)$row['revoked'] === 1) {
            // reuse detection → revoga família
            $this->revokeFamily($row['family_id'], $isStaff);
            return [null, null, 'Reuse detectado. Família revogada.'];
        }
        if (strtotime($row['expires_at']) <= time()) {
            return [null, null, 'Refresh expirado'];
        }

        $userId = (int)$row['user_id'];
        $sub    = ($isStaff ? 'staff:' : 'user:') . $userId;
        $role   = $isStaff ? 'staff' : 'user';

        [$access/*,$jti*/] = $this->makeJwt($sub, $role);
        [$newRefreshPlain, $familyId] = $this->generateRefresh($row['family_id']);

        // revoga o atual e salva o novo na mesma família
        $this->revokeRefreshId((int)$row['id'], $isStaff);
        $this->storeRefreshHash($newRefreshPlain, $userId, $isStaff, $familyId, $userAgent, $ip);

        return [$access, $newRefreshPlain, null];
    }    



    /**
     * Create Refresh Token
     *
     * @param integer $staffid
     * @param integer $ttl
     * @param string|null $userAgent
     * @param string|null $ip
     * @return array
     */
    public function create_refresh_token(int $userId, bool $isStaff, int $ttl = null, ?string $userAgent = null, ?string $ip = null): array
    {
        $ttl = $ttl ?? $this->refreshTtl;

        [$plain, $family] = $this->generateRefresh();
        $this->storeRefreshHash($plain, $userId, $isStaff, $family, $userAgent, $ip, $ttl);

        return ['plain' => $plain, 'expires_at' => time() + $ttl];
    }

    /**
     * (Opcional) Revoga um refresh token específico (se você precisar usar em logout/rotação)
     *
     * @param integer $staffid
     * @param string $plainToken
     * @return boolean
     */
    public function revoke_refresh_by_plain(string $plainToken, bool $isStaff): bool
    {
        $row = $this->findRefreshRowByPlain($plainToken, $isStaff);
        if ($row) {
            $this->revokeRefreshId((int)$row['id'], $isStaff);
            return true;
        }
        return false;
    }   


    /* ================= JWT helpers ================= */

    public function verify_jwt($jwt)
    {
        if (!$jwt || strpos($jwt, '.') === false) return null;

        [$headerB64, $payloadB64, $signB64] = explode('.', $jwt);
        $signed   = $headerB64 . '.' . $payloadB64;
        $expected = $this->b64url(hash_hmac('sha256', $signed, $this->jwtSecret, true));
        if (!hash_equals($expected, $signB64)) return null;

        $payload = json_decode($this->b64url_decode($payloadB64), true);
        if (!$payload) return null;

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] > $now) return null;
        if (isset($payload['exp']) && $payload['exp'] <= $now) return null;
        if (isset($payload['iss']) && $payload['iss'] !== $this->jwtIss) return null;
        if (isset($payload['aud']) && $payload['aud'] !== $this->jwtAud) return null;

        return $payload;
    }    
    
    public function issue_access_for($sub, $role)
    {
        return $this->makeJwt($sub, $role); // retorna [jwt, jti, expTs]
    }

    private function makeJwt($sub, $role)
    {
        $now = time();
        $exp = $now + $this->accessTtl;
        $jti = bin2hex(random_bytes(16));

        $header  = $this->b64url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->b64url(json_encode([
            'iss' => $this->jwtIss,
            'aud' => $this->jwtAud,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'sub' => $sub,   // ex.: user:123 | staff:7
            'role'=> $role,
            'jti' => $jti,
        ]));

        $signature = $this->b64url(hash_hmac('sha256', "{$header}.{$payload}", $this->jwtSecret, true));
        return ["{$header}.{$payload}.{$signature}", $jti, $exp];
    }    


    private function generateRefresh($familyId = null)
    {
        $plain  = bin2hex(random_bytes(48)); // 96 chars
        $family = $familyId ?: bin2hex(random_bytes(16));
        return [$plain, $family];
    }
    
    private function storeRefreshHash($refreshPlain, $userId, $isStaff, $familyId, $userAgent = null, $ip = null, $ttlOverride = null)
    {
        $peppered = $refreshPlain . $this->pepper;
        $hash = password_hash($peppered, PASSWORD_BCRYPT);

        $ttl = $ttlOverride ?? $this->refreshTtl;

        $data = [
            'user_id'     => (int)$userId,
            'is_staff'    => $isStaff ? 1 : 0,
            'token_hash'  => $hash,
            'family_id'   => $familyId,
            'revoked'     => 0,
            'user_agent'  => substr((string)$userAgent, 0, 255),
            'ip_address'  => substr((string)$ip, 0, 45),
            'created_at'  => date('Y-m-d H:i:s'),
            'last_used_at'=> date('Y-m-d H:i:s'),
            'expires_at'  => date('Y-m-d H:i:s', time() + $ttl),
        ];

        $this->db->insert(db_prefix() . 'staff_refresh_tokens', $data);
    }

    private function findRefreshRowByPlain($refreshPlain, $isStaff)
    {
        $this->db->from(db_prefix() . 'staff_refresh_tokens');
        $this->db->where('is_staff', $isStaff ? 1 : 0);
        $this->db->where('expires_at >=', date('Y-m-d H:i:s', time() - 31536000)); // janela p/ otimizar
        $query = $this->db->get();

        $peppered = $refreshPlain . $this->pepper;
        foreach ($query->result_array() as $row) {
            if (password_verify($peppered, $row['token_hash'])) {
                // Telemetria
                $this->db->where('id', $row['id'])->update(db_prefix() . 'staff_refresh_tokens', [
                    'last_used_at' => date('Y-m-d H:i:s'),
                ]);
                return $row;
            }
        }
        return null;
    }
    
    private function revokeRefreshId($id, $isStaff)
    {
        $this->db->where('id', (int)$id)
                 ->where('is_staff', $isStaff ? 1 : 0)
                 ->update(db_prefix() . 'staff_refresh_tokens', ['revoked' => 1]);
    }

    private function revokeFamily($familyId, $isStaff)
    {
        $this->db->where('family_id', $familyId)
                 ->where('is_staff', $isStaff ? 1 : 0)
                 ->update(db_prefix() . 'staff_refresh_tokens', ['revoked' => 1]);
    }

    /**
     * @param  string Email from the user
     * @param  Is Client or Staff
     * @return boolean
     * Generate new password key for the user to reset the password.
     */
	public function forgot_password($email, $staff)
	{
        $table  = db_prefix() . 'users';
        $_id    = 'userid';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';            
        }  
        $this->db->where('email', $email);
        $user = $this->db->get($table)->row();

        if ($user) {
            if ($user->active == 0) {
                log_activity('Inactive User Tried Password Reset [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']', 'failed');

                return array(
                    'memberinactive' => true
                );
            }

            $new_pass_key = generate_auth_key();
            $this->db->where($_id, $user->$_id);
            $this->db->update($table, array(
                'new_pass_key'           => $new_pass_key,
                'new_pass_key_requested' => date('Y-m-d H:i:s'),
            ));
            
            if ($this->db->affected_rows() > 0) {
                $this->load->model('emails_model');
                $data['new_pass_key'] = $new_pass_key;
                $data['staff']        = $staff;
                $data['userid']       = $user->$_id;
                $merge_fields         = array();
                
                $api_merge_fields = new api_merge_fields();
                
                if ($staff == false) {
                    $template     = 'contact-forgot-password';
                    $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($user->userid, $user->$_id));
                } else {
                    $template     = 'staff-forgot-password';
                    $merge_fields = array_merge($merge_fields, $api_merge_fields->get_staff_merge_fields($user->$_id));
                }
                $merge_fields       = array_merge($merge_fields, $api_merge_fields->get_password_merge_field($data, $staff, 'forgot'));
                $send               = $this->emails_model->send_email_template($template, $user->email, $merge_fields);
                               
                if ($send) {
                    return true;
                }         
                
                return false;                
            }
            
            return false;                
        }
        log_activity('Non Existing User Tried Password Reset [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ']', 'failed');

        return false;        
	} 
    
    /**
     * @param  boolean Is Client or Staff
     * @param  integer ID
     * @param  string
     * @param  string
     * @return boolean
     * User new password after successful validation of the key
     */
    public function new_password($staff, $new_pass_key, $password)
    {
        if (!$this->can_reset_password($staff, $new_pass_key)) {
            return array(
                'expired' => true,
            );
        }
        $password = app_hash_password($password);
        $table    = db_prefix() . 'users';
        $_id      = 'userid';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';
        }   

        //$this->db->where($_id, $userid);
        $this->db->where('new_pass_key', $new_pass_key);
        $this->db->update($table, array(
            'password' => $password,
        ));           
        if ($this->db->affected_rows() > 0) {
            log_activity('User Reseted Password [User ID: ' . $_id . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']', 'update');
            
            $this->db->set('new_pass_key', null);
            $this->db->set('new_pass_key_requested', null);
            $this->db->set('last_password_change', date('Y-m-d H:i:s'));
            //$this->db->where($_id, $userid);
            $this->db->where('new_pass_key', $new_pass_key);
            $this->db->update($table);
            //$this->db->where($_id, $userid);
            $user = $this->db->get($table)->row();

            return true;
        }  
        
        return null;
    }    

    /**
     * Send set password email
     * @param string $email
     * @param boolean $staff is staff of contact
     */
    public function set_password_email($email, $staff)
    {
        $table = db_prefix() . 'users';
        $_id   = 'userid';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';
        }

        $this->db->where('email', $email);
        $user = $this->db->get($table)->row();  
        
        if ($user) {
            if ($user->active == 0) {
                return [
                    'memberinactive' => true,
                ];
            }

            $new_pass_key = app_generate_hash();
            $this->db->where($_id, $user->$_id);
            $this->db->update($table, [
                'new_pass_key'           => $new_pass_key,
                'new_pass_key_requested' => date('Y-m-d H:i:s'),
            ]); 
            if ($this->db->affected_rows() > 0) {
                $this->load->model('emails_model');
                $data['new_pass_key'] = $new_pass_key;
                $data['staff']        = $staff;
                $data['userid']       = $user->$_id;
                $data['email']        = $email;

                $merge_fields = array();
                $api_merge_fields = new api_merge_fields();

                if ($staff == false) {
                    $merge_fields = array_merge($merge_fields, $api_merge_fields->get_client_contact_merge_fields($user->userid, $user->$_id));
                } else {
                    $merge_fields = array_merge($merge_fields, $api_merge_fields->get_staff_merge_fields($user->$_id));
                }
                $merge_fields = array_merge($merge_fields, $api_merge_fields->get_password_merge_field($data, $staff, 'set'));
                $send         = $this->emails_model->send_email_template('contact-set-password', $user->email, $merge_fields);

                if ($send) {
                    return true;
                }

                return false;                
            } 

            return false;                      
        }   
        
        return false;
    }    

    /**
     * Update user password from forgot password feature or set password
     * @param boolean $staff        is staff or contact
     * @param mixed $userid
     * @param string $new_pass_key the password generate key
     * @param string $password     new password
     */
    public function set_password($staff, $userid, $new_pass_key, $password)
    {
        if (!$this->can_set_password($staff, $userid, $new_pass_key)) {
            return [
                'expired' => true,
            ];
        } 

        $password = app_hash_password($password);
        $table    = 'users';
        $_id      = 'id';
        if ($staff == true) {
            $table = 'staff';
            $_id   = 'staffid';
        }      
        $this->db->where($_id, $userid);
        $this->db->where('new_pass_key', $new_pass_key);
        $this->db->update($table, [
            'password' => $password,
        ]);          
        
        if ($this->db->affected_rows() > 0) {
            log_activity('User Set Password [User ID: ' . $userid . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ']', 'update');

            $this->db->set('new_pass_key', null);
            $this->db->set('new_pass_key_requested', null);
            $this->db->set('last_password_change', date('Y-m-d H:i:s'));
            $this->db->where($_id, $userid);
            $this->db->where('new_pass_key', $new_pass_key);
            $this->db->update($table);

            return true;
        }
        
        return false;
    }    

    /**
     * @param  integer Is Client or Staff
     * @param  integer ID
     * @param  string Password reset key
     * @return boolean
     * Check if the key is not expired or not exists in database
     */
    public function can_reset_password($staff, $new_pass_key)
    {
        $table = db_prefix() . 'users';
        $_id   = 'userid';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';
        }

        //$this->db->where($_id, $userid);
        $this->db->where('new_pass_key', $new_pass_key);
        $user = $this->db->get($table)->row();

        if ($user) {
            $timestamp_now_minus_1_hour = time() - (60 * 60);
            $new_pass_key_requested     = strtotime($user->new_pass_key_requested);
            if ($timestamp_now_minus_1_hour > $new_pass_key_requested) {
                return false;
            }

            return true;
        }

        return false;
    }    

    /**
     * @param  integer Is Client or Staff
     * @param  integer ID
     * @param  string Password reset key
     * @return boolean
     * Check if the key is not expired or not exists in database
     */
    public function can_set_password($staff, $userid, $new_pass_key)
    {
        $table = db_prefix() . 'users';
        $_id   = 'userid';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';
        }    
        $this->db->where($_id, $userid);
        $this->db->where('new_pass_key', $new_pass_key);
        $user = $this->db->get($table)->row();   
        if ($user) {
            $timestamp_now_minus_48_hour = time() - (3600 * 48);
            $new_pass_key_requested      = strtotime($user->new_pass_key_requested);
            if ($timestamp_now_minus_48_hour > $new_pass_key_requested) {
                return false;
            }

            return true;
        }

        return false;                 
    }  
    
    /**
     * @param  integer ID to create autologin
     * @param  boolean Is Client or Staff
     * @return boolean
     */
    private function create_token($user_id, $staff)
    {
        $this->load->helper('cookie');
        $key = substr(md5(uniqid(rand())), 0, 16);
        $this->user_autologin->delete($user_id, $key, $staff);
        if ($this->user_autologin->set($user_id, md5($key), $staff)) {
            set_cookie([
                'name'  => 'autologin',
                'value' => serialize([
                    'user_id' => $user_id,
                    'key'     => $key,
                ]),
                'expire' => 60 * 60 * 24 * 31 * 2, // 2 months
            ]);

            return true;
        }

        return false;
    }  

    /**
     * @param  boolean Is Client or Staff
     * @return none
     */
    private function delete_token($staff)
    {
        $this->load->helper('cookie');
        if ($cookie = get_cookie('autologin', true)) {
            $data = unserialize($cookie);
            $this->user_autologin->delete($data['user_id'], md5($data['key']), $staff);
            delete_cookie('autologin', 'aal');
        }
    }   

    /**
     * @return boolean
     * Check if autologin found
     */
    public function autologin()
    {
        if (!is_logged_in()) {
            $this->load->helper('cookie');
            if ($cookie = get_cookie('autologin', true)) {
                $data = unserialize($cookie);
                if (isset($data['key']) and isset($data['user_id'])) {
                    if (!is_null($user = $this->user_autologin->get($data['user_id'], $data['key']))) {
                        // Login user
                        if ($user->staff == 1) {
                            $user_data = [
                                'staff_user_id'   => $user->id,
                                'staff_logged_in' => true,
                            ];
                        } else {
                            // Get the customer id
                            $this->db->select('userid');
                            $this->db->where('id', $user->id);
                            $contact = $this->db->get(db_prefix() . 'users')->row();

                            $user_data = [
                                'client_user_id'   => $contact->userid,
                                'contact_user_id'  => $user->id,
                                'client_logged_in' => true,
                            ];
                        }
                        $this->session->set_userdata($user_data);
                        // Renew users cookie to prevent it from expiring
                        set_cookie([
                            'name'   => 'autologin',
                            'value'  => $cookie,
                            'expire' => 60 * 60 * 24 * 31 * 2, // 2 months
                        ]);
                        $this->update_login_info($user->id, $user->staff);

                        return true;
                    }
                }
            }            
        }

        return false;
    }    

    /**
     * @param  boolean Is Client or Staff
     * @return none
     */
    private function delete_autologin($staff)
    {
        $this->load->helper('cookie');
        if ($cookie = get_cookie('autologin', true)) {
            $data = unserialize($cookie);
            $this->user_autologin->delete($data['user_id'], md5($data['key']), $staff);
            delete_cookie('autologin', 'aal');
        }
    }
        
    /* ================ Utils ================ */

    private function b64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64url_decode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function encrypt($string)
    {
        $this->load->library('encryption');

        return $this->encryption->encrypt($string);
    }

    public function decrypt($string)
    {
        $this->load->library('encryption');

        return $this->encryption->decrypt($string);
    }    
}
