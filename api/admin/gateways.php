<?php
// API Endpoint for Payment Gateways Management

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Permission check
$guard->requirePermission('admin.payment_gateways');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleListGateways();
        break;
    case 'save':
        handleSaveGateway();
        break;
    case 'delete':
        handleDeleteGateway();
        break;
    case 'test':
        handleTestGateway();
        break;
    default:
        // Get single gateway
        if (isset($_GET['id'])) {
            handleGetGateway((int)$_GET['id']);
        } else {
            errorResponse('Invalid action', 400);
        }
}

function handleListGateways() {
    global $db;
    
    $gateways = $db->fetchAll(
        "SELECT id, nama, provider, is_active, created_at, updated_at 
         FROM payment_gateways 
         ORDER BY nama ASC"
    );
    
    successResponse(['gateways' => $gateways]);
}

function handleGetGateway($id) {
    global $db;
    
    $gateway = $db->fetchOne(
        "SELECT * FROM payment_gateways WHERE id = ?",
        [$id]
    );
    
    if (!$gateway) {
        errorResponse('Gateway not found', 404);
    }
    
    successResponse(['gateway' => $gateway]);
}

function handleSaveGateway() {
    global $db;
    
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $provider = trim($_POST['provider'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $configJson = $_POST['config_json'] ?? '{}';
    
    // Validate input
    if (empty($nama)) {
        errorResponse('Gateway name is required', 400);
    }
    
    if (empty($provider)) {
        errorResponse('Provider is required', 400);
    }
    
    // Validate JSON config
    $config = json_decode($configJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        errorResponse('Invalid configuration JSON', 400);
    }
    
    // Validate provider-specific config
    $validationResult = validateProviderConfig($provider, $config);
    if (!$validationResult['valid']) {
        errorResponse($validationResult['error'], 400);
    }
    
    try {
        if ($id > 0) {
            // Update existing gateway
            $db->execute(
                "UPDATE payment_gateways 
                 SET nama = ?, provider = ?, is_active = ?, config_json = ?, updated_at = datetime('now')
                 WHERE id = ?",
                [$nama, $provider, $isActive, $configJson, $id]
            );
            
            $message = 'Gateway updated successfully';
        } else {
            // Create new gateway
            $db->execute(
                "INSERT INTO payment_gateways (nama, provider, is_active, config_json)
                 VALUES (?, ?, ?, ?)",
                [$nama, $provider, $isActive, $configJson]
            );
            
            $id = $db->lastInsertId();
            $message = 'Gateway created successfully';
        }
        
        successResponse([
            'message' => $message,
            'gateway_id' => $id
        ]);
        
    } catch (Exception $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function handleDeleteGateway() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        errorResponse('Invalid gateway ID', 400);
    }
    
    // Check if gateway is in use
    $inUse = $db->fetchOne(
        "SELECT COUNT(*) as count FROM transaksi WHERE gateway_id = ?",
        [$id]
    );
    
    if ($inUse['count'] > 0) {
        errorResponse('Cannot delete gateway: it has associated transactions', 400);
    }
    
    try {
        $db->execute("DELETE FROM payment_gateways WHERE id = ?", [$id]);
        successResponse(['message' => 'Gateway deleted successfully']);
    } catch (Exception $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function handleTestGateway() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        errorResponse('Invalid gateway ID', 400);
    }
    
    $gateway = $db->fetchOne("SELECT * FROM payment_gateways WHERE id = ?", [$id]);
    
    if (!$gateway) {
        errorResponse('Gateway not found', 404);
    }
    
    $config = json_decode($gateway['config_json'], true);
    
    // Test gateway connectivity
    $testResult = testGatewayConnection($gateway['provider'], $config);
    
    if ($testResult['success']) {
        successResponse(['message' => 'Gateway test successful']);
    } else {
        errorResponse($testResult['error'] ?? 'Gateway test failed', 400);
    }
}

function validateProviderConfig($provider, $config) {
    switch ($provider) {
        case 'midtrans':
            if (empty($config['server_key'])) {
                return ['valid' => false, 'error' => 'Server Key is required for Midtrans'];
            }
            if (empty($config['client_key'])) {
                return ['valid' => false, 'error' => 'Client Key is required for Midtrans'];
            }
            break;
            
        case 'xendit':
            if (empty($config['secret_key'])) {
                return ['valid' => false, 'error' => 'Secret Key is required for Xendit'];
            }
            break;
            
        case 'tripay':
            if (empty($config['api_key'])) {
                return ['valid' => false, 'error' => 'API Key is required for Tripay'];
            }
            if (empty($config['private_key'])) {
                return ['valid' => false, 'error' => 'Private Key is required for Tripay'];
            }
            if (empty($config['merchant_code'])) {
                return ['valid' => false, 'error' => 'Merchant Code is required for Tripay'];
            }
            break;
            
        case 'duitku':
            if (empty($config['merchant_code'])) {
                return ['valid' => false, 'error' => 'Merchant Code is required for Duitku'];
            }
            if (empty($config['api_key'])) {
                return ['valid' => false, 'error' => 'API Key is required for Duitku'];
            }
            break;
            
        case 'doku':
            if (empty($config['shared_key'])) {
                return ['valid' => false, 'error' => 'Shared Key is required for DOKU'];
            }
            if (empty($config['mall_id'])) {
                return ['valid' => false, 'error' => 'Mall ID is required for DOKU'];
            }
            break;
    }
    
    return ['valid' => true];
}

function testGatewayConnection($provider, $config) {
    switch ($provider) {
        case 'midtrans':
            return testMidtrans($config);
        case 'xendit':
            return testXendit($config);
        case 'tripay':
            return testTripay($config);
        case 'duitku':
            return testDuitku($config);
        case 'doku':
            return testDoku($config);
        default:
            return ['success' => false, 'error' => 'Provider test not implemented'];
    }
}

function testMidtrans($config) {
    $serverKey = $config['server_key'] ?? '';
    $sandbox = $config['sandbox'] ?? true;
    
    $baseUrl = $sandbox ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/ping');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($serverKey . ':'),
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Connection error: ' . $error];
    }
    
    if ($httpCode === 200) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $response];
}

function testXendit($config) {
    $secretKey = $config['secret_key'] ?? '';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($secretKey . ':')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Connection error: ' . $error];
    }
    
    if ($httpCode === 200) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $response];
}

function testTripay($config) {
    $apiKey = $config['api_key'] ?? '';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://tripay.co.id/api/merchant/payment-channel');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Connection error: ' . $error];
    }
    
    if ($httpCode === 200) {
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $response];
}

function testDuitku($config) {
    // Duitku doesn't have a simple ping endpoint, so we'll just validate config
    if (!empty($config['merchant_code']) && !empty($config['api_key'])) {
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'Invalid configuration'];
}

function testDoku($config) {
    // DOKU doesn't have a simple ping endpoint, so we'll just validate config
    if (!empty($config['shared_key']) && !empty($config['mall_id'])) {
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'Invalid configuration'];
}