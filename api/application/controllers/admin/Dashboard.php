<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends AdminController
{

  /**
   * @var Google_Service_AnalyticsReporting
   */
  private $gapi;

  private $error;

	public function __construct()
	{
        parent::__construct();
        $this->load->library('Api_Google');

        $propertyId = get_option('google_property_id');
        if (empty($propertyId)) {
            $propertyId = getenv('GA4_PROPERTY_ID') ?: config_item('google_property_id');
        }

        $propertyId = is_string($propertyId) ? trim($propertyId) : '';
        if ($propertyId === '' || !preg_match('/^\d+$/', $propertyId)) {
            log_message('error', 'GA4 property id inválido/ausente. Configure google_ga4_property_id (ex.: 398765432). Valor atual: ' . print_r($propertyId, true));
            // você pode lançar exceção aqui para falhar cedo, ou deixar “preguiçoso”
            throw new InvalidArgumentException('GA4 property id inválido ou ausente.');
        }        

        try {
            // Se quiser, passe também o caminho de credenciais por ENV/config:
            // $credentials = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: APPPATH.'third_party/google/analytics-service-account.readJson';
            // $this->gapi = new Api_Google($propertyId, $credentials); // se você implementou essa assinatura
            $this->gapi = new Api_Google($propertyId);
            log_message('debug', 'GA4 Api_Google inicializada. property=properties/' . $propertyId);
        } catch (Throwable $e) {
            log_message('error', 'Falha ao inicializar GA4 Api_Google: ' . $e->getMessage());
            // opcional: responda 500 em endpoints críticos ou deixe para o método que usa a lib tratar
            show_error('Falha ao inicializar Google Analytics.', 500);
        }
    }   
     

    /* This is admin dashboard view */
    public function index()
    {
        try {
            $data = [
                'start_date' => '2025-05-04',
                'end_date'   => '2025-11-04',
                'metrics'    => ['activeUsers'],
                'dimensions' => ['pageTitle'],
            ];

            $report = $this->gapi->requestReportData($data);
            $this->respond($report, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::index error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter relatório padrão.'
            ], 500);
        }      
    }    
  
    
    /**
     * GET /dashboard/getDateRange/{start?}/{end?}
     */
    public function getDateRange($start_date = null, $end_date = null): void
    {
        try {
            $resp = $this->gapi->createDateRange($start_date, $end_date);
            $this->respond($resp, 200);
        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getDateRange error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao montar intervalo de datas.'
            ], 500);
        }
    }
    
    /**
     * POST /dashboard/getReportData
     * body: { start_date?, end_date?, metrics[], dimensions[] }
     */
    public function getReportData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics', 'dimensions'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestReportData($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getReportData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter dados do relatório.'
            ], 500);
        }
    }

    /**
     * POST /dashboard/getFilterReportData
     * body: { start_date?, end_date?, metrics[], dimensions[], dimensions_filter }
     */
    public function getFilterReportData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics', 'dimensions', 'dimensions_filter'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestFilterReportData($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getFilterReportData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter relatório filtrado.'
            ], 500);
        }
    }

    /**
     * POST /dashboard/getReportFilterMetricData
     * body: { start_date?, end_date?, metrics[], dimensions_filter }
     */
    public function getReportFilterMetricData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics', 'dimensions_filter'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestFilterDataMetric($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getReportFilterMetricData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter métricas filtradas.'
            ], 500);
        }
    }

    /**
     * POST /dashboard/getGraphReportData
     * body: { start_date?, end_date?, metrics[], dimensions[] }
     */
    public function getGraphReportData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics', 'dimensions'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestReportData($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getGraphReportData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter dados para gráfico.'
            ], 500);
        }
    }

    /**
     * Alias para manter compatibilidade com o nome antigo se precisar
     */
    public function getGranphReportData(): void
    {
        $this->getGraphReportData();
    }

    /**
     * POST /dashboard/getRealtimeReportData
     * body: { start_date?, end_date?, metrics[], dimensions[] }
     */
    public function getRealtimeReportData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics', 'dimensions'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestRealtimeReportData($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getRealtimeReportData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter dados em tempo real.'
            ], 500);
        }
    }

    /**
     * POST /dashboard/getReportMetricData
     * body: { start_date?, end_date?, metrics[] }
     */
    public function getReportMetricData(): void
    {
        $input = $this->readJson();
        try {
            $data = $this->buildPayload(
                $input,
                ['metrics'],
                ['start_date', 'end_date']
            );

            $resp = $this->gapi->requestDataMetric($data);
            $this->respond($resp, 200);

        } catch (Throwable $e) {
            log_message('error', 'Dashboard::getReportMetricData error: ' . $e->getMessage());
            $this->respond([
                'error' => true,
                'message' => 'Falha ao obter métricas.'
            ], 500);
        }
    }
    

    /**
     * Checa e extrai campos obrigatórios/opcionais do payload.
     * @param array $required ex.: ['metrics','dimensions']
     * @param array $optional ex.: ['start_date','end_date','dimensions_filter']
     */
    private function buildPayload(array $input, array $required, array $optional = []): array
    {
        $out = [];

        foreach ($required as $k) {
            if (!array_key_exists($k, $input) || $input[$k] === null) {
                $this->respond([
                    'error' => true,
                    'message' => "Campo obrigatório ausente: {$k}"
                ], 422);
                exit;
            }
            $out[$k] = $input[$k];
        }

        foreach ($optional as $k) {
            if (array_key_exists($k, $input)) {
                $out[$k] = $input[$k];
            }
        }

        // validações simples de data (YYYY-MM-DD) se vierem
        foreach (['start_date', 'end_date'] as $dk) {
            if (isset($out[$dk]) && $out[$dk] !== null && $out[$dk] !== '') {
                $d = \DateTime::createFromFormat('Y-m-d', (string)$out[$dk]);
                if (!$d || $d->format('Y-m-d') !== (string)$out[$dk]) {
                    $this->respond([
                        'error' => true,
                        'message' => "Formato de data inválido para {$dk}. Use YYYY-MM-DD."
                    ], 422);
                    exit;
                }
            }
        }

        return $out;
    }      
}