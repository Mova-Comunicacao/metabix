<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_assistant {

    protected $api_hf_key;
    protected $api_hf_url;
    
    protected $api_hf_timeout;

    /** @var string[] */
    private $api_text_models = [];

    /** @var string[] */
    private $api_image_models = [];

    // compat
    //protected $api_image_model;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('ai', TRUE);
        $config                 = $CI->config->item('ai');


        $this->api_hf_key       = $config['api_hf_key']             ?? null;
        $this->api_hf_url       = rtrim($config['api_hf_url']       ?? 'https://router.huggingface.co', '/');
        
        //$this->api_text_model     = $config['api_text_model']     ?? '';
        //$this->api_image_model    = $config['api_image_model']    ?? '';
        $this->api_hf_timeout       = $config['api_hf_timeout']     ?? 120;

        $this->api_text_models      = $config['api_text_models']    ?? [];
        if (empty($this->api_text_models) && !empty($config['api_text_model'])) {
            $this->api_text_models  = [ $config['api_text_model'] ];
        }

        $this->api_image_models = $config['api_image_models']       ?? [];
        if (empty($this->api_image_models) && !empty($config['api_image_model'])) {
            $this->api_image_models = [ $config['api_image_model'] ];
        }        

        // Log opcional pra debug
        log_message('error', 'AI CONFIG: ' . print_r([
            'api_hf_key_prefix' => $this->api_hf_key ? substr($this->api_hf_key, 0, 5) . '...' : 'NULL',
            'api_hf_url'        => $this->api_hf_url,         
            'api_hf_timeout'    => $this->api_hf_timeout,
            'api_text_models'   => $this->api_text_models,
            'api_image_models'  => $this->api_image_models,            
        ], true));       
    }

    /**
     * Chat com fallback de modelos
     *
     * @param array $messages  Ex: [ ['role' => 'user', 'content' => 'Olá'] ]
     * @param array $options   Ex: ['temperature' => 0.7, 'max_tokens' => 1024]
     */
  
    public function chat($messages = [], array $options = [])
    {
        if (empty($this->api_hf_key)) {
            return [
                'success' => false,
                'error'   => 'AI API key not configured.'
            ];
        }

        if (empty($this->api_hf_url)) {
            return [
                'success' => false,
                'error'   => 'AI API URL not configured.'
            ];
        }              

        if (empty($this->api_text_models)) {
            return [
                'success' => false,
                'error'   => 'AI text model not configured.'
            ];
        }        

        // Defaults de geração
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens   = $options['max_tokens']  ?? 1024;
        $topP        = $options['top_p']       ?? 0.95;

        // For router: https://router.huggingface.co + /v1/chat/completions
        $url = rtrim($this->api_hf_url, '/') . '/v1/chat/completions';

        $headers = [
            'Authorization: Bearer ' . $this->api_hf_key,
            'Content-Type: application/json'
        ];

        $lastError = null;

        foreach ($this->api_text_models as $index => $model) {        
            if (!is_string($model) || $model === '') {
                continue;
            }

            $payload = [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => $temperature,
                'max_tokens'  => $maxTokens,
                'top_p'       => $topP,
                'stream'      => false,                
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => $this->api_hf_timeout,
            ]);

            $response   = curl_exec($ch);
            $errno      = curl_errno($ch);
            $status     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error      = curl_error($ch);
            curl_close($ch);

            if ($errno) {
                return [
                    'success' => false,
                    'error'   => 'cURL error: ' . $error
                ];
            }

            if ($status >= 200 && $status < 300) {
                $data = json_decode($response, true);

                if (!$data) {
                    $lastError = 'Invalid JSON from AI API.';
                    return [
                        'success' => false,
                        'error'   => $lastError,
                    ];
                }

                $text   = $data['choices'][0]['message']['content'] ?? '';
                $usage  = $data['usage'] ?? null;

                if ($index > 0) {
                    log_message('error', sprintf(
                        'movaAI: usou modelo de fallback TEXT "%s"', $model
                    ));
                }

                return [
                    'success' => true,
                    'text'    => $text,
                    'raw'     => $data,
                    'model'   => $model,
                    'usage'   => $usage
                ];
            }

            // HTTP error
            $errorMsg = 'HTTP error: ' . $status . ' | ' . $response;
            $lastError = $errorMsg;

            if ($this->shouldFallback($status, $response) && $index < count($this->api_text_models) - 1) {
                log_message('error', sprintf(
                    'movaAI: erro no modelo TEXT "%s" (%s). Tentando próximo modelo.',
                    $model,
                    $errorMsg
                ));
                continue; // vai pro próximo modelo
            }

            return [
                'success' => false,
                'error'   => $errorMsg,
            ];            
        }

        return [
            'success' => false,
            'error'   => $lastError ?: 'All text models failed.',
        ];
    }

    /**
     * Geração de imagem com fallback de modelos
     * Usa: api-inference.huggingface.co/models/<model> (grátis)
     */    
    public function image(string $prompt, int $width, int $height)
    {

        if (empty($this->api_hf_key)) {
            return [
                'success' => false,
                'error'   => 'AI API key not configured.'
            ];
        }

        if (empty($this->api_hf_url)) {
            return [
                'success' => false,
                'error'   => 'AI Inference URL not configured.'
            ];
        }   
        
        if (empty($this->api_image_models) || !is_array($this->api_image_models)) {
            return [
                'success' => false,
                'error'   => 'AI image models not configured.',
            ];
        }     
        
        // Sanitize size
        list($width, $height) = $this->sanitizeImageSize($width, $height);        

        $headers = [
            'Authorization: Bearer ' . $this->api_hf_key,
            'Content-Type: application/json',
            'Accept: image/png',
        ];

        $lastError = null;

        foreach ($this->api_image_models as $index => $model) {
            if (!is_string($model) || $model === '') {
                continue;
            }            

            // HF Inference free: https://api-inference.huggingface.co/models/<model>
            $url = rtrim($this->api_hf_url, '/') . '/hf-inference/models/' . $model;  

            $payload = [
                'inputs' => $prompt,
                'options' => [
                    'wait_for_model' => true
                ],
                'parameters' => [
                    'width'  => $width,
                    'height' => $height
                ]
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => $this->api_hf_timeout,
            ]);
    
            $response   = curl_exec($ch);
            $errno      = curl_errno($ch);
            $status     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error      = curl_error($ch);
            curl_close($ch);
    
            if ($errno) {
                return [
                    'success' => false,
                    'error'   => 'cURL error: ' . $error
                ];
            }

            if ($status >= 200 && $status < 300) {
                if ($index > 0) {
                    log_message('error', sprintf(
                        'movaAI: usou modelo de fallback IMAGE "%s"', $model
                    ));
                }

                return [
                    'success' => true,
                    'binary'  => $response,
                    'model'   => $model,
                ];
            }

            $errorMsg = 'HTTP error: ' . $status . ' | ' . $response;
            $lastError = $errorMsg;

            if ($this->shouldFallback($status, $response) && $index < count($this->api_image_models) - 1) {
                log_message('error', sprintf(
                    'movaAI: erro no modelo IMAGE "%s" (%s). Tentando próximo modelo.',
                    $model,
                    $errorMsg
                ));
                continue;
            }

            return [
                'success' => false,
                'error'   => $errorMsg,
            ];            
        }        

        return [
            'success' => false,
            'error'   => $lastError ?: 'All image models failed.',
        ];
    }    

    private function sanitizeImageSize($width, $height)
    {
        // Default values
        $min = 256;
        $max = 2048;

        $w = (int) $width;
        $h = (int) $height;

        if ($w < $min) $w = $min;
        if ($h < $min) $h = $min;
        if ($w > $max) $w = $max;
        if ($h > $max) $h = $max;

        // Many diffusion models prefer multiples of 8 or 64
        $w = $w - ($w % 8);
        $h = $h - ($h % 8);

        return [$w, $h];
    }   
    
    /**
     * Decide se devemos tentar fallback para outro modelo
     */
    private function shouldFallback(int $status, ?string $rawBody = null): bool
    {
        // status típicos onde faz sentido tentar outro modelo
        if (in_array($status, [402, 404, 408, 409, 429, 500, 502, 503, 504], true)) {
            return true;
        }

        $rawBodyLower = strtolower((string) $rawBody);

        $keywords = [
            'does not exist',
            'not found',
            'is currently loading',
            'you have exceeded your quota',
            'rate limit',
            'too many requests',
            'model is not available',
        ];

        foreach ($keywords as $word) {
            if (strpos($rawBodyLower, $word) !== false) {
                return true;
            }
        }

        return false;
    }    
}
