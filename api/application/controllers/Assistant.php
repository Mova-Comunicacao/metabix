<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Assistant extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_assistant_service');
        $this->load->model('assistant_model');
    }

    public function process()
    {
        $this->safe(function () {
            $formdata   = json_decode($this->input->raw_input_stream, true);

            $task       = $formdata['task']       ?? null;
            $text       = $formdata['text']       ?? null;
            $targetLang = $formdata['targetLang'] ?? null;
            $tone       = $formdata['tone']       ?? null;

            $history    = $formdata['history']    ?? [];
            $options    = $formdata['options']    ?? [];

            if (!$task || !$text) {
                return $this->unprocessable('Parâmetros inválidos. "task" e "text" são obrigatórios.');
            }

            $options = array_merge($options, [
                'targetLang' => $targetLang,
                'tone'       => $tone,
            ]);

            $result = $this->api_assistant_service->process($task, $text, $options, $history);

            $ok   = (bool)($result['ok'] ?? false);
            $type = $result['alert']['type'] ?? ($ok ? 'success' : 'error');

            $status = $ok ? 200 : ($type === 'warning' ? 422 : 400);

            if (!$ok) {
                return $this->respond($result, $status);
            }

            return $this->ok($result['data'] ?? null, 'update', 'text');
        });
    }

    public function generate()
    {
        $this->safe(function () {
            $formdata   = json_decode($this->input->raw_input_stream, true);

            $prompt         = $formdata['prompt']   ?? null;
            $width          = $formdata['width']    ?? 1024;
            $height         = $formdata['height']   ?? 1024;

            if (!$prompt) {
                return $this->unprocessable('Prompt inválido.');
            }        
            
            $result = $this->api_assistant_service->generate($prompt, $width, $height);

            $ok   = (bool)($result['ok'] ?? false);
            $type = $result['alert']['type'] ?? ($ok ? 'success' : 'error');

            $status = $ok ? 200 : ($type === 'warning' ? 422 : 400);

            if (!$ok) {
                return $this->respond($result, $status);
            }

            return $this->ok($result['data'] ?? null, 'update', 'text');            
        });
    }

    /**
     * Get all posts with optional filters.
     *
     * Accepts GET params:
     * - category_id (int|null)
     * - search_string (string|null)
     *
     * Returns:
     * 200 OK with an array (possibly empty).
     * 500 on unexpected errors (handled by $this->safe()).
     */    
    public function get()
    {
        $this->safe(function () {
            $task       = $this->input->get('task') ?? null;
            $client     = $this->input->get('client_id') ?? null;
            $dateFrom   = $this->input->get('date_from') ?? null;
            $dateTo     = $this->input->get('date_to') ?? null;  

            $limit      = (int)($this->input->get('limit') ?? 50);
            $offset     = (int)($this->input->get('offset') ?? 0);

            $filters = [
                'task'      => $task,
                'client_id' => $client,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ];     
            
            $assistants = $this->assistant_model->get($filters, $limit, $offset);

            // array|obj|null
            if (empty($assistants)) {
                return $this->respond([], 200);
            }     
            
            $data[] = [
                'items'  => $rows,
                'limit'  => $limit,
                'offset' => $offset,                
            ];
            return $this->respond($data, 200);
        });
    } 
        
    public function preview_image()
    {

        $url = $this->input->get('path', true); // ex: http://localhost/metabix/api/uploads/ai/ai_2025...

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tif', 'tiff', 'webp'];

        $fallback_path = FCPATH . 'assets/images/preview-not-available.jpg';
        $fallback_type = 'image/jpeg';

        if (empty($url)) {
            $full_path = $fallback_path;
            $file_type = $fallback_type;
        } else {
            // Parse URL e pega só o path (/metabix/api/uploads/ai/arquivo.png)
            $parsed   = parse_url($url);
            $urlPath  = isset($parsed['path']) ? $parsed['path'] : '';

            // Queremos só a parte a partir de /uploads/ai/
            //    Exemplo: /metabix/api/uploads/ai/arquivo.png  -> uploads/ai/arquivo.png
            $marker = '/uploads/ai/';
            $pos    = strpos($urlPath, $marker);

            if ($pos === false) {
                // URL não contém o caminho esperado, cai no fallback
                $full_path = $fallback_path;
                $file_type = $fallback_type;
            } else {
                // pega "uploads/ai/arquivo.png" (sem a primeira "/")
                $relPath = substr($urlPath, $pos + 1);

                // Mapeia pro filesystem: FCPATH . 'uploads/ai/arquivo.png'
                $full_path = realpath(FCPATH . $relPath);

                if ($full_path === false || !is_file($full_path)) {
                    $full_path = $fallback_path;
                    $file_type = $fallback_type;
                } else {
                    $pathinfo = pathinfo($full_path);
                    $ext      = strtolower($pathinfo['extension'] ?? '');

                    if (!in_array($ext, $allowed_extensions)) {
                        $full_path = $fallback_path;
                        $file_type = $fallback_type;
                    } else {
                        // Descobre o mime type baseado na extensão
                        switch ($ext) {
                            case 'png':  $file_type = 'image/png';  break;
                            case 'gif':  $file_type = 'image/gif';  break;
                            case 'bmp':  $file_type = 'image/bmp';  break;
                            case 'webp': $file_type = 'image/webp'; break;
                            case 'jpg':
                            case 'jpeg':
                            case 'tif':
                            case 'tiff':
                            default:     $file_type = 'image/jpeg'; break;
                        }
                    }
                }
            }
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $filename = basename($full_path);
        $filesize = @filesize($full_path) ?: null;

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        if ($filesize !== null) {
            header('Content-Length: ' . $filesize);
        }

        $file = fopen($full_path, 'rb');
        if ($file !== false) {
            while (!feof($file)) {
                echo fread($file, 8192);
            }
            fclose($file);
        }

        exit;
    }

    
}