<?php
declare(strict_types=1);
/**
 * Google Class
 * This class enables you to use the Google Protocol
 *
 * @subpackage Libraries
 * @category   Analytics
 */

defined('BASEPATH') || exit('No direct script access allowed');

const REPORT_REQUESTS_LIMIT = 5;
const METRICS_LIMIT = 10;
const PAGESIZE_LIMIT = 100000;

// Load the Google API PHP Client Library.
include_once(APPPATH . 'vendor/autoload.php');

use Google\Analytics\Data\V1beta\RunReportResponse;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;

use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\ApiCore\ApiException;

use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\InListFilter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\FilterExpressionList;

class Api_Google
{

    /**
     * @var Google_Client
     */
    private $client;

    private $analytics;

    private $reportingDimension = null;

    private $reportingMetric = null;

    private $property_id;

    private $startDate;

    private $end_date;

    private $metrics;

    private $dimensions;

    private $returnedReports;

    public function __construct(?string $property_id = null, ?string $credentialsPath = null)
    {
        try {
             $this->property_id = $this->resolvePropertyId($property_id);
            // Creates and returns the Analytics Reporting service object.
            $this->initializeAnalytics($credentialsPath);
            log_message('debug', 'GA4 init OK. property=properties/' . $this->property_id);
        } catch (\Throwable $e) {
            // loga e propaga (ou trate como preferir)
            log_message('error', 'GA4 init error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Descobre/valida o GA4 property id:
     * - usa o parâmetro se fornecido
     * - ENV (GA4_PROPERTY_ID)
     * - config (config_item('google_property_id'))
     * - get_option('google_google_property_id') (ajuste a chave)
     */
    private function resolvePropertyId(?string $param): string
    {
        $candidates = [
            $param,
            getenv('google_property_id') ?: null,
            function_exists('config_item') ? (config_item('google_property_id') ?: null) : null,
            function_exists('get_option') ? (get_option('google_property_id') ?: null) : null,
        ];

        foreach ($candidates as $cand) {
            $id = trim((string)$cand);
            if ($id !== '' && preg_match('/^\d+$/', $id)) {
                return $id;
            }
        }

        throw new \InvalidArgumentException(
            'GA4 property id inválido ou ausente. Configure um ID numérico (ex.: 398765432).'
        );
    }

    /**
     * Inicializa o BetaAnalyticsDataClient.
     * $credentialsPath ENV (GOOGLE_APPLICATION_CREDENTIALS)
     */
    public function initializeAnalytics(?string $credentialsPath = null) 
    {

        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.
        $path =
            $credentialsPath
            ?: (getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: null)
            ?: APPPATH . 'third_party/google/analytics-sites-mova-cc2e170a822b.json';

        if (!is_string($path) || $path === '' || !file_exists($path)) {
            throw new \InvalidArgumentException("Credenciais inválidas/inexistentes: '{$path}'");
        }

        $this->analytics = new BetaAnalyticsDataClient([
            'credentials' => $path,
        ]);

        return $this->analytics;        
    }     

    /**
     * @param $start_date
     * @param $end_date
     */
    public function createDateRange($start_date, $end_date)
    {
        $start_date = $this->normalizeDate($start_date ?? null, '30daysAgo');
        $end_date   = $this->normalizeDate($end_date ?? null, 'today');
   
        $result = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
        );                   

        return $result;
    }

    /** @return Dimension[] */
    private function buildDimensions(array $names = []): array {
        $out = [];
        foreach ($names as $n) { $out[] = new Dimension(['name' => $n]); }
        return $out;
    }

    /** @return Metric[] */
    private function buildMetrics(array $names = []): array {
        $out = [];
        foreach ($names as $n) { $out[] = new Metric(['name' => $n]); }
        return $out;
    }

    /** cria OrderBy por dimensão (alfanumérico) ou métrica (numérico) */
    private function buildOrderBy(?string $dimension = null, ?string $metric = null, bool $desc = true): array {
        $orderBys = [];
        if ($dimension) {
            $orderBys[] = new OrderBy([
                'desc' => $desc,
                'dimension' => new DimensionOrderBy([
                    'dimension_name' => $dimension,
                    // GA4: ALPHANUMERIC é mais seguro para dimension
                    'order_type' => DimensionOrderBy\OrderType::ALPHANUMERIC
                ])
            ]);
        }
        if ($metric) {
            $orderBys[] = new OrderBy([
                'desc' => $desc,
                'metric' => new MetricOrderBy(['metric_name' => $metric])
            ]);
        }
        return $orderBys;
    }    

    /**
     * requestRealtimeReportData
     *
     * simple helper & wrapper of Google Api Client
     *
     * @param $viewId
     * @param $startDate
     * @param $end_date
     * @param array $metrics
     * @param array $dimensions
     * @param array $sorting ( = [ ['fields']=>['sessions','bounceRate',..] , 'order'=>'descending' ] )
     * @param array $filterMetric ( = [ ['metric_name']=>['sessions'] , 'operator'=>'LESS_THAN' , 'comparison_value'=>'100' ] )
     * @param array $filterDimension ( = [ ['dimension_name']=>['sourceMedium'] , 'operator'=>'EXACT' , 'expressions'=>['my_campaign'] ] )
     * @return mixed
     *
     *
     */
    public function requestRealtimeReportData($data) 
    {    
        $dims = (array)($data['dimensions'] ?? []);
        $mets = (array)($data['metrics'] ?? []);

        $reportingDimensions = $this->buildDimensions($dims);
        $reportingMetrics    = $this->buildMetrics($mets);

        // Realtime: últimos 30 minutos
        $report = $this->analytics->runRealtimeReport([
            'property'      => 'properties/' . $this->property_id,
            'dimensions'    => $reportingDimensions,
            'metrics'       => $reportingMetrics,
            'minuteRanges'  => [
                [ 'start_minutes_ago' => 30, 'end_minutes_ago' => 0 ]
            ],
            // 'limit' => 1000, // se quiser
            // orderBys também existe em realtime se necessário
        ]);

        // Retorna linhas (ou normalize se preferir)
        return self::_returnedReports($report);
        // ou: return self::_normalizeTotalsAndSeries($report, true);        
    }    

    /**
     * requestReportData
     *
     * simple helper & wrapper of Google Api Client
     *
     * @param $viewId
     * @param $startDate
     * @param $end_date
     * @param array $metrics
     * @param array $dimensions
     * @param array $sorting ( = [ ['fields']=>['sessions','bounceRate',..] , 'order'=>'descending' ] )
     * @return mixed
     *
     * @link https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     * @link https://ga-dev-tools.appspot.com/query-explorer/
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/web-php
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/samples
     * @link https://github.com/google/google-api-php-client
     *
     */
    public function requestReportData($data) 
    {       

        $dims = isset($data['dimensions']) ? (array)$data['dimensions'] : [];
        $mets = isset($data['metrics'])    ? (array)$data['metrics']    : [];

        // FIX: sempre inicializar arrays
        $reportingDimensions = $this->buildDimensions($dims); // []
        $reportingMetrics    = $this->buildMetrics($mets);    // []

        // FIX: datas compatíveis GA4 (ou 30daysAgo/today)
        $start_date = $this->normalizeDate($data['start_date'] ?? null, '30daysAgo');
        $end_date   = $this->normalizeDate($data['end_date'] ?? null, 'today');

        // FIX: orderBys opcional (dimension ou metric). Evite acessar $dims[0] sem checar.
        $orderBys = [];
        if (!empty($dims)) {
            $orderBys = $this->buildOrderBy($dims[0], null, true);
        }

        $report = $this->analytics->runReport([
            'property'    => 'properties/' . $this->property_id,
            'dateRanges'  => [ new DateRange(['start_date' => $start_date, 'end_date' => $end_date]) ],
            'dimensions'  => $reportingDimensions,
            'metrics'     => $reportingMetrics,
            'orderBys'    => $orderBys
        ]);

        return self::_returnedReports($report);
    }    

    /**
     * requestFilterReportData
     *
     * simple helper & wrapper of Google Api Client
     *
     * @param $viewId
     * @param $startDate
     * @param $end_date
     * @param array $metrics
     * @param array $dimensions
     * @param array $sorting ( = [ ['fields']=>['sessions','bounceRate',..] , 'order'=>'descending' ] )
     * @param array $filterMetric ( = [ ['metric_name']=>['sessions'] , 'operator'=>'LESS_THAN' , 'comparison_value'=>'100' ] )
     * @param array $filterDimension ( = [ ['dimension_name']=>['sourceMedium'] , 'operator'=>'EXACT' , 'expressions'=>['my_campaign'] ] )
     * @return mixed
     *
     * @link https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     * @link https://ga-dev-tools.appspot.com/query-explorer/
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/web-php
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/samples
     * @link https://github.com/google/google-api-php-client
     *
     */
    public function requestFilterReportData($data) 
    {       
        $dims = (array)($data['dimensions'] ?? []);
        $mets = (array)($data['metrics'] ?? []);
        $filters = (array)($data['dimensions_filter'] ?? []);

        $reportingDimensions = $this->buildDimensions($dims);
        $reportingMetrics    = $this->buildMetrics($mets);

        $start_date = $this->normalizeDate($data['start_date'] ?? null, '30daysAgo');
        $end_date   = $this->normalizeDate($data['end_date'] ?? null, 'today');

        // FIX: monta um OR de begins_with em pageTitle com os valores em $filters
        $expressions = [];
        foreach ($filters as $value) {
            $expressions[] = new FilterExpression([
                'filter' => new Filter([
                    'field_name'   => 'pageTitle',
                    'string_filter'=> new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::BEGINS_WITH,
                        'value'      => (string)$value
                    ])
                ])
            ]);
        }
        $dimensionFilter = null;
        if (!empty($expressions)) {
            $dimensionFilter = new FilterExpression([
                'or_group' => new FilterExpressionList(['expressions' => $expressions])
            ]);
        }

        $orderBys = [];
        if (!empty($dims)) {
            $orderBys = $this->buildOrderBy($dims[0], null, true);
        }

        $report = $this->analytics->runReport([
            'property'        => 'properties/' . $this->property_id,
            'dateRanges'      => [ new DateRange(['start_date' => $start_date, 'end_date' => $end_date]) ],
            'dimensions'      => $reportingDimensions,
            'metrics'         => $reportingMetrics,
            'orderBys'        => $orderBys,
            'dimensionFilter' => $dimensionFilter
        ]);

        return self::_returnedReports($report);
        // ou: return self::_normalizeTotalsAndSeries($report, true);
    }  

