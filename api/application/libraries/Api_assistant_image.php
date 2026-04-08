<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_assistant_image {

    protected $api_hf_key;
    protected $api_hf_url;
    protected $api_hf_api_hf_model;

    protected $api_hf_timeout;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('ai', TRUE);
        $config         = $CI->config->item('ai');

        $this->api_hf_key       = $config['api_hf_key']         ?? null;
        $this->api_hf_url       = rtrim($config['api_hf_url']   ?? '', '/');
        $this->api_hf_model     = $config['api_hf_model']       ?? '';
        $this->api_hf_timeout   = $config['api_hf_timeout']     ?? 120;

        // Log opcional pra debug
        log_message('error', 'AI IMAGE CONFIG: ' . print_r([
            'api_hf_key_prefix' => $this->api_hf_key ? substr($this->api_hf_key, 0, 5) . '...' : 'NULL',
            'api_hf_url'        => $this->api_hf_url,
            'api_hf_model'      => $this->api_hf_model,
            'api_hf_timeout'    => $this->api_hf_timeout,
        ], true));     
    }

    public function call_hf_api(string $prompt, int $width, int $height)
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

        /**
         * api_hf_url pode ser:
         *  - direto no modelo (ex: https://api-inference.huggingface.co/models/black-forest-labs/FLUX.1-schnell)
         *  - ou base (ex: https://api-inference.huggingface.co/models) + api_hf_model
         */
        $url = $this->api_hf_url;
        if ($this->api_hf_model && strpos($this->api_hf_url, '/models/') === false) {
            $url = rtrim($this->api_hf_url, '/') . '/models/' . $this->api_hf_model;
        }

        $headers = [
            'Authorization: Bearer ' . $this->api_hf_key,
            'Content-Type: application/json'
        ];

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

        if ($status < 200 || $status >= 300) {
            return [
                'success' => false,
                'error'   => 'HTTP error: ' . $status . ' | ' . $response
            ];
        }

        // Para geração de imagem, o retorno aqui é binário (PNG/JPEG)
        return [
            'success' => true,
            'binary'  => $response,
        ];
    }
}
