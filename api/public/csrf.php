<?php
// SolusiPaymentManagement Public API - Get CSRF Token

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    errorResponse('Method not allowed', 405);
}

// Return CSRF token
successResponse([
    'csrf_token' => getCsrfToken()
]);
