<?php
// SolusiPaymentManagement Duitku Payment Gateway Adapter

class Duitku extends PgAdapter {
    private $merchantCode;
    private $apiKey;
    private $baseUrl;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->validateConfig(['merchant_code', 'api_key']);

        $this->merchantCode = $config['merchant_code'];
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $this->isSandbox ?
            'https://sandbox.duitku.com/webapi/api/merchant' :
            'https://passport.duitku.com/webapi/api/merchant';
    }

    public function createInvoice($invoiceData) {
        $orderId = $invoiceData['order_id'];
        $amount = (int) $invoiceData['amount'];
        $paymentMethod = $invoiceData['payment_method'] ?? 'BT';

        $signature = md5($this->merchantCode . $orderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $orderId,
            'productDetails' => $invoiceData['description'] ?? 'Payment',
            'customerVaName' => $invoiceData['customer_name'],
            'email' => $invoiceData['customer_email'],
            'phoneNumber' => $invoiceData['customer_phone'],
            'callbackUrl' => $invoiceData['callback_url'] ?? '',
            'returnUrl' => $invoiceData['return_url'] ?? '',
            'signature' => $signature,
            'expiryPeriod' => 1440
        ];

        $headers = ['Content-Type: application/json'];

        try {
            $response = $this->makeRequest(
                $this->baseUrl . '/v2/inquiry',
                'POST',
                json_encode($payload),
                $headers
            );

            if ($response['code'] == 200 && isset($response['data']['paymentUrl'])) {
                return [
                    'success' => true,
                    'invoice_id' => $orderId,
                    'payment_url' => $response['data']['paymentUrl'],
                    'reference' => $response['data']['reference'],
                    'raw_response' => json_encode($response['data'])
                ];
            }

            return [
                'success' => false,
                'error' => $response['data']['Message'] ?? 'Unknown error'
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
        $merchantOrderId = $callbackData['merchantOrderId'] ?? '';
        $amount = $callbackData['amount'] ?? '';
        $resultCode = $callbackData['resultCode'] ?? '';

        $expectedSignature = md5($this->merchantCode . $amount . $merchantOrderId . $this->apiKey);
        return $signature === $expectedSignature;
    }

    public function parseStatus($response) {
        $resultCode = $response['resultCode'] ?? '';

        if ($resultCode == '00') return 'paid';
        if ($resultCode == '01') return 'pending';

        return 'failed';
    }
}
