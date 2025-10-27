<?php
$page_title = 'ISP Vouchers';
require_once '../includes/admin_header.php';

// Security check: Ensure user is an admin and has permission
$guard->requirePermission('admin.vouchers');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">ISP Vouchers</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#voucherModal">
            <i class="fas fa-plus"></i> Add Voucher Pricing
        </button>
    </div>
</div>

<!-- Vouchers Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Duration (Hours)</th>
                        <th>Price</th>
                        <th>Agent Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vouchers-table-body">
                    <!-- Voucher rows will be inserted here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Voucher Modal -->
<div class="modal fade" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherModalLabel">Add/Edit Voucher Pricing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="voucherForm">
                    <input type="hidden" id="voucherId" name="id">
                    <div class="mb-3">
                        <label for="voucherPackage" class="form-label">Package</label>
                        <select class="form-select" id="voucherPackage" name="package_id" required></select>
                    </div>
                    <div class="mb-3">
                        <label for="voucherDuration" class="form-label">Duration (Hours)</label>
                        <input type="number" class="form-control" id="voucherDuration" name="duration_hours" required>
                    </div>
                    <div class="mb-3">
                        <label for="voucherPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="voucherPrice" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="voucherAgentPrice" class="form-label">Agent Price</label>
                        <input type="number" class="form-control" id="voucherAgentPrice" name="agent_price" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="voucherHotspotProfile" class="form-label">Hotspot Profile</label>
                        <input type="text" class="form-control" id="voucherHotspotProfile" name="hotspot_profile">
                    </div>
                    <div class="mb-3">
                        <label for="voucherStatus" class="form-label">Status</label>
                        <select class="form-select" id="voucherStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
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
    function loadPackagesDropdown() {
        $.get('/api/admin/packages.php?action=list', function(data) {
            let options = '<option value="">Select Package</option>';
            data.forEach(function(pkg) {
                options += `<option value="${pkg.id}">${pkg.name}</option>`;
            });
            $('#voucherPackage').html(options);
        });
    }

    function loadVouchers() {
        $.get('/api/admin/vouchers.php?action=list', function(data) {
            let tableBody = '';
            if(data.length === 0) {
                tableBody = '<tr><td colspan="6" class="text-center">No voucher pricing found.</td></tr>';
            }
            data.forEach(function(voucher) {
                tableBody += `
                    <tr>
                        <td>${voucher.package_name}</td>
                        <td>${voucher.duration_hours}</td>
                        <td>${voucher.price}</td>
                        <td>${voucher.agent_price}</td>
                        <td><span class="badge bg-${voucher.status === 'active' ? 'success' : 'danger'}">${voucher.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${voucher.id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${voucher.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#vouchers-table-body').html(tableBody);
        });
    }

    loadPackagesDropdown();
    loadVouchers();

    $('#voucherForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let voucherId = $('#voucherId').val();
        let url = voucherId ? '/api/admin/vouchers.php?action=update' : '/api/admin/vouchers.php?action=create';
        $.post(url, formData, function() {
            $('#voucherModal').modal('hide');
            loadVouchers();
        });
    });

    $('#vouchers-table-body').on('click', '.btn-edit', function() {
        let voucherId = $(this).data('id');
        $.get('/api/admin/vouchers.php?action=get&id=' + voucherId, function(voucher) {
            $('#voucherForm')[0].reset();
            $('#voucherId').val(voucher.id);
            $('#voucherPackage').val(voucher.package_id);
            $('#voucherDuration').val(voucher.duration_hours);
            $('#voucherPrice').val(voucher.price);
            $('#voucherAgentPrice').val(voucher.agent_price);
            $('#voucherHotspotProfile').val(voucher.hotspot_profile);
            $('#voucherStatus').val(voucher.status);
            $('#voucherModal').modal('show');
        });
    });

    $('#vouchers-table-body').on('click', '.btn-delete', function() {
        if (confirm('Are you sure you want to delete this voucher pricing?')) {
            let voucherId = $(this).data('id');
            $.post('/api/admin/vouchers.php?action=delete&id=' + voucherId, function() {
                loadVouchers();
            });
        }
    });

    $('#voucherModal').on('hidden.bs.modal', function () {
        $('#voucherForm')[0].reset();
        $('#voucherId').val('');
    });
});
</script>
EOT;

require_once '../includes/admin_footer.php';
?>
