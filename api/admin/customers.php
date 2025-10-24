<?php
// SolusiPaymentManagement Admin API - Customers Management

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            handleListCustomers();
        } else {
            errorResponse('Invalid action', 400);
        }
        break;

    case 'POST':
        if ($action === 'create') {
            handleCreateCustomer();
        } elseif ($action === 'update') {
            handleUpdateCustomer();
        } elseif ($action === 'delete') {
            handleDeleteCustomer();
        } elseif ($action === 'isolir') {
            handleIsolirCustomer();
        } elseif ($action === 'activate') {
            handleActivateCustomer();
        } else {
            errorResponse('Invalid action', 400);
        }
        break;

    default:
        errorResponse('Method not allowed', 405);
}

function handleListCustomers() {
    global $db;

    $page = (int) ($_GET['page'] ?? 1);
    $limit = (int) ($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;

    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $map = $_GET['map'] ?? 'false';

    $where = [];
    $params = [];

    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    if ($search) {
        $where[] = "(nama LIKE ? OR email LIKE ? OR kode_pelanggan LIKE ? OR pppoe_user LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get total count
    $total = $db->fetchOne("SELECT COUNT(*) as count FROM pelanggan {$whereClause}", $params)['count'];

    // Get customers
    $selectFields = $map === 'true' ?
        "id, kode_pelanggan, nama, lat, lon, status" :
        "id, kode_pelanggan, nama, email, telp, alamat, paket, status, pppoe_user, router_id, created_at";

    $customers = $db->fetchAll(
        "SELECT {$selectFields} FROM pelanggan {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );

    successResponse([
        'customers' => $customers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handleCreateCustomer() {
    global $db;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $required = ['nama', 'email', 'paket'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '{$field}' is required", 400);
        }
    }

    // Generate customer code
    $kodePelanggan = generateCustomerCode();

    // Prepare data
    $data = [
        'kode_pelanggan' => $kodePelanggan,
        'nama' => sanitizeInput($input['nama']),
        'email' => sanitizeInput($input['email']),
        'telp' => sanitizeInput($input['telp'] ?? ''),
        'alamat' => sanitizeInput($input['alamat'] ?? ''),
        'paket' => sanitizeInput($input['paket']),
        'pppoe_user' => sanitizeInput($input['pppoe_user'] ?? ''),
        'pppoe_pass_enc' => !empty($input['pppoe_pass']) ? encryptData($input['pppoe_pass']) : null,
        'router_id' => $input['router_id'] ?? null,
        'profile_aktif' => sanitizeInput($input['profile_aktif'] ?? 'default'),
        'profile_isolir' => sanitizeInput($input['profile_isolir'] ?? 'ISOLIR'),
        'ip_static' => sanitizeInput($input['ip_static'] ?? ''),
        'status' => 'active'
    ];

    // Insert customer
    $customerId = $db->insert(
        "INSERT INTO pelanggan (kode_pelanggan, nama, email, telp, alamat, paket, pppoe_user, pppoe_pass_enc, router_id, profile_aktif, profile_isolir, ip_static, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        array_values($data)
    );

    // Provision if PPPoE credentials provided
    if (!empty($data['pppoe_user']) && !empty($input['pppoe_pass'])) {
        provisionCustomer($customerId, $data);
    }

    // Log activity
    global $logger;
    $logger->logDataChange('pelanggan', 'create', $customerId, null, $data);

    successResponse([
        'customer_id' => $customerId,
        'kode_pelanggan' => $kodePelanggan,
        'message' => 'Customer created successfully'
    ], 'Customer created successfully');
}

function handleUpdateCustomer() {
    global $db;

    $customerId = $_GET['id'] ?? 0;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Get existing customer
    $existing = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$existing) {
        errorResponse('Customer not found', 404);
    }

    // Prepare update data
    $updates = [];
    $params = [];

    $fields = ['nama', 'email', 'telp', 'alamat', 'paket', 'status', 'pppoe_user', 'router_id', 'profile_aktif', 'profile_isolir', 'ip_static'];
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = sanitizeInput($input[$field]);
        }
    }

    // Handle password update
    if (!empty($input['pppoe_pass'])) {
        $updates[] = "pppoe_pass_enc = ?";
        $params[] = encryptData($input['pppoe_pass']);
    }

    if (empty($updates)) {
        errorResponse('No fields to update', 400);
    }

    $params[] = $customerId;

    // Update customer
    $db->execute(
        "UPDATE pelanggan SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?",
        $params
    );

    // Re-provision if PPPoE credentials changed
    $newData = array_merge($existing, $input);
    if (!empty($newData['pppoe_user']) && (!empty($input['pppoe_pass']) || !empty($newData['pppoe_pass_enc']))) {
        provisionCustomer($customerId, $newData);
    }

    // Log activity
    global $logger;
    $logger->logDataChange('pelanggan', 'update', $customerId, $existing, $newData);

    successResponse(['message' => 'Customer updated successfully']);
}

function handleDeleteCustomer() {
    global $db;

    $customerId = $_GET['id'] ?? 0;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }

    // Check if customer has unpaid invoices
    $unpaidInvoices = $db->fetchOne(
        "SELECT COUNT(*) as count FROM faktur WHERE pelanggan_id = ? AND status IN ('draft', 'sent')",
        [$customerId]
    );

    if ($unpaidInvoices['count'] > 0) {
        errorResponse('Cannot delete customer with unpaid invoices', 400);
    }

    // Get customer data before deletion
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);

    // Delete customer
    $db->execute("DELETE FROM pelanggan WHERE id = ?", [$customerId]);

    // Log activity
    global $logger;
    $logger->logDataChange('pelanggan', 'delete', $customerId, $customer, null);

    successResponse(['message' => 'Customer deleted successfully']);
}

