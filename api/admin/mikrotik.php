<?php
// SolusiPaymentManagement Admin API - MikroTik Management

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    switch ($action) {
        case 'routers':
            handleListRouters();
            break;
        case 'dashboard':
            handleRouterSummary();
            break;
        case 'pppoe_users':
            handleGetPppUsers();
            break;
        case 'pppoe_profiles':
            handleGetPppProfiles();
            break;
        case 'hotspot_users':
            handleGetHotspotUsers();
            break;
        case 'hotspot_profiles':
            handleGetHotspotProfiles();
            break;
        case 'hotspot_servers':
            handleGetHotspotServers();
            break;
        case 'test':
            handleTestRouter();
            break;
        default:
            errorResponse('Invalid action', 400);
    }
    exit;
}

if ($method === 'POST') {
    switch ($action) {
        case 'test':
            handleTestRouter();
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
        case 'pppoe_create':
            handleCreatePppUser();
            break;
        case 'pppoe_update':
            handleUpdatePppUser();
            break;
        case 'pppoe_delete':
            handleDeletePppUser();
            break;
        case 'pppoe_disconnect':
            handleDisconnectPppUser();
            break;
        case 'pppoe_profile_save':
            handleSavePppProfile();
            break;
        case 'pppoe_profile_delete':
            handleDeletePppProfile();
            break;
        case 'hotspot_create':
            handleAddHotspotUser();
            break;
        case 'hotspot_update':
            handleUpdateHotspotUser();
            break;
        case 'hotspot_delete':
            handleDeleteHotspotUser();
            break;
        case 'hotspot_disconnect':
            handleDisconnectHotspotUser();
            break;
        case 'hotspot_profile_save':
            handleSaveHotspotProfile();
            break;
        case 'hotspot_profile_delete':
            handleDeleteHotspotProfile();
            break;
        case 'hotspot_generate_voucher':
            handleGenerateHotspotVouchers();
            break;
        default:
            errorResponse('Invalid action', 400);
    }
    exit;
}

