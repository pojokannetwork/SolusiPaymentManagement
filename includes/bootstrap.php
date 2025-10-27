<?php
// SolusiPaymentManagement Bootstrap File
// Include this at the top of all PHP files

// Include configuration files first
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Include core includes
spl_autoload_register(function ($class) {
    // Autoload PEAR2 libraries bundled under includes/thirdparty
    if (strpos($class, 'PEAR2\\') !== 0) {
        return;
    }

    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $baseDirs = [
        __DIR__ . '/thirdparty/Net_RouterOS/src/',
        __DIR__ . '/thirdparty/Net_Transmitter/src/',
    ];

    foreach ($baseDirs as $baseDir) {
        $file = $baseDir . $relativePath;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/router_guard.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/activity_logger.php';

// Include adapters and integrations
require_once __DIR__ . '/ollama_ai.php';
require_once __DIR__ . '/openstreetmap.php';
require_once __DIR__ . '/mikrotik_api.php';
require_once __DIR__ . '/mikrotik_service.php';
require_once __DIR__ . '/radius_sql_coa.php';
require_once __DIR__ . '/olt_monitoring.php';
require_once __DIR__ . '/whatsapp_notifications.php';
require_once __DIR__ . '/whatsapp_queue.php';
require_once __DIR__ . '/fiber_management.php';

// Include payment gateway adapters
require_once __DIR__ . '/pg_adapter/PgAdapter.php';
require_once __DIR__ . '/pg_adapter/Midtrans.php';
require_once __DIR__ . '/pg_adapter/Xendit.php';
// Other adapters can be included as needed

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Create logs directory if it doesn't exist
$logDir = dirname(LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Initialize database connection
global $db;
$db = Database::getInstance();

// Apply dynamic settings stored in database
$storedTimezone = getSetting('timezone', APP_TIMEZONE);
if (!empty($storedTimezone) && $storedTimezone !== date_default_timezone_get()) {
    date_default_timezone_set($storedTimezone);
}

if (!defined('APP_DISPLAY_NAME')) {
    define('APP_DISPLAY_NAME', getSetting('app_name', APP_NAME));
}

if (!defined('APP_CURRENCY')) {
    define('APP_CURRENCY', getSetting('currency', 'IDR'));
}

// Global error handler for production
if (APP_ENV !== 'development') {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        return true;
    });

    set_exception_handler(function($exception) {
        error_log("Uncaught exception: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    });
}

// Function to sanitize output
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Function to send JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Function to send error response
function errorResponse($message, $statusCode = 400, $errors = []) {
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $statusCode);
}

// Function to send success response
function successResponse($data = [], $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}
