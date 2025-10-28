<?php
$page_title = 'Billing Monitor';
require_once __DIR__ . '/../includes/admin_header.php';

// Check permissions
$guard->requirePermission('admin.customers');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-check me-2"></i>Billing Monitor</h2>
    <div class="btn-group">
        <button class="btn btn-outline-warning" onclick="runAutoIsolir()">
            <i class="fas fa-ban me-2"></i>Run Auto Isolir
        </button>
        <button class="btn btn-outline-info" onclick="generateMonthlyBills()">
            <i class="fas fa-file-invoice me-2"></i>Generate Bills
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Overdue Today</h6>
                        <h3 id="overdue-count">-</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title">Due This Week</h6>
                        <h3 id="week-count">-</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-week fa-2x opacity-75"></i>
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
                        <h6 class="card-title">Prepaid Active</h6>
                        <h3 id="prepaid-count">-</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-credit-card fa-2x opacity-75"></i>
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
                        <h6 class="card-title">Auto Isolir Enabled</h6>
                        <h3 id="auto-isolir-count">-</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-robot fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Filter Status</label>
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="overdue">Overdue Today</option>
                    <option value="due-week">Due This Week</option>
                    <option value="prepaid">Prepaid</option>
                    <option value="auto-isolir">Auto Isolir Enabled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Billing Type</label>
                <select class="form-select" id="billing-filter">
                    <option value="">All Types</option>
                    <option value="postpaid">Postpaid</option>
                    <option value="prepaid">Prepaid</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" class="form-control" id="start-date">
                    <span class="input-group-text">to</span>
                    <input type="date" class="form-control" id="end-date">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button class="btn btn-primary" onclick="loadBillingData()">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="billing-table" class="table table-striped">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Code</th>
                        <th>Package</th>
                        <th>Billing Type</th>
                        <th>Active Date</th>
                        <th>Next Bill Date</th>
                        <th>Isolir Date</th>
                        <th>Status</th>
                        <th>Auto Isolir</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?php $page_specific_scripts = <<<'EOT'
<script>
let billingTable;

$(document).ready(function() {
    initializeBillingTable();
    loadSummaryData();
    setDefaultDateRange();
});

