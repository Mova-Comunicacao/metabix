<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Handles upload for project files
 * @param  mixed $project_id project id
 * @return boolean
 */

function handle_project_pictures_uploads($project_id, $staffid)
{
    $filesIDS = [];
    $errors   = [];

    if (isset($_FILES['file']['name'])
        && ($_FILES['file']['name'] != '' || is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)) {
        hooks()->do_action('before_upload_project_attachment', $project_id);

        if (!is_array($_FILES['file']['name'])) {
            $_FILES['file']['name']     = [$_FILES['file']['name']];
            $_FILES['file']['type']     = [$_FILES['file']['type']];
            $_FILES['file']['tmp_name'] = [$_FILES['file']['tmp_name']];
            $_FILES['file']['error']    = [$_FILES['file']['error']];
            $_FILES['file']['size']     = [$_FILES['file']['size']];
        }

        $path = get_upload_path_by_type('projects') . $project_id . '/';

        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            if (_api_upload_error($_FILES['file']['error'][$i])) {
                $errors[$_FILES['file']['name'][$i]] = _api_upload_error($_FILES['file']['error'][$i]);

                return array(
                    'message' => $errors[$_FILES['file']['name'][$i]]
                );         
                return false; 
            }

            // Get the temp file path
            $tmpFilePath = $_FILES['file']['tmp_name'][$i];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                _maybe_create_upload_path($path);
                $originalFilename = unique_filename($path, $_FILES['file']['name'][$i]);
                $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);
                
                // In case client side validation is bypassed
                if (!_upload_pictures_allowed($filename)) {
                    return array(
                        'message' => 'Image extension not allowed. Extensions: ' . get_option('site_pic_types')
                    );                  
                    return false;
                }

                $newFilePath = $path . $filename;
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI = & get_instance();
                    
                    $data = array(
                        'project_id' => $project_id,
                        'file_name'  => $filename,
                        'original_file_name'  => $originalFilename,
                        'filetype'   => $_FILES['file']['type'][$i],
                        'dateadded'  => date('Y-m-d H:i:s'),
                        'staffid'    => $staffid,
                        'subject'    => $originalFilename,
                    );
                    
                    $CI->db->insert(db_prefix() . 'projects_pictures', $data);
                    $insert_id = $CI->db->insert_id();
                    if ($insert_id) {
                        if (is_image($newFilePath)) {
                            create_img_thumb($path, $filename);                            
                        }
                        array_push($filesIDS, $insert_id);
                    } else {
                        unlink($newFilePath);

                        return false;
                    }            
                } 
            }            
        }
    }

    if (count($filesIDS) > 0) {
        return true;
    }    

    return false;
}
/**
 * Handles upload for project files
 * @param  mixed $project_id project id
 * @return boolean
 */
function handle_project_picture_uploads($project_id, $staffid, $subject = '', $description = '')
{
    $errors     = '';
    $message    = '';

    if (isset($_FILES['file']) && _api_upload_error($_FILES['file']['error'])) {   
        return array(
            'message' => _api_upload_error($_FILES['file']['error'])
        );         
        return false; 
    }    
    if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
        $path = get_upload_path_by_type('projects') . $project_id . '/';
        
        hooks()->do_action('before_upload_project_picture', $project_id);  
        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];   
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            _maybe_create_upload_path($path);
            $originalFilename   = unique_filename($path, $_FILES['file']['name']);
            $filename           = app_generate_hash() . '.' . get_file_extension($originalFilename);
         
            // In case client side validation is bypassed
            if (!_upload_pictures_allowed($filename)) {
                return array(
                    'message' => 'Image extension not allowed. Extensions: ' . get_option('site_pic_types')
                );                  
                return false; 
            }

            $newFilePath = $path . $filename;      
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $CI = & get_instance();

                $data = array(
                    'project_id'    => $project_id,
                    'file_name'  => $filename,
                    'original_file_name'  => $originalFilename,
                    'filetype'   => $_FILES['file']['type'],
                    'dateadded'  => date('Y-m-d H:i:s'),
                    'staffid'    => $staffid,
                    'subject'    => $subject,                           
                    'description' => $description,                    
                );

                $CI->db->insert(db_prefix() . 'projects_pictures', $data);
                $insert_id = $CI->db->insert_id();
                if ($insert_id) {
                    if (is_image($newFilePath)) {
                        //create_img_thumb($path, $filename);
                        $config = array(
                            'image_library' => 'gd2',
                            'source_image' => $newFilePath,
                            'new_image' => $path,
                            'maintain_ratio' => true,
                            'create_thumb' => true,
                            'thumb_marker' => '_thumb',
                            'width' => hooks()->apply_filters('project_image_thumb_width', 800),
                            'height' => hooks()->apply_filters('project_image_thumb_height', 800)
                        );
                        $CI->image_lib->initialize($config);
                        $CI->image_lib->resize();
                        $CI->image_lib->clear();

                        $additional_data = $originalFilename;
                        $CI->projects_model->log_activity($project_id, $staffid, '', 'project_activity_uploaded_file', $additional_data);                       
                    }                    
                } else {
                    @unlink($newFilePath);

                    return false;                    
                }
                
                return true;
            }           
        }  
    }

    return false;
}
/**
 * Handles uploading a user's/post picture:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Moves the uploaded file to the post uploads folder
 * - Updates the database with the new picture filename
 * - Returns an array with type (success/error), message, picture URL
 */
