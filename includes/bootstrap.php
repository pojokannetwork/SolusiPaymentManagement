<?php
// SolusiPaymentManagement Bootstrap File
// Include this at the top of all PHP files

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Include configuration files
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Include core includes
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/router_guard.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/activity_logger.php';

// Include adapters and integrations
require_once __DIR__ . '/ollama_ai.php';
require_once __DIR__ . '/openstreetmap.php';
require_once __DIR__ . '/mikrotik_api.php';
require_once __DIR__ . '/radius_sql_coa.php';

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
