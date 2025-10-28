<?php

class PgFactory {
    public static function create($provider, $config) {
        switch (strtolower($provider)) {
            case 'midtrans':
                return new MidtransAdapter($config);
            case 'xendit':
                return new XenditAdapter($config);
            case 'tripay':
                return new TripayAdapter($config);
            case 'duitku':
                return new DuitkuAdapter($config);
            case 'doku':
                return new DokuAdapter($config);
            case 'ovo':
                return new OvoAdapter($config);
            case 'gopay':
                return new GopayAdapter($config);
            default:
                throw new Exception("Unsupported payment gateway: " . $provider);
        }
    }
    
    public static function getAvailableProviders() {
        return [
            'midtrans' => 'Midtrans',
            'xendit' => 'Xendit',
            'tripay' => 'Tripay',
            'duitku' => 'Duitku',
            'doku' => 'DOKU',
            'ovo' => 'OVO',
            'gopay' => 'GoPay'
        ];
    }
}

abstract class PaymentGatewayAdapter {
    protected $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    abstract public function createPayment($data);
    abstract public function verifyCallback($data);
    abstract public function checkStatus($orderId);
    
    protected function makeRequest($url, $data = null, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP Request Error: " . $error);
        }
        
        return [
            'status_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }
}

class MidtransAdapter extends PaymentGatewayAdapter {
    