function initializeBillingTable() {
    billingTable = $('#billing-table').DataTable({
        ajax: {
            url: '/api/admin/billing_monitor.php?action=list',
            dataSrc: 'data',
            data: function(d) {
                d.status_filter = $('#status-filter').val();
                d.billing_filter = $('#billing-filter').val();
                d.start_date = $('#start-date').val();
                d.end_date = $('#end-date').val();
            }
        },
        columns: [
            { 
                data: null,
                render: function(data) {
                    return `<div><strong>${data.nama}</strong><br><small class="text-muted">${data.email || ''}</small></div>`;
                }
            },
            { data: 'kode_pelanggan' },
            { data: 'paket' },
            {
                data: 'sistem_bayar',
                render: function(data) {
                    const badges = {
                        'postpaid': '<span class="badge bg-info">Postpaid</span>',
                        'prepaid': '<span class="badge bg-warning">Prepaid</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'tanggal_aktif',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('id-ID') : '-';
                }
            },
            {
                data: null,
                render: function(data) {
                    if (data.sistem_bayar === 'prepaid') return '-';
                    return calculateNextBillDate(data.tanggal_aktif, data.cycle_billing);
                }
            },
            {
                data: 'tanggal_isolir',
                render: function(data, type, row) {
                    if (!data || row.sistem_bayar === 'prepaid') return '-';
                    const isolirDate = new Date(data);
                    const today = new Date();
                    const isOverdue = isolirDate < today;
                    const dateStr = isolirDate.toLocaleDateString('id-ID');
                    
                    if (isOverdue) {
                        return `<span class="badge bg-danger">${dateStr}</span>`;
                    } else {
                        const diffDays = Math.ceil((isolirDate - today) / (1000 * 60 * 60 * 24));
                        if (diffDays <= 7) {
                            return `<span class="badge bg-warning">${dateStr}</span>`;
                        } else {
                            return dateStr;
                        }
                    }
                }
            },
            {
                data: 'status',
                render: function(data) {
                    const badges = {
                        'active': '<span class="badge bg-success">Active</span>',
                        'isolir': '<span class="badge bg-danger">Isolir</span>',
                        'suspended': '<span class="badge bg-warning">Suspended</span>',
                        'terminated': '<span class="badge bg-secondary">Terminated</span>'
                    };
                    return badges[data] || data;
                }
            },
            {
                data: 'auto_isolir',
                render: function(data) {
                    return data == 1 ? 
                        '<i class="fas fa-check-circle text-success"></i>' : 
                        '<i class="fas fa-times-circle text-danger"></i>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let actions = `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editCustomer(${data.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                    `;
                    
                    if (data.status === 'active' && data.tanggal_isolir) {
                        const isolirDate = new Date(data.tanggal_isolir);
                        const today = new Date();
                        if (isolirDate <= today) {
                            actions += `
                                <button class="btn btn-outline-danger" onclick="manualIsolir(${data.id})" title="Isolir Now">
                                    <i class="fas fa-ban"></i>
                                </button>
                            `;
                        }
                    }
                    
                    if (data.sistem_bayar === 'postpaid') {
                        actions += `
                            <button class="btn btn-outline-success" onclick="generateBill(${data.id})" title="Generate Bill">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                        `;
                    }
                    
                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[6, 'asc']], // Sort by isolir date
        pageLength: 25,
        responsive: true
    });
}

function loadSummaryData() {
    $.get('/api/admin/billing_monitor.php?action=summary').done(function(response) {
        if (response.success) {
            const data = response.data;
            $('#overdue-count').text(data.overdue || 0);
            $('#week-count').text(data.due_week || 0);
            $('#prepaid-count').text(data.prepaid || 0);
            $('#auto-isolir-count').text(data.auto_isolir || 0);
        }
    });
}

function setDefaultDateRange() {
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    
    $('#start-date').val(today.toISOString().split('T')[0]);
    $('#end-date').val(nextWeek.toISOString().split('T')[0]);
}

function loadBillingData() {
    billingTable.ajax.reload();
    loadSummaryData();
}

function calculateNextBillDate(tanggalAktif, cycleBilling) {
    if (!tanggalAktif) return '-';
    
    const aktif = new Date(tanggalAktif);
    const today = new Date();
    const nextBill = new Date(today.getFullYear(), today.getMonth() + 1, cycleBilling || 1);
    
    return nextBill.toLocaleDateString('id-ID');
}

function runAutoIsolir() {
    if (!confirm('Run auto isolir for all overdue customers?')) return;
    
    $.post('/api/admin/billing_monitor.php?action=auto_isolir')
    .done(function(response) {
        if (response.success) {
            alert(`Auto isolir completed. ${response.count || 0} customers isolated.`);
            loadBillingData();
        } else {
            alert('Auto isolir failed: ' + (response.message || 'Unknown error'));
        }
    })
    .fail(function() {
        alert('Failed to run auto isolir');
    });
}

function generateMonthlyBills() {
    if (!confirm('Generate monthly bills for all postpaid customers?')) return;
    
    $.post('/api/admin/billing_monitor.php?action=generate_bills')
    .done(function(response) {
        if (response.success) {
            alert(`Bills generated. ${response.count || 0} invoices created.`);
            loadBillingData();
        } else {
            alert('Bill generation failed: ' + (response.message || 'Unknown error'));
        }
    })
    .fail(function() {
        alert('Failed to generate bills');
    });
}

function manualIsolir(customerId) {
    if (!confirm('Isolate this customer now?')) return;
    
    $.post('/api/admin/customers.php?action=isolir&id=' + customerId)
    .done(function(response) {
        if (response.success) {
            alert('Customer isolated successfully');
            loadBillingData();
        } else {
            alert('Isolation failed: ' + (response.message || 'Unknown error'));
        }
    });
}

function generateBill(customerId) {
    $.post('/api/admin/billing_monitor.php?action=generate_single_bill&customer_id=' + customerId)
    .done(function(response) {
        if (response.success) {
            alert('Bill generated successfully');
            if (response.invoice_id) {
                window.open('/admin/invoices.php?id=' + response.invoice_id, '_blank');
            }
        } else {
            alert('Bill generation failed: ' + (response.message || 'Unknown error'));
        }
    });
}

function editCustomer(id) {
    window.location.href = '/admin/customers.php?edit=' + id;
}
</script>
EOT;
?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>