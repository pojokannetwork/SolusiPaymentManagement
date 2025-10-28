<?php
// SolusiPaymentManagement GoPay Payment Gateway Adapter (via Midtrans)

class Gopay extends PgAdapter {
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
            'payment_type' => 'gopay',
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
            'gopay' => [
                'enable_callback' => true,
                'callback_url' => $invoiceData['callback_url'] ?? ''
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

            if ($response['code'] == 201) {
                $actions = $response['data']['actions'] ?? [];
                $deeplink = '';
                $qrcode = '';

                foreach ($actions as $action) {
                    if ($action['name'] == 'generate-qr-code') {
                        $qrcode = $action['url'];
                    }
                    if ($action['name'] == 'deeplink-redirect') {
                        $deeplink = $action['url'];
                    }
                }

                return [
                    'success' => true,
                    'invoice_id' => $response['data']['order_id'],
                    'payment_url' => $deeplink,
                    'qr_code_url' => $qrcode,
                    'reference' => $response['data']['transaction_id'],
                    'raw_response' => json_encode($response['data'])
                ];
            }

            return [
                'success' => false,
                'error' => $response['data']['status_message'] ?? 'Unknown error'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPayUrl($invoiceId) {
        return null;
    }

    public function verifyCallback($callbackData) {
        $signature = $callbackData['signature_key'] ?? '';
        $orderId = $callbackData['order_id'] ?? '';
        $statusCode = $callbackData['status_code'] ?? '';
        $grossAmount = $callbackData['gross_amount'] ?? '';

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return $signature === $expectedSignature;
    }

    public function parseStatus($response) {
        $transactionStatus = $response['transaction_status'] ?? 'pending';
        $fraudStatus = $response['fraud_status'] ?? 'accept';

        if ($transactionStatus == 'settlement' && $fraudStatus == 'accept') {
            return 'paid';
        } elseif ($transactionStatus == 'pending') {
            return 'pending';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            return 'failed';
        }

        return 'pending';
    }

    private function formatItems($items) {
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'id' => $item['id'] ?? uniqid(),
                'name' => $item['name'] ?? $item['deskripsi'],
                'price' => (int) ($item['price'] ?? $item['harga']),
                'quantity' => (int) ($item['qty'] ?? 1)
            ];
        }
        return $formatted;
    }
}
