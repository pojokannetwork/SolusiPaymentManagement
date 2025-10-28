<?php
// SolusiPaymentManagement Tripay Payment Gateway Adapter

class Tripay extends PgAdapter {
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->validateConfig(['api_key', 'private_key', 'merchant_code']);

        $this->apiKey = $config['api_key'];
        $this->privateKey = $config['private_key'];
        $this->merchantCode = $config['merchant_code'];
        $this->baseUrl = $this->isSandbox ?
            'https://tripay.co.id/api-sandbox' :
            'https://tripay.co.id/api';
    }

    public function createInvoice($invoiceData) {
        $orderId = $invoiceData['order_id'];
        $amount = (int) $invoiceData['amount'];
        $method = $invoiceData['payment_method'] ?? 'BRIVA';

        $signature = hash_hmac('sha256', $this->merchantCode . $orderId . $amount, $this->privateKey);

        $payload = [
            'method' => $method,
            'merchant_ref' => $orderId,
            'amount' => $amount,
            'customer_name' => $invoiceData['customer_name'],
            'customer_email' => $invoiceData['customer_email'],
            'customer_phone' => $invoiceData['customer_phone'],
            'order_items' => $this->formatItems($invoiceData['items']),
            'signature' => $signature
        ];

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/transaction/create',
                'POST',
                json_encode($payload),
                $headers
            );

            if ($response['code'] == 200 && $response['data']['success']) {
                return [
                    'success' => true,
                    'invoice_id' => $orderId,
                    'payment_url' => $response['data']['data']['checkout_url'],
                    'reference' => $response['data']['data']['reference'],
                    'raw_response' => json_encode($response['data'])
                ];
            }

            return [
                'success' => false,
                'error' => $response['data']['message'] ?? 'Unknown error'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPayUrl($invoiceId) {
        return null; // Returned in createInvoice
    }

    public function verifyCallback($callbackData) {
        $signature = $callbackData['signature'] ?? '';
        $reference = $callbackData['reference'] ?? '';
        $status = $callbackData['status'] ?? '';

        $expectedSignature = hash_hmac('sha256', $reference . ':' . $status, $this->privateKey);
        return $signature === $expectedSignature;
    }

    public function parseStatus($response) {
        $status = $response['status'] ?? 'UNPAID';

        $statusMap = [
            'UNPAID' => 'pending',
            'PAID' => 'paid',
            'EXPIRED' => 'expired',
            'FAILED' => 'failed'
        ];

        return $statusMap[$status] ?? 'pending';
    }

    private function formatItems($items) {
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'name' => $item['name'] ?? $item['deskripsi'],
                'price' => (int) ($item['price'] ?? $item['harga']),
                'quantity' => (int) ($item['qty'] ?? 1)
            ];
        }
        return $formatted;
    }
}
