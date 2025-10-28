<?php
$page_title = 'Employee Management';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.employees');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog me-2"></i>Employee Management</h2>
    <button class="btn btn-primary" onclick="showCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Employee
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Employees</h6>
                <h3 id="total-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Active</h6>
                <h3 id="active-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h6>Inactive</h6>
                <h3 id="inactive-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Total Payroll</h6>
                <h3 id="total-salary">Rp 0</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="dept-filter">
                    <option value="">All Departments</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" id="search-filter" placeholder="Search by name, NIP, position...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="clearFilters()"><i class="fas fa-undo"></i> Clear</button>
            </div>
        </div>
    </div>
</div>

<!-- Employees Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>NIP</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Salary</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employees-tbody">
                    <tr><td colspan="8" class="text-center"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" id="employee-id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="emp-nama" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" class="form-control" id="emp-nip">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" id="emp-email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="emp-telepon">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="emp-departemen" list="dept-list">
                            <datalist id="dept-list">
                                <option value="IT">
                                <option value="Finance">
                                <option value="HR">
                                <option value="Operations">
                                <option value="Sales">
                                <option value="Customer Service">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" id="emp-posisi">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Base Salary</label>
                            <input type="number" class="form-control" id="emp-gaji" value="0" step="100000">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Join Date</label>
                            <input type="date" class="form-control" id="emp-tgl-masuk" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="emp-status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="password-field">
                        <label class="form-label">Password (for new user)</label>
                        <input type="password" class="form-control" id="emp-password" placeholder="Leave empty to use default: password123">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEmployee()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
let employeeModal;

$(document).ready(function() {
    employeeModal = new bootstrap.Modal(document.getElementById('employeeModal'));
    loadSummary();
    loadEmployees();
    $('#status-filter, #dept-filter, #search-filter').on('change keyup', loadEmployees);
});

function loadSummary() {
    $.get('/api/admin/employees.php?action=summary')
    .done(function(response) {
        if (response.success && response.data) {
            const d = response.data;
            $('#total-count').text(d.total || 0);
            $('#active-count').text(d.active || 0);
            $('#inactive-count').text(d.inactive || 0);
            $('#total-salary').text(formatRupiah(d.total_salary || 0));

            // Populate department filter
            const deptFilter = $('#dept-filter');
            deptFilter.find('option:not(:first)').remove();
            if (d.departments && d.departments.length > 0) {
                d.departments.forEach(dept => {
                    deptFilter.append(`<option value="${dept.departemen}">${dept.departemen} (${dept.count})</option>`);
                });
            }
        }
    });
}

function loadEmployees() {
    const params = new URLSearchParams({
        action: 'list',
        status: $('#status-filter').val(),
        departemen: $('#dept-filter').val(),
        search: $('#search-filter').val()
    });

    $.get('/api/admin/employees.php?' + params.toString())
    .done(function(response) {
        if (response.success) {
            const tbody = $('#employees-tbody');
            tbody.empty();

            if (response.data.length === 0) {
                tbody.append('<tr><td colspan="8" class="text-center text-muted">No employees found</td></tr>');
                return;
            }

            response.data.forEach(emp => {
                const statusBadge = emp.status === 'active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';

                tbody.append(`
                    <tr>
                        <td>${emp.nip || '-'}</td>
                        <td>
                            <div>${emp.nama}</div>
                            <small class="text-muted">${emp.email}</small>
                        </td>
                        <td>${emp.departemen || '-'}</td>
                        <td>${emp.posisi || '-'}</td>
                        <td>${formatRupiah(emp.gaji_pokok || 0)}</td>
                        <td>${formatDate(emp.tgl_masuk)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editEmployee(${emp.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteEmployee(${emp.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
            });
        }
    });
}

function showCreateModal() {
    $('#modalTitle').text('Add Employee');
    $('#employeeForm')[0].reset();
    $('#employee-id').val('');
    $('#password-field').show();
    employeeModal.show();
}

function editEmployee(id) {
    $.get('/api/admin/employees.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const emp = response.data;
            $('#modalTitle').text('Edit Employee');
            $('#employee-id').val(emp.id);
            $('#emp-nama').val(emp.nama);
            $('#emp-nip').val(emp.nip);
            $('#emp-email').val(emp.email);
            $('#emp-telepon').val(emp.telepon);
            $('#emp-departemen').val(emp.departemen);
            $('#emp-posisi').val(emp.posisi);
            $('#emp-gaji').val(emp.gaji_pokok);
            $('#emp-tgl-masuk').val(emp.tgl_masuk);
            $('#emp-status').val(emp.status);
            $('#password-field').hide();
            employeeModal.show();
        }
    });
}

function saveEmployee() {
    const form = $('#employeeForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const data = {
        id: $('#employee-id').val(),
        nama: $('#emp-nama').val(),
        nip: $('#emp-nip').val(),
        email: $('#emp-email').val(),
        telepon: $('#emp-telepon').val(),
        departemen: $('#emp-departemen').val(),
        posisi: $('#emp-posisi').val(),
        gaji_pokok: parseFloat($('#emp-gaji').val()) || 0,
        tgl_masuk: $('#emp-tgl-masuk').val(),
        status: $('#emp-status').val(),
        password: $('#emp-password').val()
    };

    const url = data.id ? '/api/admin/employees.php?action=update' : '/api/admin/employees.php?action=create';

    $.ajax({
        url: url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                employeeModal.hide();
                loadEmployees();
                loadSummary();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to save employee');
        }
    });
}

function deleteEmployee(id) {
    if (!confirm('Are you sure you want to delete this employee?')) return;

    $.post('/api/admin/employees.php?action=delete', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadEmployees();
            loadSummary();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function clearFilters() {
    $('#status-filter, #dept-filter, #search-filter').val('');
    loadEmployees();
}

function formatRupiah(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', { minimumFractionDigits: 0 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
