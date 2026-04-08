<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Staff extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staff_model');
    }

    public function getAll()
    {
        $this->safe(function () {
		    $admins = $this->staff_model->getAll();
            // array|obj|null
            if (empty($admins)) {
                return $this->respond([], 200);
            }            
		
            // Role cache to avoid N+1 calls
            $roleCache = [];
                        
            // Helper: derive website safely from email or fallback to adminWebsite
            $deriveWebsite = static function ($email, $adminWebsite) {
                // Prefer explicit adminWebsite when present
                if (!empty($adminWebsite)) {
                    return (string)$adminWebsite;
                }
                // Validate email first
                $email = filter_var((string)$email, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    return null;
                }
                // Extract domain after '@'
                $atPos = strpos($email, '@');
                if ($atPos === false) {
                    return null;
                }
                $domain = substr($email, $atPos + 1);
                return $domain !== '' ? $domain : null;
            };

            // helper data ISO
            $toIso = function($dateStr) {
                $ts = strtotime((string)$dateStr);
                return $ts ? date('c', $ts) : (string)$dateStr;
            };   


            $data = [];
			foreach($admins as $row){
                // Resolve role using cache (assuming $row->role holds the role ID)
                $roleId = isset($row->role) ? (int)$row->role : 0;
                if ($roleId > 0) {
                    if (!array_key_exists($roleId, $roleCache)) {
                        // Cache miss: fetch once and cache
                        $roleCache[$roleId] = $this->roles_model->get($roleId);
                    }
                    $roleObj = $roleCache[$roleId];
                } else {
                    $roleObj = null;
                }        

                $data[] = [
                    'id'                => (int)$row->staffid,
                    'staffid'           => (int)$row->staffid,
                    'firstname'         => (string)($row->firstname ?? ''),
                    'lastname'          => (string)($row->lastname ?? ''),
                    'fullname'          => (string)($row->fullname ?? trim(($row->firstname ?? '').' '.($row->lastname ?? ''))),
                    'email'             => (string)($row->email ?? ''),
                    'phone'             => (string)($row->phone ?? ''),
                    'avatar'            => staff_profile_image_url($row->staffid, 'thumb'),
                    'token'             => (string)($row->token ?? ''), // se for sensível, considere remover daqui
                    'role'              => $roleObj, // objeto já cacheado
                    'admin'             => isset($row->admin) ? (int)$row->admin : 0,
                    'username'          => (string)($row->username ?? ''),
                    'default_language'  => (string)($row->default_language ?? ''),
                    'date'              => $toIso($row->datecreated ?? null),
                    'address'           => (string)($row->address ?? ''),
                    'website'           => $deriveWebsite($row->email ?? null, $row->adminWebsite ?? null),
                    'active'            => isset($row->active) ? (int)$row->active : 0,
                ];
			}
            return $this->respond($data, 200);
        });
	} 

    public function getItemById($id = '')
    {
        if (!has_permission('staff', '', 'view')) {
            access_denied('staff');
        }

        $this->safe(function () use ($id) {
            hooks()->do_action('staff_staff_view_profile', $id);

            $sid = (int)$id;
            if ($sid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $row = $this->staff_model->get($id);
            if (!$row) {
                return $this->notFound('Staff not found.');
            }

            $toIso = static function ($dateStr) {
                if (empty($dateStr)) return null;
                $ts = strtotime((string)$dateStr);
                return $ts ? date('c', $ts) : (string)$dateStr;
            };

            $deriveWebsite = static function ($email, $explicitWebsite) {
                if (!empty($explicitWebsite)) {
                    return (string)$explicitWebsite;
                }
                $valid = filter_var((string)$email, FILTER_VALIDATE_EMAIL);
                if ($valid === false) return null;
                $pos = strpos($valid, '@');
                if ($pos === false) return null;
                $domain = substr($valid, $pos + 1);
                return $domain ?: null;
            };

            // role (objeto)
            $roleObj = null;
            if (!empty($row->role)) {
                $roleObj = $this->roles_model->get((int)$row->role);
            }

            // permissions pode vir serializado/json dependendo do seu modelo
            $permissions = $row->permissions ?? null;
            if (is_string($permissions)) {
                $decoded = json_decode($permissions, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $permissions = $decoded;
                }
            }        

            $data = [
                'id'               => (int)$row->staffid,
                'staffid'          => (int)$row->staffid,
                'firstname'        => (string)($row->firstname ?? ''),
                'lastname'         => (string)($row->lastname ?? ''),
                'fullname'         => (string)($row->fullname ?? trim(($row->firstname ?? '').' '.($row->lastname ?? ''))),
                'email'            => (string)($row->email ?? ''),
                'phone'            => (string)($row->phone ?? ''),
                'avatar'           => staff_profile_image_url($row->staffid, 'thumb'),
                // 'token'         => $row->token,
                // 'password'      => $row->password,
                'admin'            => isset($row->admin) ? (int)$row->admin : 0,
                'role'             => $roleObj,
                'permissions'      => $permissions,
                'username'         => (string)($row->username ?? ''),
                'default_language' => (string)($row->default_language ?? ''),
                'date'             => $toIso($row->datecreated ?? null),
                'address'          => (string)($row->address ?? ''),
                'active'           => isset($row->active) ? (int)$row->active : 0,
                'website'          => $deriveWebsite($row->email ?? null, $row->website ?? null),
            ];

            return $this->respond($data, 200);
        });      
                
    }   

	public function create()
	{
        if (!has_permission('staff', '', 'create')) {
            access_denied('staff');
        }

        $this->safe(function () {
            hooks()->do_action('staff_staff_create_profile');

            // Load payload (expects JSON body)
            $formdata = $this->readJson();

            if (empty($formdata) || !is_array($formdata)) {
                return $this->unprocessable('Empty or invalid payload.');
            }
                        
            // Required fields
            $errors = [];
            $firstname = trim((string)($formdata['firstname'] ?? ''));
            $lastname  = trim((string)($formdata['lastname']  ?? ''));
            $emailRaw  = trim((string)($formdata['email']     ?? ''));
            $roleId    = (int)($formdata['role']              ?? 1);
            $password  = (string)($formdata['password']       ?? '');

            if ($firstname === '') $errors['firstname'] = 'Required.';
            if ($lastname  === '') $errors['lastname']  = 'Required.';
            if ($password  === '') $errors['password']  = 'Required.';

            $role = $this->roles_model->get($roleId);
            if (!$role) {
                return $this->unprocessable('Invalid role.', ['role' => 'Role not found.']);
            }

            // Checa duplicidade de e-mail
            $this->db->where('email', $email);
            $this->db->limit(1);
            $dup = $this->db->get(db_prefix().'staff')->row();
            if ($dup) {
                return $this->unprocessable('Email already exists.', ['email' => 'This email is already in use.']);
            }
            
            // E-mail: normaliza e valida
            $email = strtolower($emailRaw);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email.';
            }

            if ($roleId <= 0) {
                $errors['role'] = 'Invalid role.';
            }

            if (!empty($errors)) {
                return $this->unprocessable('Validation failed.', $errors);
            }

            // Flags opcionais
            $active    = !empty($formdata['active']) ? 1 : 0;
            $send_mail = !empty($formdata['send_mail']) ? 1 : 0;

            // Idioma padrão
            $defaultLang = get_staff_default_language();
            if ($defaultLang === '') {
                $defaultLang = get_option('active_language');
            }

            $data = [
                'firstname'         => $firstname,
                'lastname'          => $lastname,
                'email'             => $email,
                'role'              => $roleId,
                'password'          => $password,
                'active'            => $active,
                'send_welcome_email'=> $send_mail,
                'default_language'  => (string)$defaultLang,
                'datecreated'       => date('Y-m-d H:i:s'),
                'token'             => app_generate_hash(),
            ];

            $id = $this->staff_model->add($data);
            if (!$id) {
                return $this->unprocessable('Failed to create staff member.');
            }            
        
            // Dispara hook (e opcionalmente e-mail)
            hooks()->do_action('after_staff_created', $id, $data);
            if ($send_mail) {
                hooks()->do_action('send_staff_welcome_email', $id);
                // $this->staff_model->send_welcome_email($id);
            }

            return $this->ok(['id' => (int)$id], 'create', 'staff_member');
		});
	}    

	public function update($id) 
	{
        if (!has_permission('staff', '', 'edit')) {
            access_denied('staff');
        }
        $this->safe(function () use ($id) {
            hooks()->do_action('staff_staff_edit_profile', $id);

            $pid = (int) $id;
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            // Load payload (expects JSON body)
            $formdata = $this->readJson();  

            $firstname   = trim((string)($formdata['firstname'] ?? ''));
            $lastname    = trim((string)($formdata['lastname'] ?? ''));
            $phone       = trim((string)($formdata['phone'] ?? ''));
            $emailRaw    = trim((string)($formdata['email'] ?? ''));
            $address     = trim((string)($formdata['address'] ?? ''));
            $username    = trim((string)($formdata['username'] ?? ''));
            $roleId      = (int)($formdata['role'] ?? 0);
            $permissions = $formdata['permissions'] ?? null; 
            $admin       = !empty($formdata['admin']) ? 1 : 0;
            $language    = (string)($formdata['default_language'] ?? '');

            $errors = [];
            if ($firstname === '') $errors['firstname'] = 'Required.';
            if ($lastname  === '') $errors['lastname']  = 'Required.';

            // Email
            $email = strtolower($emailRaw);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email.';
            }

            if ($roleId <= 0) {
                $errors['role'] = 'Invalid role.';
            } else {
                $role = $this->roles_model->get($roleId);
                if (!$role) {
                    $errors['role'] = 'Role not found.';
                }
            }

            if (!empty($errors)) {
                return $this->unprocessable('Validation failed.', $errors);
            }

            $current = $this->staff_model->get($pid);
            if (!$current) {
                return $this->respond(['error' => true, 'message' => 'Staff not found.'], 404);
            }            

            if (strcasecmp((string)$current->email, $email) !== 0) {
                $this->db->where('email', $email);
                $this->db->where('staffid !=', $pid);
                $dupEmail = $this->db->get(db_prefix().'staff')->row();
                if ($dupEmail) {
                    return $this->unprocessable('Email already exists.', ['email' => 'This email is already in use.']);
                }
            }
                    
            if ($username !== '' && strcasecmp((string)$current->username, $username) !== 0) {
                $this->db->where('username', $username);
                $this->db->where('staffid !=', $pid);
                $dupUser = $this->db->get(db_prefix().'staff')->row();
                if ($dupUser) {
                    return $this->unprocessable('Username already exists.', ['username' => 'This username is already in use.']);
                }
            }   

            if ((int)$current->staffid === get_staff_user_id() && ( ($admin !== (int)$current->admin) || ($roleId !== (int)$current->role) )) {
                return $this->unprocessable('You cannot change your own role or admin flag.');
            }

            /*
            // Normaliza permissions: se vier array/obj -> json; se for string, tenta manter json válido
            if (is_array($permissions) || is_object($permissions)) {
                $permissions = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                // Se vier string não-vazia, tenta validar como JSON; se falhar, guarda como string crua
                if (is_string($permissions) && $permissions !== '') {
                    json_decode($permissions);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // opcional: forçar json, mas aqui preservamos string original
                    }
                } else {
                    $permissions = null;
                }
            }     
            */

            $data = [
                'firstname'        => $firstname,
                'lastname'         => $lastname,
                'phone'            => $phone,
                'email'            => $email,
                'address'          => $address,
                'username'         => $username,
                'role'             => $roleId,
                'permissions'      => $permissions,
                'admin'            => $admin,
                'default_language' => $language,
            ];

            $data = array_filter($data, static function ($v) {
                // mantém 0 e '0'; remove null e string vazia
                return !($v === null || $v === '');
            });            
			

			$success = $this->staff_model->update($pid, $data);

            if ($success) {
                return $this->ok($data, 'update', 'staff_member');      
            }


            return $this->unprocessable('Failed to update staff.'); 
		});	
	}    
    
    
	public function uploadAvatar($id) 
	{
        $this->safe(function () use ($id) {
            $pid = (int)$id;

            $result = handle_profile_image_upload($pid);

            // if invalid or erro
            if (!$result || !is_array($result)) {
                $result = set_alert(false, 'unexpected_error', 'Empty response from upload helper.');
            }

            $ok   = (bool)($result['ok'] ?? false);
            $type = $result['alert']['type'] ?? ($ok ? 'success' : 'error');

            $status = $ok ? 200 : ($type === 'warning' ? 422 : 400);

            if (!$ok) {
                return $this->respond($result, $status);
            }

            return $this->ok($result['data'] ?? null, 'upload', 'picture');
        }); 				      			
	}  
    
    /* Remove staff profile image / ajax */
	public function deleteAvatar($staff_id)
	{
        $this->safe(function () use ($staff_id) {
            $id = (int)$staff_id;        
            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            $success = $this->staff_model->deleteAvatar($id);

            if ($success) {
                // 200 OK
                return $this->ok(null, 'delete', 'avatar');
            }

            // Fail
            return $this->unprocessable('Failed to delete item.');   
        });
    }       
    
    /**
     * Change staff password
     * - Only the user himself can change his password, unless current user is admin
     * - Requires currentPassword unless caller is admin changing someone else's password
     * - Returns JSON payload { id, alert } with proper HTTP status codes
     *
     * @param int $id Staff ID whose password will be changed
     */
    public function change_password($id) 
	{

        $this->safe(function () use ($id) {
            $sid = (int) $id;

            if ($sid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            // Permission: user can change only his own password unless admin
            $currentId = function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0;
            $isAdmin   = function_exists('is_admin') ? is_admin() : false;
            $isSelf    = ($sid === $currentId);     
            
            if (!$isAdmin && !$isSelf) {
                return $this->forbidden('You cannot change other users’ passwords.');
            }

            // Load payload (expects JSON body)
            $formdata = $this->readJson();

            $currentPassword = (string) ($formdata['currentPassword'] ?? '');
            $newPassword     = (string) ($formdata['password'] ?? '');
                        
            // Basic validation
            $errors = [];
            if (!$isAdmin) { 
                // self-change requires current password
                if ($currentPassword === '') {
                    $errors['currentPassword'] = 'Required.';
                }
            }
            if ($newPassword === '') {
                $errors['password'] = 'Required.';
            }    
            
            if (!empty($errors)) {
                return $this->unprocessable('Failed to create slide.', $errors);
            }

            // Optional: add policy checks (length/complexity)
            // if (strlen($newPassword) < 8) { $errors['password'] = 'Must be at least 8 characters.'; }

            $user = $this->staff_model->get($sid);
            if (!$user) {
                return $this->notFound('Staff member not found.');
            }    
                
            // Verify current password when required
            if ($isAdmin) {
                if (!app_hasher()->CheckPassword($currentPassword, $user->password)) {
                    return $this->unprocessable('Verify current password.', ['currentPassword' => _l('staff_old_password_incorrect') ?: 'Current password is incorrect.']);
                }
            }       
            
            // Change password in model
            $success = $this->staff_model->change_password($sid, $newPassword);            

            if ($success) {
                hooks()->do_action('staff_password_changed', $sid);

                return $this->ok($data, 'update', 'staff_password'); 
            }   
            
            return $this->unprocessable('Failed to update password.', ['change' => _l('staff_problem_changing_password') ?: 'Could not change password.']);
        });     
    } 

	public function delete($id)
	{

        if (has_permission('staff', '', 'delete')) {
             access_denied('staff');
        }

        $this->safe(function () use ($id) {
            hooks()->do_action('before_staff_delete_attempt', $id);

            $pid = (int) $id;
            if ($pid <= 0) {
                return $this->unprocessable('Missing or invalid id.', ['id' => 'Required and must be greater than zero.']);
            }

            // Fetch target staff row
            $staff = $this->staff_model->get($pid);
            if (!$staff) {
                return $this->notFound('Staff member not found.');
            }

            // Prevent user from deleting his own account
            $currentId = function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0;
            if ($pid === $currentId) {
                return $this->unprocessable('Deleting his own account.', ['self' => 'You cannot delete your own account.']);
            }            

            // Protect the primary administrator (ID=1 and admin=1) from being deleted by non-admins
            if (!is_admin() && (int)$staff->staffid === 1 && (int)$staff->admin === 1) {
                return $this->forbidden('Protect the primary administrator.', ['self' => 'You cannot delete the primary administrator.']);
            }       
            
            // Optional: prevent deleting last remaining admin
            // (Uncomment if you want this rule.)
            // if ((int)$staff->admin === 1) {
            //     $this->db->where('admin', 1);
            //     $this->db->where('staffid !=', $pid);
            //     $otherAdmins = $this->db->count_all_results(db_prefix() . 'staff');
            //     if ($otherAdmins === 0) {
            //         return $this->respond([
            //             'id'    => $pid,
            //             'alert' => set_alert(false, 'unprocessable', 'staff_member', ['admin' => 'Cannot delete the last remaining administrator.'])
            //         ], 422);
            //     }
            // }            

            // Perform delete in the model (should handle related cleanups, hooks, etc.)
            $success = $this->staff_model->delete($pid);
            if ($success) {
                hooks()->do_action('after_staff_deleted', $pid);
                // 200 OK
                return $this->ok(['id' => $id], 'delete', 'staff_member');
            }        
            
            // Fail
            return $this->unprocessable('Failed to delete staff member.');            
        });
	}	    

    public function get_staff_permissions($id = '') 
    {
        $staff  = $this->staff_model->get($id);

        $permissionsData = [ 'funcData' => ['staff_id' => isset($staff) ? $staff->staffid : null ] ];
        if (isset($staff)) {
            $permissionsData['staff'] = $staff;
        }
        if (isset($staff)) {
            $is_admin = is_admin($staff->staffid);
        }

        $builtReport = array();
        $capability_obj = array();
        $permission_obj = array();

        foreach (get_available_staff_permissions($permissionsData) as $feature => $permission) {
            $permissions = array(
                'name' => $permission['name'] ?? '',
                'before' => isset($permission['before']) ? $permission['before'] : '',                   
            );

            $builtReport[] = $permissions;

            foreach ($permission['capabilities'] as $capability => $name) {
                $help = '';
                $checked  = '';
                $disabled = '';
                    
                if (
                    (isset($is_admin) && $is_admin) || 
                    (is_array($name) && isset($name['not_applicable']) && $name['not_applicable']) || 
                    (
                        ($capability == 'view_own' || $capability == 'view' && array_key_exists('view_own', $permission['capabilities']) && array_key_exists('view', $permission['capabilities'])) && 
                        ((isset($staff) && staff_can(($capability == 'view' ? 'view_own' : 'view'), $feature, $staff->staffid)) || 
                        (isset($role) && has_role_permission($role->roleid, ($capability == 'view' ? 'view_own' : 'view'), $feature)))
                    )
                ) {
                    $disabled = ' disabled ';
                } elseif (
                        (isset($staff) && staff_can($capability, $feature, $staff->staffid)) ||
                        isset($role) && has_role_permission($role->roleid, $capability, $feature)
                    ) {
                    $checked = ' checked ';
                }


                if (isset($permission['help']) && array_key_exists($capability, $permission['help'])) {
                    $help = $permission['help'][$capability];
                }

                $capabilities = array(
                    'label' => !is_array($name) ? $name : $name['name'],
                    'help' => $help,
                    'capability' => $capability,
                    'disabled' => $disabled,
                    'checked' => $checked,
                    'feature' => $feature
                );

                $builtReport[] = $capabilities;
            }
        }

        $response = $builtReport;

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));              
    }

    public function get_roles()
    {

        $response = $this->roles_model->get();

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));   
    }       

    /* Get role permission for specific role id */
    public function role_changed($id)
    {
        $response = $this->roles_model->get($id)->permissions;

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));   
    }        
}