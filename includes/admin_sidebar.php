<?php
// Admin Sidebar Menu List
// This file should only contain the <ul> list of navigation items.
// The parent page is responsible for the <nav> container and styling.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
function is_active($files){ global $script; return in_array($script, (array)$files, true) ? ' active' : ''; }
?>
<?php
// Safe permission checker with fallbacks so the menu doesn't disappear
$can = function($perm){
    try {
        $guard = RouterGuard::getInstance();
        $user = $guard->getCurrentUser();
        // Full access for admin role
        $role = $user['role'] ?? ($_SESSION['user_role'] ?? null);
        if ($role === 'admin') return true;

        if (function_exists('hasPermission')) {
            return (bool) hasPermission($perm);
        }
        return (bool) $guard->hasPermission($perm);
    } catch (Throwable $e) {
        // On any error, avoid hiding the entire menu for logged-in users
        return (($_SESSION['user_role'] ?? null) === 'admin');
    }
};
?>
<ul class="nav flex-column">
    <!-- Main Dashboard -->
    <li class="nav-item">
        <a class="nav-link<?php echo is_active('dashboard.php'); ?>" href="/admin/dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
    </li>

    <!-- Customer Management -->
    <?php if ($can('admin.customers') || $can('admin.customers_map') || $can('admin.agents')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-users me-2"></i>Customer Management
        </a>
        <ul class="submenu">
            <?php if ($can('admin.customers')): ?><li><a class="nav-link<?php echo is_active('customers.php'); ?>" href="/admin/customers.php">
                <i class="fas fa-user me-2"></i>All Customers
            </a></li><?php endif; ?>
            <?php if ($can('admin.customers_map')): ?><li><a class="nav-link<?php echo is_active('customers_map.php'); ?>" href="/admin/customers_map.php">
                <i class="fas fa-map-marked-alt me-2"></i>Customer Map
            </a></li><?php endif; ?>
            <?php if ($can('admin.agents')): ?><li><a class="nav-link<?php echo is_active('agents.php'); ?>" href="/admin/agents.php">
                <i class="fas fa-handshake me-2"></i>Mitra
            </a></li><?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Financial -->
    <?php if ($can('admin.invoices') || $can('admin.transactions') || $can('admin.payment_gateways') || $can('admin.taxes')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-credit-card me-2"></i>Financial
        </a>
        <ul class="submenu">
            <?php if ($can('admin.invoices')): ?><li><a class="nav-link<?php echo is_active('invoices.php'); ?>" href="/admin/invoices.php">
                <i class="fas fa-file-invoice me-2"></i>Invoices
            </a></li><?php endif; ?>
            <?php if ($can('admin.transactions')): ?><li><a class="nav-link<?php echo is_active('transactions.php'); ?>" href="/admin/transactions.php">
                <i class="fas fa-exchange-alt me-2"></i>Transactions
            </a></li><?php endif; ?>
            <?php if ($can('admin.customers')): ?><li><a class="nav-link<?php echo is_active('billing_monitor.php'); ?>" href="/admin/billing_monitor.php">
                <i class="fas fa-calendar-check me-2"></i>Billing Monitor
            </a></li><?php endif; ?>
            <?php if ($can('admin.payment_gateways')): ?><li><a class="nav-link<?php echo is_active('payment_gateways.php'); ?>" href="/admin/payment_gateways.php">
                <i class="fas fa-money-check me-2"></i>Payment Gateways
            </a></li><?php endif; ?>
            <?php if ($can('admin.taxes')): ?><li><a class="nav-link<?php echo is_active('taxes.php'); ?>" href="/admin/taxes.php">
                <i class="fas fa-file-invoice-dollar me-2"></i>Taxes
            </a></li><?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Network Management -->
    <?php if ($can('admin.settings') || $can('admin.assets') || $can('admin.odp') || $can('admin.onu')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-network-wired me-2"></i>Network
        </a>
        <ul class="submenu">
            <?php if ($can('admin.settings')): ?><li><a class="nav-link<?php echo is_active('mikrotik.php'); ?>" href="/admin/mikrotik.php">
                <i class="fas fa-router me-2"></i>Mikrotik
            </a></li><?php endif; ?>
            <?php if ($can('admin.assets')): ?><li class="nav-item has-submenu">
                <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
                    <i class="fas fa-broadcast-tower me-2"></i>Fiber Optic
                </a>
                <ul class="submenu">
                    <li><a class="nav-link<?php echo is_active('fiber_dashboard.php'); ?>" href="/admin/fiber_dashboard.php">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a></li>
                    <li><a class="nav-link<?php echo is_active('fiber_management.php'); ?>" href="/admin/fiber_management.php">
                        <i class="fas fa-cog me-2"></i>Management
                    </a></li>
                    <li><a class="nav-link<?php echo is_active('fiber_map.php'); ?>" href="/admin/fiber_map.php">
                        <i class="fas fa-map-marked-alt me-2"></i>Map View
                    </a></li>
                </ul>
            </li><?php endif; ?>
            <?php if ($can('admin.dashboard')): ?><li><a class="nav-link<?php echo is_active(['olt_monitoring.php']); ?>" href="/admin/olt_monitoring.php">
                <i class="fas fa-satellite-dish me-2"></i>OLT Monitoring
            </a></li><?php endif; ?>
            <?php if ($can('admin.odp')): ?><li><a class="nav-link<?php echo is_active('odp.php'); ?>" href="/admin/odp.php">
                <i class="fas fa-sitemap me-2"></i>ODP
            </a></li><?php endif; ?>
            <?php if ($can('admin.onu')): ?><li><a class="nav-link<?php echo is_active('onu.php'); ?>" href="/admin/onu.php">
                <i class="fas fa-hdd me-2"></i>ONU
            </a></li><?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Services -->
    <?php if ($can('admin.packages') || $can('admin.vouchers')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-box me-2"></i>Services
        </a>
        <ul class="submenu">
            <?php if ($can('admin.packages')): ?><li><a class="nav-link<?php echo is_active('packages.php'); ?>" href="/admin/packages.php">
                <i class="fas fa-cube me-2"></i>Packages
            </a></li><?php endif; ?>
            <?php if ($can('admin.vouchers')): ?><li><a class="nav-link<?php echo is_active('vouchers.php'); ?>" href="/admin/vouchers.php">
                <i class="fas fa-ticket-alt me-2"></i>Vouchers
            </a></li><?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <!-- HR Management -->
    <?php if ($can('admin.employees') || $can('admin.payroll')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-id-badge me-2"></i>HR Management
        </a>
        <ul class="submenu">
            <?php if ($can('admin.employees')): ?><li><a class="nav-link<?php echo is_active('employees.php'); ?>" href="/admin/employees.php">
                <i class="fas fa-users-cog me-2"></i>Employees
            </a></li><?php endif; ?>
            <?php if ($can('admin.payroll')): ?><li><a class="nav-link<?php echo is_active('payroll.php'); ?>" href="/admin/payroll.php">
                <i class="fas fa-money-bill-wave me-2"></i>Payroll
            </a></li><?php endif; ?>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Communications -->
    <?php if ($can('admin.customers')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-message me-2"></i>Communications
        </a>
        <ul class="submenu">
            <li><a class="nav-link<?php echo is_active('whatsapp_gateway.php'); ?>" href="/admin/whatsapp_gateway.php">
                <i class="fab fa-whatsapp me-2"></i>WhatsApp Gateway (QR)
            </a></li>
            <li><a class="nav-link<?php echo is_active('whatsapp_notifications.php'); ?>" href="/admin/whatsapp_notifications.php">
                <i class="fas fa-bell me-2"></i>WhatsApp Notifications
            </a></li>
            <li><a class="nav-link<?php echo is_active('lusi_assistant.php'); ?>" href="/admin/lusi_assistant.php">
                <i class="fas fa-robot me-2"></i>LUSI Assistant
            </a></li>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Assets -->
    <?php if ($can('admin.assets')): ?>
    <li class="nav-item">
        <a class="nav-link<?php echo is_active('assets.php'); ?>" href="/admin/assets.php">
            <i class="fas fa-boxes-stacked me-2"></i>Assets
        </a>
    </li>
    <?php endif; ?>

    <!-- Warehouse / Inventory -->
    <?php if ($can('admin.warehouse')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-warehouse me-2"></i>Gudang / Stok Gudang
        </a>
        <ul class="submenu">
            <li><a class="nav-link<?php echo is_active('warehouse.php'); ?>" href="/admin/warehouse.php">
                <i class="fas fa-boxes me-2"></i>Ringkasan Stok
            </a></li>
            <li><a class="nav-link<?php echo is_active('warehouse_receipts.php'); ?>" href="/admin/warehouse_receipts.php">
                <i class="fas fa-inbox me-2"></i>Penerimaan
            </a></li>
            <li><a class="nav-link<?php echo is_active('warehouse_issues.php'); ?>" href="/admin/warehouse_issues.php">
                <i class="fas fa-outdent me-2"></i>Pengeluaran / Mutasi
            </a></li>
            <li><a class="nav-link<?php echo is_active('warehouse_adjustments.php'); ?>" href="/admin/warehouse_adjustments.php">
                <i class="fas fa-balance-scale me-2"></i>Penyesuaian
            </a></li>
            <li><a class="nav-link<?php echo is_active('warehouse_categories.php'); ?>" href="/admin/warehouse_categories.php">
                <i class="fas fa-tags me-2"></i>Kategori
            </a></li>
            <li><a class="nav-link<?php echo is_active('warehouse_locations.php'); ?>" href="/admin/warehouse_locations.php">
                <i class="fas fa-map-marker-alt me-2"></i>Lokasi
            </a></li>
        </ul>
    </li>
    <?php endif; ?>

    <!-- Settings -->
    <?php if ($can('admin.settings')): ?>
    <li class="nav-item has-submenu">
        <a class="nav-link" href="javascript:void(0)" onclick="return toggleSubmenu(this)">
            <i class="fas fa-cog me-2"></i>Settings
        </a>
        <ul class="submenu">
            <li><a class="nav-link<?php echo is_active('settings.php'); ?>" href="/admin/settings.php">
                <i class="fas fa-sliders-h me-2"></i>General Settings
            </a></li>
            <li><a class="nav-link" href="/admin/settings.php#radius">
                <i class="fas fa-server me-2"></i>RADIUS & NAS
            </a></li>
            <li><a class="nav-link<?php echo is_active('portal_settings.php'); ?>" href="/admin/portal_settings.php">
                <i class="fas fa-palette me-2"></i>Portal Settings
            </a></li>
            <!-- Logo Settings merged into Portal Settings -->
        </ul>
    </li>
    <?php endif; ?>
</ul>
<script>
function logout(){ if(confirm('Are you sure you want to logout?')){ $.post('/api/public/logout').done(function(){ window.location.href = '/'; }); } }
</script>