function handle_post_picture_uploads($post_id, $staffid, $subject = '', $description = '')
{
    $CI = &get_instance();

    if (!$post_id) {
        return set_alert(false, 'bad_request', 'Missing slide ID.');
    }

    if (!is_numeric($staff_id)) {
        $staff_id = get_staff_user_id();
    }

    // Verifica erros de upload
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }   

    $tmpFilePath = $_FILES['file']['tmp_name'] ?? '';
    $originalName = $_FILES['file']['name'] ?? '';

    // Build upload path
    $basePath = rtrim(get_upload_path_by_type('posts'), '/\\'); // e.g., /uploads/posts/
    $path     = $basePath . '/' . $post_id . '/';
    _maybe_create_upload_path($path);

    // Nome único para o arquivo
    $originalFilename   = unique_filename($path, $originalName);
    $filename           = app_generate_hash() . '.' . get_file_extension($originalFilename);

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }

    hooks()->do_action('before_upload_post_picture', $post_id);  

    // Verifica se o arquivo foi enviado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return set_alert(false, 'unprocessable', 'No file uploaded or upload error.');
    }  

    // Move arquivo enviado
    $newFilePath = $path . $filename;   
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Determine real mime type (safer than $_FILES['file']['type'])
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $det = finfo_file($finfo, $newFilePath);
            if ($det) {
                $mime = $det;
            }
            finfo_close($finfo);
        }
    }

    // Optional metadata (if you want to keep parity with company pictures)
    $subject     = is_null($subject) ? '' : trim((string)$subject);
    $description = is_null($description) ? '' : trim((string)$description);


    // Gera imagens redimensionadas
    $CI->load->library('image_lib');    

    $thumbWidth  = (int) hooks()->apply_filters('post_image_thumb_width', 500);
    $thumbHeight = (int) hooks()->apply_filters('post_image_thumb_height', 500);

    // Thumb 500x500
    $config = [
        'image_library'  => 'gd2',
        'source_image'   => $newFilePath,
        'new_image'      => $path,          // when create_thumb = true, this is the directory
        'maintain_ratio' => true,
        'create_thumb'   => true,
        'thumb_marker'   => '_thumb',
        'width'          => $thumbWidth,
        'height'         => $thumbHeight,
    ];
    $CI->image_lib->initialize($config);
    $resizeOk = $CI->image_lib->resize();
    $resizeErr = $CI->image_lib->display_errors('', '');
    $CI->image_lib->clear();

    // Upload the file into the company uploads dir
    $data = [
        'post_id'            => $post_id,
        'file_name'          => $filename,
        'original_file_name' => $originalName,
        'filetype'           => $mime,
        'dateadded'          => date('Y-m-d H:i:s'),
        'staffid'            => $staffid,
        'subject'            => $subject,
        'description'        => $description,                   
    ]; 

    $CI->db->insert(db_prefix() . 'posts_pictures', $data);
    $insert_id = $CI->db->insert_id();   
    if ($insert_id <= 0 || $CI->db->affected_rows() === 0) {
        // Best-effort cleanup on DB failure
        @unlink($newFilePath);
        @is_file($thumbPath) && @unlink($thumbPath);
        return set_alert(false, 'unexpected_error', 'Database insert failed.');
    } 

    $publicBase = rtrim(base_url('uploads/posts/' . rawurlencode((string)$post_id)), '/') . '/';
    $fileUrl    = $publicBase . rawurlencode($filename);
    $thumbUrl   = $publicBase . rawurlencode($thumbFilename);

    // Retorno para frontend
    $resp = set_alert(true, 'create', 'picture');
    $resp['data'] = [
        'id'               => $insert_id,
        'file_name'        => $filename,
        'original_name'    => $originalName,
        'filetype'         => $mime,
        'url'              => $fileUrl,
        'thumb_url'        => $resizeOk ? $thumbUrl : null,
        'thumb_error'      => $resizeOk ? null : ($resizeErr ?: null),
        'post_id'          => $post_id,
        'staffid'          => $staffid,
        'subject'          => $subject,
        'description'      => $description,
    ];

    hooks()->do_action('after_upload_post_picture', $post_id, $insert_id);

    return $resp;
}
/**
 * Handles uploading a user's/slide picture:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Moves the uploaded file to the slide uploads folder
 * - Updates the database with the new picture filename
 * - Returns an array with type (success/error), message, picture URL
 */
function handle_slide_picture_uploads($slide_id, $staff_id, $subject = '', $description = '')
{
    $CI = &get_instance();

    if (!$slide_id) {
        return set_alert(false, 'bad_request', 'Missing slide ID.');
    }

    if (!is_numeric($staff_id)) {
        $staff_id = get_staff_user_id();
    }

    // Verifica erros de upload
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }    

    $tmpFilePath = $_FILES['file']['tmp_name'];
    $originalName = $_FILES['file']['name'];

    // Define o caminho de upload
    $path = get_upload_path_by_type('slides');
    _maybe_create_upload_path($path);

    // Nome único para o arquivo
    $originalFilename   = unique_filename($path, $originalName);
    $filename           = app_generate_hash() . '.' . get_file_extension($originalFilename);    

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }

    hooks()->do_action('before_upload_slide_picture', $slide_id); 

    // Verifica se o arquivo foi enviado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return set_alert(false, 'unprocessable', 'No file uploaded or upload error.');
    }  

    // Move arquivo enviado
    $newFilePath = $path . $filename;   
    if (!move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Optional metadata (if you want to keep parity with company pictures)
    if ($subject !== null)     { $update['subject']     = $subject; }
    if ($description !== null) { $update['description'] = $description; }

    // Upload the file into the company uploads dir
    $data = [
        'slideid'    => $slide_id,
        'file_name'  => $filename,
        'original_file_name'  => $originalFilename,
        'filetype'   => $_FILES['file']['type'],
        'dateadded'  => date('Y-m-d H:i:s'),
        'staffid'    => $staff_id,
        'subject'    => $subject,                    
        'description'    => $description,                    
    ]; 

    if (!class_exists('slides_model', false)) {
        $CI->load->model('slides_model');
    }

    $CI->slides_model->upload_picture($data); 


    // Retorno para frontend
    $resp = set_alert(true, 'create', 'picture');
    $resp['data'] = [
        'file_name' => $filename,
        'original_name' => $originalFilename,
        'filetype' => $_FILES['file']['type'],
        'path' => $newFilePath,
    ];

    return $resp;
}
/**
 * Handles uploading a user's/company picture:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Moves the uploaded file to the company uploads folder
 * - Updates the database with the new picture filename
 * - Returns an array with type (success/error), message, picture URL
 */
