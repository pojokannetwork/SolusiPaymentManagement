<?php
$page_title = 'Transactions';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard->requirePermission('admin.transactions');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-exchange-alt me-2"></i>Transactions</h2>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Pending</h6>
                        <h3 id="pending-count">0</h3>
                        <p class="mb-0 small" id="pending-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Paid</h6>
                        <h3 id="paid-count">0</h3>
                        <p class="mb-0 small" id="paid-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Failed</h6>
                        <h3 id="failed-count">0</h3>
                        <p class="mb-0 small" id="failed-amount">Rp 0</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Today's Revenue</h6>
                        <h3 id="today-revenue">Rp 0</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Revenue Chart</h5>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-primary active" onclick="loadRevenueChart('week')" id="btn-week">Week</button>
            <button type="button" class="btn btn-outline-primary" onclick="loadRevenueChart('month')" id="btn-month">Month</button>
            <button type="button" class="btn btn-outline-primary" onclick="loadRevenueChart('year')" id="btn-year">Year</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="revenueChart" height="80"></canvas>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Filter Status</label>
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="expired">Expired</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Gateway</label>
                <select class="form-select" id="gateway-filter">
                    <option value="">All Gateways</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="search-filter" placeholder="Ref, invoice, customer...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start-date-filter">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" id="end-date-filter">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="transactions-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Reference</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Gateway</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactions-tbody">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transaction-detail-content">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let transactionModal;
let revenueChart;
let currentPeriod = 'week';

$(document).ready(function() {
    transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));

    loadSummary();
    loadTransactions();
    loadGateways();
    loadRevenueChart('week');

    // Set up filter listeners
    $('#status-filter, #gateway-filter, #search-filter, #start-date-filter, #end-date-filter').on('change keyup', function() {
        loadTransactions();
    });
});

function loadSummary() {
    $.get('/api/admin/transactions.php?action=summary')
    .done(function(response) {
        if (response.success && response.data) {
            const data = response.data;
            $('#pending-count').text(data.pending || 0);
            $('#pending-amount').text(formatRupiah(data.total_pending || 0));
            $('#paid-count').text(data.paid || 0);
            $('#paid-amount').text(formatRupiah(data.total_paid || 0));
            $('#failed-count').text(data.failed || 0);
            $('#failed-amount').text(formatRupiah(data.total_failed || 0));
            $('#today-revenue').text(formatRupiah(data.today_revenue || 0));
        }
    });
}

function loadTransactions() {
    const status = $('#status-filter').val();
    const gateway = $('#gateway-filter').val();
    const search = $('#search-filter').val();
    const startDate = $('#start-date-filter').val();
    const endDate = $('#end-date-filter').val();

    const params = new URLSearchParams({
        action: 'list',
        status: status,
        gateway: gateway,
        search: search,
        start_date: startDate,
        end_date: endDate
    });

    $.get('/api/admin/transactions.php?' + params.toString())
    .done(function(response) {
        if (response.success) {
            displayTransactions(response.data);
        }
    });
}

