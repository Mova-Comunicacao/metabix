<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifica o uso mensal da IA por cliente e retorna:
 *  - TRUE  → cliente ainda pode usar
 *  - FALSE → limite atingido
 *
 * Também pode retornar detalhes para logs.
 */
function ai_client_can_use($user_id)
{
    $CI =& get_instance();
    $CI->load->config('ai');

    $config = $CI->config->item('ai');

    $limit = $config['max_monthly_requests_per_client'] ?? 0;

    // Se limite = 0 → ilimitado
    if ((int)$limit === 0) {
        return [
            'allowed' => true,
            'used'    => 0,
            'limit'   => 0,
        ];
    }

    // Intervalo do mês
    $monthStart = date('Y-m-01 00:00:00');
    $monthEnd   = date('Y-m-t 23:59:59');

    // Conta apenas requisições com status "success"
    $CI->db->from('assistant_log');
    $CI->db->where('user_id', $user_id);
    $CI->db->where('status', 'success');
    $CI->db->where('dateadded >=', $monthStart);
    $CI->db->where('dateadded <=', $monthEnd);

    $used = (int)$CI->db->count_all_results();

    return [
        'allowed' => $used < $limit,
        'used'    => $used,
        'limit'   => $limit,
    ];
}
/**
 * Verifica o uso mensal GLOBAL da IA e retorna:
 *  - allowed: true/false
 *  - used:    quantas requisições já foram usadas no mês
 *  - limit:   limite configurado
 *
 * Usa a tabela assistant_log (status = 'success').
 */
function ai_can_use()
{
    $CI =& get_instance();
    $CI->load->config('ai');

    $config = $CI->config->item('ai');
    $limit  = $config['max_monthly_requests'] ?? 0;

    // Se limite = 0
    if ((int)$limit === 0) {
        return [
            'allowed' => true,
            'used'    => 0,
            'limit'   => 0,
        ];
    }

    // Intervalo do mês atual
    $monthStart = date('Y-m-01 00:00:00');
    $monthEnd   = date('Y-m-t 23:59:59');

    // Conta apenas requisições com status "success"
    $CI->db->from('assistant_log');
    $CI->db->where('status', 'success');
    $CI->db->where('dateadded >=', $monthStart);
    $CI->db->where('dateadded <=', $monthEnd);

    $used = (int)$CI->db->count_all_results();

    return [
        'allowed' => $used < $limit,
        'used'    => $used,
        'limit'   => $limit,
    ];
}

