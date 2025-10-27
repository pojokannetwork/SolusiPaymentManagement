<?php
// SolusiPaymentManagement Application Configuration

define('APP_NAME', 'SolusiPaymentManagement');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // production, staging, development
define('APP_URL', 'http://localhost:8888'); // Change in production
define('APP_TIMEZONE', 'Asia/Jakarta');

// Security settings
define('SESSION_LIFETIME', 1800); // 30 minutes
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('RATE_LIMIT_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_LOCK_TIME', 300); // 5 minutes

// File upload settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// API settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute per IP

// Payment gateway callback URL
define('PAYMENT_CALLBACK_URL', APP_URL . '/api/payment_callback.php');

// Ollama AI settings
define('OLLAMA_HOST', 'http://localhost:11434');
define('OLLAMA_MODEL', 'llama3');
define('OLLAMA_TIMEOUT', 30); // seconds

// OpenStreetMap settings
define('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search');
define('NOMINATIM_REVERSE_URL', 'https://nominatim.openstreetmap.org/reverse');
define('NOMINATIM_USER_AGENT', 'SolusiPaymentManagement/1.0');

// MikroTik settings
define('MIKROTIK_DEFAULT_PORT', 8728);
define('MIKROTIK_TLS_PORT', 8729);
define('MIKROTIK_TIMEOUT', 10); // seconds

// RADIUS settings
define('RADIUS_COA_PORT', 3799);
define('RADIUS_TIMEOUT', 5); // seconds

// Database settings (see database.php for connection details)

// Logging
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/../logs/app.log');

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}

// Autoload classes (PSR-4 style)
spl_autoload_register(function ($class) {
    $prefix = 'SolusiPaymentManagement\\';
    $base_dir = __DIR__ . '/../includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
