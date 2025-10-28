<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $gateways = $db->fetchAll("SELECT * FROM payment_gateways ORDER BY name ASC");
    successResponse(['data' => $gateways]);
}
