<?php
// SolusiPaymentManagement DOKU Payment Gateway Adapter

class Doku extends PgAdapter {
    private $clientId;
    private $sharedKey;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->validateConfig(['client_id', 'shared_key']);

        $this->clientId = $config['client_id'];
        $this->sharedKey = $config['shared_key'];
        $this->baseUrl = $this->isSandbox ?
            'https://sandbox.doku.com/api/payment' :
            'https://api.doku.com/api/payment';
    }

    public function createInvoice($invoiceData) {
        $orderId = $invoiceData['order_id'];
        $amount = (int) $invoiceData['amount'];

        $words = $amount . $this->sharedKey . $orderId;
        $signature = hash('sha256', $words);

        $payload = [
            'order' => [
                'invoice_number' => $orderId,
                'amount' => $amount,
                'currency' => 'IDR',
                'callback_url' => $invoiceData['callback_url'] ?? '',
                'line_items' => $this->formatItems($invoiceData['items'])
            ],
            'payment' => [
                'payment_due_date' => 60
            ],
            'customer' => [
                'name' => $invoiceData['customer_name'],
                'email' => $invoiceData['customer_email'],
                'phone' => $invoiceData['customer_phone']
            ]
        ];

        $headers = [
            'Client-Id: ' . $this->clientId,
            'Request-Id: ' . uniqid(),
            'Request-Timestamp: ' . date('Y-m-d\TH:i:s\Z'),
            'Signature: ' . $signature,
            'Content-Type: application/json'
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/v1/invoice',
                'POST',
                json_encode($payload),
                $headers
            );

            if ($response['code'] == 200) {
                return [
                    'success' => true,
                    'invoice_id' => $orderId,
                    'payment_url' => $response['data']['invoice']['url'],
                    'reference' => $response['data']['invoice']['id'],
                    'raw_response' => json_encode($response['data'])
                ];
            }

            return [
                'success' => false,
                'error' => $response['data']['error']['message'] ?? 'Unknown error'
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
        $amount = $callbackData['transaction']['amount'] ?? '';
        $invoiceNumber = $callbackData['order']['invoice_number'] ?? '';

        $words = $amount . $this->sharedKey . $invoiceNumber;
        $expectedSignature = hash('sha256', $words);

        return $signature === $expectedSignature;
    }

    public function parseStatus($response) {
        $status = $response['transaction']['status'] ?? 'PENDING';

        $statusMap = [
            'SUCCESS' => 'paid',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'EXPIRED' => 'expired'
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