function displayTransactions(transactions) {
    const tbody = $('#transactions-tbody');
    tbody.empty();

    if (transactions.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">No transactions found</td></tr>');
        return;
    }

    transactions.forEach(transaction => {
        const statusBadge = getStatusBadge(transaction.status);
        const row = `
            <tr>
                <td>
                    <div>${formatDateTime(transaction.created_at)}</div>
                    ${transaction.paid_at ? '<small class="text-muted">Paid: ' + formatDateTime(transaction.paid_at) + '</small>' : ''}
                </td>
                <td>
                    <div class="font-monospace small">${transaction.external_ref || '-'}</div>
                </td>
                <td>
                    <div>${transaction.invoice_number || '-'}</div>
                </td>
                <td>
                    <div>${transaction.customer_name || '-'}</div>
                    <small class="text-muted">${transaction.kode_pelanggan || ''}</small>
                </td>
                <td>
                    <span class="badge bg-secondary">${transaction.gateway_name || '-'}</span>
                </td>
                <td class="fw-bold">${formatRupiah(transaction.amount)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="viewTransaction(${transaction.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'failed': '<span class="badge bg-danger">Failed</span>',
        'expired': '<span class="badge bg-secondary">Expired</span>',
        'refunded': '<span class="badge bg-info">Refunded</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function loadGateways() {
    $.get('/api/admin/payment_gateways.php?action=list')
    .done(function(response) {
        if (response.success && response.data) {
            const select = $('#gateway-filter');
            select.empty().append('<option value="">All Gateways</option>');
            response.data.forEach(gateway => {
                if (gateway.enabled == 1) {
                    select.append(`<option value="${gateway.id}">${gateway.name}</option>`);
                }
            });
        }
    });
}

function viewTransaction(id) {
    $.get('/api/admin/transactions.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const txn = response.data;

            let callbackHtml = '';
            if (txn.callback_data) {
                callbackHtml = `
                    <div class="mt-3">
                        <h6>Callback Data:</h6>
                        <pre class="bg-light p-3 rounded small">${JSON.stringify(txn.callback_data, null, 2)}</pre>
                    </div>
                `;
            }

            const content = `
                <div class="transaction-detail">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Transaction Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>${txn.id}</td>
                                </tr>
                                <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td class="font-monospace">${txn.external_ref || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>${getStatusBadge(txn.status)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td class="fw-bold">${formatRupiah(txn.amount)} ${txn.currency}</td>
                                </tr>
                                <tr>
                                    <td><strong>Gateway:</strong></td>
                                    <td>${txn.gateway_name} (${txn.gateway_code})</td>
                                </tr>
                                <tr>
                                    <td><strong>Signature Valid:</strong></td>
                                    <td>${txn.signature_valid ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Customer & Invoice</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Invoice:</strong></td>
                                    <td>${txn.invoice_number || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Invoice Total:</strong></td>
                                    <td>${formatRupiah(txn.invoice_total || 0)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Customer:</strong></td>
                                    <td>${txn.customer_name || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Customer Code:</strong></td>
                                    <td>${txn.kode_pelanggan || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>${txn.telepon || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${txn.email || '-'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Created At:</strong> ${formatDateTime(txn.created_at)}</p>
                            <p class="mb-1"><strong>Updated At:</strong> ${formatDateTime(txn.updated_at)}</p>
                            ${txn.paid_at ? '<p class="mb-1"><strong>Paid At:</strong> ' + formatDateTime(txn.paid_at) + '</p>' : ''}
                        </div>
                    </div>

                    ${callbackHtml}
                </div>
            `;

            $('#transaction-detail-content').html(content);
            transactionModal.show();
        }
    });
}

function loadRevenueChart(period) {
    currentPeriod = period;

    // Update active button
    $('.btn-group button').removeClass('active');
    $('#btn-' + period).addClass('active');

    $.get('/api/admin/transactions.php?action=revenue_chart&period=' + period)
    .done(function(response) {
        if (response.success && response.data) {
            renderRevenueChart(response.data, period);
        }
    });
}

function renderRevenueChart(data, period) {
    const ctx = document.getElementById('revenueChart').getContext('2d');

    const labels = data.map(item => {
        if (period === 'year') {
            // Format as "Jan 2024"
            const [year, month] = item.date.split('-');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return monthNames[parseInt(month) - 1] + ' ' + year;
        } else {
            // Format as "DD MMM"
            const date = new Date(item.date);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }
    });

    const values = data.map(item => parseFloat(item.total));

    if (revenueChart) {
        revenueChart.destroy();
    }

    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (Rp)',
                data: values,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + formatRupiah(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000) + 'K';
                        }
                    }
                }
            }
        }
    });
}

function clearFilters() {
    $('#status-filter').val('');
    $('#gateway-filter').val('');
    $('#search-filter').val('');
    $('#start-date-filter').val('');
    $('#end-date-filter').val('');
    loadTransactions();
}

function formatRupiah(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '-';
    const date = new Date(dateTimeStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
