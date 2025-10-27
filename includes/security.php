<?php
// SolusiPaymentManagement Security Functions

// Generate secure password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate secure token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number (Indonesian format)
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(\+62|62|0)[8-9][0-9]{7,11}$/', $phone);
}

// Rate limiting for login attempts
function checkRateLimit($identifier, $maxAttempts = RATE_LIMIT_LOGIN_ATTEMPTS, $lockTime = RATE_LIMIT_LOCK_TIME) {
    $storedAttempts = getSetting('login_attempts', RATE_LIMIT_LOGIN_ATTEMPTS);
    if (is_numeric($storedAttempts)) {
        $attemptsVal = (int) $storedAttempts;
        if ($attemptsVal >= 3 && $attemptsVal <= 10) {
            $maxAttempts = $attemptsVal;
        }
    }

    $key = "rate_limit_{$identifier}";
    $attempts = $_SESSION[$key]['attempts'] ?? 0;
    $lastAttempt = $_SESSION[$key]['last_attempt'] ?? 0;

    // Reset if lock time has passed
    if (time() - $lastAttempt > $lockTime) {
        $attempts = 0;
    }

    if ($attempts >= $maxAttempts) {
        return false; // Rate limited
    }

    return true;
}

function incrementRateLimit($identifier) {
    $key = "rate_limit_{$identifier}";
    $_SESSION[$key]['attempts'] = ($_SESSION[$key]['attempts'] ?? 0) + 1;
    $_SESSION[$key]['last_attempt'] = time();
}

function resetRateLimit($identifier) {
    $key = "rate_limit_{$identifier}";
    unset($_SESSION[$key]);
}

// Validate file upload
function validateFileUpload($file, $allowedTypes = UPLOAD_ALLOWED_TYPES, $maxSize = UPLOAD_MAX_SIZE) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error'];
    }

    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }

    // Check file extension as additional security
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension'];
    }

    return ['valid' => true];
}

// Generate secure filename
function generateSecureFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return generateToken(16) . '.' . $extension;
}

// Encrypt sensitive data (simple obfuscation, not for high-security data)
function encryptData($data, $key = null) {
    if ($key === null) {
        $key = substr(hash('sha256', APP_NAME . 'secret_key'), 0, 32);
    }

    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Decrypt data
function decryptData($encryptedData, $key = null) {
    if ($key === null) {
        $key = substr(hash('sha256', APP_NAME . 'secret_key'), 0, 32);
    }

    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Check if request is from API
function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
}

// Get client IP (considering proxies)
function getClientIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Validate Indonesian ID number (NIK)
function validateNIK($nik) {
    return preg_match('/^[0-9]{16}$/', $nik);
}

// Validate currency amount
function validateAmount($amount) {
    return is_numeric($amount) && $amount >= 0 && $amount <= 999999999.99;
}

// Generate invoice number
function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Generate customer code
function generateCustomerCode() {
    return 'CUST-' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

// CSRF Token Functions
function getCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        if (isApiRequest()) {
            errorResponse('Invalid CSRF token', 403);
        } else {
            die('Invalid CSRF token');
        }
    }
}