function handle_company_picture_uploads($staff_id, $subject = '', $description = '')
{
    $CI = &get_instance();

    if (!is_numeric($staff_id)) {
        $staff_id = get_staff_user_id();
    }

    // Verifica erros de upload
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }    
  

    $tmpFilePath = $_FILES['file']['tmp_name'];
    $originalName = $_FILES['file']['name'];
        
    // Define o caminho de upload
    $path = get_upload_path_by_type('company');
    _maybe_create_upload_path($path);

    // Nome único para o arquivo
    $originalFilename   = unique_filename($path, $originalName);
    $filename           = app_generate_hash() . '.' . get_file_extension($originalFilename);    

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }

    hooks()->do_action('before_upload_company_picture', $staff_id); 

    // Verifica se o arquivo foi enviado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return set_alert(false, 'unprocessable', 'No file uploaded or upload error.');
    }  

    // Move arquivo enviado
    $newFilePath = $path . $filename;   
    if (!move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Upload the file into the company uploads dir
    $data = array(
        'file_name'  => $filename,
        'original_file_name'  => $originalFilename,
        'filetype'   => $_FILES['file']['type'],
        'dateadded'  => date('Y-m-d H:i:s'),
        'staffid'    => $staff_id,
        'subject'    => $subject,                    
        'description'    => $description,                    
    ); 

    if (!class_exists('company_model', false)) {
        $CI->load->model('company_model');
    }

    $CI->company_model->upload_picture($data); 


    // Retorno para frontend
    $resp = set_alert(true, 'create', 'picture');
    $resp['data'] = [
        'file_name' => $filename,
        'original_name' => $originalFilename,
        'filetype' => $_FILES['file']['type'],
        'path' => $newFilePath,
    ];

    return $resp; 
}
/**
 * Handles uploading a user's/technology content picture:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Moves the uploaded file to the technology content uploads folder
 * - Updates the database with the new picture filename
 * - Returns an array with type (success/error), message, picture URL
 */
function handle_technology_picture_uploads($staff_id, $subject = '', $description = '')
{
    $CI = &get_instance();

    if (!is_numeric($staff_id)) {
        $staff_id = get_staff_user_id();
    }

    // Verifica erros de upload
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }    
  

    $tmpFilePath = $_FILES['file']['tmp_name'] ?? '';
    $originalName = $_FILES['file']['name'] ?? '';
        
   // Build upload path
    $path = get_upload_path_by_type('technology');
    _maybe_create_upload_path($path);

    // Nome único para o arquivo
    $originalFilename   = unique_filename($path, $originalName);
    $filename           = app_generate_hash() . '.' . get_file_extension($originalFilename);    

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }

    hooks()->do_action('before_upload_technology_picture', $staff_id); 

    // Verifica se o arquivo foi enviado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return set_alert(false, 'unprocessable', 'No file uploaded or upload error.');
    }  

    // Move arquivo enviado
    $newFilePath = $path . $filename;   
    if (!move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Optional metadata (if you want to keep parity with technology pictures)
    $subject     = is_null($subject) ? '' : trim((string)$subject);
    $description = is_null($description) ? '' : trim((string)$description);

    // Upload the file into the technology uploads dir
    $data = array(
        'file_name'  => $filename,
        'original_file_name'  => $originalFilename,
        'filetype'   => $_FILES['file']['type'],
        'dateadded'  => date('Y-m-d H:i:s'),
        'staffid'    => $staff_id,
        'subject'    => $subject,                    
        'description'    => $description,                    
    ); 

    if (!class_exists('technology_model', false)) {
        $CI->load->model('technology_model');
    }

    $CI->technology_model->upload_picture($data); 


    // Retorno para frontend
    $resp = set_alert(true, 'create', 'picture');
    $resp['data'] = [
        'file_name' => $filename,
        'original_name' => $originalFilename,
        'filetype' => $_FILES['file']['type'],
        'path' => $newFilePath,
    ];

    return $resp; 
}
/**
 * Upload picture for Technology Item
 * - Validates upload and extension (reuses _upload_pictures_allowed / site_pic_types)
 * - Ensures destination path exists (business/items)
 * - Removes previous file if exists
 * - Updates DB with new file metadata
 * - Returns standardized set_alert payload + data for frontend
 *
 * @param int         $item_id     Technology item ID (required)
 * @return array                   set_alert payload with data or error
 */
