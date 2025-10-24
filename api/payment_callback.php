<?php
// SolusiPaymentManagement Payment Callback Handler

require_once '../includes/bootstrap.php';

header('Content-Type: application/json');

// Log all incoming callbacks
$rawInput = file_get_contents('php://input');
$headers = getallheaders();
$method = $_SERVER['REQUEST_METHOD'];
$queryParams = $_GET;
$postParams = $_POST;

global $logger;
$logger->log('payment_callback_received', [
    'method' => $method,
    'headers' => $headers,
    'query' => $queryParams,
    'post' => $postParams,
    'raw_body' => $rawInput,
    'ip' => getClientIP()
]);

// Auto-detect payment provider
$provider = detectProvider($headers, $queryParams, $postParams, $rawInput);

if (!$provider) {
    errorResponse('Unknown payment provider', 400);
}

// Process callback based on provider
switch ($provider) {
    case 'midtrans':
        processMidtransCallback($rawInput);
        break;
    case 'xendit':
        processXenditCallback($rawInput, $headers);
        break;
    case 'tripay':
        processTripayCallback($postParams);
        break;
    case 'duitku':
        processDuitkuCallback($postParams);
        break;
    case 'doku':
        processDokuCallback($postParams);
        break;
    case 'ovo':
        processOvoCallback($rawInput);
        break;
    case 'gopay':
        processGopayCallback($rawInput);
        break;
    default:
        errorResponse('Unsupported payment provider', 400);
}

function detectProvider($headers, $query, $post, $raw) {
    // Check headers for provider-specific signatures
    if (isset($headers['X-Callback-Signature']) || strpos($raw, 'midtrans') !== false) {
        return 'midtrans';
    }

    if (isset($headers['X-Callback-Token']) || isset($query['external_id'])) {
        return 'xendit';
    }

    if (isset($post['reference']) && isset($post['merchant_ref'])) {
        return 'tripay';
    }

    if (isset($post['resultCode']) && isset($post['merchantOrderId'])) {
        return 'duitku';
    }

    if (isset($post['TRANSIDMERCHANT']) || isset($post['AMOUNT'])) {
        return 'doku';
    }

    if (isset($post['ovo']) || strpos($raw, 'ovo') !== false) {
        return 'ovo';
    }

    if (isset($post['gopay']) || strpos($raw, 'gopay') !== false) {
        return 'gopay';
    }

    return null;
}

function processMidtransCallback($rawInput) {
    $data = json_decode($rawInput, true);
    if (!$data) {
        errorResponse('Invalid JSON payload', 400);
    }

    $orderId = $data['order_id'] ?? '';
    if (empty($orderId)) {
        errorResponse('Order ID missing', 400);
    }

    // Get gateway configuration
    $gateway = getGatewayByOrderId($orderId);
    if (!$gateway) {
        errorResponse('Gateway not found for order', 400);
    }

    // Create adapter and verify callback
    $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
    $result = $adapter->verifyCallback($data);

    if (!$result['success']) {
        errorResponse('Callback verification failed: ' . $result['error'], 400);
    }

    // Process payment
    processPaymentCallback($result, $gateway['id']);
}

function processXenditCallback($rawInput, $headers) {
    $data = json_decode($rawInput, true);
    if (!$data) {
        errorResponse('Invalid JSON payload', 400);
    }

    $externalId = $data['external_id'] ?? '';
    if (empty($externalId)) {
        errorResponse('External ID missing', 400);
    }

    // Get gateway configuration
    $gateway = getGatewayByOrderId($externalId);
    if (!$gateway) {
        errorResponse('Gateway not found for order', 400);
    }

    // Create adapter and verify callback
    $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
    $result = $adapter->verifyCallback($data);

    if (!$result['success']) {
        errorResponse('Callback verification failed: ' . $result['error'], 400);
    }

    // Process payment
    processPaymentCallback($result, $gateway['id']);
}

function processTripayCallback($postData) {
    $reference = $postData['reference'] ?? '';
    if (empty($reference)) {
        errorResponse('Reference missing', 400);
    }

    // Get gateway configuration
    $gateway = getGatewayByReference($reference);
    if (!$gateway) {
        errorResponse('Gateway not found for reference', 400);
    }

    // Create adapter and verify callback
    $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
    $result = $adapter->verifyCallback($postData);

    if (!$result['success']) {
        errorResponse('Callback verification failed: ' . $result['error'], 400);
    }

    // Process payment
    processPaymentCallback($result, $gateway['id']);
}

function processDuitkuCallback($postData) {
    $orderId = $postData['merchantOrderId'] ?? '';
    if (empty($orderId)) {
        errorResponse('Order ID missing', 400);
    }

    // Get gateway configuration
    $gateway = getGatewayByOrderId($orderId);
    if (!$gateway) {
        errorResponse('Gateway not found for order', 400);
    }

    // Create adapter and verify callback
    $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
    $result = $adapter->verifyCallback($postData);

    if (!$result['success']) {
        errorResponse('Callback verification failed: ' . $result['error'], 400);
    }

    // Process payment
    processPaymentCallback($result, $gateway['id']);
}

function processDokuCallback($postData) {
    $orderId = $postData['TRANSIDMERCHANT'] ?? '';
    if (empty($orderId)) {
        errorResponse('Order ID missing', 400);
    }

    // Get gateway configuration
    $gateway = getGatewayByOrderId($orderId);
    if (!$gateway) {
        errorResponse('Gateway not found for order', 400);
    }

    // Create adapter and verify callback
    $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
    $result = $adapter->verifyCallback($postData);

    if (!$result['success']) {
        errorResponse('Callback verification failed: ' . $result['error'], 400);
    }

    // Process payment
    processPaymentCallback($result, $gateway['id']);
}

