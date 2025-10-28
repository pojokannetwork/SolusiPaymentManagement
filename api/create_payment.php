<?php
// API for creating payments

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/PaymentGatewayFactory.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

// Validate required fields
$requiredFields = ['invoice_id', 'gateway_id', 'amount', 'customer_name'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        errorResponse("Field {$field} is required", 400);
    }
}

$invoiceId = (int)$data['invoice_id'];
$gatewayId = (int)$data['gateway_id'];
$amount = (float)$data['amount'];
$customerName = $data['customer_name'];
$customerEmail = $data['customer_email'] ?? '';
$customerPhone = $data['customer_phone'] ?? '';
$description = $data['description'] ?? 'Payment for invoice #' . $invoiceId;

try {
    // Get invoice details
    $invoice = $db->fetchOne(
        "SELECT f.*, p.nama as customer_name, p.email, p.telp 
         FROM faktur f 
         JOIN pelanggan p ON f.pelanggan_id = p.id 
         WHERE f.id = ?",
        [$invoiceId]
    );
    
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }
    
    if ($invoice['status'] === 'paid') {
        errorResponse('Invoice already paid', 400);
    }
    
    // Get gateway configuration
    $gateway = $db->fetchOne(
        "SELECT * FROM payment_gateways WHERE id = ? AND is_active = 1",
        [$gatewayId]
    );
    
    if (!$gateway) {
        errorResponse('Gateway not found or inactive', 404);
    }
    
    // Create payment gateway adapter
    $config = json_decode($gateway['config_json'], true);
    $adapter = PgFactory::create($gateway['provider'], $config);
    
    // Generate unique order ID
    $orderId = $invoice['nomor'] . '_' . time();
    
    // Prepare payment data
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'customer_name' => $customerName ?: $invoice['customer_name'],
        'customer_email' => $customerEmail ?: $invoice['email'],
        'customer_phone' => $customerPhone ?: $invoice['telp'],
        'description' => $description,
        'return_url' => getBaseUrl() . '/payment/success?invoice=' . $invoiceId,
        'cancel_url' => getBaseUrl() . '/payment/cancel?invoice=' . $invoiceId
    ];
    
    // Create payment
    $result = $adapter->createPayment($paymentData);
    
    if (!$result['success']) {
        errorResponse($result['error'] ?? 'Payment creation failed', 400);
    }
    
    // Save transaction to database
    $transactionId = $db->execute(
        "INSERT INTO transaksi (faktur_id, gateway_id, amount, status, external_ref, payment_url, created_at, updated_at)
         VALUES (?, ?, ?, 'pending', ?, ?, NOW(), NOW())",
        [
            $invoiceId,
            $gatewayId,
            $amount,
            $result['reference'] ?? $orderId,
            $result['payment_url'] ?? null
        ]
    );
    
    // Log payment creation
    global $logger;
    if (isset($logger)) {
        $logger->log('payment_created', [
            'transaction_id' => $transactionId,
            'invoice_id' => $invoiceId,
            'gateway_id' => $gatewayId,
            'amount' => $amount,
            'order_id' => $orderId
        ]);
    }
    
    successResponse([
        'transaction_id' => $transactionId,
        'payment_url' => $result['payment_url'],
        'order_id' => $orderId,
        'reference' => $result['reference'] ?? null,
        'token' => $result['token'] ?? null
    ]);
    
} catch (Exception $e) {
    errorResponse('Payment creation error: ' . $e->getMessage(), 500);
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}