function handle_technology_item_picture_uploads($item_id)
{
    $CI = &get_instance();

    // Basic guards
    if (empty($item_id) || !is_numeric($item_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['file'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }

    // Extract upload basics
    $tmpFilePath   = $_FILES['file']['tmp_name'];
    $originalName  = $_FILES['file']['name'];
    $mimeType      = $_FILES['file']['type'];    

    if (empty($tmpFilePath) || empty($originalName)) {
        return set_alert(false, 'unprocessable', 'Empty file or temporary path.');
    }
    
    // Destination path for technology items (technology/items)
    $path = rtrim(get_upload_path_by_type('technology'), '/') . '/icons/';
    _maybe_create_upload_path($path);

    
    // Validate allowed extensions using your existing helper
    $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['png', 'jpeg', 'jpg', 'svg'];    
    $allowed_extensions = hooks()->apply_filters('technology_items_file_upload_allowed_extensions', $allowed_extensions);

    if (!in_array($extension, $allowed_extensions, true)) {
        return set_alert(false, 'unprocessable', _l('settings_allowed_upload_file_types') . ' .png, .jpeg, .jpg, .svg');
    }

    // Generate unique target filename
    $filename    = unique_filename($path, $_FILES['file']['name']);
    $newFilePath = $path . $filename;

    // Pre-upload hook for observers
    hooks()->do_action('before_upload_technology_item_picture', $item_id);

    // Remove previous file if exists
    $CI->db->where('id', $item_id);
    $current = $CI->db->get(db_prefix() . 'technology_items')->row();
    if ($current && !empty($current->file_name)) {
        $oldPath = $path . $current->file_name;
        if (is_file($oldPath)) {
            // Suppress unlink warnings but keep operation safe
            @unlink($oldPath);
        }
    }

    // Move uploaded file to final path
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Persist new filename to DB
    $CI->db->where('id', (int)$item_id);
    $CI->db->update(db_prefix() . 'technology_items', ['file_name' => $filename]);

    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');
    }    

    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'item_id'       => (int)$item_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'file_name'     => $filename,
        'relative_path' => 'uploads/technology/icons/'.$filename,        
    ];  

    return $resp;
}
/**
 * Handles uploading a user's/partner avatar:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Removes the old avatar and its thumbnails (thumb and small)
 * - Moves the uploaded file to the partner uploads folder
 * - Creates resized images: thumb (320x320) and small (96x96)
 * - Updates the database with the new avatar filename
 * - Deletes the original file after creating the thumbnails
 * - Returns an array with type (success/error), message, and small avatar URL
 */
function handle_partners_picture_uploads($partner_id)
{
    $CI = &get_instance();

    // Basic guards
    if (empty($partner_id) || !is_numeric($partner_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['file'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }  

    // Extract upload basics
    $tmpFilePath    = $_FILES['file']['tmp_name'] ?? '';
    $originalName   = $_FILES['file']['name'] ?? '';
    $mimeType       = $_FILES['file']['type'] ?? ''; 


    // Destination path for team partner_id (team/partner_id)
    $path = rtrim(get_upload_path_by_type('partners'), '/\\') . '/';
    _maybe_create_upload_path($path);

    
    // Generate unique target filename
    $filename = unique_filename($path, $originalName);
    $newFilePath = $path . $filename;

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }    

    // Pre-upload hook for observers
    hooks()->do_action('before_upload_partner_picture', $partner_id);

    // Remove previous file if exists
    $CI->db->where('id', $partner_id);
    $current = $CI->db->get(db_prefix() . 'partners')->row();
    if ($current && !empty($current->file_name)) {
        $oldPath = $path . $current->file_name;
        if (is_file($oldPath)) {
            // Suppress unlink warnings but keep operation safe
            @unlink($oldPath);
        }
    }

    // Move uploaded file to final path
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }    

    // Gera imagens redimensionadas
    $CI->load->library('image_lib');

    // Small 375x375
    $config                   = [];
    $config['image_library']  = 'gd2';
    $config['source_image']   = $newFilePath;
    $config['new_image']      = $filename;
    $config['maintain_ratio'] = true;
    $config['width']          = 375;
    $config['height']         = 375;
    $CI->image_lib->initialize($config);
    $CI->image_lib->resize();
    $CI->image_lib->clear();

    /// Persist new filename to DB
    $CI->db->where('id', $partner_id);
    $CI->db->update(db_prefix() . 'partners', [
        'file_name' => $filename
    ]);   

    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');        
    }
    
    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'id'            => (int)$partner_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'file_name'     => $originalName,     
    ];     
    
    return $resp;

}
/**
 * Handles uploading a user's/customer avatar:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Removes the old avatar and its thumbnails (thumb and small)
 * - Moves the uploaded file to the customer uploads folder
 * - Creates resized images: thumb (320x320) and small (96x96)
 * - Updates the database with the new avatar filename
 * - Deletes the original file after creating the thumbnails
 * - Returns an array with type (success/error), message, and small avatar URL
 */
