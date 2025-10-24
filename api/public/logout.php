<?php
// SolusiPaymentManagement Public API - Logout

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// Perform logout
$guard->logout();

// Return success response
successResponse(['message' => 'Logged out successfully']);
