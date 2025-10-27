<?php
// SolusiPaymentManagement Admin API - General Settings

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    handleGetGeneralSettings();
} elseif ($method === 'POST') {
    handleUpdateGeneralSettings();
} else {
    errorResponse('Method not allowed', 405);
}

function handleGetGeneralSettings() {
    $settings = [
        'app_name' => getSetting('app_name', APP_NAME),
        'timezone' => getSetting('timezone', APP_TIMEZONE),
        'currency' => getSetting('currency', 'IDR'),
        'admin_theme' => getSetting('admin_theme', 'tabler'),
        'session_lifetime' => (int) getSetting('session_lifetime_minutes', SESSION_LIFETIME / 60),
        'login_attempts' => (int) getSetting('login_attempts', RATE_LIMIT_LOGIN_ATTEMPTS),
    ];

    successResponse(['settings' => $settings]);
}

function handleUpdateGeneralSettings() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $updated = [];

    if (isset($input['app_name'])) {
        $value = trim($input['app_name']);
        if ($value === '') {
            errorResponse('Application name cannot be empty', 400);
        }
        setSetting('app_name', sanitizeInput($value));
        $updated[] = 'app_name';
    }

    if (isset($input['timezone'])) {
        $allowedTimezones = ['Asia/Jakarta', 'UTC'];
        $timezone = $input['timezone'];
        if (!in_array($timezone, $allowedTimezones, true)) {
            errorResponse('Invalid timezone selection', 400);
        }
        setSetting('timezone', $timezone);
        date_default_timezone_set($timezone);
        $updated[] = 'timezone';
    }

    if (isset($input['currency'])) {
        $allowedCurrencies = ['IDR', 'USD'];
        $currency = $input['currency'];
        if (!in_array($currency, $allowedCurrencies, true)) {
            errorResponse('Invalid currency selection', 400);
        }
        setSetting('currency', $currency);
        $updated[] = 'currency';
    }

    if (isset($input['admin_theme'])) {
        $allowedThemes = ['default', 'tabler', 'sneat', 'materio'];
        $theme = $input['admin_theme'];
        if (!in_array($theme, $allowedThemes, true)) {
            errorResponse('Invalid admin theme', 400);
        }
        setSetting('admin_theme', $theme);
        $updated[] = 'admin_theme';
    }

    if (isset($input['session_lifetime'])) {
        $minutes = (int) $input['session_lifetime'];
        if ($minutes < 5 || $minutes > 480) {
            errorResponse('Session lifetime must be between 5 and 480 minutes', 400);
        }
        setSetting('session_lifetime_minutes', $minutes);
        $updated[] = 'session_lifetime_minutes';
    }

    if (isset($input['login_attempts'])) {
        $attempts = (int) $input['login_attempts'];
        if ($attempts < 3 || $attempts > 10) {
            errorResponse('Login attempts limit must be between 3 and 10', 400);
        }
        setSetting('login_attempts', $attempts);
        $updated[] = 'login_attempts';
    }

    global $logger;
    $logger->log('settings_update', [
        'section' => 'general',
        'updated_settings' => $updated
    ]);

    successResponse(['updated' => $updated], 'General settings updated successfully');
}
