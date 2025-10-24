<?php
// SolusiPaymentManagement Ollama AI Integration

class OllamaAI {
    private static $instance = null;
    private $host;
    private $model;
    private $timeout;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->host = OLLAMA_HOST;
        $this->model = OLLAMA_MODEL;
        $this->timeout = OLLAMA_TIMEOUT;
    }

    // Make request to Ollama API
    private function makeRequest($endpoint, $data = []) {
        $url = rtrim($this->host, '/') . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Ollama API request failed: {$error}");
            return ['error' => $error];
        }

        if ($httpCode !== 200) {
            error_log("Ollama API returned HTTP {$httpCode}: {$response}");
            return ['error' => "HTTP {$httpCode}", 'response' => $response];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode Ollama response: " . json_last_error_msg());
            return ['error' => 'Invalid JSON response'];
        }

        return $result;
    }

    // Generate text response
    public function generate($prompt, $options = []) {
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false
        ];

        // Merge with additional options
        $data = array_merge($data, $options);

        $result = $this->makeRequest('/api/generate', $data);

        if (isset($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }

        return [
            'success' => true,
            'response' => $result['response'] ?? '',
            'done' => $result['done'] ?? false,
            'context' => $result['context'] ?? null
        ];
    }

    // Chat with context
    public function chat($messages, $options = []) {
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false
        ];

        $data = array_merge($data, $options);

        $result = $this->makeRequest('/api/chat', $data);

        if (isset($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }

        return [
            'success' => true,
            'message' => $result['message']['content'] ?? '',
            'done' => $result['done'] ?? false
        ];
    }

    // Check if Ollama is running
    public function isRunning() {
        $result = $this->makeRequest('/api/tags');
        return !isset($result['error']);
    }

    // Get available models
    public function getModels() {
        $result = $this->makeRequest('/api/tags');

        if (isset($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }

        return [
            'success' => true,
            'models' => array_column($result['models'] ?? [], 'name')
        ];
    }

    // NOC Assistant - Help with network operations
    public function nocAssistant($query, $context = []) {
        $systemPrompt = "You are a NOC (Network Operations Center) assistant for an ISP. " .
                       "Help with network troubleshooting, customer support, and technical issues. " .
                       "Be helpful, professional, and provide clear step-by-step solutions.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        if (!empty($context)) {
            $messages[] = ['role' => 'system', 'content' => 'Additional context: ' . json_encode($context)];
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        return $this->chat($messages);
    }

    // Call Center Assistant - Help with customer service
    public function callCenterAssistant($query, $customerInfo = []) {
        $systemPrompt = "You are a call center assistant for an ISP. " .
                       "Help customers with billing questions, service issues, and general inquiries. " .
                       "Be friendly, patient, and provide accurate information about services.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        if (!empty($customerInfo)) {
            $messages[] = ['role' => 'system', 'content' => 'Customer information: ' . json_encode($customerInfo)];
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        return $this->chat($messages);
    }

    // Billing Assistant - Help with billing questions
    public function billingAssistant($query, $billingInfo = []) {
        $systemPrompt = "You are a billing assistant for an ISP. " .
                       "Help customers understand their bills, payment options, and resolve billing disputes. " .
                       "Explain charges clearly and provide payment guidance.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        if (!empty($billingInfo)) {
            $messages[] = ['role' => 'system', 'content' => 'Billing information: ' . json_encode($billingInfo)];
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        return $this->chat($messages);
    }

    // Generate NOC report summary
    public function generateNOCReport($data) {
        $prompt = "Generate a concise NOC (Network Operations Center) report summary based on the following data:\n\n" .
                 json_encode($data, JSON_PRETTY_PRINT) . "\n\n" .
                 "Include key metrics, issues, and recommendations.";

        return $this->generate($prompt, ['temperature' => 0.3]);
    }

    // Analyze customer behavior patterns
    public function analyzeCustomerBehavior($customerData) {
        $prompt = "Analyze the following customer data and provide insights about usage patterns, " .
                 "potential issues, and recommendations:\n\n" .
                 json_encode($customerData, JSON_PRETTY_PRINT);

        return $this->generate($prompt, ['temperature' => 0.5]);
    }

    // Generate troubleshooting guide
    public function generateTroubleshootingGuide($issue) {
        $prompt = "Create a step-by-step troubleshooting guide for the following network issue: {$issue}\n\n" .
                 "Include common causes, diagnostic steps, and solutions.";

        return $this->generate($prompt, ['temperature' => 0.2]);
    }

    // Set model
    public function setModel($model) {
        $this->model = $model;
    }

    // Get current model
    public function getModel() {
        return $this->model;
    }

    // Test connection
    public function testConnection() {
        return $this->isRunning();
    }
}

// Global Ollama AI instance
$ollama = OllamaAI::getInstance();
