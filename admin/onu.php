<?php
$page_title = 'ONU Management';
require_once '../includes/admin_header.php';

// Security check: Ensure user is an admin and has permission
$guard->requirePermission('admin.onu');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">ONU Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#onuModal">
            <i class="fas fa-plus"></i> Add ONU
        </button>
    </div>
</div>

<!-- ONU Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Serial Number</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>Customer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="onu-table-body">
                    <!-- ONU rows will be inserted here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ONU Modal -->
<div class="modal fade" id="onuModal" tabindex="-1" aria-labelledby="onuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="onuModalLabel">Add/Edit ONU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="onuForm">
                    <input type="hidden" id="onuId" name="id">
                    <div class="mb-3">
                        <label for="onuSerialNumber" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="onuSerialNumber" name="serial_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="onuModel" class="form-label">Model</label>
                        <input type="text" class="form-control" id="onuModel" name="model">
                    </div>
                    <div class="mb-3">
                        <label for="onuStatus" class="form-label">Status</label>
                        <select class="form-select" id="onuStatus" name="status">
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="defective">Defective</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="onuCustomer" class="form-label">Customer</label>
                        <select class="form-select" id="onuCustomer" name="customer_id"></select>
                    </div>
                    <div class="mb-3">
                        <label for="onuNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="onuNotes" name="notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$page_specific_scripts = <<<'EOT'
<script>
$(document).ready(function() {
    function loadCustomersDropdown() {
        $.get('/api/admin/customers.php?action=list', function(data) {
            let options = '<option value="">Select Customer (optional)</option>';
            data.forEach(function(customer) {
                options += `<option value="${customer.id}">${customer.nama} (${customer.kode_pelanggan})</option>`;
            });
            $('#onuCustomer').html(options);
        });
    }

    function loadOnus() {
        $.get('/api/admin/onu.php?action=list', function(data) {
            let tableBody = '';
            if(data.length === 0) {
                tableBody = '<tr><td colspan="5" class="text-center">No ONUs found.</td></tr>';
            }
            data.forEach(function(onu) {
                tableBody += `
                    <tr>
                        <td>${onu.serial_number}</td>
                        <td>${onu.model || ''}</td>
                        <td><span class="badge bg-${getBootstrapClassForStatus(onu.status)}">${onu.status}</span></td>
                        <td>${onu.customer_name || 'N/A'}</td>
                        <td>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${onu.id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${onu.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#onu-table-body').html(tableBody);
        });
    }

    function getBootstrapClassForStatus(status) {
        switch (status) {
            case 'available': return 'success';
            case 'in_use': return 'primary';
            case 'defective': return 'danger';
            case 'maintenance': return 'warning';
            default: return 'dark';
        }
    }

    loadCustomersDropdown();
    loadOnus();

    $('#onuForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let onuId = $('#onuId').val();
        let url = onuId ? '/api/admin/onu.php?action=update' : '/api/admin/onu.php?action=create';
        $.post(url, formData, function() {
            $('#onuModal').modal('hide');
            loadOnus();
        });
    });

    $('#onu-table-body').on('click', '.btn-edit', function() {
        let onuId = $(this).data('id');
        $.get('/api/admin/onu.php?action=get&id=' + onuId, function(onu) {
            $('#onuForm')[0].reset();
            $('#onuId').val(onu.id);
            $('#onuSerialNumber').val(onu.serial_number);
            $('#onuModel').val(onu.model);
            $('#onuStatus').val(onu.status);
            $('#onuCustomer').val(onu.customer_id);
            $('#onuNotes').val(onu.notes);
            $('#onuModal').modal('show');
        });
    });

    $('#onu-table-body').on('click', '.btn-delete', function() {
        if (confirm('Are you sure you want to delete this ONU?')) {
            let onuId = $(this).data('id');
            $.post('/api/admin/onu.php?action=delete&id=' + onuId, function() {
                loadOnus();
            });
        }
    });

    $('#onuModal').on('hidden.bs.modal', function () {
        $('#onuForm')[0].reset();
        $('#onuId').val('');
    });
});
</script>
EOT;

require_once '../includes/admin_footer.php';
?>
