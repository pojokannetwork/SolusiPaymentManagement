<?php
// SolusiPaymentManagement Midtrans Payment Gateway Adapter

class Midtrans extends PgAdapter {
    private $serverKey;
    private $clientKey;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);

        $this->validateConfig(['server_key', 'client_key']);

        $this->serverKey = $config['server_key'];
        $this->clientKey = $config['client_key'];
        $this->baseUrl = $this->isSandbox ?
            'https://api.sandbox.midtrans.com/v2' :
            'https://api.midtrans.com/v2';
    }

    public function createInvoice($invoiceData) {
        $payload = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $invoiceData['order_id'],
                'gross_amount' => (int) $invoiceData['amount']
            ],
            'customer_details' => [
                'first_name' => $invoiceData['customer_name'],
                'email' => $invoiceData['customer_email'],
                'phone' => $invoiceData['customer_phone']
            ],
            'item_details' => $this->formatItems($invoiceData['items']),
            'bank_transfer' => [
                'bank' => $invoiceData['bank'] ?? 'bca'
            ]
        ];

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/charge',
                'POST',
                json_encode($payload),
                $headers
            );

            $this->logActivity('create_invoice', [
                'provider' => 'midtrans',
                'order_id' => $invoiceData['order_id'],
                'response_code' => $response['code']
            ]);

            if ($response['code'] == 201) {
                return [
                    'success' => true,
                    'invoice_id' => $response['data']['order_id'],
                    'payment_url' => $response['data']['redirect_url'] ?? null,
                    'reference' => $response['data']['transaction_id'],
                    'raw_response' => json_encode($response['data'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['data']['status_message'] ?? 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            $this->logActivity('create_invoice_error', [
                'provider' => 'midtrans',
                'order_id' => $invoiceData['order_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPayUrl($invoiceId) {
        // Midtrans doesn't have a separate pay URL method
        // The URL is returned in createInvoice
        return null;
    }

    public function verifyCallback($callbackData) {
        // Verify signature
        $signature = $callbackData['signature_key'] ?? '';
        $orderId = $callbackData['order_id'] ?? '';
        $statusCode = $callbackData['status_code'] ?? '';
        $grossAmount = $callbackData['gross_amount'] ?? '';

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        if (!hash_equals($signature, $expectedSignature)) {
            $this->logActivity('callback_signature_invalid', [
                'provider' => 'midtrans',
                'order_id' => $orderId
            ]);
            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $this->logActivity('callback_verified', [
            'provider' => 'midtrans',
            'order_id' => $orderId,
            'status' => $callbackData['transaction_status'] ?? 'unknown'
        ]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'status' => $this->parseStatus($callbackData),
            'reference' => $callbackData['transaction_id'] ?? '',
            'signature_valid' => true,
            'raw_callback' => json_encode($callbackData)
        ];
    }

    public function parseStatus($response) {
        $status = $response['transaction_status'] ?? '';

        switch ($status) {
            case 'capture':
            case 'settlement':
                return 'paid';
            case 'pending':
                return 'pending';
            case 'deny':
            case 'cancel':
            case 'expire':
            case 'failure':
                return 'failed';
            default:
                return 'unknown';
        }
    }

    private function formatItems($items) {
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'id' => $item['id'] ?? 'item_' . rand(1000, 9999),
                'price' => (int) $item['price'],
                'quantity' => (int) $item['quantity'],
                'name' => substr($item['name'], 0, 50)
            ];
        }
        return $formatted;
    }
}
