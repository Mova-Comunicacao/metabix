<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staff_model');
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

            $data = [
                'firstname'        => $firstname,
                'lastname'         => $lastname,
                'phone'            => $phone,
                'email'            => $email,
                'address'          => $address,
                'username'         => $username,
                'role'             => $roleId,
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
	public function deleteProfileAvatar($id)
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

            $newPassword     = (string) ($formdata['password'] ?? '');
                        
            // Basic validation
            $errors = [];
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
                
            // Change password in model
            $success = $this->staff_model->change_password($sid, $newPassword);            

            if ($success) {
                hooks()->do_action('staff_password_changed', $sid);

                return $this->ok($data, 'update', 'staff_password'); 
            }   
            
            return $this->unprocessable('Failed to update password.', ['change' => _l('staff_problem_changing_password') ?: 'Could not change password.']);
        }); 
    }	
  
}
