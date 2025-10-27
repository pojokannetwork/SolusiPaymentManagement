<?php
// SolusiPaymentManagement Admin API - AI Settings

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    handleGetAiSettings();
} elseif ($method === 'POST') {
    handleUpdateAiSettings();
} else {
    errorResponse('Method not allowed', 405);
}

function handleGetAiSettings() {
    $settings = [
        'ollama_host' => getSetting('ollama_host', OLLAMA_HOST),
        'ollama_model' => getSetting('ollama_model', OLLAMA_MODEL),
    ];

    successResponse(['settings' => $settings]);
}

function handleUpdateAiSettings() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $updated = [];

    if (isset($input['ollama_host'])) {
        $host = trim($input['ollama_host']);
        if ($host === '') {
            errorResponse('Ollama host cannot be empty', 400);
        }
        setSetting('ollama_host', $host);
        $updated[] = 'ollama_host';
    }

    if (isset($input['ollama_model'])) {
        $model = trim($input['ollama_model']);
        if ($model === '') {
            errorResponse('AI model cannot be empty', 400);
        }
        setSetting('ollama_model', $model);
        $updated[] = 'ollama_model';
    }

    $testResult = null;
    $storedHost = getSetting('ollama_host', OLLAMA_HOST);
    $storedModel = getSetting('ollama_model', OLLAMA_MODEL);

    if (isset($input['ollama_host']) || isset($input['ollama_model'])) {
        // Refresh singleton configuration after updating settings
        if (class_exists('OllamaAI')) {
            OllamaAI::getInstance()->refreshConfig();
        }
    }

    if (!empty($input['test_connection'])) {
        $hostToTest = $input['ollama_host'] ?? $storedHost;
        $testResult = performOllamaPing($hostToTest);
    }

    global $logger;
    $logger->log('settings_update', [
        'section' => 'ai',
        'updated_settings' => $updated,
        'test_result' => $testResult
    ]);

    $response = ['updated' => $updated];
    if ($testResult !== null) {
        $response['test_result'] = $testResult;
    }

    successResponse($response, 'AI settings updated successfully');
}

function performOllamaPing($host) {
    $url = rtrim($host, '/') . '/api/tags';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}", 'response' => $response];
    }

    return ['success' => true];
}
