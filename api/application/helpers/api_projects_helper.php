<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get project by ID or current queried project
 * @param  mixed $id project id
 * @return mixed
 */
function get_project($id = null)
{
    if (empty($id) && isset($GLOBALS['project'])) {
        return $GLOBALS['project'];
    }

    // Client global object not set
    if (empty($id)) {
        return null;
    }

    if (!class_exists('projects_model', false)) {
        get_instance()->load->model('projects_model');
    }

    $project = get_instance()->projects_model->get($id);

    return $project;
}

/**
 * Get project name by passed id
 * @param  mixed $id
 * @return string
 */
function get_project_name_by_id($id)
{
    $CI      = & get_instance();
    $project = $CI->api_object_cache->get('project-name-data-' . $id);

    if (!$project) {
        $CI->db->select('name');
        $CI->db->where('id', $id);
        $project = $CI->db->get(db_prefix() . 'projects')->row();
        $CI->api_object_cache->add('project-name-data-' . $id, $project);
    }

    if ($project) {
        return $project->name;
    }

    return '';
}

/**
 * Get project status by passed project id
 * @param  mixed $id project id
 * @return array
 */
function get_project_status_by_id($id)
{
    $CI = &get_instance();
    if (!class_exists('projects_model')) {
        $CI->load->model('projects_model');
    }

    $statuses = $CI->projects_model->get_project_statuses();

    $status = [
          'id'    => 0,
          'color' => '#333',
          'name'  => '[Status Not Found]',
          'order' => 1,
      ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * Return project image url
 * @param  mixed $project_id
 * @param  string $type
 * @return string
 */
function project_image_url($id, $project_id, $type = '')
{
    $url  = '';
    $CI   = &get_instance();
    $path = $CI->api_object_cache->get('user-project-image-path-' . $project_id);

    if (!$path) {
        $CI->api_object_cache->add('user-project-image-path-' . $project_id, $url);

        if (!class_exists('projects_model')) {
            $CI->load->model('projects_model');
        }
        
        $pic = $CI->projects_model->get_picture($id);

        if ($pic) {
            $fullPath  = $path . $pic->file_name; 
            $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
            $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
            $thumbPath = $fname . '_thumb.' . $fext;
            if (!empty($thumbPath)) {
                $thumb = $thumbPath;
            }  

            if ($pic && !empty($thumbPath)) {
                $path = 'api/uploads/projects/' . $project_id. '/' . $thumb;
                $CI->api_object_cache->set('user-project-image-path-' . $project_id, $path);
            }
        }
    }

    if ($path) {
        $url = base_url($path);
    }

    return $url;
}