function handleIsolirCustomer() {
    global $db;

    $customerId = $_GET['id'] ?? 0;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }

    // Get customer
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) {
        errorResponse('Customer not found', 404);
    }

    // Update status
    $db->execute(
        "UPDATE pelanggan SET status = 'isolir', updated_at = NOW() WHERE id = ?",
        [$customerId]
    );

    // Provision isolation
    provisionCustomer($customerId, array_merge($customer, ['status' => 'isolir']));

    // Log activity
    global $logger;
    $logger->log('customer_isolir', ['customer_id' => $customerId]);

    successResponse(['message' => 'Customer isolated successfully']);
}

function handleActivateCustomer() {
    global $db;

    $customerId = $_GET['id'] ?? 0;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }

    // Get customer
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) {
        errorResponse('Customer not found', 404);
    }

    // Update status
    $db->execute(
        "UPDATE pelanggan SET status = 'active', updated_at = NOW() WHERE id = ?",
        [$customerId]
    );

    // Provision activation
    provisionCustomer($customerId, array_merge($customer, ['status' => 'active']));

    // Log activity
    global $logger;
    $logger->log('customer_activate', ['customer_id' => $customerId]);

    successResponse(['message' => 'Customer activated successfully']);
}

function provisionCustomer($customerId, $customerData) {
    $sourceOfTruth = getSetting('source_of_truth', 'radius');

    if ($sourceOfTruth === 'radius') {
        $radius = new RadiusSqlCoa();
        $profile = $customerData['status'] === 'isolir' ? $customerData['profile_isolir'] : $customerData['profile_aktif'];
        $result = $radius->provisionCustomer([
            'pppoe_user' => $customerData['pppoe_user'],
            'pppoe_pass' => decryptData($customerData['pppoe_pass_enc']),
            'profile' => $profile,
            'rate_limit' => $customerData['paket'] // Could be mapped to actual rate limit
        ]);
    } else {
        // MikroTik provisioning
        if (!empty($customerData['router_id'])) {
            $mtk = MtkFactory::createFromRouter($customerData['router_id']);
            $profile = $customerData['status'] === 'isolir' ? $customerData['profile_isolir'] : $customerData['profile_aktif'];
            $result = $mtk->provisionCustomer([
                'pppoe_user' => $customerData['pppoe_user'],
                'profile' => $profile,
                'enabled' => $customerData['status'] === 'active'
            ]);
        }
    }

    // Log provisioning result
    global $logger;
    $logger->log('customer_provision', [
        'customer_id' => $customerId,
        'source' => $sourceOfTruth,
        'result' => $result ?? 'no_provisioning_needed'
    ]);
}
