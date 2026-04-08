<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_assistant_service {

    protected $CI;
    protected $max_chars;
    protected $max_monthly_requests;
    protected $max_monthly_requests_per_client;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('ai');

        $this->max_chars = $this->CI->config->item('ai')['max_chars'] ?? 8000;
        $this->max_monthly_requests = $this->CI->config->item('ai')['max_monthly_requests'] ?? 0;
        $this->max_monthly_requests_per_client = $this->CI->config->item('ai')['max_monthly_requests_per_client'] ?? 0;

        
        $this->CI->load->library('api_assistant');
        $this->CI->load->model('assistant_model');
    }

    /**
     * Chat de texto com log + limite + padrão set_alert
     *
     * @param array $messages Ex: [ ['role'=>'user','content'=>'...'] ]
     * @param array $options  Ex: ['temperature'=>0.7]
     */    
    public function process($task, $text, $options = [], $history = [])
    {

        $task       = trim($task);
        $text       = trim($text);
        $targetLang = $options['targetLang'] ?? null;
        $tone       = $options['tone'] ?? null;

        $temperature = isset($options['temperature']) ? (float)$options['temperature'] : 1.0;
        $top_p       = isset($options['top_p'])       ? (float)$options['top_p']       : 1.0;
        $max_tokens  = isset($options['max_tokens'])  ? (int)$options['max_tokens']    : 1024;
        $stream      = !empty($options['stream']);
        $json_mode   = !empty($options['json_mode']);
        $moderation  = !empty($options['moderation']);

        $user_id    = function_exists('get_staff_user_id') ? get_staff_user_id() : null;
        $client_id  = function_exists('get_client_company_id') ? get_client_company_id() : null;

        $input_len = mb_strlen($text);

        $log = function($status, $extra = []) use ($task, $targetLang, $user_id, $client_id, $input_len) {
            $data = array_merge([
                'user_id'       => $user_id,
                'client_id'     => $client_id,
                'task'          => $task,
                'target_lang'   => $targetLang,
                'input_chars'   => $input_len,
                'output_chars'  => $extra['output_chars']  ?? null,
                'approx_tokens' => $extra['approx_tokens'] ?? null,
                'status'        => $status,
                'error_message' => $extra['error_message'] ?? null,
            ], []);
            $this->CI->assistant_model->add($data);
        };

        // Per-user limit (if logged)
        if($user_id) {
            $limitCheck = ai_client_can_use($user_id);  
            if (!$limitCheck['allowed']) {

                $msg = "Limite mensal da IA atingido. ({$limitCheck['used']} de {$limitCheck['limit']} usos)";

                // LOGA (se quiser)
                $log('error', ['error_message' => $msg]);

                // Resposta padrão da sua API
                $resp = set_alert(false, 'unprocessable', $msg);
                $resp['data'] = [
                    'error' => $msg,
                ];
                return $resp;
            }                      
        }

        // Global / account limit
        $limitCheck = ai_can_use();
        if (!$limitCheck['allowed']) {
            $msg = 'Limite mensal de uso da IA atingido. '
                . '(' . $limitCheck['used'] . ' de ' . $limitCheck['limit'] . ' usos).';

            $log('error', ['error_message' => $msg]);

            $resp = set_alert(false, 'unprocessable', $msg);
            $resp['data'] = [
                'error' => $msg,
            ];
            return $resp;
        }

        // Max chars validation
        if ($this->max_chars > 0 && $input_len > $this->max_chars) {
            $log('error', ['error_message' => 'Texto muito longo.']);
            // erro de validação no padrão da sua API
            $resp = set_alert(false, 'unprocessable', 'Texto muito longo. Reduza o conteúdo antes de enviar.');
            $resp['data'] = [
                'error' => 'Texto muito longo. Reduza o conteúdo antes de enviar.',
            ];
            return $resp;
        }

        // Build system + user prompts + history
        $systemPrompt = $this->buildSystemPrompt($tone, $targetLang);
        $userPrompt   = $this->buildUserPrompt($task, $text, $targetLang, $tone);

        $messages = [
            [ 'role' => 'system', 'content' => $systemPrompt ],
        ];

        $historyMessages = $this->buildHistoryMessages($history ?? []);
        $messages        = array_merge($messages, $historyMessages);  
        
        $messages[] = [ 'role' => 'user', 'content' => $userPrompt ];

        hooks()->do_action('before_rewrite_process', $task);  

        $chatOptions = [
            'temperature' => $temperature,
            'top_p'       => $top_p,
            'max_tokens'  => $max_tokens,
            'stream'      => $stream,
        ];

        if ($json_mode) {
            $chatOptions['response_format'] = ['type' => 'json_object'];
        }

        // se tiver model dinâmico:
        if (!empty($options['model'])) {
            $chatOptions['model'] = $options['model'];
        }

        $result = $this->CI->api_assistant->chat($messages, $chatOptions);

        // Erro na chamada da IA
        if (!$result['success']) {
            $log('error', ['error_message' => $result['error'] ?? 'Erro ao processar com IA.']);
            
            $msg = 'Erro ao processar com IA';
            $resp = set_alert(false, 'unexpected_error', $result['error'] ?? $msg);
            $resp['data'] = [
                'error' => $result['error'] ?? $msg,
            ];
            return $resp;
        }

        $modelUsed = $result['model']  ?? null;
        $usage     = $result['usage']  ?? null;

        $output = trim($result['text'] ?? '');
        $output_len = mb_strlen($output);
        $approx_tokens = (int)round(($input_len + $output_len) / 4);        

        // default
        $log('success', [
            'output_chars'  => $output_len,
            'approx_tokens' => $approx_tokens,
            'model'         => $modelUsed,
        ]);

        // SUCESSO
        $resp = set_alert(true, 'update', 'text');
        $resp['data'] = [
            'output' => trim($result['text']),
            'model'  => $modelUsed,
            'usage'  => $usage,            
        ];

        hooks()->do_action('after_rewrite_process', $task, $messages);

        return $resp;
    }

    /**
     * Chat de image com log + limite + padrão set_alert
     *
     * @param array $messages Ex: [ ['role'=>'user','content'=>'...'] ]
     * @param array $options  Ex: ['temperature'=>0.7]
     */       
    public function generate($prompt, $width, $height)
    {
        $prompt = trim($prompt);
        $width  = (int)$width;
        $height = (int)$height;

        $user_id    = function_exists('get_staff_user_id') ? get_staff_user_id() : null;
        $client_id  = function_exists('get_client_company_id') ? get_client_company_id() : null;

        $input_len = mb_strlen($prompt);

        $log = function($status, $extra = []) use ($user_id, $client_id, $input_len) {
            $data = [
                'user_id'       => $user_id,
                'client_id'     => $client_id,
                'task'          => 'image',
                'target_lang'   => null,
                'input_chars'   => $input_len,
                'output_chars'  => $extra['output_chars']  ?? null,
                'approx_tokens' => $extra['approx_tokens'] ?? null,
                'status'        => $status,
                'error_message' => $extra['error_message'] ?? null,
            ];
            $this->CI->assistant_model->add($data);
        };   
        
        $limitCheck = ai_can_use();
        if (!$limitCheck['allowed']) {
            $msg = 'Limite mensal de uso da IA atingido. '
                . '(' . $limitCheck['used'] . ' de ' . $limitCheck['limit'] . ' usos).';

            $log('error', ['error_message' => $msg]);

            $resp = set_alert(false, 'unexpected_error', 'Limite de uso atingido.');
            $resp['data'] = [
                'error' => $msg,
            ];
            return $resp;
        }        

        $context = [
            'prompt' => $prompt,
            'width'  => $width,
            'height' => $height,
        ];

        hooks()->do_action('before_image_generate', $context);

        // Chama a imagem IA
        $result = $this->CI->api_assistant->image($prompt, $width, $height);

        // Erro na chamada da IA
        if (empty($result['success']) || !$result['success']) {
            $errorMsg = $result['error'] ?? 'Falha ao gerar imagem.';
            $log('error', ['error_message' => $errorMsg]);

            $resp = set_alert(false, 'unexpected_error', 'Falha ao gerar imagem.');
            $resp['data'] = [
                'error' => $errorMsg,
            ];
            return $resp;
        }

        $binary     = $result['binary'] ?? null;
        $modelUsed  = $result['model']  ?? null;

        if (!$binary) {
            $log('error', ['error_message' => 'Empty image binary from API.']);

            $resp = set_alert(false, 'unexpected_error', 'Falha ao gerar imagem (sem conteúdo).');
            $resp['data'] = [
                'error' => 'Empty image binary from API.'
            ];
            return $resp;
        }

        $filename = 'ai_' . date('Ymd_His') . '_' . uniqid() . '.png';
        $dir      = FCPATH . 'uploads/ai' . '/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filepath = $dir . $filename;
        file_put_contents($filepath, $binary);

        // helper folder URL segura
        $folderUrl = null;
        if (!empty($filename)) {
            $folder = trim((string) 'ai', "/ \t\n\r\0\x0B");
            if ($folder !== '') {
                $folderUrl = rtrim(base_url('api/uploads/' . rawurlencode($folder)), '/') . '/' . $filename;
            }            
        }

        //$url = base_url('api/uploads/ai/' . $filename);

        $log('success', [
            'output_chars'  => 0,
            'approx_tokens' => null,
            'model'         => $modelUsed,
        ]);

        $context['output_url'] = $folderUrl;
        $context['model']      = $modelUsed;

        // SUCESSO
        $resp = set_alert(true, 'update', 'text');
        $resp['data'] = [
            'output' => $folderUrl,
            'model'  => $modelUsed,
        ];

        hooks()->do_action('after_image_generate', $context);

        return $resp;
    }
   

    private function buildSystemPrompt($tone = null, $targetLang = null): string
    {
        $toneLabel = $tone ?: 'neutro';

        $langLabel = 'português brasileiro';
        if ($targetLang === 'es') {
            $langLabel = 'espanhol latino-americano';
        } elseif ($targetLang === 'en') {
            $langLabel = 'inglês';
        }

        return implode("\n", [
            "Você é um assistente de escrita (movaAI) para um painel de administração de sites.",
            "Você ajuda a reescrever, traduzir e continuar textos de forma clara, profissional e objetiva.",
            "Responda SEMPRE apenas com o texto final, sem comentários adicionais nem explicações.",
            "Mantenha o idioma de saída em {$langLabel}, a menos que a instrução diga o contrário.",
            "Tom preferencial: {$toneLabel}.",
        ]);
    }

    private function buildHistoryMessages(array $history, $maxMessages = 10): array
    {
        // Pega só as últimas N
        $history = array_slice($history, -$maxMessages);

        if (!is_array($history)) {
            $history = [];
        }

        $messages = [];
        foreach ($history as $item) {
            // se vier como objeto, normaliza
            if (is_object($item)) {
                $item = (array) $item;
            }

            $type = $item['type'] ?? null;
            $text = $item['text'] ?? '';

            if (!$text) {
                continue;
            }

            // ignorar imagens
            if (!empty($item['isImage'])) {
                continue;
            }

            $role = $type === 'out' ? 'assistant' : 'user';
            $messages[] = [
                'role'    => $role,
                'content' => $text,
            ];
        }

        return $messages;
    }    

    private function buildUserPrompt($task, $text, $targetLang = null, $tone = null)
    {
        $toneLabel = $tone ?: 'neutro';
        
        switch ($task) {
            case 'rewrite':
                return implode("\n", [
                    "Reescreva o texto abaixo no tom \"{$toneLabel}\", mantendo o mesmo significado.",
                    "Melhore fluidez, clareza e correção gramatical.",
                    "Não invente informações novas.",
                    "Responda apenas com o texto reescrito, sem comentários adicionais.",
                    "",
                    "Texto:",
                    '...',
                    $text,
                    '...',
                ]);

            case 'translate':
                $langLabel = 'inglês';
                if ($targetLang === 'pt') {
                    $langLabel = 'português brasileiro';
                } elseif ($targetLang === 'es') {
                    $langLabel = 'espanhol latino-americano';
                }

                return implode("\n", [
                    "Traduza o texto abaixo para {$langLabel}.",
                    "Mantenha o estilo e o tom o mais próximos possível do original.",
                    "Não reescreva, apenas traduza. Não explique a tradução.",
                    "",
                    "Texto:",
                    '...',
                    $text,
                    '...',
                ]);

            case 'continue':
                return implode("\n", [
                    "Continue o texto abaixo mantendo o mesmo estilo e tom.",
                    "Não repita frases já escritas.",
                    "Aprofunde a ideia, mas sem inventar fatos concretos que não são mencionados.",
                    "",
                    "Texto a ser continuado:",
                    '...',
                    $text,
                    '...',
                ]);

            default:
                // fallback genérico
                return $text;
        }
    }
}