function handle_customers_picture_uploads($customer_id)
{
    $CI = &get_instance();

    // Basic guards
    if (empty($customer_id) || !is_numeric($customer_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['file'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }  

    // Extract upload basics
    $tmpFilePath    = $_FILES['file']['tmp_name'] ?? '';
    $originalName   = $_FILES['file']['name'] ?? '';
    $mimeType       = $_FILES['file']['type'] ?? ''; 


    // Destination path for team customer_id (team/customer_id)
    $path = rtrim(get_upload_path_by_type('customers'), '/\\') . '/';
    _maybe_create_upload_path($path);

    
    // Generate unique target filename
    $filename = unique_filename($path, $originalName);
    $newFilePath = $path . $filename;

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }    

    // Pre-upload hook for observers
    hooks()->do_action('before_upload_customer_picture', $customer_id);

    // Remove previous file if exists
    $CI->db->where('id', $customer_id);
    $current = $CI->db->get(db_prefix() . 'customers')->row();
    if ($current && !empty($current->file_name)) {
        $oldPath = $path . $current->file_name;
        if (is_file($oldPath)) {
            // Suppress unlink warnings but keep operation safe
            @unlink($oldPath);
        }
    }

    // Move uploaded file to final path
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }    

    // Gera imagens redimensionadas
    $CI->load->library('image_lib');

    // Small 375x375
    $config                   = [];
    $config['image_library']  = 'gd2';
    $config['source_image']   = $newFilePath;
    $config['new_image']      = $filename;
    $config['maintain_ratio'] = true;
    $config['width']          = 375;
    $config['height']         = 375;
    $CI->image_lib->initialize($config);
    $CI->image_lib->resize();
    $CI->image_lib->clear();

    /// Persist new filename to DB
    $CI->db->where('id', $customer_id);
    $CI->db->update(db_prefix() . 'customers', [
        'file_name' => $filename
    ]);   

    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');        
    }
    
    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'id'            => (int)$customer_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'file_name'     => $originalName,     
    ];     
    
    return $resp;

}
/**
 * Handles uploading a user's/team avatar:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Removes the old avatar and its thumbnails (thumb and small)
 * - Moves the uploaded file to the team uploads folder
 * - Creates resized images: thumb (320x320) and small (96x96)
 * - Updates the database with the new avatar filename
 * - Deletes the original file after creating the thumbnails
 * - Returns an array with type (success/error), message, and small avatar URL
 */
function handle_teams_avatar_uploads($team_id)
{
    $CI = &get_instance();

    // Basic guards
    if (empty($team_id) || !is_numeric($team_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['file_avatar'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['file_avatar']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file_avatar']['error']));
    }  

    // Extract upload basics
    $tmpFilePath    = $_FILES['file_avatar']['tmp_name'] ?? '';
    $originalName   = $_FILES['file_avatar']['name'] ?? '';


    // Destination path for team team_id (team/team_id)
    $basePath = rtrim(get_upload_path_by_type('teams'), '/\\');
    $path     = $basePath . '/' . $team_id . '/'; 
    _maybe_create_upload_path($path);

    
    // Generate unique target filename
    $filename = unique_filename($path, $originalName);

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }    

    // Remove previous file if exists
    $CI->db->where('id', $team_id);
    $_file = $CI->db->get(db_prefix() . 'teams')->row();
    if ($_file && !empty($_file->file_avatar)) {
        @unlink($path . $_file->file_avatar);
        @unlink($path . 'small_' . pathinfo($_file->file_avatar, PATHINFO_BASENAME));
    }

    // Move uploaded file to final path
    $newFilePath = $path . $filename;
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }    

    // Gera imagens redimensionadas
    $CI->load->library('image_lib');

    // Small 250x250
    $config                   = [];
    $config['image_library']  = 'gd2';
    $config['source_image']   = $newFilePath;
    $config['new_image']      = 'small_' . $filename;
    $config['maintain_ratio'] = true;
    $config['width']          = 250;
    $config['height']         = 250;
    $CI->image_lib->initialize($config);
    $CI->image_lib->resize();
    $CI->image_lib->clear();

    /// Persist new filename to DB
    $CI->db->where('id', $team_id);
    $CI->db->update(db_prefix() . 'teams', [
        'file_avatar' => $filename
    ]);    
    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');        
    }

    // Remove arquivo original
    //@unlink($newFilePath);
    
    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'id'            => (int)$team_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'file_avatar'   => $originalName,     
    ];     
    
    return $resp;

}
/**
 * Upload picture for Technology Item
 * - Validates upload and extension (reuses _upload_pictures_allowed / site_pic_types)
 * - Ensures destination path exists (business/items)
 * - Removes previous file if exists
 * - Updates DB with new file metadata
 * - Returns standardized set_alert payload + data for frontend
 *
 * @param int         $carousel_id     Technology item ID (required)
 * @return array                   set_alert payload with data or error
 */
function handle_carousel_picture_uploads($carousel_id)
{
    $CI = &get_instance();

    // Basic guards
    if (empty($carousel_id) || !is_numeric($carousel_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['file'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['file']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['file']['error']));
    }

    // Extract upload basics
    $tmpFilePath   = $_FILES['file']['tmp_name'];
    $originalName  = $_FILES['file']['name'];
    $mimeType      = $_FILES['file']['type'];    

    if (empty($tmpFilePath) || empty($originalName)) {
        return set_alert(false, 'unprocessable', 'Empty file or temporary path.');
    }
    
    // Destination path for technology items (technology/items)
    $path = rtrim(get_upload_path_by_type('carousel'), '/') . '/';
    _maybe_create_upload_path($path);
    
    // Generate unique target filename
    $filename    = unique_filename($path, $_FILES['file']['name']);
    $newFilePath = $path . $filename;

    // Valida extensão
    if (!_upload_pictures_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('site_pic_types'));
    }

    // Pre-upload hook for observers
    hooks()->do_action('before_upload_technology_item_picture', $carousel_id);

    // Remove previous file if exists
    $CI->db->where('id', $carousel_id);
    $current = $CI->db->get(db_prefix() . 'carousel')->row();
    if ($current && !empty($current->file_name)) {
        $oldPath = $path . $current->file_name;
        if (is_file($oldPath)) {
            // Suppress unlink warnings but keep operation safe
            @unlink($oldPath);
        }
    }

    // Move uploaded file to final path
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Persist new filename to DB
    $CI->db->where('id', (int)$carousel_id);
    $CI->db->update(db_prefix() . 'carousel', [
        'file_name' => $filename,
        'original_file_name' => $originalName
    ]);

    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');
    }    

    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'carousel_id'   => (int)$carousel_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'file_name'     => $filename,     
    ];  

    return $resp;
}
/**
 * Category file
 * @param  mixed $id Category ID to add file
 * @return array  - Result values
 */
