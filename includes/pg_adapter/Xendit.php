<?php
// SolusiPaymentManagement Xendit Payment Gateway Adapter

class Xendit extends PgAdapter {
    private $secretKey;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);

        $this->validateConfig(['secret_key']);

        $this->secretKey = $config['secret_key'];
        $this->baseUrl = 'https://api.xendit.co';
    }

    public function createInvoice($invoiceData) {
        $payload = [
            'external_id' => $invoiceData['order_id'],
            'amount' => (float) $invoiceData['amount'],
            'description' => $invoiceData['description'] ?? 'Payment for invoice',
            'customer' => [
                'given_names' => $invoiceData['customer_name'],
                'email' => $invoiceData['customer_email']
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['email'],
                'invoice_reminder' => ['email'],
                'invoice_paid' => ['email']
            ],
            'success_redirect_url' => $invoiceData['success_url'] ?? APP_URL . '/customer/invoices',
            'failure_redirect_url' => $invoiceData['failure_url'] ?? APP_URL . '/customer/invoices'
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->secretKey . ':')
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/v2/invoices',
                'POST',
                json_encode($payload),
                $headers
            );

            $this->logActivity('create_invoice', [
                'provider' => 'xendit',
                'external_id' => $invoiceData['order_id'],
                'response_code' => $response['code']
            ]);

            if ($response['code'] == 200) {
                return [
                    'success' => true,
                    'invoice_id' => $response['data']['external_id'],
                    'payment_url' => $response['data']['invoice_url'],
                    'reference' => $response['data']['id'],
                    'raw_response' => json_encode($response['data'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['data']['message'] ?? 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            $this->logActivity('create_invoice_error', [
                'provider' => 'xendit',
                'external_id' => $invoiceData['order_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPayUrl($invoiceId) {
        // Get invoice details to retrieve payment URL
        $headers = [
            'Authorization: Basic ' . base64_encode($this->secretKey . ':')
        ];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/v2/invoices/' . $invoiceId,
                'GET',
                [],
                $headers
            );

            if ($response['code'] == 200) {
                return $response['data']['invoice_url'];
            }
        } catch (Exception $e) {
            // Log error but don't throw
        }

        return null;
    }

    public function verifyCallback($callbackData) {
        // Xendit uses webhook signature verification
        $signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

        if (empty($signature)) {
            return ['success' => false, 'error' => 'Missing callback signature'];
        }

        // Verify signature (simplified - in production, implement proper HMAC verification)
        $expectedSignature = hash_hmac('sha256', json_encode($callbackData), $this->secretKey);

        if (!hash_equals($signature, $expectedSignature)) {
            $this->logActivity('callback_signature_invalid', [
                'provider' => 'xendit',
                'external_id' => $callbackData['external_id'] ?? ''
            ]);
            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $this->logActivity('callback_verified', [
            'provider' => 'xendit',
            'external_id' => $callbackData['external_id'],
            'status' => $callbackData['status'] ?? 'unknown'
        ]);

        return [
            'success' => true,
            'order_id' => $callbackData['external_id'],
            'status' => $this->parseStatus($callbackData),
            'reference' => $callbackData['id'] ?? '',
            'signature_valid' => true,
            'raw_callback' => json_encode($callbackData)
        ];
    }

    public function parseStatus($response) {
        $status = $response['status'] ?? '';

        switch ($status) {
            case 'PAID':
            case 'SETTLED':
                return 'paid';
            case 'PENDING':
                return 'pending';
            case 'EXPIRED':
            case 'FAILED':
                return 'failed';
            default:
                return 'unknown';
        }
    }
}
