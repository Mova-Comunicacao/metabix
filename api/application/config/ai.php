<?php
defined('BASEPATH') OR exit('No direct script access allowed');


$config['ai'] = [
    'api_hf_key'        => HF_TOKEN,
    'api_hf_url'        => 'https://router.huggingface.co',

    'api_image_models'  => [
        'stabilityai/stable-diffusion-xl-base-1.0',           
        'stabilityai/stable-diffusion-2-1',
        'stabilityai/sd-turbo',
        'runwayml/stable-diffusion-v1-5',      
    ],

    'api_text_models'   => [
        'meta-llama/Llama-3.3-70B-Instruct', 
        'meta-llama/Llama-3.1-70B-Instruct',
        'meta-llama/Llama-3.1-8B-Instruct',
    ],

    'api_image_model'   => '',
    'api_text_model'    => '',

    'api_hf_timeout'    => 120,    
              
    // limite de caracteres por requisição
    'max_chars' => 8000,
    'max_monthly_requests' => 600,
    'max_monthly_requests_per_client' => 600,

    // Branding
    'assistant_name'    => 'movaAI',
    'assistant_version' => 'beta',    
];
