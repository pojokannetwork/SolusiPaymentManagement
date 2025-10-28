<?php
// SolusiPaymentManagement OVO Payment Gateway Adapter (via aggregator)

class Ovo extends PgAdapter {
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->validateConfig(['api_key', 'secret_key']);

        $this->apiKey = $config['api_key'];
        $this->secretKey = $config['secret_key'];
        $this->baseUrl = $this->isSandbox ?
            'https://sandbox-api.oyindonesia.com' :
            'https://api.oyindonesia.com';
    }

    public function createInvoice($invoiceData) {
        $orderId = $invoiceData['order_id'];
        $amount = (int) $invoiceData['amount'];
        $phoneNumber = $invoiceData['customer_phone'];

        $payload = [
            'partner_tx_id' => $orderId,
            'amount' => $amount,
            'phone_number' => $phoneNumber,
            'description' => $invoiceData['description'] ?? 'Payment',
            'callback_url' => $invoiceData['callback_url'] ?? ''
        ];

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'X-Oy-Username: ' . ($this->config['username'] ?? ''),
            'Content-Type: application/json'
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/api/payment-checkout/create-invoice',
                'POST',
                json_encode($payload),
                $headers
            );

            if ($response['code'] == 200 && $response['data']['status'] == true) {
                return [
                    'success' => true,
                    'invoice_id' => $orderId,
                    'payment_url' => $response['data']['url'],
                    'reference' => $response['data']['payment_link_id'],
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
        return null;
    }

    public function verifyCallback($callbackData) {
        $signature = $callbackData['signature'] ?? '';
        $partnerTxId = $callbackData['partner_tx_id'] ?? '';
        $status = $callbackData['status'] ?? '';

        $data = $partnerTxId . '|' . $status;
        $expectedSignature = hash_hmac('sha256', $data, $this->secretKey);

        return $signature === $expectedSignature;
    }

    public function parseStatus($response) {
        $status = $response['status'] ?? 'PENDING';

        $statusMap = [
            'COMPLETE' => 'paid',
            'SUCCESS' => 'paid',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'EXPIRED' => 'expired'
        ];

        return $statusMap[$status] ?? 'pending';
    }
}
