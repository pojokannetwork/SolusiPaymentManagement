<?php
/**
 * System Test Page
 * Tests all newly implemented features and APIs
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/includes/bootstrap.php';

// Check authentication
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - Solusi Payment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-section { margin-bottom: 30px; }
        .test-item { padding: 15px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .test-item.success { background-color: #d4edda; border-color: #c3e6cb; }
        .test-item.error { background-color: #f8d7da; border-color: #f5c6cb; }
        .test-item.pending { background-color: #fff3cd; border-color: #ffeeba; }
        .api-response { background: #f8f9fa; padding: 10px; border-radius: 3px; margin-top: 10px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4"><i class="fas fa-vial me-2"></i>System Test Page</h1>

    <?php if (!$isLoggedIn): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Not Logged In!</strong>
            <a href="/" class="alert-link">Please login</a> to test API endpoints that require authentication.
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Logged In!</strong> User ID: <?php echo $_SESSION['user_id']; ?>
        </div>
    <?php endif; ?>

    <!-- File System Tests -->
    <div class="test-section">
        <h3>1. File System Tests</h3>
        <?php
        $files_to_check = [
            'API Files' => [
                '/api/admin/invoices.php',
                '/api/admin/transactions.php',
                '/api/admin/employees.php',
                '/api/admin/payroll.php',
                '/api/admin/assets.php',
                '/api/admin/payment_gateways.php'
            ],
            'Admin Pages' => [
                '/admin/invoices.php',
                '/admin/transactions.php',
                '/admin/employees.php',
                '/admin/payroll.php',
                '/admin/assets.php'
            ],
            'Payment Gateway Adapters' => [
                '/includes/pg_adapter/Tripay.php',
                '/includes/pg_adapter/Duitku.php',
                '/includes/pg_adapter/Doku.php',
                '/includes/pg_adapter/Ovo.php',
                '/includes/pg_adapter/Gopay.php'
            ]
        ];

        foreach ($files_to_check as $category => $files) {
            echo "<h5>$category</h5>";
            foreach ($files as $file) {
                $fullPath = __DIR__ . $file;
                $exists = file_exists($fullPath);
                $readable = is_readable($fullPath);
                $class = ($exists && $readable) ? 'success' : 'error';
                $icon = ($exists && $readable) ? 'check-circle' : 'times-circle';

                echo "<div class='test-item $class'>";
                echo "<i class='fas fa-$icon me-2'></i>";
                echo "<strong>$file</strong> - ";
                echo "Exists: " . ($exists ? 'Yes' : 'No') . ", ";
                echo "Readable: " . ($readable ? 'Yes' : 'No');
                if ($exists) {
                    $owner = posix_getpwuid(fileowner($fullPath));
                    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                    echo " (Owner: {$owner['name']}, Perms: $perms)";
                }
                echo "</div>";
            }
        }
        ?>
    </div>

    <!-- Database Tests -->
    <div class="test-section">
        <h3>2. Database Tests</h3>
        <?php
        $tables = [
            'faktur' => 'Invoices',
            'invoice_items' => 'Invoice Items',
            'transaksi' => 'Transactions',
            'karyawan' => 'Employees',
            'payroll' => 'Payroll',
            'aset' => 'Assets',
            'payment_gateways' => 'Payment Gateways'
        ];

        foreach ($tables as $table => $label) {
            try {
                $result = $db->fetchOne("SELECT COUNT(*) as count FROM $table");
                $count = $result['count'];
                echo "<div class='test-item success'>";
                echo "<i class='fas fa-check-circle me-2'></i>";
                echo "<strong>$label ($table)</strong> - Records: $count";
                echo "</div>";
            } catch (Exception $e) {
                echo "<div class='test-item error'>";
                echo "<i class='fas fa-times-circle me-2'></i>";
                echo "<strong>$label ($table)</strong> - Error: " . $e->getMessage();
                echo "</div>";
            }
        }
        ?>
    </div>

    <!-- Permission Tests -->
    <div class="test-section">
        <h3>3. Permission Tests</h3>
        <?php
        if ($isLoggedIn) {
            $guard = RouterGuard::getInstance();
            $permissions = [
                'admin.invoices',
                'admin.transactions',
                'admin.employees',
                'admin.payroll',
                'admin.assets',
                'admin.settings'
            ];

            foreach ($permissions as $permission) {
                $hasPermission = $guard->hasPermission($permission);
                $class = $hasPermission ? 'success' : 'error';
                $icon = $hasPermission ? 'check-circle' : 'times-circle';

                echo "<div class='test-item $class'>";
                echo "<i class='fas fa-$icon me-2'></i>";
                echo "<strong>$permission</strong> - " . ($hasPermission ? 'Granted' : 'Denied');
                echo "</div>";
            }
        } else {
            echo "<div class='test-item pending'>";
            echo "<i class='fas fa-exclamation-triangle me-2'></i>";
            echo "Login required to test permissions";
            echo "</div>";
        }
        ?>
    </div>

    <!-- API Endpoint Tests (only if logged in) -->
    <?php if ($isLoggedIn): ?>
    <div class="test-section">
        <h3>4. API Endpoint Tests</h3>
        <p class="text-muted">Click the buttons below to test each API endpoint:</p>

        <div class="row">
            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('invoices', 'summary')">
                    <i class="fas fa-file-invoice me-2"></i>Test Invoices API
                </button>
                <div id="result-invoices" class="api-response d-none"></div>
            </div>

            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('transactions', 'summary')">
                    <i class="fas fa-exchange-alt me-2"></i>Test Transactions API
                </button>
                <div id="result-transactions" class="api-response d-none"></div>
            </div>

            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('employees', 'summary')">
                    <i class="fas fa-users me-2"></i>Test Employees API
                </button>
                <div id="result-employees" class="api-response d-none"></div>
            </div>

            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('payroll', 'summary')">
                    <i class="fas fa-money-bill-wave me-2"></i>Test Payroll API
                </button>
                <div id="result-payroll" class="api-response d-none"></div>
            </div>

            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('assets', 'summary')">
                    <i class="fas fa-box me-2"></i>Test Assets API
                </button>
                <div id="result-assets" class="api-response d-none"></div>
            </div>

            <div class="col-md-4 mb-3">
                <button class="btn btn-primary w-100" onclick="testAPI('payment_gateways', 'list')">
                    <i class="fas fa-credit-card me-2"></i>Test Payment Gateways API
                </button>
                <div id="result-payment_gateways" class="api-response d-none"></div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-success btn-lg" onclick="testAllAPIs()">
                <i class="fas fa-play me-2"></i>Test All APIs
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- System Information -->
    <div class="test-section">
        <h3>5. System Information</h3>
        <div class="test-item">
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?><br>
            <strong>Current Directory:</strong> <?php echo __DIR__; ?><br>
            <strong>Session ID:</strong> <?php echo session_id(); ?><br>
            <strong>Database Path:</strong> <?php echo DB_PATH; ?>
        </div>
    </div>

    <div class="mt-4 mb-5">
        <a href="/" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = '<?php echo getCsrfToken(); ?>';

$.ajaxSetup({
    headers: {
        'X-CSRF-Token': csrfToken
    }
});

function testAPI(module, action) {
    const resultDiv = $('#result-' + module);
    resultDiv.removeClass('d-none').html('<i class="fas fa-spinner fa-spin"></i> Loading...');

    $.ajax({
        url: '/api/admin/' + module + '.php?action=' + action,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            resultDiv.html(
                '<strong class="text-success"><i class="fas fa-check-circle"></i> Success!</strong><br>' +
                '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
            );
        },
        error: function(xhr, status, error) {
            resultDiv.html(
                '<strong class="text-danger"><i class="fas fa-times-circle"></i> Error!</strong><br>' +
                '<strong>Status:</strong> ' + xhr.status + '<br>' +
                '<strong>Error:</strong> ' + error + '<br>' +
                '<pre>' + xhr.responseText + '</pre>'
            );
        }
    });
}

function testAllAPIs() {
    const modules = ['invoices', 'transactions', 'employees', 'payroll', 'assets'];
    const actions = {
        'invoices': 'summary',
        'transactions': 'summary',
        'employees': 'summary',
        'payroll': 'summary',
        'assets': 'summary'
    };

    modules.forEach(module => {
        setTimeout(() => testAPI(module, actions[module]), 200);
    });

    setTimeout(() => testAPI('payment_gateways', 'list'), 1200);
}
</script>
</body>
</html>
