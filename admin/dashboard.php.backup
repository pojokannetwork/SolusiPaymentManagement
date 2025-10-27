<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.dashboard');
?>

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
        <div class="card shadow-sm stat-card bg-primary text-white">
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
        <div class="card shadow-sm stat-card bg-success text-white">
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
        <div class="card shadow-sm stat-card bg-warning text-white">
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
        <div class="card shadow-sm stat-card bg-info text-white">
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
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Revenue Trend (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm">
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
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Recent Payments</h5>
            </div>
            <div class="card-body">
                <div id="recent-payments" class="list-group list-group-flush">
                    <div class="text-center p-3 text-muted"><div class="spinner-border spinner-border-sm"></div> Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Recent System Activity</h5>
            </div>
            <div class="card-body">
                <div id="recent-activity" class="list-group list-group-flush">
                    <div class="text-center p-3 text-muted"><div class="spinner-border spinner-border-sm"></div> Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_specific_scripts = <<<'EOT'
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    loadStats();
    loadCharts();
    loadRecentData();
});

function loadStats() {
    $.get('/api/admin/dashboard/stats').done(response => {
        if (response.success) {
            $('#total-customers').text(response.data.total_customers);
            $('#active-customers').text(response.data.active_customers);
            $('#pending-invoices').text(response.data.pending_invoices);
            $('#monthly-revenue').text(formatCurrency(response.data.monthly_revenue));
        }
    }).fail(() => console.error('Failed to load stats'));
}

function loadCharts() {
    $.get('/api/admin/dashboard/revenue_chart').done(response => {
        if (response.success) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, { type: 'line', data: response.data, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { callback: value => formatCurrency(value) } } } } });
        }
    });
    $.get('/api/admin/dashboard/customer_status_chart').done(response => {
        if (response.success) {
            const ctx = document.getElementById('customerStatusChart').getContext('2d');
            new Chart(ctx, { type: 'doughnut', data: response.data, options: { responsive: true } });
        }
    });
}

function loadRecentData() {
    $.get('/api/admin/dashboard/recent_payments').done(response => {
        let html = '<div class="text-center p-3 text-muted">No recent payments found.</div>';
        if (response.success && response.data.length) {
            html = '';
            response.data.forEach(p => {
                html += `<div class="list-group-item"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1">${p.customer_name}</h6><small>${formatCurrency(p.amount)}</small></div><p class="mb-1">${p.invoice_number}</p><small class="text-muted">${formatDateTime(p.paid_at)}</small></div>`;
            });
        }
        $('#recent-payments').html(html);
    });
    $.get('/api/admin/dashboard/recent_activity').done(response => {
        let html = '<div class="text-center p-3 text-muted">No recent activity.</div>';
        if (response.success && response.data.length) {
            html = '';
            response.data.forEach(a => {
                html += `<div class="list-group-item"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1">${a.action}</h6><small class="text-muted">${formatDateTime(a.created_at)}</small></div><p class="mb-1">${a.user_name || 'System'}</p></div>`;
            });
        }
        $('#recent-activity').html(html);
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
}

function formatDateTime(datetime) {
    return new Date(datetime).toLocaleString('id-ID');
}
</script>
EOT;

require_once __DIR__ . '/../includes/admin_footer.php';
?>
