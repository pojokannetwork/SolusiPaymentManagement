<?php
$page_title = 'Payroll Management';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.payroll');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-money-bill-wave me-2"></i>Payroll Management</h2>
    <button class="btn btn-success" onclick="showGenerateModal()">
        <i class="fas fa-cogs me-2"></i>Generate Payroll
    </button>
</div>

<!-- Period Selection -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Period Month</label>
                <select class="form-select" id="month-filter">
                    <?php for($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                        <?= date('F', mktime(0,0,0,$m,1)) ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Period Year</label>
                <select class="form-select" id="year-filter">
                    <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status Filter</label>
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="loadPayroll()">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-success w-100" onclick="exportPayroll()">
                    <i class="fas fa-file-excel me-2"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Payroll</h6>
                <h3 id="total-count">0</h3>
                <p class="mb-0" id="total-amount">Rp 0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6>Draft/Approved</h6>
                <h3 id="draft-count">0</h3>
                <p class="mb-0" id="pending-amount">Rp 0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Paid</h6>
                <h3 id="paid-count">0</h3>
                <p class="mb-0" id="paid-amount">Rp 0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Remaining to Pay</h6>
                <h3 id="remaining">Rp 0</h3>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Base Salary</th>
                        <th>Overtime</th>
                        <th>Bonus</th>
                        <th>Deductions</th>
                        <th>Tax</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="payroll-tbody">
                    <tr><td colspan="10" class="text-center"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    This will generate payroll for all active employees for the selected period.
                </div>
                <div class="mb-3">
                    <label class="form-label">Month</label>
                    <select class="form-select" id="gen-month">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                            <?= date('F', mktime(0,0,0,$m,1)) ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Year</label>
                    <select class="form-select" id="gen-year">
                        <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="generatePayroll()">Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payroll Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control" id="edit-employee" readonly>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Base Salary</label>
                        <input type="number" class="form-control" id="edit-gaji" step="10000" onchange="calculateTotal()">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Overtime</label>
                        <input type="number" class="form-control" id="edit-lembur" step="10000" value="0" onchange="calculateTotal()">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bonus</label>
                        <input type="number" class="form-control" id="edit-bonus" step="10000" value="0" onchange="calculateTotal()">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Deductions</label>
                        <input type="number" class="form-control" id="edit-potongan" step="10000" value="0" onchange="calculateTotal()">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tax</label>
                        <input type="number" class="form-control" id="edit-pajak" step="1000" value="0" onchange="calculateTotal()">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="edit-status">
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-success">
                    <strong>Net Pay:</strong> <span id="calculated-total">Rp 0</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePayroll()">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
let generateModal, editModal;

$(document).ready(function() {
    generateModal = new bootstrap.Modal(document.getElementById('generateModal'));
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    loadSummary();
    loadPayroll();
    $('#month-filter, #year-filter, #status-filter').on('change', function() {
        loadSummary();
        loadPayroll();
    });
});

function loadSummary() {
    const month = $('#month-filter').val();
    const year = $('#year-filter').val();

    $.get('/api/admin/payroll.php?action=summary&month=' + month + '&year=' + year)
    .done(function(response) {
        if (response.success && response.data) {
            const d = response.data;
            $('#total-count').text(d.total || 0);
            $('#total-amount').text(formatRupiah(d.total_amount || 0));
            $('#draft-count').text((d.draft || 0) + (d.approved || 0));
            $('#pending-amount').text(formatRupiah(d.pending_amount || 0));
            $('#paid-count').text(d.paid || 0);
            $('#paid-amount').text(formatRupiah(d.paid_amount || 0));
            $('#remaining').text(formatRupiah(d.pending_amount || 0));
        }
    });
}

function loadPayroll() {
    const params = new URLSearchParams({
        action: 'list',
        month: $('#month-filter').val(),
        year: $('#year-filter').val(),
        status: $('#status-filter').val()
    });

    $.get('/api/admin/payroll.php?' + params.toString())
    .done(function(response) {
        if (response.success) {
            const tbody = $('#payroll-tbody');
            tbody.empty();

            if (response.data.length === 0) {
                tbody.append('<tr><td colspan="10" class="text-center text-muted">No payroll records found. Click "Generate Payroll" to create.</td></tr>');
                return;
            }

            response.data.forEach(pay => {
                const statusBadge = getStatusBadge(pay.status);
                const period = getMonthName(pay.periode_bulan) + ' ' + pay.periode_tahun;

                tbody.append(`
                    <tr>
                        <td>
                            <div>${pay.employee_name}</div>
                            <small class="text-muted">${pay.nip || '-'} | ${pay.departemen || '-'}</small>
                        </td>
                        <td>${period}</td>
                        <td>${formatRupiah(pay.gaji_pokok)}</td>
                        <td>${formatRupiah(pay.lembur)}</td>
                        <td>${formatRupiah(pay.bonus)}</td>
                        <td>${formatRupiah(pay.potongan)}</td>
                        <td>${formatRupiah(pay.pajak)}</td>
                        <td class="fw-bold">${formatRupiah(pay.total_bayar)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                ${pay.status !== 'paid' ? `
                                    <button class="btn btn-outline-primary" onclick="editPayroll(${pay.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                ` : ''}
                                ${pay.status === 'approved' ? `
                                    <button class="btn btn-outline-success" onclick="markPaid(${pay.id})" title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                ` : ''}
                                ${pay.status !== 'paid' ? `
                                    <button class="btn btn-outline-danger" onclick="deletePayroll(${pay.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `);
            });
        }
    });
}

function showGenerateModal() {
    generateModal.show();
}

function generatePayroll() {
    const data = {
        month: $('#gen-month').val(),
        year: $('#gen-year').val()
    };

    $.ajax({
        url: '/api/admin/payroll.php?action=generate',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message + '\n' + response.count + ' payroll records generated.');
                generateModal.hide();
                loadSummary();
                loadPayroll();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

function editPayroll(id) {
    $.get('/api/admin/payroll.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const pay = response.data;
            $('#edit-id').val(pay.id);
            $('#edit-employee').val(pay.employee_name + ' (' + pay.nip + ')');
            $('#edit-gaji').val(pay.gaji_pokok);
            $('#edit-lembur').val(pay.lembur);
            $('#edit-bonus').val(pay.bonus);
            $('#edit-potongan').val(pay.potongan);
            $('#edit-pajak').val(pay.pajak);
            $('#edit-status').val(pay.status);
            calculateTotal();
            editModal.show();
        }
    });
}

function calculateTotal() {
    const gaji = parseFloat($('#edit-gaji').val()) || 0;
    const lembur = parseFloat($('#edit-lembur').val()) || 0;
    const bonus = parseFloat($('#edit-bonus').val()) || 0;
    const potongan = parseFloat($('#edit-potongan').val()) || 0;
    const pajak = parseFloat($('#edit-pajak').val()) || 0;
    const total = gaji + lembur + bonus - potongan - pajak;
    $('#calculated-total').text(formatRupiah(total));
}

function updatePayroll() {
    const data = {
        id: $('#edit-id').val(),
        gaji_pokok: parseFloat($('#edit-gaji').val()),
        lembur: parseFloat($('#edit-lembur').val()),
        bonus: parseFloat($('#edit-bonus').val()),
        potongan: parseFloat($('#edit-potongan').val()),
        pajak: parseFloat($('#edit-pajak').val()),
        status: $('#edit-status').val()
    };

    $.ajax({
        url: '/api/admin/payroll.php?action=update',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                editModal.hide();
                loadSummary();
                loadPayroll();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

function markPaid(id) {
    if (!confirm('Mark this payroll as paid?')) return;

    const metode = prompt('Payment method (transfer/cash/check):', 'transfer');
    if (!metode) return;

    $.ajax({
        url: '/api/admin/payroll.php?action=mark_paid',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id: id, metode: metode }),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                loadSummary();
                loadPayroll();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

function deletePayroll(id) {
    if (!confirm('Delete this payroll record?')) return;

    $.post('/api/admin/payroll.php?action=delete', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadSummary();
            loadPayroll();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function exportPayroll() {
    alert('Export feature coming soon. Will export to Excel/CSV format.');
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'approved': '<span class="badge bg-warning">Approved</span>',
        'paid': '<span class="badge bg-success">Paid</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function getMonthName(month) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[month - 1];
}

function formatRupiah(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0 });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
