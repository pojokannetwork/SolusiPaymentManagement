<?php
// SolusiPaymentManagement Admin API - MikroTik Management

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'test';

if ($method !== 'POST') {
    errorResponse('Method not allowed', 405);
}

switch ($action) {
    case 'test':
        handleTestRouter();
        break;
    case 'routers':
        handleListRouters();
        break;
    case 'create_router':
        handleCreateRouter();
        break;
    case 'update_router':
        handleUpdateRouter();
        break;
    case 'delete_router':
        handleDeleteRouter();
        break;
    default:
        errorResponse('Invalid action', 400);
}

function handleTestRouter() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $routerId = $input['router_id'] ?? 0;
    if (!$routerId) {
        errorResponse('Router ID is required', 400);
    }

    $result = MtkFactory::testRouter($routerId);

    if ($result['success']) {
        successResponse([
            'message' => 'Router connection successful',
            'identity' => $result['identity']
        ]);
    } else {
        errorResponse('Router connection failed: ' . $result['error'], 400);
    }
}

function handleListRouters() {
    global $db;

    $routers = $db->fetchAll(
        "SELECT id, name, host, port, username, use_tls, comment, created_at, updated_at
         FROM mikrotik_routers
         ORDER BY name"
    );

    // Mask sensitive data
    foreach ($routers as &$router) {
        unset($router['password_enc']);
    }

    successResponse(['routers' => $routers]);
}

function handleCreateRouter() {
    global $db;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $required = ['name', 'host', 'username', 'password'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '{$field}' is required", 400);
        }
    }

    // Check if name already exists
    $existing = $db->fetchOne("SELECT id FROM mikrotik_routers WHERE name = ?", [$input['name']]);
    if ($existing) {
        errorResponse('Router name already exists', 400);
    }

    // Insert router
    $routerId = $db->insert(
        "INSERT INTO mikrotik_routers (name, host, port, username, password_enc, use_tls, comment, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            sanitizeInput($input['name']),
            sanitizeInput($input['host']),
            (int) ($input['port'] ?? 8728),
            sanitizeInput($input['username']),
            encryptData($input['password']),
            (int) ($input['use_tls'] ?? 0),
            sanitizeInput($input['comment'] ?? '')
        ]
    );

    // Log activity
    global $logger;
    $logger->logDataChange('mikrotik_routers', 'create', $routerId);

    successResponse([
        'router_id' => $routerId,
        'message' => 'Router created successfully'
    ]);
}

function handleUpdateRouter() {
    global $db;

    $routerId = $_GET['id'] ?? 0;
    if (!$routerId) {
        errorResponse('Router ID is required', 400);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Get existing router
    $existing = $db->fetchOne("SELECT * FROM mikrotik_routers WHERE id = ?", [$routerId]);
    if (!$existing) {
        errorResponse('Router not found', 404);
    }

    // Prepare update data
    $updates = [];
    $params = [];

    $fields = ['name', 'host', 'port', 'username', 'use_tls', 'comment'];
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = $field === 'port' || $field === 'use_tls' ? (int) $input[$field] : sanitizeInput($input[$field]);
        }
    }

    // Handle password update
    if (!empty($input['password'])) {
        $updates[] = "password_enc = ?";
        $params[] = encryptData($input['password']);
    }

    if (empty($updates)) {
        errorResponse('No fields to update', 400);
    }

    // Check name uniqueness if being updated
    if (isset($input['name']) && $input['name'] !== $existing['name']) {
        $duplicate = $db->fetchOne("SELECT id FROM mikrotik_routers WHERE name = ? AND id != ?", [$input['name'], $routerId]);
        if ($duplicate) {
            errorResponse('Router name already exists', 400);
        }
    }

    $params[] = $routerId;

    // Update router
    $db->execute(
        "UPDATE mikrotik_routers SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?",
        $params
    );

    // Log activity
    global $logger;
    $logger->logDataChange('mikrotik_routers', 'update', $routerId, $existing, array_merge($existing, $input));

    successResponse(['message' => 'Router updated successfully']);
}

function handleDeleteRouter() {
    global $db;

    $routerId = $_GET['id'] ?? 0;
    if (!$routerId) {
        errorResponse('Router ID is required', 400);
    }

    // Check if router is used by customers
    $customers = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pelanggan WHERE router_id = ?",
        [$routerId]
    );

    if ($customers['count'] > 0) {
        errorResponse('Cannot delete router that is assigned to customers', 400);
    }

    // Get router data before deletion
    $router = $db->fetchOne("SELECT * FROM mikrotik_routers WHERE id = ?", [$routerId]);

    // Delete router
    $db->execute("DELETE FROM mikrotik_routers WHERE id = ?", [$routerId]);

    // Log activity
    global $logger;
    $logger->logDataChange('mikrotik_routers', 'delete', $routerId, $router, null);

    successResponse(['message' => 'Router deleted successfully']);
}
