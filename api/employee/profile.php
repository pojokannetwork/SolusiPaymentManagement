<?php
// SolusiPaymentManagement Employee API

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Check authentication
$guard = RouterGuard::getInstance();
$guard->requireAuth();

$user = getCurrentUser();
if ($user['role'] !== 'employee') {
    errorResponse('Access denied', 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$pathInfo = $_SERVER['PATH_INFO'] ?? '/';

switch ($method) {
    case 'GET':
        if ($pathInfo === '/profile') {
            // Get employee profile
            successResponse([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'nama' => $user['nama'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            errorResponse('Endpoint not found', 404);
        }
        break;
        
    default:
        errorResponse('Method not allowed', 405);
}