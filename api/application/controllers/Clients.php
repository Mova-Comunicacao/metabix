<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        hooks()->do_action('after_clients_area_init', $this);
    }    

    /**
     * API Root endpoint.
     *
     * Returns basic app info (e.g., company name).
     *
     * Response: 200 OK
     */    
    public function index()
    {
        $this->safe(function () {
            $data = [
                'title' => (string) get_option('company_name'),
            ];
            return $this->respond($data, 200);
        });    
    }       
        
    /**
     * Get all active languages.
     *
     * Returns all languages from the languages_model where 'active' = 1.
     * Fields: id, name, code, language_cod, flag, etc. (depending on model structure)
     *
     * Response: 200 OK (array of languages)
     */    
	public function languages()
	{
        $this->safe(function () {
            $langs = $this->languages_model->get(null, ['active' => 1]);

            if (empty($langs)) {
                return $this->respond([], 200);
            }

            return $this->respond($langs, 200);
        });
	}  

    /**
     * Public slides endpoint.
     *
     * Returns active slides with a short description and main picture (if any).
     * Folder and file URLs are built as:
     *   {base}/api/uploads/slides/{slide_id}/<file>
     * Thumbnail convention: "small_{file_name}" in the same folder (fallback to original if missing).
     *
     * Response: 200 OK with an array (possibly empty).
     */
    public function slides()
    {
        $this->safe(function () {
            $slides = $this->slides_model->get('', ['slides.active' => 1]);

            if (empty($slides)) {
                return $this->respond([], 200);
            }            

            $data = [];
			foreach($slides as $row){
                // Short description (safe cast)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 150));
                
                // Base folder URL: /api/uploads/slides/
                $base   = rtrim(base_url('api/uploads/slides'), '/');
                $folder = $base . '/';

                // Picture (first/main) - optional
                $pic      = $this->slides_model->get_picture($row->id);
                $pictures = [];

                if (!empty($pic)) {
                    $fileName   = (string)($pic->file_name ?? '');
                    $origName   = (string)($pic->original_file_name ?? '');
                    $subject    = (string)($pic->subject ?? '');
                    $descPic    = (string)($pic->description ?? '');

                    // thumb
                    $thumbName  = 'thumb_' . $fileName;

                    // Prefer thumb; fall back to original if needed
                    $thumbUrl = $folder . rawurlencode($thumbName);
                    $fileUrl  = $folder . rawurlencode($fileName);

                    $pictures = [
                        'file_name'          => $fileName,
                        'original_file_name' => $origName,
                        'subject'            => $subject,
                        'description'        => sanitize_html_input($descPic),
                        'url'                => $fileUrl,
                        'thumb'              => $thumbUrl, // client may fall back to url if 404
                    ];
                }

				$data[] = [
                    'id'            => $row->id,
					'name'          => $row->name,
					'description'   => sanitize_html_input($shortDesc),
					'link'          => $row->link,
					'mask'          => $row->mask,
					'folder'        => $folder,
                    'active'        => $row->active,
					'pictures'      => $pictures,
                    'language'      => $row->language_cod,
                ];			
			}
            return $this->respond($data, 200);
		});
    }  
  
    /**
     * Get all active posts.
     *
     * Optional GET params:
     * - category_id (int)
     * - search_string (string)
     *
     * Returns active posts with basic info and thumbnail.
     * Response: 200 OK (array or empty array)
     */
    public function posts()
    {
        $this->safe(function () {
            // Collect filter params
            $ts_filter_data = [
                'category_id'   => $this->input->get('category_id'),
                'search_string' => $this->input->get('search_string'),
            ];
            $filter = ['filter' => $ts_filter_data];

            // Fetch posts
            $posts = $this->posts_model->get('', $filter, ['posts.active' => 1]);
            
            if (empty($posts)) {
                return $this->respond([], 200);
            }        

            $data = [];
			foreach($posts as $row){ 
                // Shortened description
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 50));

                // Folder URL (safe encoding)
                $folder = trim((string)($row->folder ?? ''), "/ \t\n\r\0\x0B");                
                // Folder URL safe
                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                }
                // Thumbnail helper (fallback handled by post_image_url)
                $thumbUrl = post_image_url((int)$row->id, 'thumb');

				$data[] = [
                    'id'               => isset($row->id) ? (int)$row->id : null,
                    'name'             => (string)($row->name ?? ''),
                    'description'      => $shortDesc,
                    'long_description' => sanitize_html_input((string)($row->long_description ?? '')),
                    'folder'           => $folderUrl,
                    'order'            => isset($row->order) ? (int)$row->order : 0,
                    'external_link'    => (string)($row->external_link ?? ''),
                    'slug'             => (string)($row->slug ?? ''),
                    'language'         => (string)($row->language_cod ?? ''),
                    'pictures'         => [
                        'thumb' => $thumbUrl,
                    ],
                ];                
            }
            return $this->respond($data, 200);         
        });
    }   
    
    /**
     * Get posts by slug.
     *
     * @param string $slug
     *
     * Returns an array of posts matching the slug, including:
     * - basic fields, categories, and pictures (with file URL + thumb URL)
     * - folder URL: /api/uploads/posts/{id}/
     * - time_read via estimateReadingTime()
     *
     * Response: 200 OK (array or empty array)
     */
    public function getPostsBySlug($slug)
    {
        $this->safe(function () use ($slug) {
            $slug = (string)$slug;
            if ($slug === '') {
                return $this->unprocessable('Missing or invalid slug.', [
                    'slug' => 'Required and must not be empty.'
                ]);
            }

            $posts = $this->posts_model->slug($slug);
            // array|obj|null
            if (empty($posts)) {
                return $this->respond([], 200);
            }

            // Helper date -> ISO-8601 (RFC3339)
            $toIso = static function ($dateStr) {
                $ts = @strtotime((string)$dateStr);
                return $ts ? date('c', $ts) : (string)$dateStr;
            };            

            $data = [];
			foreach($posts as $row){ 
                $id        = isset($row->id) ? (int)$row->id : null;
                
                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        // Folder URL: /api/uploads/posts/{id}/
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . rawurlencode((int)$row->id) . '/';
                    }
                }  

                // Pictures
                $pictures = [];
                $project_pic = $this->posts_model->get_pictures($id);
                if (!empty($project_pic)) {
                    foreach ($project_pic as $pic) {
                        $fname   = (string)($pic->file_name ?? '');
                        $orig    = (string)($pic->original_file_name ?? '');
                        $subject = (string)($pic->subject ?? '');
                        $pdesc   = (string)($pic->description ?? '');
                        $visible = isset($pic->visible_full) ? (int)$pic->visible_full : null;

                        // URLs (prefer thumb small_{file_name})
                        $fileUrl  = $folderUrl . rawurlencode($fname);
                        $thumbUrl = $folderUrl . rawurlencode($fname . '_thumb');

                        $pictures[] = [
                            'file_name'          => $fname,
                            'original_file_name' => $orig,
                            'visible_full'       => $visible,
                            'subject'            => $subject,
                            'description'        => $pdesc,
                            'url'                => $fileUrl,
                            'thumb'              => $thumbUrl,
                        ];
                    }
                }

                // Categories
                $categories = [];
                $post_cat = $this->posts_model->get_categories($id);
                if (!empty($post_cat)) {
                    foreach ($post_cat as $cat) {
                        $categories[] = [
                            'id'   => isset($cat->id) ? (int)$cat->id : null,
                            'name' => (string)($cat->name ?? ''),
                        ];
                    }
                }  
                
                $longDesc = sanitize_html_input((string)($row->long_description ?? ''));
                $data[] = [
                    'id'               => $id,
                    'name'             => (string)($row->name ?? ''),
                    'description'      => (string)($row->description ?? ''),
                    'long_description' => $longDesc, 
                    'folder'           => $folderUrl,
                    'slug'             => (string)($row->slug ?? ''),
                    'order'            => isset($row->order) ? (int)$row->order : 0,
                    'date'             => $toIso($row->dateadded ?? null),
                    'language'         => (string)($row->language_cod ?? ''),
                    'categories'       => $categories,
                    'pictures'         => $pictures,
                    'time_read'        => estimateReadingTime($longDesc),
                ];   
            }
            return $this->respond($data, 200); 
        });
    }    

    /**
     * Get all post categories.
     *
     * Returns all categories from posts_model.
     * 
     * Response:
     * - 200 OK with an array of categories (possibly empty)
     */
    public function categories()
    {
        $this->safe(function () {
            $categories = $this->posts_model->get_categories();
            
            // array|obj|null
            if (empty($categories)) {
                return $this->respond([], 200);
            }

            $data = [];
            foreach($categories as $row){
                $data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'description'   => (string)($row->description ?? ''),
                    'language'      => (string)($row->language_cod ?? ''),                  
                ];
            }
            return $this->respond($data, 200);
        });
    }

    /**
     * Get categories for a given post.
     *
     * @param int|string $postid
     *
     * Responses:
     * - 200 OK with an array of categories (possibly empty)
     * - 422 Unprocessable when id is missing/invalid
     */
    public function category($postid)
    {
        $this->safe(function () use ($postid) {
            $id = (int)$postid;
            
            if ($id <= 0) {
                return $this->unprocessable('Missing or invalid id.', [
                    'id' => 'Required and must be greater than zero.'
                ]);
            }

            $categories = $this->posts_model->get_categories($id);
            // array|obj|null
            if (empty($categories)) {
                return $this->respond([], 200);
            }     
            
            $data = [];
            foreach($categories as $row){
                $data[] = [
                    'category_id' => isset($row->category_id) ? (int)$row->category_id : null,
                    'id'          => isset($row->id) ? (int)$row->id : null,
                    'name'        => (string)($row->name ?? ''),
                    'language'    => (string)($row->language_cod ?? ''), 
                ];                    
            }
            return $this->respond($data, 200);
        });
    }     

    /**
     * Public company endpoint.
     *
     * Returns active company with a short description and main picture (if any).
     * Folder and file URLs are built as:
     *   {base}/api/uploads/{folder}/<file>
     *
     * Response: 200 OK with an array (possibly empty).
     */
    public function company()
    {
        $this->safe(function () {
            $company = $this->company_model->get();
            if (empty($company)) {
                return $this->respond([], 200);
            }

            $data = [];
			foreach($company as $row){        
                // Short description (safe cast)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 250));

                // Base folder URL: /api/uploads/company/
                $base   = rtrim(base_url('api/uploads'), '/');
                $folder = $base . $row->folder. '/';

                $data[] = [
                    'name'          => (string)($row->name ?? ''),
                    'description'   => $shortDesc,
                    'long_description' => sanitize_html_input((string)($row->long_description ?? '')),
                    'folder'        => $folder,
                    'language'      => (string)($row->language_cod ?? ''),
                ];
            }
            return $this->respond($data, 200);          
        });
    }    

    /**
     * Get all company items.
     *
     * Returns all items from the company_model with normalized data and URLs.
     * Each item may include:
     * - id, name, description
     * - folder (URL)
     * - file_name
     * - visible_draft (bool/int)
     * - language (code)
     *
     * Response: 200 OK (array or empty array)
     */
	public function companyItems()
	{
        $this->safe(function () {
            $items = $this->company_model->get_items();

            if (empty($items)) {
                return $this->respond([], 200);
            }

            $data = [];
			foreach($items as $row){ 
				$data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
					'description'   => sanitize_html_input((string)($row->description ?? '')),
					'order'         => isset($row->order) ? (int)$row->order : 0, 
                    'language'      => (string)($row->language_cod ?? ''),
                ];
			}
            return $this->respond($data, 200);          
		});
	}   
    
    /**
     * Get company pictures.
     *
     * @param int|bool $limit Optional limit for the number of results.
     *
     * Returns all company pictures (or limited set) with file URLs and metadata.
     *
     * Response: 200 OK (array or empty array)
     */
	public function companyIndexPictures($limit = true)
	{
        $this->safe(function () use ($limit) {
            $data = $this->company_model->get_pictures('', $limit);
            if (empty($data)) {
                $response = array(
                    'type' => 'info',
                    'message' => 'No Pictures'
                );                   
                return $this->respond($response, 200);
            }

            return $this->respond($data, 200);          
        });
	}  
    
    /**
     * Get company pictures.
     *
     * @param int|bool $limit Optional limit for the number of results.
     *
     * Returns all company pictures (or limited set) with file URLs and metadata.
     *
     * Response: 200 OK (array or empty array)
     */    
	public function companyPictures($limit = false)
	{
        $this->safe(function () use ($limit) {
            $pictures = $this->company_model->get_pictures('', $limit);

            if (empty($pictures)) {
                return $this->respond([], 200);
            }

            // Folder URL (safe encoding)
            $baseUrl = rtrim(base_url('api/uploads/company'), '/');

            $data = [];
            foreach($pictures as $pic){
                $fileName = (string)($pic->file_name ?? '');
                $origName = (string)($pic->original_file_name ?? '');
                $subject  = (string)($pic->subject ?? '');   
                $desc     = (string)($pic->description ?? '');     
                
                // URLs seguras (encode por arquivo)
                $fileUrl  = $baseUrl . '/' . rawurlencode($fileName);
            
                $data[] = [
                    'file_name'          => $fileName,
                    'original_file_name' => $origName,                      
                    'subject'            => $subject,
                    'description'        => $desc,                                                   
                ];
            }      
            return $this->respond($data, 200);          
        });
	}         

    /**
     * Get company seals.
     * Returns all company seals (or limited set) with file URLs and metadata.
     *
     * Response: 200 OK (array or empty array)
     */  
    public function companySeals()
     {
        $this->safe(function () {
            $seals = $this->company_model->get_seals();
            
            // array|obj|null
            if (empty($seals)) {
                return $this->respond([], 200);
            }

            $base = rtrim(base_url('api/uploads/seals'), '/');

            $data = [];
             foreach($seals as $row){ 
                $fileName = (string)($row->file_name ?? '');
                $origName = (string)($row->original_file_name ?? '');
                $subject  = (string)($row->subject ?? '');   
                $desc     = (string)($row->description ?? ''); 
                           
                // Folder URL safe
                $fileName = !empty($row->file_name) ? (string)$row->file_name : null;
                $folderUrl = $base . '/';

                                
                // URLs seguras (encode por arquivo)
                $fileUrl  = $folderUrl . rawurlencode($fileName);

                $data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'subject'       => $subject,
                    'file_name'     => $fileName,
                    'original_file_name' => $origName,     
                    'description'   => $desc,                    
                    'external'      => (string)($row->external ?? ''),
                    'folder'        => $folderUrl,
                    'order'         => isset($row->order) ? (int)$row->order : 0, 
                    'url'           => $fileUrl,                     
                ];
            }
            return $this->respond($data, 200);           
         });
    }  

    /**
     * Public technolgy endpoint.
     *
     * Returns active technolgy with a short description and main picture (if any).
     * Folder and file URLs are built as:
     *   {base}/api/uploads/{folder}/<file>
     *
     * Response: 200 OK with an array (possibly empty).
     */
    public function technology()
    {
        $this->safe(function () {
            $technology = $this->technology_model->get();
            if (empty($technology)) {
                return $this->respond([], 200);
            }

            $data = [];
            foreach($technology as $row){        
                // Short description (safe cast)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 250));

                // Base folder URL: /api/uploads/technology/
                $base   = rtrim(base_url('api/uploads'), '/');
                $folder = $base . $row->folder. '/';
                                
                $data[] = [
                    'name'          => (string)($row->name ?? ''),
                    'description'   => $shortDesc,
                    'long_description' => sanitize_html_input((string)($row->long_description ?? '')),
                    'folder'        => $folder,
                    'language'      => (string)($row->language_cod ?? ''),
                ];
            }
            return $this->respond($data, 200);  
        });       
    }    

    /**
     * Get all technology items.
     *
     * Returns all items from the technology_model with normalized data and URLs.
     * Each item may include:
     * - id, name, description
     * - folder (URL)
     * - file_name
     * - visible_draft (bool/int)
     * - language (code)
     *
     * Response: 200 OK (array or empty array)
     */
	public function technologyItems()
	{
        $this->safe(function () {
            $items = $this->technology_model->get_items();

            if (empty($items)) {
                return $this->respond([], 200);
            }

            $data = [];
            foreach($items as $row){ 
                // Folder URL (safe encoding)
                $folder = trim((string)($row->folder ?? ''), "/ \t\n\r\0\x0B");
                $folderUrl = null;

                if ($folder !== '') {
                    $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($row->folder)), '/') . '/icons/';
                }   

                // Description short version (safe)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 625));

                $data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'folder'        => $folderUrl,
                    'file_name'     => (string)($row->file_name ?? ''),                  
                    'description'   => sanitize_html_input($shortDesc),
                    'visible_draft' => isset($row->visible_draft) ? (int)$row->visible_draft : 0,
                    'order'         => isset($row->order) ? (int)$row->order : 0,
                    'language'      => (string)($row->language_cod ?? ''),
                ];
            }
            return $this->respond($data, 200);    
        });      
	} 

    /**
     * Get technology pictures.
     *
     * @param int|bool $limit Optional limit for the number of results.
     *
     * Returns all technology pictures (or limited set) with file URLs and metadata.
     *
     * Response: 200 OK (array or empty array)
     */   
	public function technologyPictures()
	{
        $this->safe(function () use ($limit) {
            $pictures = $this->technology_model->get_pictures('');

            if (empty($pictures)) {
                return $this->respond([], 200);
            }
                       
            // Folder URL (safe encoding)
            $baseUrl = rtrim(base_url('api/uploads/technology'), '/');

            $data = [];
            foreach($pictures as $pic){
                $fileName = (string)($pic->file_name ?? '');
                $origName = (string)($pic->original_file_name ?? '');
                $subject  = (string)($pic->subject ?? '');   
                $desc     = (string)($pic->description ?? '');  

                // URLs seguras (encode por arquivo)
                $fileUrl  = $baseUrl . '/' . rawurlencode($fileName);
                $thumbUrl = $baseUrl . '/thumb_' . rawurlencode($fileName);

                $data[] = [
                    'file_name'          => $fileName,
                    'original_file_name' => $origName,                      
                    'subject'            => $subject,
                    'description'        => $desc,  
                    'url'                => $fileUrl,                       
                    'thumb'              => $thumbUrl,
                ];
            }      
            return $this->respond($data, 200);          
        });
	}    
    
    /**
     * Get all technology diagnosis.
     *
     * Returns all diagnosis from the technology_model with normalized data and URLs.
     * Each item may include:
     * - id, name, description
    * - visible_draft (bool/int)
     * - language (code)
     *
     * Response: 200 OK (array or empty array)
     */
	public function technologyDiagnosis()
	{
        $this->safe(function () {
            $diagnosis = $this->technology_model->get_diagnosis();

            if (empty($diagnosis)) {
                return $this->respond([], 200);
            }

            $data = [];
            foreach($diagnosis as $row){ 
                // Description short version (safe)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 625));

                if (is_array($row)) {
                    $id              = isset($row['id']) ? (int)$row['id'] : null;
                    $name            = (string)($row['name'] ?? '');
                    $description     = (string)($row['description'] ?? '');
                    $longDescription = (string)($row['long_description'] ?? '');
                    $visibleDraft    = isset($row['visible_draft']) ? (int)$row['visible_draft'] : 0;
                    $order           = isset($row['order']) ? (int)$row['order'] : 0;
                    $language        = (string)($row['language'] ?? '');
                } 

                $data[] = [
                    'id'               => $id,
                    'name'             => $name,
                    'description'      => $shortDesc,
                    'long_description' => sanitize_html_input($longDescription),
                    'visible_draft'    => $visibleDraft,
                    'order'            => $order,
                    'language'         => $language,
                ];
            }            
            return $this->respond($data, 200); 
        });
    }

    /**
     * Get all technology precision.
     *
     * Returns all precision from the technology_model with normalized data and URLs.
     * Each item may include:
     * - id, name, description
     * - language (code)
     *
     * Response: 200 OK (array or empty array)
     */
	public function technologyPrecision()
	{
        $this->safe(function () {
            $precision = $this->technology_model->get_precision();

            if (empty($precision)) {
                return $this->respond([], 200);
            }

            $data = [];
            foreach($precision as $row){ 
                // Description short version (safe)
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 625));

                if (is_array($row)) {
                    $id              = isset($row['id']) ? (int)$row['id'] : null;
                    $name            = (string)($row['name'] ?? '');
                    $description     = (string)($row['description'] ?? '');
                    $order           = isset($row['order']) ? (int)$row['order'] : 0;
                    $language        = (string)($row['language'] ?? '');
                } 

                $data[] = [
                    'id'               => $id,
                    'name'             => $name,
                    'description'      => sanitize_html_input($description),
                    'order'            => $order,
                    'language'         => $language,
                ];
            }            
            return $this->respond($data, 200); 
        });
    }    

    /**
     * Get technology videos (not visible to customer).
     *
     * Fetches videos from technology_model with filter ['visible_to_customer' => 0].
     * Returns a normalized payload including a derived video_id and thumbnail.
     *
     * Response: 200 OK (array or empty array)
     */
    public function technologyVideos()
    {
        $this->safe(function () {
            $videos = $this->technology_model->get_videos(['visible_to_customer' => 0]);

            if (empty($videos)) {
                return $this->respond([], 200);
            }              

            $data = [];
            foreach($videos as $v){
                // Shortened description
                $shortDesc = strip_tags(character_limiter((string)($row->description ?? ''), 50));

                // External video URL (e.g., YouTube/Vimeo) → extract id + thumb
                $external = (string)($v->external ?? '');
                $videoId  = $external !== '' ? getVideoLocation($external) : null;
                $thumb    = $external !== '' ? video_image($external) : null;

                $data[] = [
                    'id'                  => isset($v->id) ? (int)$v->id : null,
                    'name'                => (string)($v->name ?? ''),
                    'description'         => sanitize_html_input($shortDesc),
                    'long_description'    => sanitize_html_input((string)($v->long_description ?? '')),
                    'video_id'            => $videoId,
                    'visible_to_customer' => isset($v->visible_to_customer) ? (int)$v->visible_to_customer : 0,
                    'order'               => isset($v->order) ? (int)$v->order : 0,
                    'thumb'               => $thumb,                                               
                ];                                                
            }
            return $this->respond($data, 200); 
        });
    }    

    public function carousel()
    {
        $this->safe(function () {
            $carousel = $this->carousel_model->get('', ['visible_to_customer' => 0]);

            // array|obj|null
            if (empty($carousel)) {
                return $this->respond([], 200);
            }


            $data = [];
            foreach ($carousel as $row) {  
                $fileName = (string)($row->file_name ?? '');

                // helper folder URL segura
                $folderUrl = null;
                if (!empty($row->folder)) {
                    $folder = trim((string)$row->folder, "/ \t\n\r\0\x0B");
                    if ($folder !== '') {
                        $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/';
                    }
                }      
                
                // URLs seguras (encode por arquivo)
                $fileUrl = $folderUrl && $fileName !== '' ? $folderUrl . rawurlencode($fileName) : null;
     
                // THUMB:
                // Se for vídeo com link externo → gera thumb via helper video_image()
                // Senão, usa thumb_arquivo padrão
                $thumbUrl = null;
                $thumbId  = null;
                if (!empty($row->external)) {
                    $thumbUrl = video_image($row->external);
                    $thumbId  = getVideoLocation($row->external);
                } elseif ($folderUrl && $fileName !== '') {
                    $thumbUrl = $folderUrl . rawurlencode($fileName);
                }

                $data[] = [
                    'id'          => isset($row->id) ? (int)$row->id : null,
                    'type'        => (string)($row->type ?? ''),
                    'file_name'   => $fileName,
                    'name'        => (string) ($row->subject ?? ''),
                    'description' => sanitize_html_input(strip_tags(character_limiter((string) ($row->description ?? ''), 50))),
                    'url'         => $fileUrl ?: (string)($row->external ?? ''),
                    'thumb'       => $thumbUrl,
                    'thumbId'     => $thumbId,
                    'order'       => isset($row->order) ? (int)$row->order : 0,
                ];            
            }        
            return $this->respond($data, 200);
        });
    } 

    /**
     * Get all post partners.
     *
     * Returns all partners from posts_model.
     * Folder and file URLs are built as:
     *   {base}/api/uploads/<file>* 
     * 
     * Response:
     * - 200 OK with an array of partners (possibly empty)
     */
    public function partners()
     {
        $this->safe(function () {
            $partners = $this->partners_model->get();
            
            // array|obj|null
            if (empty($partners)) {
                return $this->respond([], 200);
            }

            $base = rtrim(base_url('api/uploads/partners'), '/');

            $data = [];
             foreach($partners as $row){ 
                $fileName = (string)($row->file_name ?? '');
                // Folder URL safe
                $folderUrl = $base . '/';

                // thumb
                $thumbName  = 'thumb_' . $fileName;
                                
                // Prefer thumb; fall back to original if needed
                $thumbUrl = $folderUrl . rawurlencode($thumbName);
                $fileUrl  = $folderUrl . rawurlencode($fileName);

                $partnercountry = get_country_name($row->countryid);
                $country = [];
                if(!empty($partnercountry)){
                    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $partnercountry);
                    $slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $slug);
                    $slug = strtolower(trim(preg_replace('/\s+/', '-', $slug)));
                    $country = [
                        'name' => $slug,
                    ]; 
                }

                $data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'description'   => sanitize_html_input((string)($row->description ?? '')),
                    'external_link' => (string)($row->external_link ?? ''),
                    'folder'        => $folderUrl,
                    'order'         => isset($row->order) ? (int)$row->order : 0, 
                    'type'          => isset($row->type) ? (int)$row->type : 0, 
                    'url'           => $fileUrl,
                    'thumb'         => $thumbUrl,
                    'country'       => $country
                ];
            }
            return $this->respond($data, 200);           
         });
    }      

    /**
     * Get all post customers.
     *
     * Returns all customers from posts_model.
     * Folder and file URLs are built as:
     *   {base}/api/uploads/<file>* 
     * 
     * Response:
     * - 200 OK with an array of customers (possibly empty)
     */
    public function customers()
     {
        $this->safe(function () {
            $customers = $this->customers_model->get();
            
            // array|obj|null
            if (empty($customers)) {
                return $this->respond([], 200);
            }

            $base = rtrim(base_url('api/uploads/customers'), '/');

            $data = [];
             foreach($customers as $row){ 
                $fileName = (string)($row->file_name ?? '');
           
                // Folder URL safe
                $fileName = !empty($row->file_name) ? (string)$row->file_name : null;
                $folderUrl = $base . '/';

                // thumb
                $thumbName  = 'thumb_' . $fileName;
                                
                // Prefer thumb; fall back to original if needed
                $thumbUrl = $folderUrl . rawurlencode($thumbName);
                $fileUrl  = $folderUrl . rawurlencode($fileName);

                $data[] = [
                    'id'            => isset($row->id) ? (int)$row->id : null,
                    'name'          => (string)($row->name ?? ''),
                    'external_link' => (string)($row->external_link ?? ''),
                    'folder'        => $folderUrl,
                    'order'         => isset($row->order) ? (int)$row->order : 0, 
                    'url'           => $fileUrl,
                    'thumb'         => $thumbUrl,
                ];
            }
            return $this->respond($data, 200);           
         });
    }  

    /**
     * Get all teams.
     *
     * Returns all team records with basic details and avatar URLs.
     * Each item includes folder URL (/api/uploads/teams/{id}/) and file_avatar (full path).
     *
     * Response: 200 OK (array or empty array)
     */
    public function teams()
    {
        $this->safe(function () {
            $teams = $this->teams_model->get();

            if (empty($teams)) {
                return $this->respond([], 200);
            }

            $base = rtrim(base_url('api/uploads/teams'), '/');
            
            $data = [];
			foreach($teams as $row){ 
                $id = isset($row->id) ? (int)$row->id : 0;
                $folderUrl = $base . '/' . rawurlencode((string)$id) . '/';

                $fileName = !empty($row->file_avatar) ? (string)$row->file_avatar : null;
                $fileUrl  = $fileName ? $folderUrl . rawurlencode($fileName) : null;

                $data[] = [
                    'id'          => $id,
                    'name'        => (string)($row->name ?? ''),
                    'description' => sanitize_html_input((string)($row->description ?? '')),
                    'phonenumber' => (string)($row->phonenumber ?? ''),
                    'email'       => (string)($row->email ?? ''),
                    'folder'      => $folderUrl,
                    'file_avatar' => $fileUrl,
                    'order'       => isset($row->order) ? (int)$row->order : 0,
                    'language'    => (string)($row->language_cod ?? ''),
                ];
            }
            return $this->respond($data, 200);          
        });
    }     
      

    public function social()
    {
        $this->safe(function () {
            $data = $this->social_model->get(null, ['active' => 1]);
            if (empty($data)) {
                $response = array(
                    'type' => 'info',
                    'message' => 'No Pictures'
                );                   
                return $this->respond($response, 200);
            }

            return $this->respond($data, 200);  
        });
    }    

    public function addLead()
    {
        $this->safe(function () {
            // Load payload (expects JSON body)
            $formdata = $this->readJson();
            
            if (empty($formdata) || !is_array($formdata)) {
                return $this->unprocessable('Empty or invalid payload.');
            }

            // Required fields
            $errors = [];
            $name           = (string)($formdata['name']           ?? '');
            $phonenumber    = (string)($formdata['phonenumber']    ?? '');
            $emailRaw       = trim((string)($formdata['email']     ?? ''));
            $source         = (int)($formdata['source']            ?? 3);

            // E-mail: normaliza e valida
            $email = strtolower($emailRaw);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email.';
            }    
            
            if (!empty($errors)) {
                return $this->unprocessable('Validation failed.', $errors);
            }            

			$data = [
                'name'        => $name,
				'phonenumber' => $phonenumber,
				'email'       => $email,
                'source'      => $source
            ];

            // Insere via model
            $id = $this->leads_model->add($data);
            if (!$id) {
                return $this->unprocessable('Failed to create lead.');
            }

           return $this->ok(['id' => (int)$id], 'create', 'lead');
        });      
    }  

    /**
     * Get all countries.
     *
     * Returns all countries from posts_model.
     * 
     * Response:
     * - 200 OK with an array of countries (possibly empty)
     */
    public function countries()
    {
        $this->safe(function () {
            $countries = get_all_countries();

            // array|obj|null
            if (empty($countries)) {
                return $this->respond([], 200);
            }

            $data = [];
			foreach($countries as $row){ 
                $data[] = [
                    'name'     => (string)($row['short_name'] ?? ''),
                    'iso'      => (string)($row['iso2'] ?? ''),
                    'code'     => (string)($row['calling_code'] ?? ''),
                    'value'    => (int)$row['country_id'],
                ];
            }
            return $this->respond($data, 200);
        });
    }

    /**
     * Send contact email (optionally with one attachment).
     *
     * Expects multipart/form-data (POST):
     *  - subject   (string, required)
     *  - firstname (string, required)
     *  - lastname  (string, required)
     *  - email     (string, required, valid email)
     *  - phone     (string, optional)
     *  - message   (string, required)
     *  - file      (file,   optional; extension must be in ticket_attachments_file_extensions)
     *
     * Responses (via $this->safe):
     *  - 200 OK     on success
     *  - 400/422    on invalid payload or disallowed attachment
     */
    public function settings()
    {
        $this->safe(function () {

            $whatsapp_chat_clients_area = get_option('whatsapp_chat_clients_area');
            $whatsapp_chat_clients_area = html_entity_decode(clear_textarea_breaks($whatsapp_chat_clients_area));

            if(isMobile()){
                $service = 'api.whatsapp.com';
            }
            else {
                $service = 'web.whatsapp.com';
            }

            $data = [
				'main_domain'              => get_option('main_domain'),
				'company_name'             => get_option('company_name'),
				'business_name'            => get_option('business_name'),
				'company_address'          => get_option('company_address'),
				'company_city'             => get_option('company_city'),
				'company_alt_phonenumber'  => get_option('company_alt_phonenumber'),
				'company_postal_code'      => get_option('company_postal_code'),
				'company_phonenumber'      => get_option('company_phonenumber'),
				'company_email'            => get_option('company_email'),
				'company_description'      => get_option('company_description'),
                
                'ticket_attachments_file_extensions' => get_option('ticket_attachments_file_extensions'),

                // Whatsapp
                'whatsapp_chat'            => get_option('whatsapp_chat'),					
                'whatsapp_chat_clients_area' => 'https://' . $service . '/send?phone=' . $whatsapp_chat_clients_area,						
                'whatsapp_chat_description' => '&text=' . get_option('whatsapp_chat_description'),	            
            ];
           return $this->respond($data, 200);    
        });    
    }    

    // Send email - No templates used only simple string
    public function send_email()
    {
		
        $this->safe(function () {
            if (!$this->input->post()) {
                return $this->badRequest('Invalid request method. Expected POST.');
            }

            // Normalize inputs
            $subject   = trim((string)$this->input->post('subject'));
            $firstname = trim((string)$this->input->post('firstname'));
            $lastname  = trim((string)$this->input->post('lastname'));
            $email     = trim((string)$this->input->post('email'));
            $phone     = trim((string)$this->input->post('phone'));
            $message   = trim((string)$this->input->post('message'));

            // Basic validations
            $errors = [];
            if ($firstname === '')  { $errors['firstname'] = 'Required.'; }
            if ($lastname === '')   { $errors['lastname']  = 'Required.'; }
            if ($message === '')    { $errors['message']   = 'Required.'; }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email.';
            }
            if (!empty($errors)) {
                return $this->unprocessable('Invalid form data.', $errors);
            }

            $data = [
                'subject'   => $subject,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'email'     => $email,
                'phone'     => $phone,
                'message'   => $message,
            ];
            
            $this->load->model('emails_model');

            // Handle optional attachment
            $attachmentAdded = false;
            $disallowedExt   = null;

            if (isset($_FILES['file']) && is_array($_FILES['file']) && ($_FILES['file']['name'] ?? '') !== '') {
                // Check PHP upload errors first
                if (!empty($_FILES['file']['error']) && $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    return $this->badRequest('Upload error.', ['file_error' => (int)$_FILES['file']['error']]);
                }

                $extension = strtolower((string)pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = explode(',', (string)get_option('ticket_attachments_file_extensions'));
                $allowed_extensions = array_values(array_filter(array_map('trim', $allowed_extensions)));
                $allowed_extensions = hooks()->apply_filters('ticket_attachments_file_extensions', $allowed_extensions);

                if ($extension === '' || !in_array($extension, $allowed_extensions, true)) {
                    $disallowedExt = $extension;
                } else {
                    // Add the attachment to emails_model queue
                    $this->emails_model->add_attachment([
                        'attachment' => $_FILES['file']['tmp_name'],
                        'filename'   => $_FILES['file']['name'],
                        'type'       => $_FILES['file']['type'] ?? 'application/octet-stream',
                        'read'       => true,
                    ]);
                    $attachmentAdded = true;
                }
            }  
            
            // If user attempted an upload but extension is not allowed, stop here
            if ($disallowedExt !== null) {
                return $this->unprocessable(
                    'File extension not allowed.',
                    [
                        'extension' => $disallowedExt,
                        'allowed'   => implode(', ', $allowed_extensions ?? []),
                    ]
                );
            }            

            // Send email via model
            $success = $this->emails_model->send_email_contact($data);

            if ($success) {
                return $this->respond([
                    'type'    => 'success',
                    'title'   => 'Success!',
                    'message' => _l('custom_file_success_send'),
                    'meta'    => ['attachment_added' => $attachmentAdded],
                ], 200);
            }  
            
            // Soft failure (e.g., SMTP issue)
            return $this->respond([
                'type'    => 'warning',
                'title'   => 'Warning!',
                'message' => _l('custom_file_fail_send'),
                'meta'    => ['attachment_added' => $attachmentAdded],
            ], 200);            
        });
    }     
}