    public function createPayment($data) {
        $serverKey = $this->config['server_key'];
        $sandbox = $this->config['sandbox'] ?? true;
        
        $baseUrl = $sandbox ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
        
        $payload = [
            'transaction_details' => [
                'order_id' => $data['order_id'],
                'gross_amount' => (int)$data['amount']
            ],
            'customer_details' => [
                'first_name' => $data['customer_name'],
                'email' => $data['customer_email'] ?? '',
                'phone' => $data['customer_phone'] ?? ''
            ],
            'item_details' => [[
                'id' => 'item-1',
                'price' => (int)$data['amount'],
                'quantity' => 1,
                'name' => $data['description'] ?? 'Payment'
            ]]
        ];
        
        $headers = [
            'Authorization: Basic ' . base64_encode($serverKey . ':'),
            'Content-Type: application/json'
        ];
        
        $response = $this->makeRequest($baseUrl . '/v2/charge', $payload, $headers);
        
        if ($response['status_code'] === 201) {
            return [
                'success' => true,
                'payment_url' => $response['data']['redirect_url'] ?? null,
                'token' => $response['data']['token'] ?? null,
                'reference' => $response['data']['transaction_id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'error' => $response['data']['status_message'] ?? 'Payment creation failed'
        ];
    }
    
    public function verifyCallback($data) {
        $serverKey = $this->config['server_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        
        // Verify signature
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $isValid = ($signatureKey === ($data['signature_key'] ?? ''));
        
        // Map status
        $status = 'pending';
        if ($data['transaction_status'] === 'settlement' || 
            $data['transaction_status'] === 'capture') {
            $status = 'paid';
        } elseif (in_array($data['transaction_status'], ['cancel', 'expire', 'failure'])) {
            $status = 'failed';
        }
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'reference' => $data['transaction_id'] ?? '',
            'status' => $status,
            'amount' => $grossAmount,
            'signature_valid' => $isValid
        ];
    }
    
    public function checkStatus($orderId) {
        $serverKey = $this->config['server_key'];
        $sandbox = $this->config['sandbox'] ?? true;
        
        $baseUrl = $sandbox ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
        
        $headers = [
            'Authorization: Basic ' . base64_encode($serverKey . ':')
        ];
        
        $response = $this->makeRequest($baseUrl . '/v2/' . $orderId . '/status', null, $headers);
        
        if ($response['status_code'] === 200) {
            $data = $response['data'];
            $status = 'pending';
            
            if ($data['transaction_status'] === 'settlement' || 
                $data['transaction_status'] === 'capture') {
                $status = 'paid';
            } elseif (in_array($data['transaction_status'], ['cancel', 'expire', 'failure'])) {
                $status = 'failed';
            }
            
            return [
                'success' => true,
                'status' => $status,
                'reference' => $data['transaction_id'] ?? ''
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to check status'
        ];
    }
}

class XenditAdapter extends PaymentGatewayAdapter {
    
    public function createPayment($data) {
        $secretKey = $this->config['secret_key'];
        
        $payload = [
            'external_id' => $data['order_id'],
            'amount' => (int)$data['amount'],
            'payer_email' => $data['customer_email'] ?? '',
            'description' => $data['description'] ?? 'Payment',
            'success_redirect_url' => $data['return_url'] ?? '',
            'failure_redirect_url' => $data['cancel_url'] ?? ''
        ];
        
        $headers = [
            'Authorization: Basic ' . base64_encode($secretKey . ':'),
            'Content-Type: application/json'
        ];
        
        $response = $this->makeRequest('https://api.xendit.co/v2/invoices', $payload, $headers);
        
        if ($response['status_code'] === 201) {
            return [
                'success' => true,
                'payment_url' => $response['data']['invoice_url'],
                'reference' => $response['data']['id']
            ];
        }
        
        return [
            'success' => false,
            'error' => $response['data']['message'] ?? 'Payment creation failed'
        ];
    }
    
    public function verifyCallback($data) {
        // Xendit callback verification
        $status = 'pending';
        
        if ($data['status'] === 'SETTLED') {
            $status = 'paid';
        } elseif (in_array($data['status'], ['EXPIRED', 'CANCELLED'])) {
            $status = 'failed';
        }
        
        return [
            'success' => true,
            'order_id' => $data['external_id'],
            'reference' => $data['id'] ?? '',
            'status' => $status,
            'amount' => $data['amount'] ?? 0,
            'signature_valid' => true // Implement proper verification if needed
        ];
    }
    
    public function checkStatus($orderId) {
        $secretKey = $this->config['secret_key'];
        
        $headers = [
            'Authorization: Basic ' . base64_encode($secretKey . ':')
        ];
        
        $response = $this->makeRequest('https://api.xendit.co/v2/invoices?external_id=' . $orderId, null, $headers);
        
        if ($response['status_code'] === 200 && !empty($response['data'])) {
            $invoice = $response['data'][0];
            $status = 'pending';
            
            if ($invoice['status'] === 'SETTLED') {
                $status = 'paid';
            } elseif (in_array($invoice['status'], ['EXPIRED', 'CANCELLED'])) {
                $status = 'failed';
            }
            
            return [
                'success' => true,
                'status' => $status,
                'reference' => $invoice['id']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to check status'
        ];
    }
}

// Simplified adapters for other providers
class TripayAdapter extends PaymentGatewayAdapter {
    public function createPayment($data) {
        return ['success' => false, 'error' => 'Tripay integration not fully implemented'];
    }
    
    public function verifyCallback($data) {
        return [
            'success' => true,
            'order_id' => $data['merchant_ref'] ?? '',
            'reference' => $data['reference'] ?? '',
            'status' => $data['status'] === 'PAID' ? 'paid' : 'pending',
            'amount' => $data['amount'] ?? 0,
            'signature_valid' => true
        ];
    }
    
    public function checkStatus($orderId) {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}

class DuitkuAdapter extends PaymentGatewayAdapter {
    public function createPayment($data) {
        return ['success' => false, 'error' => 'Duitku integration not fully implemented'];
    }
    
    public function verifyCallback($data) {
        return [
            'success' => true,
            'order_id' => $data['merchantOrderId'] ?? '',
            'reference' => $data['reference'] ?? '',
            'status' => $data['resultCode'] === '00' ? 'paid' : 'failed',
            'amount' => $data['amount'] ?? 0,
            'signature_valid' => true
        ];
    }
    
    public function checkStatus($orderId) {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}

class DokuAdapter extends PaymentGatewayAdapter {
    public function createPayment($data) {
        return ['success' => false, 'error' => 'DOKU integration not fully implemented'];
    }
    
    public function verifyCallback($data) {
        return [
            'success' => true,
            'order_id' => $data['TRANSIDMERCHANT'] ?? '',
            'reference' => $data['TRANSID'] ?? '',
            'status' => $data['RESULTMSG'] === 'SUCCESS' ? 'paid' : 'failed',
            'amount' => $data['AMOUNT'] ?? 0,
            'signature_valid' => true
        ];
    }
    
    public function checkStatus($orderId) {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}

class OvoAdapter extends PaymentGatewayAdapter {
    public function createPayment($data) {
        return ['success' => false, 'error' => 'OVO integration not fully implemented'];
    }
    
    public function verifyCallback($data) {
        return [
            'success' => true,
            'order_id' => $data['order_id'] ?? '',
            'reference' => $data['reference'] ?? '',
            'status' => $data['status'] === 'SUCCESS' ? 'paid' : 'pending',
            'amount' => $data['amount'] ?? 0,
            'signature_valid' => true
        ];
    }
    
    public function checkStatus($orderId) {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}

class GopayAdapter extends PaymentGatewayAdapter {
    public function createPayment($data) {
        return ['success' => false, 'error' => 'GoPay integration not fully implemented'];
    }
    
    public function verifyCallback($data) {
        return [
            'success' => true,
            'order_id' => $data['order_id'] ?? '',
            'reference' => $data['reference'] ?? '',
            'status' => $data['status'] === 'SUCCESS' ? 'paid' : 'pending',
            'amount' => $data['amount'] ?? 0,
            'signature_valid' => true
        ];
    }
    
    public function checkStatus($orderId) {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}