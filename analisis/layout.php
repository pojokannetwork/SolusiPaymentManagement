<?php
/**
 * Main Layout Template for SolusiPaymentManagement
 * This template provides a consistent layout for all pages
 */

// Ensure bootstrap is loaded
if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/../includes/bootstrap.php';
}

$user = getCurrentUser();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-width: 250px;
            --navbar-height: 56px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            background: #343a40;
            min-height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1040;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 0.75rem 1rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #0d6efd;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
            border-left-color: #fff;
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h5 {
            margin: 0;
            font-weight: 600;
            color: #fff;
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex-grow: 1;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        /* Top Navbar for Mobile */
        .top-navbar {
            background: #343a40;
            color: #fff;
            padding: 0.5rem 1rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--navbar-height);
            z-index: 1030;
            display: none;
        }

        .top-navbar .navbar-brand {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .top-navbar .btn-menu {
            background: #0d6efd;
            border: none;
            color: #fff;
            padding: 0.5rem 0.75rem;
        }

        .top-navbar .btn-menu:hover {
            background: #0b5ed7;
            color: #fff;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem 1rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .page-header h2 {
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-card .card-title {
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Form Styles */
        .form-control, .form-select {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Button Styles */
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: 100%;
                max-width: var(--sidebar-width);
                height: 100vh;
                top: var(--navbar-height);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .top-navbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .main-content {
                margin-left: 0;
                padding-top: calc(var(--navbar-height) + 1rem);
            }

            .page-header h1,
            .page-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Utility Classes */
        .text-muted-custom {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .badge-custom {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        <?php if (isset($additionalCss)): ?>
        <?php echo $additionalCss; ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Top Navbar for Mobile -->
    <nav class="top-navbar">
        <a class="navbar-brand" href="#">
            <i class="fas fa-cogs me-2"></i>
            SPM
        </a>
        <button class="btn btn-menu" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5>
                <i class="fas fa-cogs me-2"></i>
                SolusiPaymentManagement
            </h5>
        </div>

        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <?php if ($user['role'] === 'admin'): ?>
                    <!-- Admin Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/dashboard') !== false ? 'active' : ''; ?>" href="/admin/dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/customers') !== false ? 'active' : ''; ?>" href="/admin/customers">
                            <i class="fas fa-users me-2"></i>Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/invoices') !== false ? 'active' : ''; ?>" href="/admin/invoices">
                            <i class="fas fa-file-invoice me-2"></i>Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/transactions') !== false ? 'active' : ''; ?>" href="/admin/transactions">
                            <i class="fas fa-credit-card me-2"></i>Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/payment_gateways') !== false ? 'active' : ''; ?>" href="/admin/payment_gateways">
                            <i class="fas fa-money-check me-2"></i>Payment Gateways
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/customers_map') !== false ? 'active' : ''; ?>" href="/admin/customers_map">
                            <i class="fas fa-map-marked-alt me-2"></i>Customer Map
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/employees') !== false ? 'active' : ''; ?>" href="/admin/employees">
                            <i class="fas fa-user-tie me-2"></i>Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/assets') !== false ? 'active' : ''; ?>" href="/admin/assets">
                            <i class="fas fa-boxes me-2"></i>Assets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/payroll') !== false ? 'active' : ''; ?>" href="/admin/payroll">
                            <i class="fas fa-money-bill me-2"></i>Payroll
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/taxes') !== false ? 'active' : ''; ?>" href="/admin/taxes">
                            <i class="fas fa-percent me-2"></i>Taxes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/admin/settings') !== false ? 'active' : ''; ?>" href="/admin/settings">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                <?php elseif ($user['role'] === 'employee'): ?>
                    <!-- Employee Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/employee/dashboard') !== false ? 'active' : ''; ?>" href="/employee/dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/employee/attendance') !== false ? 'active' : ''; ?>" href="/employee/attendance">
                            <i class="fas fa-calendar-check me-2"></i>Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/employee/leave') !== false ? 'active' : ''; ?>" href="/employee/leave">
                            <i class="fas fa-calendar-times me-2"></i>Leave Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/employee/payroll') !== false ? 'active' : ''; ?>" href="/employee/payroll">
                            <i class="fas fa-money-bill me-2"></i>Payroll
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/employee/profile') !== false ? 'active' : ''; ?>" href="/employee/profile">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                    </li>
                <?php elseif ($user['role'] === 'customer'): ?>
                    <!-- Customer Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/customer/dashboard') !== false ? 'active' : ''; ?>" href="/customer/dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/customer/invoices') !== false ? 'active' : ''; ?>" href="/customer/invoices">
                            <i class="fas fa-file-invoice me-2"></i>Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/customer/payments') !== false ? 'active' : ''; ?>" href="/customer/payments">
                            <i class="fas fa-credit-card me-2"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($currentPath, '/customer/profile') !== false ? 'active' : ''; ?>" href="/customer/profile">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="sidebar-footer">
            <a class="nav-link text-danger" href="/logout" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <?php if (isset($pageTitle)): ?>
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
                </div>
                <div class="text-muted-custom">
                    Welcome, <strong><?php echo htmlspecialchars($user['nama']); ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Content -->
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Toggle for Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        });

        // Close sidebar when a link is clicked on mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    document.getElementById('sidebar').classList.remove('show');
                }
            });
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        <?php if (isset($additionalJs)): ?>
        <?php echo $additionalJs; ?>
        <?php endif; ?>
    </script>
</body>
</html>

