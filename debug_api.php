<?php
// Debug API Access
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Debug Test</h1>\n";
echo "<hr>\n";

// Test 1: Bootstrap
echo "<h3>Test 1: Bootstrap Loading</h3>\n";
try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✅ Bootstrap loaded successfully<br>\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

// Test 2: Database Connection
echo "<h3>Test 2: Database Connection</h3>\n";
if (isset($db)) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM pengguna");
        echo "✅ Database connected. Users count: " . $result['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>\n";
    }
}

// Test 3: Session
echo "<h3>Test 3: Session Status</h3>\n";
echo "Session ID: " . session_id() . "<br>\n";
echo "Logged in: " . (isset($_SESSION['user_id']) ? 'Yes (User ID: ' . $_SESSION['user_id'] . ')' : 'No') . "<br>\n";

// Test 4: API File Existence
echo "<h3>Test 4: API Files Check</h3>\n";
$apiFiles = [
    'api/admin/invoices.php',
    'api/admin/transactions.php',
    'api/admin/employees.php',
    'api/admin/payroll.php',
    'api/admin/assets.php',
    'api/admin/payment_gateways.php'
];

foreach ($apiFiles as $file) {
    $exists = file_exists($file);
    $readable = is_readable($file);
    echo ($exists && $readable ? '✅' : '❌') . " $file - ";
    echo "Exists: " . ($exists ? 'Yes' : 'No') . ", ";
    echo "Readable: " . ($readable ? 'Yes' : 'No') . "<br>\n";
}

// Test 5: Direct API Call (if logged in)
if (isset($_SESSION['user_id'])) {
    echo "<h3>Test 5: Direct API Call</h3>\n";

    $_GET['action'] = 'summary';
    ob_start();
    try {
        include 'api/admin/invoices.php';
        $output = ob_get_clean();
        $json = json_decode($output, true);
        if ($json) {
            echo "✅ API Response: <pre>" . print_r($json, true) . "</pre>\n";
        } else {
            echo "❌ Invalid JSON response: " . htmlspecialchars($output) . "<br>\n";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ API Error: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "<h3>Test 5: Skipped (not logged in)</h3>\n";
    echo "<a href='/login.php'>Login first to test API calls</a><br>\n";
}

// Test 6: Permission Check
echo "<h3>Test 6: Permission System</h3>\n";
if (class_exists('RouterGuard')) {
    echo "✅ RouterGuard class exists<br>\n";
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
        if ($user) {
            echo "✅ Current user: " . $user['nama'] . " (" . $user['email'] . ")<br>\n";
        } else {
            echo "❌ No current user<br>\n";
        }
    }
} else {
    echo "❌ RouterGuard class not found<br>\n";
}

echo "<hr>\n";
echo "<p><strong>Note:</strong> If you're not logged in, <a href='/login.php'>login as admin</a> first to test API calls.</p>\n";