errorResponse('Method not allowed', 405);

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

    $name = trim((string) $input['name']);
    $host = trim((string) $input['host']);
    $username = trim((string) $input['username']);
    $comment = isset($input['comment']) ? trim((string) $input['comment']) : '';

    // Check if name already exists
    $existing = $db->fetchOne("SELECT id FROM mikrotik_routers WHERE name = ?", [$name]);
    if ($existing) {
        errorResponse('Router name already exists', 400);
    }

    // Insert router
    $routerId = $db->insert(
        "INSERT INTO mikrotik_routers (name, host, port, username, password_enc, use_tls, comment, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $name,
            $host,
            (int) ($input['port'] ?? 8728),
            $username,
            encryptData($input['password']),
            (int) ($input['use_tls'] ?? 0),
            $comment
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

    // Normalize incoming values
    $normalized = $input;
    foreach (['name', 'host', 'username', 'comment'] as $field) {
        if (array_key_exists($field, $normalized) && $normalized[$field] !== null) {
            $normalized[$field] = trim((string) $normalized[$field]);
        }
    }

    // Prepare update data
    $updates = [];
    $params = [];

    $fields = ['name', 'host', 'port', 'username', 'use_tls', 'comment'];
    foreach ($fields as $field) {
        if (array_key_exists($field, $normalized)) {
            $updates[] = "{$field} = ?";
            if ($field === 'port' || $field === 'use_tls') {
                $params[] = (int) $normalized[$field];
            } else {
                $params[] = $normalized[$field];
            }
        }
    }

    // Handle password update
    if (!empty($normalized['password'])) {
        $updates[] = "password_enc = ?";
        $params[] = encryptData($normalized['password']);
    }

    if (empty($updates)) {
        errorResponse('No fields to update', 400);
    }

    // Check name uniqueness if being updated
    if (isset($normalized['name'])) {
        $newName = $normalized['name'];
        if ($newName !== $existing['name']) {
            $duplicate = $db->fetchOne("SELECT id FROM mikrotik_routers WHERE name = ? AND id != ?", [$newName, $routerId]);
            if ($duplicate) {
                errorResponse('Router name already exists', 400);
            }
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
    $logger->logDataChange('mikrotik_routers', 'update', $routerId, $existing, array_merge($existing, $normalized));

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

function handleRouterSummary() {
    try {
        $summary = MikrotikService::getRouterSummary();
        $pppUsers = MikrotikService::getPppUsers(false);
        $active = array_filter($pppUsers, fn($user) => $user['active']);
        $inactive = array_filter($pppUsers, fn($user) => !$user['active']);

        successResponse([
            'summary' => $summary,
            'pppoe' => [
                'total' => count($pppUsers),
                'active' => count($active),
                'inactive' => count($inactive)
            ],
            'active_connections' => array_values(array_map(function ($user) {
                $details = $user['active_details'] ?? [];
                return [
                    'name' => $user['name'] ?? '',
                    'address' => $details['address'] ?? '',
                    'uptime' => $details['uptime'] ?? '',
                    'service' => $details['service'] ?? ($user['service'] ?? ''),
                    'profile' => $user['profile'] ?? ''
                ];
            }, array_slice($active, 0, 25)))
        ]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleGetPppUsers() {
    try {
        $users = MikrotikService::getPppUsers();
        successResponse(['users' => $users]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleCreatePppUser() {
    $input = getJsonInput();
    try {
        $payload = [
            'name' => trim($input['username'] ?? ''),
            'password' => (string) ($input['password'] ?? ''),
            'profile' => $input['profile'] ?? 'default',
            'service' => $input['service'] ?? 'pppoe',
        ];
        MikrotikService::createPppUser($payload);
        successResponse(['message' => 'User PPPoE berhasil dibuat']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleUpdatePppUser() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? $input['username'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier user diperlukan', 400);
    }

    $updates = $input['updates'] ?? [];
    $updates = formatMikrotikAttributes($updates);

    try {
        MikrotikService::updatePppUser($identifier, $updates);
        successResponse(['message' => 'User PPPoE berhasil diperbarui']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDeletePppUser() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier user diperlukan', 400);
    }

    try {
        MikrotikService::deletePppUser($identifier);
        successResponse(['message' => 'User PPPoE berhasil dihapus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDisconnectPppUser() {
    $input = getJsonInput();
    $username = $input['username'] ?? null;
    if (!$username) {
        errorResponse('Username diperlukan', 400);
    }

    try {
        MikrotikService::disconnectPppSession($username);
        successResponse(['message' => 'Koneksi PPPoE diputus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleGetPppProfiles() {
    try {
        $profiles = MikrotikService::getPppProfiles();
        successResponse(['profiles' => $profiles]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleSavePppProfile() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    $payload = $input['profile'] ?? [];

    if (empty($payload['name']) && !$identifier) {
        errorResponse('Nama profil diperlukan', 400);
    }

    $payload = formatMikrotikAttributes($payload);

    try {
        MikrotikService::savePppProfile($payload, $identifier);
        successResponse(['message' => 'Profil PPPoE tersimpan']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDeletePppProfile() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier profil diperlukan', 400);
    }

    try {
        MikrotikService::deletePppProfile($identifier);
        successResponse(['message' => 'Profil PPPoE dihapus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleGetHotspotUsers() {
    try {
        $users = MikrotikService::getHotspotUsers();
        successResponse(['users' => $users]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleAddHotspotUser() {
    $input = getJsonInput();
    $payload = formatMikrotikAttributes($input);

    if (empty($payload['name']) || empty($payload['password'])) {
        errorResponse('Nama dan password wajib diisi', 400);
    }

    try {
        MikrotikService::addHotspotUser($payload);
        successResponse(['message' => 'User hotspot ditambahkan']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleUpdateHotspotUser() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    $payload = formatMikrotikAttributes($input['updates'] ?? []);

    if (!$identifier) {
        errorResponse('Identifier user hotspot diperlukan', 400);
    }

    try {
        MikrotikService::updateHotspotUser($identifier, $payload);
        successResponse(['message' => 'User hotspot diperbarui']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDeleteHotspotUser() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier user hotspot diperlukan', 400);
    }

    try {
        MikrotikService::deleteHotspotUser($identifier);
        successResponse(['message' => 'User hotspot dihapus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDisconnectHotspotUser() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier user hotspot diperlukan', 400);
    }

    try {
        MikrotikService::disconnectHotspotUser($identifier);
        successResponse(['message' => 'User hotspot diputus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleGetHotspotProfiles() {
    try {
        $profiles = MikrotikService::getHotspotProfiles();
        successResponse(['profiles' => $profiles]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleSaveHotspotProfile() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    $payload = formatMikrotikAttributes($input['profile'] ?? []);

    if (empty($payload['name']) && !$identifier) {
        errorResponse('Nama profil diperlukan', 400);
    }

    try {
        MikrotikService::saveHotspotProfile($payload, $identifier);
        successResponse(['message' => 'Profil hotspot tersimpan']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleDeleteHotspotProfile() {
    $input = getJsonInput();
    $identifier = $input['identifier'] ?? null;
    if (!$identifier) {
        errorResponse('Identifier profil hotspot diperlukan', 400);
    }

    try {
        MikrotikService::deleteHotspotProfile($identifier);
        successResponse(['message' => 'Profil hotspot dihapus']);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function handleGetHotspotServers() {
    try {
        $servers = MikrotikService::getHotspotServers();
        successResponse(['servers' => $servers]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 500);
    }
}

function handleGenerateHotspotVouchers() {
    $input = getJsonInput();
    $count = (int) ($input['count'] ?? 0);
    if ($count <= 0) {
        errorResponse('Jumlah voucher tidak valid', 400);
    }

    $options = [
        'prefix' => $input['prefix'] ?? 'VCHR',
        'length' => (int) ($input['length'] ?? 6),
        'profile' => $input['profile'] ?? 'default',
        'server' => $input['server'] ?? null,
        'comment' => $input['comment'] ?? '',
    ];

    try {
        $vouchers = MikrotikService::generateHotspotVouchers($count, $options);
        successResponse(['vouchers' => $vouchers]);
    } catch (Throwable $e) {
        errorResponse($e->getMessage(), 400);
    }
}

function getJsonInput(): array
{
    $stream = file_get_contents('php://input');
    $data = json_decode($stream, true);
    if (is_array($data)) {
        return $data;
    }
    return $_POST ?? [];
}

function formatMikrotikAttributes(array $payload): array
{
    $formatted = [];
    foreach ($payload as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $routerKey = str_replace('_', '-', $key);
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }
            $formatted[$routerKey] = $trimmed;
        } else {
            $formatted[$routerKey] = $value;
        }
    }
    return $formatted;
}