    /**
     * @param $property_id
     * @param $dateStart
     * @param $dateEnd
     * @param array $metrics
     * @param array $filterDimension ( = [ ['dimension_name']=>['sourceMedium'] , 'operator'=>'EXACT' , 'expressions'=>['my_campaign'] ] )
     * @return mixed
     * 
     *
     * https://ga-dev-tools.appspot.com/query-explorer/
     *
     */
    public function requestFilterDataMetric($data) 
    {

        $mets    = (array)($data['metrics'] ?? []);
        $filters = (array)($data['dimensions_filter'] ?? []);

        $reportingMetrics = $this->buildMetrics($mets);

        $start_date = $this->normalizeDate($data['start_date'] ?? null, '30daysAgo');
        $end_date   = $this->normalizeDate($data['end_date'] ?? null, 'today');

        // mesmo filtro de pageTitle (OR de begins_with)
        $expressions = [];
        foreach ($filters as $value) {
            $expressions[] = new FilterExpression([
                'filter' => new Filter([
                    'field_name'   => 'pageTitle',
                    'string_filter'=> new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::BEGINS_WITH,
                        'value'      => (string)$value
                    ])
                ])
            ]);
        }
        $dimensionFilter = null;
        if (!empty($expressions)) {
            $dimensionFilter = new FilterExpression([
                'or_group' => new FilterExpressionList(['expressions' => $expressions])
            ]);
        }

        $report = $this->analytics->runReport([
            'property'        => 'properties/' . $this->property_id,
            'dateRanges'      => [ new DateRange(['start_date' => $start_date, 'end_date' => $end_date]) ],
            'metrics'         => $reportingMetrics,
            'dimensionFilter' => $dimensionFilter
        ]);

        return self::_normalizeTotalsAndSeries($report);
    }     

    /**
     * @param $property_id
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     *
     * https://ga-dev-tools.appspot.com/query-explorer/
     *
     */
    public function requestDataMetric($data) 
    {
        $mets = (array)($data['metrics'] ?? []);
        
        // Create the Metrics object.
        $reportingMetrics = $this->buildMetrics($mets);        
    

        $start_date = $this->normalizeDate($data['start_date'] ?? null, '30daysAgo');
        $end_date   = $this->normalizeDate($data['end_date'] ?? null, 'today');   


        $report = $this->analytics->runReport([
            'property'   => 'properties/' . $this->property_id,
            'dateRanges' => [ new DateRange(['start_date' => $start_date, 'end_date' => $end_date]) ],
            'metrics'    => $reportingMetrics,
        ]);

         return self::_normalizeTotalsAndSeries($report);
    }    
    
    
    /**
     * private _returnedReports function.
     * This function takes in the $data object and reorganizes its structure.
     * The new structure takes the form of:
     * {
     *    'totals': {
     *        'metricItem' => metricValue,
     *        'metricItem' => metricValue,
     *        ...
     *     },
     *     'rows': [
     *         {
     *             'dimensions' => {
     *             'dimensionItem' => dimensionValue,
     *             'dimensionItem' => dimensionValue,
     *             ...
     *             },
     *             'metrics' => {
     *             'metricItem' => metricValue,
     *             'metricItem' => metricValue,
     *             ...
     *             }
     *         }, ...
     *     ]
     * }
     * @param  $data
     * @return array
     */
    private static function _returnedReports(RunReportResponse $data): array
    {

        $rows = [];

        /** $metricHeader */
        $metricHeaders = [];
        foreach ($data->getMetricHeaders() as $mh) {
            $metricHeaders[] = $mh->getName();
        }    
        
        /** $dimensionHeader */
        $dimensionHeaders = [];
        foreach ($data->getDimensionHeaders() as $dh) {
            $dimensionHeaders[] = $dh->getName();
        }


        /** $row */
        foreach ($data->getRows() as $row) {
            
            // {name,value}
            $dimValues = $row->getDimensionValues();
            $dimArr = [];
            foreach ($dimensionHeaders as $i => $name) {
                $val = $dimValues[$i]->getValue() ?? '';
                $item = ['name' => $name, 'value' => $val];

                // extras opcionais que você já usava
                if ($name === 'country') {
                    $item['flags'] = strtolower(preg_replace('/\s+/', '-', $val));
                }
                if ($name === 'date') {
                    // GA4 date vem como YYYYMMDD; normaliza para YYYY-MM-DD
                    $ts = strtotime($val);
                    $item['date'] = $ts ? date('Y-m-d', $ts) : $val;
                }

                $dimArr[] = $item;
            }
             
            // {name,value,formatting}
            $metValues = $row->getMetricValues();
            $metArr = [];
            foreach ($metricHeaders as $i => $name) {
                $raw = $metValues[$i]->getValue();
                $num = is_numeric($raw) ? (float)$raw : 0;
                $metArr[] = [
                    'name' => $name,
                    'value' => $num,
                    'formatting' => function_exists('bd_nice_number') ? bd_nice_number($num) : (string)$num
                ];
            }            
            
            $rows[] = [
                'dimensions' => $dimArr,
                'metrics' => $metArr
            ];     
        }

        return $rows;
    }    
    
    /**
     * Normaliza o RunReportResponse do GA4 para { totals: {metricName: value}, series: [] }
     * - Soma a PRIMEIRA métrica de cada linha em "totals"
     * - Se $useFirstDimension for true e existir dimensão, usa o valor da 1ª dimensão como "name" de cada ponto.
     * - Caso contrário, retorna uma série única com o total agregado.
     */
    private static function _normalizeTotalsAndSeries(RunReportResponse $data, bool $useFirstDimension = false): array
    {
        // nomes das métricas na ordem
        $metricHeaders = [];
        foreach ($data->getMetricHeaders() as $mh) {
            $metricHeaders[] = $mh->getName();
        }

        // inicia acumulador com 0 para cada métrica
        $totals = [];
        foreach ($metricHeaders as $name) {
            $totals[$name] = 0;
        }

        // soma valores de todas as linhas
        foreach ($data->getRows() as $row) {
            $mv = $row->getMetricValues();
            foreach ($mv as $i => $metricValue) {
                $totals[$metricHeaders[$i]] += (int)$metricValue->getValue();
            }
        }

        return ['totals' => $totals, 'series' => []];
    }

    private function setReturnedReports(array $returnedReports): void
    {
        $this->returnedReports = $returnedReports;
    }    

    /**
     * Normaliza datas para o que a GA4 Data API aceita.
     * - aceita null/''  → usa $fallback (ex.: '30daysAgo' ou 'today')
     * - aceita keywords da GA4: 'today', 'yesterday', 'NdaysAgo'
     * - aceita timestamp numérico (segundos) → 'YYYY-MM-DD'
     * - aceita strings parseáveis (ex.: '2025-10-05', '2025/10/05', '05-10-2025') → 'YYYY-MM-DD'
     * - se não reconhecer, retorna $fallback
     */
    private function normalizeDate(?string $d, string $fallback): string
    {
        if ($d === null || $d === '') {
            return $fallback;
        }

        $d = trim((string)$d);

        // keywords válidas na GA4
        if ($d === 'today' || $d === 'yesterday' || preg_match('/^\d+daysAgo$/', $d)) {
            return $d;
        }

        // timestamp (apenas dígitos)
        if (ctype_digit($d)) {
            return date('Y-m-d', (int)$d);
        }

        // já está no formato YYYY-MM-DD?
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }

        // tenta parsear qualquer data válida
        $ts = strtotime($d);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        // fallback final
        return $fallback;
    }    
}