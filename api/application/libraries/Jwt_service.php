<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class Jwt_service
{
    /** @var CI_Controller */
    protected $CI;

    protected string $secret;
    protected string $issuer;
    protected string $audience;
    protected int    $ttl;
    protected int    $leeway;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('jwt');

        $this->secret   = (string) $this->CI->config->item('jwt_secret');
        $this->issuer   = (string) $this->CI->config->item('jwt_issuer');
        $this->audience = (string) $this->CI->config->item('jwt_audience');
        $this->ttl      = (int)    $this->CI->config->item('jwt_ttl');
        $this->leeway   = (int)    $this->CI->config->item('jwt_leeway');

        // tolerância global (para clocks diferentes)
        JWT::$leeway = $this->leeway;
    }

    /**
     * Gera um JWT (HS256)
     * @param int|string $subject  (id do usuário)
     * @param array $extra         (claims adicionais, ex.: ['role'=>'admin'])
     * @param int|null $ttl        (override da validade em segundos)
     * @return array [jwt, expTs]
     */
    public function makeToken($subject, array $extra = [], ?int $ttl = null): array
    {
        $now   = time();
        $exp   = $now + ($ttl ?? $this->ttl);
        $jti   = bin2hex(random_bytes(12));

        $payload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now - 1,
            'exp' => $exp,
            'jti' => $jti,
            'sub' => (string)$subject,
        ], $extra);

        $jwt = JWT::encode($payload, $this->secret, 'HS256');
        return [$jwt, $exp];
    }

    /**
     * Decodifica e valida um JWT. Retorna o payload como objeto.
     */
    public function decode(string $jwt)
    {
        return JWT::decode($jwt, new Key($this->secret, 'HS256'));
    }

    /**
     * Lê o header Authorization: Bearer ... e retorna o payload (ou lança 401)
     */
    public function requirePayloadFromBearer()
    {
        $authHeader = $this->CI->input->get_request_header('Authorization', true);
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            $this->unauthorized('Missing bearer token');
        }
        $token = trim(substr($authHeader, 7));

        try {
            return $this->decode($token);
        } catch (ExpiredException $e) {
            $this->unauthorized('Token expired');
        } catch (SignatureInvalidException $e) {
            $this->unauthorized('Invalid signature');
        } catch (BeforeValidException $e) {
            $this->unauthorized('Token not active yet');
        } catch (Throwable $e) {
            $this->unauthorized('Invalid token');
        }
    }

    /**
     * Helper: retorna 401 JSON e encerra
     */
    protected function unauthorized(string $message)
    {
        $this->CI->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode(['type'=>'error','message'=>$message]));
        exit;
    }
}
