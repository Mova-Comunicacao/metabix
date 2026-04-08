<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get post by ID or current queried post
 * @param  mixed $id post id
 * @return mixed
 */
function get_post($id = null)
{
    if (empty($id) && isset($GLOBALS['post'])) {
        return $GLOBALS['post'];
    }

    // Client global object not set
    if (empty($id)) {
        return null;
    }

    if (!class_exists('posts_model', false)) {
        get_instance()->load->model('posts_model');
    }

    $post = get_instance()->posts_model->get($id);

    return $post;
}

/**
 * Get post name by passed id
 * @param  mixed $id
 * @return string
 */
function get_post_name_by_id($id)
{
    $CI      = & get_instance();
    $post = $CI->api_object_cache->get('post-name-data-' . $id);

    if (!$post) {
        $CI->db->select('name');
        $CI->db->where('id', $id);
        $post = $CI->db->get(db_prefix() . 'posts')->row();
        $CI->api_object_cache->add('post-name-data-' . $id, $post);
    }

    if ($post) {
        return $post->name;
    }

    return '';
}

/**
 * Get post status by passed post id
 * @param  mixed $id post id
 * @return array
 */
function get_post_status_by_id($id)
{
    $CI = &get_instance();
    if (!class_exists('posts_model')) {
        $CI->load->model('posts_model');
    }

    $statuses = $CI->posts_model->get_post_statuses();

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
 * Return post image url
 * @param  mixed $post_id
 * @param  string $type
 * @return string
 */
function post_image_url($post_id, $type = '')
{
    $url  = '';
    $CI   = &get_instance();
    $path = $CI->api_object_cache->get('user-post-image-path-' . $post_id);

    if (!$path) {
        $CI->api_object_cache->add('user-post-image-path-' . $post_id, $url);

        if (!class_exists('posts_model')) {
            $CI->load->model('posts_model');
        }
        
        $pic = $CI->posts_model->get_picture($post_id);

        $fullPath  = $path . $pic->file_name; 
        $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
        $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
        $thumbPath = $fname . '_thumb.' . $fext;
        if (!empty($thumbPath)) {
            $thumb = $thumbPath;
        }  

        if ($pic && !empty($thumbPath)) {
            $path = 'api/uploads/posts/' . $post_id. '/' . $thumb;
            $CI->api_object_cache->set('user-post-image-path-' . $post_id, $path);
        }
    }

    if ($path) {
        $url = base_url($path);
    }

    return $url;
}