function handle_category_file_uploads($id)
{
    $message    = '';

    if (isset($_FILES['file']) && _api_upload_error($_FILES['file']['error'])) {   
        return array(
            'message' => _api_upload_error($_FILES['file']['error'])
        );         
        return false; 
    }    
    if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
        $path = get_upload_path_by_type('categories_icons');
        
        hooks()->do_action('before_upload_category_file');
        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            _maybe_create_upload_path($path);  
            $filename    = unique_filename($path, $_FILES['file']['name']); 
            // Getting file extension
            $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));   
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'svg'
            ];

            $allowed_extensions = hooks()->apply_filters('contact_category_file_upload_allowed_extensions', $allowed_extensions);

            if (!in_array($extension, $allowed_extensions)) {
                return array(
                    'message' => 'Image extension not allowed. Extensions: ' . get_option('site_pic_types')
                );                  
                return false; 
            }                   
            $CI = & get_instance();
                 
            // Remove old image  
            $CI->db->where('id', $id);
            $_file = $CI->db->get(db_prefix() . 'categories')->row();
            $_filename = $path . $_file->file_name;
            if($_filename && file_exists($path . $_file->file_name)) {
                @unlink($_filename);
            }	  
            
            $newFilePath = $path . $filename;                           
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI->db->where('id', $id);
                $CI->db->update(db_prefix() . 'categories', [
                    'file_name' => $filename,
                ]);
                // Remove original image
                //unlink($newFilePath);

                return true; 
            }                         
        }       
    }

    return false;
}
/**
 * Clients file
 * @param  mixed $userid Client ID to add file
 * @return array  - Result values
 */
function handle_client_file_uploads($userid)
{
    $message    = '';

    if (isset($_FILES['file']) && _api_upload_error($_FILES['file']['error'])) {   
        return array(
            'message' => _api_upload_error($_FILES['file']['error'])
        );         
        return false; 
    }    
    if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
        $path = get_upload_path_by_type('client_logo_images') . $userid . '/';
        
        hooks()->do_action('before_upload_client_file');
        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            _maybe_create_upload_path($path);  
            $filename    = unique_filename($path, $_FILES['file']['name']); 
            // Getting file extension
            $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));   
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png'
            ];

            $allowed_extensions = hooks()->apply_filters('contact_client_file_upload_allowed_extensions', $allowed_extensions);

            if (!in_array($extension, $allowed_extensions)) {
                return array(
                    'message' => 'Image extension not allowed. Extensions: ' . $allowed_extensions
                );                  
                return false; 
            }                   
            $CI = & get_instance();
                 
            // Remove old image  
            $CI->db->where('userid', $userid);
            $_file = $CI->db->get(db_prefix() . 'clients')->row();
            $_filename = $path . $_file->logo_image;
            if($_filename && file_exists($path . $_file->logo_image)) {
                @unlink($_filename);
            }	  
            
            $newFilePath = $path . $filename;                           
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                //$config['new_image']      = 'thumb_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = hooks()->apply_filters('contact_client_logo_thumb_width', 320);
                $config['height']         = hooks()->apply_filters('contact_client_logo_thumb_height', 320);
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();
                $CI->db->where('userid', $userid);
                $CI->db->update(db_prefix() . 'clients', [
                    'logo_image' => $filename,
                ]);
                // Remove original image
                //unlink($newFilePath);

                return true; 
            }                         
        }       
    }

    return false;
}
/**
 * Maybe upload contact profile image
 * @param  string $contact_id contact_id or current logged in contact id will be used if not passed
 * @return boolean
 */
