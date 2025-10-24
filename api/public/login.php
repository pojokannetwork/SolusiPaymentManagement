<?php
// SolusiPaymentManagement Public API - Login

require_once __DIR__ . '/../../includes/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    if (isApiRequest()) {
        errorResponse('Method not allowed', 405);
    } else {
        header('Location: /');
        exit;
    }
}

// Get input data
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// Validate CSRF token for non-API requests
if (!isApiRequest() && !validateCsrfToken($csrfToken)) {
    header('Location: /?message=' . urlencode('Invalid security token. Please try again.'));
    exit;
}

if (empty($email) || empty($password)) {
    if (isApiRequest()) {
        errorResponse('Email and password are required', 400);
    } else {
        header('Location: /?message=' . urlencode('Email and password are required'));
        exit;
    }
}

if (!validateEmail($email)) {
    if (isApiRequest()) {
        errorResponse('Invalid email format', 400);
    } else {
        header('Location: /?message=' . urlencode('Invalid email format'));
        exit;
    }
}

// Check rate limiting
if (!checkRateLimit("login_{$email}")) {
    $message = 'Too many login attempts. Please try again later.';
    if (isApiRequest()) {
        errorResponse($message, 429);
    } else {
        header('Location: /?message=' . urlencode($message));
        exit;
    }
}

// Attempt login
$guard = RouterGuard::getInstance();
$loginResult = $guard->login($email, $password);

if (!$loginResult['success']) {
    incrementRateLimit("login_{$email}");
    if (isApiRequest()) {
        errorResponse($loginResult['message'], 401);
    } else {
        header('Location: /?message=' . urlencode($loginResult['message']));
        exit;
    }
}

// Get user role from login result
$user = $loginResult['user'];
$role = $user['role'];

// Reset rate limit on successful login
resetRateLimit("login_{$email}");

// Determine redirect URL based on role
$redirectUrls = [
    'admin' => '/admin/',
    'employee' => '/employee/',
    'customer' => '/customer/'
];

$redirect = $redirectUrls[$role] ?? '/';

if (isApiRequest()) {
    // Generate CSRF token
    $csrfToken = getCsrfToken();
    
    // Return success response
    successResponse([
        'redirect' => $redirect,
        'csrf_token' => $csrfToken,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'nama' => $user['nama'],
            'role' => $user['role']
        ]
    ]);
} else {
    header('Location: ' . $redirect);
    exit;
}
