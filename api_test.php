<?php
// Simple API Test Script
session_start();
require_once 'includes/bootstrap.php';

// Simulate logged in admin user
$_SESSION['user_id'] = 1; // Admin user ID
$_SESSION['authenticated'] = true;

echo "<h2>API Test Results</h2>\n";
echo "<pre>\n";

// Test 1: Invoices Summary
echo "TEST 1: Invoices Summary API\n";
echo "-----------------------------\n";
$_GET['action'] = 'summary';
ob_start();
include 'api/admin/invoices.php';
$result = ob_get_clean();
echo "Result: " . $result . "\n\n";

// Test 2: Transactions Summary
echo "TEST 2: Transactions Summary API\n";
echo "-----------------------------\n";
$_GET['action'] = 'summary';
ob_start();
include 'api/admin/transactions.php';
$result = ob_get_clean();
echo "Result: " . $result . "\n\n";

// Test 3: Employees Summary
echo "TEST 3: Employees Summary API\n";
echo "-----------------------------\n";
$_GET['action'] = 'summary';
ob_start();
include 'api/admin/employees.php';
$result = ob_get_clean();
echo "Result: " . $result . "\n\n";

echo "</pre>";
