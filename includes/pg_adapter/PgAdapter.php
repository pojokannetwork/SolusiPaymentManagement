<?php
// SolusiPaymentManagement Payment Gateway Adapter Base Class

abstract class PgAdapter {
    protected $config;
    protected $isSandbox;

    public function __construct($config = []) {
        $this->config = $config;
        $this->isSandbox = $config['sandbox'] ?? true;
    }

    // Abstract methods that must be implemented by each gateway
    abstract public function createInvoice($invoiceData);
    abstract public function getPayUrl($invoiceId);
    abstract public function verifyCallback($callbackData);
    abstract public function parseStatus($response);

    // Common methods
    public function isSandbox() {
        return $this->isSandbox;
    }

    public function setSandbox($sandbox) {
        $this->isSandbox = $sandbox;
    }

    // Generate unique reference
    protected function generateReference($prefix = 'INV') {
        return $prefix . '-' . date('YmdHis') . '-' . rand(1000, 9999);
    }

    // Validate required config
    protected function validateConfig($requiredKeys) {
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new Exception("Missing required config: {$key}");
            }
        }
    }

    // Make HTTP request
    protected function makeRequest($url, $method = 'POST', $data = [], $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("HTTP request failed: {$error}");
        }

        return [
            'code' => $httpCode,
            'response' => $response,
            'data' => json_decode($response, true) ?: []
        ];
    }

    // Log gateway activity
    protected function logActivity($action, $data) {
        global $logger;
        $logger->log("payment_gateway_{$action}", $data);
    }
}

// Factory class to create gateway instances
class PgFactory {
    public static function create($provider, $config) {
        $className = ucfirst(strtolower($provider));

        if (!class_exists($className)) {
            $file = __DIR__ . "/{$className}.php";
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new Exception("Payment gateway '{$provider}' not supported");
            }
        }

        return new $className($config);
    }

    public static function getSupportedProviders() {
        return ['midtrans', 'xendit', 'tripay', 'duitku', 'doku', 'ovo', 'gopay'];
    }
}
