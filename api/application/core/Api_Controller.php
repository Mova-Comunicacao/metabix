<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_Controller extends CI_Controller
{
    protected $current_db_version;

    public function __construct()
    {
        parent::__construct();


        if (function_exists('header_remove')) {
            header_remove('Access-Control-Allow-Origin');
            header_remove('Access-Control-Allow-Credentials');
            header_remove('Vary');
        }        

        $allowedOrigins = ['http://localhost:4200', 'http://localhost:5000', 'http://localhost',];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true"); // <- essencial p/ cookies
            header("Vary: Origin");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Cache-Control, Content-Type, Accept, Authorization");

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        
        $globals = $GLOBALS; // Ostensibly by-value copy

        $GLOBALS['EXT']->call_hook('pre_controller_constructor');
                    
        /**
         * Fix for users who don't replace all files during update !!!
         */
        if (!class_exists('ForceUTF8\Encoding') && file_exists(APPPATH . 'vendor/autoload.php')) {
            require_once(APPPATH . 'vendor/autoload.php');
        }

        if (CI_VERSION != '3.1.11') {
            echo '<h2>Additionally you will need to replace the <b>system</b> folder. We updated Codeigniter to 3.1.13.</h2>';
            echo '<p>From the newest downloaded files upload the <b>system</b> folder to your installation directory.';
            die;
        }

        if (!extension_loaded('mbstring') && (!function_exists('mb_strtoupper') || !function_exists('mb_strtolower'))) {
            die('<h1>"mbstring" PHP extension is not loaded. Enable this extension from cPanel or consult with your hosting provider to assist you enabling "mbstring" extension.</h4>');
        }

        /**
         * Set system timezone based on selected timezone from options
         * @var string
         */
        $this->db->reconnect();    
        $timezone = get_option('default_timezone');
        if ($timezone != '') {
            date_default_timezone_set($timezone);
        }    

        //$this->load->model('authentication_model');
        //$this->authentication_model->autologin();

        if ($this instanceof ClientsController) {
            load_client_language();
        } elseif ($this instanceof AdminController) {
            load_admin_language();
        } else {
            // When App_Controller is only extended or any other CORE controller that is not instance of ClientsController or AdminController
            // Will load the default sytem language, so we can get the locale and language from $GLOBALS;
            load_admin_language();
        }        

        $vars             = [];
        $vars['locale']   = $GLOBALS['locale'];
        $vars['language'] = $GLOBALS['language'];

        $this->load->vars($vars);

        $this->current_db_version = $this->api->get_current_db_version();

        hooks()->do_action('api_init');
    }

    
    public static function ymd(?string $date): ?string
    {
        if ($date === null || $date === '') return null;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return ($d && $d->format('Y-m-d') === $date) ? $date : null;
    }

    /**
     * Lê JSON do body. Retorna array vazio se não houver body.
     * Lança 400 se JSON inválido.
     */
    public function readJson(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            $this->badRequest('Empty request body.');
            exit;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->badRequest('Invalid JSON.');
            exit;
        }
        return is_array($data) ? $data : [];
    }  
        
    /**
     * Resposta JSON padrão
     */
    protected function respond($payload, int $status = 200): void
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** 200 OK com payload opcional */
    protected function ok($data = null, string $action = 'action', string $context = ''): void
    {
        $resp = set_alert(true, $action, $context, $data);
        $this->respond($resp, 200);
    }

    /** 400 Bad Request */
    protected function badRequest(string $message = 'Requisição inválida.', array $fields = []): void
    {
        $resp = set_alert(false, 'bad_request', $message, null, $fields ?: null);
        $this->respond($resp, 400);
    }

    /** 422 Unprocessable Entity */
    protected function unprocessable(string $message = 'Dados inválidos.', array $fields = []): void
    {
        $resp = set_alert(false, 'unprocessable', $message, null, $fields ?: null);
        $this->respond($resp, 422);
    }

    /** 404 Not Found  */
    protected function notFound(string $message = 'Not found'): void
    {
        $resp = set_alert(false, 'not_found', $message);
        $this->respond($resp, 404);
    }

    /** 401 Unauthorized */
    protected function unauthorized(string $message = 'Unauthorized'): void
    {
        $resp = set_alert(false, 'unauthorized', $message);
        $this->respond($resp, 401);
    }

    /** 403 Forbidden */
    protected function forbidden(string $message = 'Forbidden'): void
    {
        $resp = set_alert(false, 'forbidden', $message);
        $this->respond($resp, 403);
    }

    /** 500 Server Error */
    protected function serverError(string $message = 'Erro inesperado. Tente novamente.'): void
    {
        $resp = set_alert(false, 'server_error', $message);
        $this->respond($resp, 500);
    }
    
    /** try/catch centralizado para normalizar erros inesperados */
    protected function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\DomainException $e) {
            $this->badRequest($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->unprocessable($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'SERVER ERROR: '.$e->getMessage().' | '.$e->getTraceAsString());
            $this->serverError('Internal server error.');
        }
    }    
}
