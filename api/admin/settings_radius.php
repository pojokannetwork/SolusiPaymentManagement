<?php
// SolusiPaymentManagement Admin API - RADIUS Settings

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handleGetRadiusSettings();
} elseif ($method === 'POST') {
    handleUpdateRadiusSettings();
} else {
    errorResponse('Method not allowed', 405);
}

function handleGetRadiusSettings() {
    $settings = [
        'radius_db_host' => getSetting('radius_db_host', 'localhost'),
        'radius_db_name' => getSetting('radius_db_name', 'radius'),
        'radius_db_user' => getSetting('radius_db_user', 'radius'),
        'radius_db_pass' => '', // Don't return password
        'nas_ip' => getSetting('nas_ip', '192.168.1.1'),
        'nas_secret' => '', // Don't return secret
        'profile_default' => getSetting('profile_default', 'default'),
        'profile_isolir' => getSetting('profile_isolir', 'ISOLIR'),
        'source_of_truth' => getSetting('source_of_truth', 'radius')
    ];

    successResponse(['settings' => $settings]);
}

function handleUpdateRadiusSettings() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Handle CoA test request explicitly
    if (!empty($input['send_coa']) && !empty($input['coa_username'])) {
        try {
            $radius = new RadiusSqlCoa([
                'nas_ip' => $input['nas_ip'] ?? getSetting('nas_ip'),
                'nas_secret' => !empty($input['nas_secret']) ? $input['nas_secret'] : decryptData(getSetting('nas_secret'))
            ]);
            $result = $radius->sendCoA(sanitizeInput($input['coa_username']));
            successResponse(['message' => 'CoA request sent', 'test_result' => $result]);
        } catch (Throwable $e) {
            errorResponse('Failed to send CoA: ' . $e->getMessage(), 500);
        }
    }

    $settings = [
        'radius_db_host',
        'radius_db_name',
        'radius_db_user',
        'nas_ip',
        'profile_default',
        'profile_isolir',
        'source_of_truth'
    ];

    $updated = [];

    foreach ($settings as $setting) {
        if (isset($input[$setting])) {
            setSetting($setting, sanitizeInput($input[$setting]));
            $updated[] = $setting;
        }
    }

    // Handle password separately (encrypt)
    if (!empty($input['radius_db_pass'])) {
        setSetting('radius_db_pass', encryptData($input['radius_db_pass']));
        $updated[] = 'radius_db_pass';
    }

    if (!empty($input['nas_secret'])) {
        setSetting('nas_secret', encryptData($input['nas_secret']));
        $updated[] = 'nas_secret';
    }

    // Test RADIUS connection if requested
    $testResult = null;
    if (isset($input['test_connection']) && $input['test_connection']) {
        $radius = new RadiusSqlCoa([
            'nas_ip' => $input['nas_ip'] ?? getSetting('nas_ip'),
            'nas_secret' => !empty($input['nas_secret']) ? $input['nas_secret'] : decryptData(getSetting('nas_secret'))
        ]);
        $testResult = $radius->test();
    }

    // Log activity
    global $logger;
    $logger->log('settings_update', [
        'section' => 'radius',
        'updated_settings' => $updated,
        'test_result' => $testResult
    ]);

    $response = ['message' => 'RADIUS settings updated successfully'];

    if ($testResult !== null) {
        $response['test_result'] = $testResult;
    }

    successResponse($response);
}