function handle_contact_profile_image_upload($contact_id)
{
    $message    = '';

    if (isset($_FILES['profile_image']) && _api_upload_error($_FILES['profile_image']['error'])) {   
        return array(
            'message' => _api_upload_error($_FILES['profile_image']['error'])
        );         
        return false; 
    }    
    if (isset($_FILES['profile_image']['name']) && $_FILES['profile_image']['name'] != '') {
        $path = get_upload_path_by_type('contact_profile_images') . $contact_id . '/';
        
        hooks()->do_action('before_upload_contact_profile_image');
        // Get the temp file path
        $tmpFilePath = $_FILES['profile_image']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            _maybe_create_upload_path($path);  
            $filename    = unique_filename($path, $_FILES['profile_image']['name']); 
            // Getting file extension
            $extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));   
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
            ];

            $allowed_extensions = hooks()->apply_filters('contact_profile_image_upload_allowed_extensions', $allowed_extensions);

            if (!in_array($extension, $allowed_extensions)) {
                return array(
                    'message' => 'Image extension not allowed. Extensions: ' . get_option('site_pic_types')
                );                  
                return false; 
            }                   
            $CI = & get_instance();
                 
            // Remove old image  
            $CI->db->where('id', $contact_id);
            $_file = $CI->db->get(db_prefix() . 'contacts')->row();
            $_filename = $path . $_file->profile_image;
            if($_filename && file_exists($path . $_file->profile_image)) {
                @unlink($_filename);
            }	  
            
            $newFilePath = $path . $filename;                           
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = hooks()->apply_filters('contact_profile_image_small_width', 150);
                $config['height']         = hooks()->apply_filters('contact_profile_image_small_height', 150);
               
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $CI->db->where('id', $contact_id);
                $CI->db->update(db_prefix() . 'contacts', [
                    'profile_image' => $filename,
                ]);
                // Remove original image
                unlink($newFilePath);                

                return true; 
            }                         
        }       
    }

    return false;
}
/**
 * Handles uploading a user's/staff avatar:
 * - Checks if a file was uploaded and if there are no upload errors
 * - Creates the destination directory if it does not exist
 * - Generates a unique filename for the uploaded file
 * - Validates the file extension
 * - Removes the old avatar and its thumbnails (thumb and small)
 * - Moves the uploaded file to the staff uploads folder
 * - Creates resized images: thumb (320x320) and small (96x96)
 * - Updates the database with the new avatar filename
 * - Deletes the original file after creating the thumbnails
 * - Returns an array with type (success/error), message, and small avatar URL
 */
function handle_profile_image_upload($profile_id)
{

    $CI = &get_instance();

    // Basic guards
    if (empty($profile_id) || !is_numeric($profile_id)) {
        return set_alert(false, 'unprocessable', 'Invalid item ID.');
    }

    // Check file presence early
    if (!isset($_FILES['avatar'])) {
        return set_alert(false, 'unprocessable', 'No file provided.');
    }    

    // Map PHP upload error to API message
    if (_api_upload_error($_FILES['avatar']['error'])) {
        return set_alert(false, 'unprocessable', _api_upload_error($_FILES['avatar']['error']));
    }    

    // Extract upload basics
    $tmpFilePath    = $_FILES['avatar']['tmp_name'];
    $originalName   = $_FILES['avatar']['name'];


    // Destination path for staff profile_id (staff/profile_id)
    $path = rtrim(get_upload_path_by_type('staff'), '/') . $profile_id . '/';
    _maybe_create_upload_path($path);

    // Generate unique target filename
    $filename = unique_filename($path, $originalName);

    // Valida extensão
    if (!_upload_avatar_allowed($filename)) {
        return set_alert(false, 'unprocessable', 'Image extension not allowed. Allowed: ' . get_option('avatar_types'));
    }


    // Remove previous file if exists
    $CI->db->where('staffid', $profile_id);
    $_file = $CI->db->get(db_prefix() . 'staff')->row();
    if ($_file && !empty($_file->avatar)) {
        @unlink($path . $_file->avatar);
        @unlink($path . 'small_' . pathinfo($_file->avatar, PATHINFO_BASENAME));
        @unlink($path . 'thumb_' . pathinfo($_file->avatar, PATHINFO_BASENAME));
    }

    // Move uploaded file to final path
    $newFilePath = $path . $filename;
    if (!@move_uploaded_file($tmpFilePath, $newFilePath)) {
        return set_alert(false, 'unexpected_error', 'Failed to move uploaded file.');
    }

    // Gera imagens redimensionadas
    $CI->load->library('image_lib');

    // Thumb 320x320
    $config = [
        'image_library' => 'gd2',
        'source_image' => $newFilePath,
        'new_image' => 'thumb_' . $filename,
        'maintain_ratio' => true,
        'width' => 320,
        'height' => 320
    ];
    $CI->image_lib->initialize($config);
    $CI->image_lib->resize();
    $CI->image_lib->clear();

    // Small 96x96
    $config['new_image'] = 'small_' . $filename;
    $config['width'] = 96;
    $config['height'] = 96;
    $CI->image_lib->initialize($config);
    $CI->image_lib->resize();
    $CI->image_lib->clear();

    /// Persist new filename to DB
    $CI->db->where('staffid', $profile_id);
    $CI->db->update(db_prefix() . 'staff', [
        'avatar' => $filename
    ]);
    if ($CI->db->affected_rows() === 0) {
        // Rollback file if DB update failed
        if (is_file($newFilePath)) {
            @unlink($newFilePath);
        }
        return set_alert(false, 'unexpected_error', 'Database update failed.');        
    }

    // Remove arquivo original
    unlink($newFilePath);
    
    // Build frontend payload with useful info
    $resp = set_alert(true, 'upload', 'picture');  
    $resp['data'] = [
        'staffid'       => (int)$profile_id,
        'path'          => $newFilePath, // absolute server path (use relative if needed)
        'avatar'        => staff_profile_image_url($profile_id, 'small')        
    ];     
    
    return $resp;
}
/**
 * Maybe upload staff profile image
 * @param  string $staff_id staff_id or current logged in staff id will be used if not passed
 * @return boolean
 */
