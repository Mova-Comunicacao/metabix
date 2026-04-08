<?php
defined('BASEPATH') or exit('No direct script access allowed');

function cors() {
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
}