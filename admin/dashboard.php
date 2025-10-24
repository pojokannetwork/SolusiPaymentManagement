<?php
// SolusiPaymentManagement Admin Dashboard

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.dashboard');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }
        .main-content {
            margin-left: 0;
        }
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar position-fixed" style="width: 250px;">
        <div class="p-3">
            <h5 class="text-white mb-4">
                <i class="fas fa-cogs me-2"></i>
                SolusiPaymentManagement
            </h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="invoices.php">
                        <i class="fas fa-file-invoice me-2"></i>Invoices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-credit-card me-2"></i>Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payment_gateways.php">
                        <i class="fas fa-money-check me-2"></i>Payment Gateways
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers_map.php">
                        <i class="fas fa-map-marked-alt me-2"></i>Customer Map
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard</h2>
                <div>
                    <span class="text-muted">Welcome, <?php echo sanitizeOutput($user['nama']); ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4" id="stats-cards">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Customers</h6>
                                    <h3 id="total-customers">-</h3>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Active Customers</h6>
                                    <h3 id="active-customers">-</h3>
                                </div>
                                <i class="fas fa-user-check fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Pending Invoices</h6>
                                    <h3 id="pending-invoices">-</h3>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Revenue This Month</h6>
                                    <h3 id="monthly-revenue">-</h3>
                                </div>
                                <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Revenue Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Customer Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="customerStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Payments</h5>
                        </div>
                        <div class="card-body">
                            <div id="recent-payments" class="list-group list-group-flush">
                                <div class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity" class="list-group list-group-flush">
                                <div class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        // CSRF token for AJAX requests
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });

        // Load dashboard data
        $(document).ready(function() {
            loadStats();
            loadCharts();
            loadRecentData();
        });

        function loadStats() {
            $.get('/api/admin/dashboard/stats')
                .done(function(response) {
                    if (response.success) {
                        $('#total-customers').text(response.data.total_customers);
                        $('#active-customers').text(response.data.active_customers);
                        $('#pending-invoices').text(response.data.pending_invoices);
                        $('#monthly-revenue').text(formatCurrency(response.data.monthly_revenue));
                    }
                })
                .fail(function() {
                    console.error('Failed to load stats');
                });
        }

        function loadCharts() {
            // Revenue chart
            $.get('/api/admin/dashboard/revenue_chart')
                .done(function(response) {
                    if (response.success) {
                        const ctx = document.getElementById('revenueChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: response.data,
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return formatCurrency(value);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });

            // Customer status chart
            $.get('/api/admin/dashboard/customer_status_chart')
                .done(function(response) {
                    if (response.success) {
                        const ctx = document.getElementById('customerStatusChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: response.data,
                            options: {
                                responsive: true
                            }
                        });
                    }
                });
        }

        function loadRecentData() {
            // Recent payments
            $.get('/api/admin/dashboard/recent_payments')
                .done(function(response) {
                    if (response.success) {
                        let html = '';
                        response.data.forEach(function(payment) {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${payment.customer_name}</h6>
                                        <small>${formatCurrency(payment.amount)}</small>
                                    </div>
                                    <p class="mb-1">${payment.invoice_number}</p>
                                    <small class="text-muted">${formatDateTime(payment.paid_at)}</small>
                                </div>
                            `;
                        });
                        $('#recent-payments').html(html);
                    }
                });

            // Recent activity
            $.get('/api/admin/dashboard/recent_activity')
                .done(function(response) {
                    if (response.success) {
                        let html = '';
                        response.data.forEach(function(activity) {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${activity.action}</h6>
                                        <small class="text-muted">${formatDateTime(activity.created_at)}</small>
                                    </div>
                                    <p class="mb-1">${activity.user_name || 'System'}</p>
                                </div>
                            `;
                        });
                        $('#recent-activity').html(html);
                    }
                });
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        }

        function formatDateTime(datetime) {
            return new Date(datetime).toLocaleString('id-ID');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                $.post('/api/public/logout')
                    .done(function() {
                        window.location.href = '/';
                    });
            }
        }
    </script>
</body>
</html>