function handle_admin_avatar_upload($staff_id = '')
{

    if (isset($_FILES['adminAvatar']['name']) && $_FILES['adminAvatar']['name'] != '') {
        do_action('before_upload_admin_avatar');
        $path = get_upload_path_by_type('avatars');
        // Get the temp file path
        $tmpFilePath = $_FILES['adminAvatar']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            // Getting file extension
            $extension = strtolower(pathinfo($_FILES['adminAvatar']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
            ];

            if (!in_array($extension, $allowed_extensions)) {
                //set_alert('warning', _l('file_php_extension_blocked'));

                return false;
            }
            //_maybe_create_upload_path($path);
            $filename    = unique_filename($path, $_FILES['adminAvatar']['name']);
            $newFilePath = $path . '/' . $filename;
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 320;
                $config['height']         = 320;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 96;
                $config['height']         = 96;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->db->where('adminId', $staff_id);
                $CI->db->update('admins', [
                    'adminAvatar' => $filename,
                ]);
                // Remove original image
                unlink($newFilePath);

                return true;
            }
        }
    }

    return false;
}
/**
 * Check if path exists if not exists will create one
 * This is used when uploading files
 * @param  string $path path to check
 * @return null
 */
function _maybe_create_upload_path($path)
{
    if (!file_exists($path)) {
        // Cria diretórios recursivamente
        if (!mkdir($path, 0755, true)) {
            throw new Exception("Falha ao criar diretório: $path");
        }

        // Cria index.html vazio para proteger o diretório
        $indexFile = rtrim($path, '/') . '/index.html';
        $handle = fopen($indexFile, 'w');
        if ($handle) {
            fclose($handle);
        }
    }
}

function create_img_thumb($path, $filename, $width = 1000, $height = 1000)
{
    $CI = & get_instance();

    $source_path = rtrim($path, '/') . '/' . $filename;
    $target_path = $path;
    $config_manip = array(
        'image_library' => 'gd2',
        'source_image' => $source_path,
        'new_image' => $target_path,
        'maintain_ratio' => true,
        'create_thumb' => true,
        'thumb_marker' => '_thumb',
        'width' => $width,
        'height' => $height
    );

    $CI->image_lib->initialize($config_manip);
    $CI->image_lib->resize();
    $CI->image_lib->clear();
}

function create_img_posts_thumb($path, $filename, $width = 520, $height = 520)
{
    $CI = &get_instance();

    $source_path = rtrim($path, '/') . '/' . $filename;
    $target_path = $path;
    $config_manip = array(
        'image_library'  => 'gd2',
        'source_image'   => $source_path,
        'new_image'      => $target_path,
        'maintain_ratio' => true,
        'create_thumb'   => true,
        'thumb_marker'   => '_thumb',
        'width'          => $width,
        'height'         => $height,
    );

    $CI->image_lib->initialize($config_manip);
    $CI->image_lib->resize();
    $CI->image_lib->clear();
}
/**
 * Handles uploads error with translation texts
 * @param  mixed $error type of error
 * @return mixed
 */
function _api_upload_error($error)
{
    // Get the Max Upload Size allowed
    $maxUpload = (int)(ini_get('upload_max_filesize'));  

    $uploadErrors = [
        0 => _l('file_uploaded_success'),
        1 => _l('file_exceeds_max_filesize') . '. Maximum size: ' . $maxUpload . 'MB',
        2 => _l('file_exceeds_maxfile_size_in_form'),
        3 => _l('file_uploaded_partially'),
        4 => _l('file_not_uploaded'),
        6 => _l('file_missing_temporary_folder'),
        7 => _l('file_failed_to_write_to_disk'),
        8 => _l('file_php_extension_blocked'),
    ];

    if (isset($uploadErrors[$error]) && $error != 0) {
        return $uploadErrors[$error];
    }

    return false;
}
/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function _upload_extension_allowed($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('allowed_files'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}
/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function _upload_pictures_allowed($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('site_pic_types'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}
/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function _upload_avatar_allowed($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('avatar_types'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}
/**
 * Function that return full path for upload based on passed type
 * @param  string $type
 * @return string
 */
function get_upload_path_by_type($type)
{
    $path = '';
    switch ($type) {
        case 'avatars':
            $path = AVATAR_ATTACHMENTS_FOLDER;

        break;   
        case 'slides':
            $path = SLIDES_UPLOADS_FOLDER;

        break;  
        case 'categories':
            $path = CATEGORIES_UPLOADS_FOLDER;

        break;      
        case 'company':
            $path = COMPANY_UPLOADS_FOLDER;

        break;   
        case 'technology':
            $path = TECHNOLOGY_UPLOADS_FOLDER;

        break;           
        case 'staff':
            $path = STAFF_UPLOADS_FOLDER;
    
        break;
        case 'client_logo_images':
            $path = CLIENT_LOGO_IMAGES_FOLDER;
    
        break; 
        case 'contact_profile_images':
            $path = CONTACT_PROFILE_IMAGES_FOLDER;
    
        break;                       
        case 'services':
            $path = SERVICES_UPLOADS_FOLDER;

        break;   
        case 'teams':
            $path = TEAMS_UPLOADS_FOLDER;

        break;   
        case 'carousel':
            $path = CAROUSEL_UPLOADS_FOLDER;

        break;           
        case 'partners':
            $path = PARTNERS_UPLOADS_FOLDER;            

        break;  
        case 'customers':
            $path = CUSTOMERS_UPLOADS_FOLDER;            

        break;          
        case 'projects':
            $path = PROJECTS_UPLOADS_FOLDER;

        break; 
        case 'posts':
            $path = POSTS_UPLOADS_FOLDER;

        break;                                           
    }

    return hooks()->apply_filters('get_upload_path_by_type', $path, $type);
}