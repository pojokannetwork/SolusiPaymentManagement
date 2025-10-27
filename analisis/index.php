<?php
// SolusiPaymentManagement Main Entry Point

require_once 'includes/bootstrap.php';

// Route handling
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

// Remove query string and script name to get the path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace(dirname($scriptName), '', $path);
$path = ltrim($path, '/');

// Default route
if (empty($path) || $path === 'index.php') {
    $path = '';
}

// Route to appropriate handler
if (preg_match('#^api/#', $path)) {
    // API routes
    handleApiRoute($path);
} elseif (preg_match('#^admin/#', $path)) {
    // Admin routes
    handleAdminRoute($path);
} elseif (preg_match('#^employee/#', $path)) {
    // Employee routes
    handleEmployeeRoute($path);
} elseif (preg_match('#^customer/#', $path)) {
    // Customer routes
    handleCustomerRoute($path);
} else {
    // Public routes
    handlePublicRoute($path);
}

function handleApiRoute($path) {
    // Remove 'api/' prefix
    $apiPath = substr($path, 4);

    // Parse API path
    $parts = explode('/', $apiPath);
    $version = $parts[0] ?? 'v1';
    $section = $parts[1] ?? '';
    $action = $parts[2] ?? '';

    // Build file path
    if ($section === 'payment_callback') {
        $filePath = "api/payment_callback.php";
    } elseif ($section === 'public' && $action) {
        $filePath = "api/public/{$action}.php";
    } elseif ($section && $action) {
        $filePath = "api/{$section}/{$action}.php";
    } else {
        $filePath = "api/{$section}.php";
    }

    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
    }
}

function handleAdminRoute($path) {
    // Check authentication
    RouterGuard::getInstance()->requireAuth();

    // Check if user is admin
    $user = getCurrentUser();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        include 'templates/403.php';
        exit;
    }

    // Remove 'admin/' prefix
    $adminPath = substr($path, 6);

    if (empty($adminPath)) {
        $adminPath = 'dashboard.php';
    }

    $filePath = "admin/{$adminPath}";

    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        include 'templates/404.php';
    }
}

function handleEmployeeRoute($path) {
    // Check authentication
    RouterGuard::getInstance()->requireAuth();

    // Check if user is employee
    $user = getCurrentUser();
    if ($user['role'] !== 'employee') {
        http_response_code(403);
        include 'templates/403.php';
        exit;
    }

    // Remove 'employee/' prefix
    $employeePath = substr($path, 9);

    if (empty($employeePath)) {
        $employeePath = 'dashboard.php';
    }

    $filePath = "employee/{$employeePath}";

    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        include 'templates/404.php';
    }
}

function handleCustomerRoute($path) {
    // Check authentication
    RouterGuard::getInstance()->requireAuth();

    // Check if user is customer
    $user = getCurrentUser();
    if ($user['role'] !== 'customer') {
        http_response_code(403);
        include 'templates/403.php';
        exit;
    }

    // Remove 'customer/' prefix
    $customerPath = substr($path, 9);

    if (empty($customerPath)) {
        $customerPath = 'dashboard.php';
    }

    $filePath = "customer/{$customerPath}";

    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        include 'templates/404.php';
    }
}

function handlePublicRoute($path) {
    if (empty($path)) {
        // Login page
        include 'templates/login.php';
    } elseif ($path === 'login') {
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'api/public/login.php';
        } else {
            include 'templates/login.php';
        }
    } elseif ($path === 'logout') {
        RouterGuard::getInstance()->logout();
        header('Location: /');
        exit;
    } elseif (preg_match('#^assets/#', $path)) {
        // Handle static assets
        $filePath = $path;
        if (file_exists($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon'
            ];

            if (isset($mimeTypes[$extension])) {
                header('Content-Type: ' . $mimeTypes[$extension]);
                readfile($filePath);
                exit;
            }
        }
        http_response_code(404);
        include 'templates/404.php';
    } else {
        http_response_code(404);
        include 'templates/404.php';
    }
}