function processOvoCallback($rawInput) {
    // OVO callback processing (simplified)
    $data = json_decode($rawInput, true);
    $orderId = $data['order_id'] ?? '';

    if (empty($orderId)) {
        errorResponse('Order ID missing', 400);
    }

    // Get gateway and process (implementation depends on OVO API)
    $gateway = getGatewayByOrderId($orderId);
    if ($gateway) {
        $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
        $result = $adapter->verifyCallback($data);
        if ($result['success']) {
            processPaymentCallback($result, $gateway['id']);
        }
    }

    successResponse(['status' => 'OK']);
}

function processGopayCallback($rawInput) {
    // GoPay callback processing (simplified)
    $data = json_decode($rawInput, true);
    $orderId = $data['order_id'] ?? '';

    if (empty($orderId)) {
        errorResponse('Order ID missing', 400);
    }

    // Get gateway and process (implementation depends on GoPay API)
    $gateway = getGatewayByOrderId($orderId);
    if ($gateway) {
        $adapter = PgFactory::create($gateway['provider'], json_decode($gateway['config_json'], true));
        $result = $adapter->verifyCallback($data);
        if ($result['success']) {
            processPaymentCallback($result, $gateway['id']);
        }
    }

    successResponse(['status' => 'OK']);
}

function processPaymentCallback($callbackResult, $gatewayId) {
    global $db, $logger;

    $orderId = $callbackResult['order_id'];
    $status = $callbackResult['status'];
    $reference = $callbackResult['reference'] ?? '';

    // Find transaction
    $transaction = $db->fetchOne(
        "SELECT t.*, f.pelanggan_id, f.status as invoice_status
         FROM transaksi t
         JOIN faktur f ON t.faktur_id = f.id
         WHERE t.external_ref = ? OR (t.faktur_id = (
             SELECT id FROM faktur WHERE nomor = ?
         ))",
        [$reference, $orderId]
    );

    if (!$transaction) {
        errorResponse('Transaction not found', 404);
    }

    // Update transaction
    $db->execute(
        "UPDATE transaksi SET
         status = ?,
         paid_at = NOW(),
         raw_callback_json = ?,
         signature_valid = ?,
         updated_at = NOW()
         WHERE id = ?",
        [
            $status,
            json_encode($callbackResult),
            $callbackResult['signature_valid'] ? 1 : 0,
            $transaction['id']
        ]
    );

    // Update invoice status if payment successful
    if ($status === 'paid' && $transaction['invoice_status'] !== 'paid') {
        $db->execute(
            "UPDATE faktur SET status = 'paid', updated_at = NOW() WHERE id = ?",
            [$transaction['faktur_id']]
        );

        // Provision customer (activate service)
        provisionCustomerOnPayment($transaction['pelanggan_id']);

        $logger->logPayment('paid', $transaction['faktur_id'], $transaction['amount'], 'callback');
    } elseif (in_array($status, ['failed', 'expired'])) {
        // Provision customer (isolate service)
        provisionCustomerOnFailure($transaction['pelanggan_id']);

        $logger->logPayment('failed', $transaction['faktur_id'], $transaction['amount'], 'callback');
    }

    successResponse(['status' => 'OK', 'processed' => true]);
}

function getGatewayByOrderId($orderId) {
    global $db;

    // Find gateway by checking if order ID matches any invoice number
    $gateway = $db->fetchOne(
        "SELECT pg.*
         FROM payment_gateways pg
         JOIN transaksi t ON pg.id = t.gateway_id
         JOIN faktur f ON t.faktur_id = f.id
         WHERE f.nomor = ? AND pg.is_active = 1",
        [$orderId]
    );

    return $gateway;
}

function getGatewayByReference($reference) {
    global $db;

    $gateway = $db->fetchOne(
        "SELECT pg.*
         FROM payment_gateways pg
         JOIN transaksi t ON pg.id = t.gateway_id
         WHERE t.external_ref = ? AND pg.is_active = 1",
        [$reference]
    );

    return $gateway;
}

function provisionCustomerOnPayment($customerId) {
    global $db;

    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) return;

    $sourceOfTruth = getSetting('source_of_truth', 'radius');

    if ($sourceOfTruth === 'radius') {
        $radius = new RadiusSqlCoa();
        $radius->provisionCustomer([
            'pppoe_user' => $customer['pppoe_user'],
            'pppoe_pass' => decryptData($customer['pppoe_pass_enc']),
            'profile' => $customer['profile_aktif'],
            'rate_limit' => $customer['paket']
        ]);
    } else {
        if (!empty($customer['router_id'])) {
            $mtk = MtkFactory::createFromRouter($customer['router_id']);
            $mtk->setPppSecretProfile($customer['pppoe_user'], $customer['profile_aktif']);
            $mtk->enable($customer['pppoe_user']);
        }
    }

    // Update customer status
    $db->execute(
        "UPDATE pelanggan SET status = 'active', updated_at = NOW() WHERE id = ?",
        [$customerId]
    );
}

function provisionCustomerOnFailure($customerId) {
    global $db;

    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) return;

    $sourceOfTruth = getSetting('source_of_truth', 'radius');

    if ($sourceOfTruth === 'radius') {
        $radius = new RadiusSqlCoa();
        $radius->setGroup($customer['pppoe_user'], $customer['profile_isolir']);
        $radius->sendCoA($customer['pppoe_user']);
    } else {
        if (!empty($customer['router_id'])) {
            $mtk = MtkFactory::createFromRouter($customer['router_id']);
            $mtk->setPppSecretProfile($customer['pppoe_user'], $customer['profile_isolir']);
        }
    }

    // Update customer status
    $db->execute(
        "UPDATE pelanggan SET status = 'isolir', updated_at = NOW() WHERE id = ?",
        [$customerId]
    );
}
