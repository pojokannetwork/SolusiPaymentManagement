<?php
$page_title = 'ISP Packages';
require_once '../includes/admin_header.php';

// Security check: Ensure user is an admin and has permission
$guard->requirePermission('admin.packages');

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">ISP Packages</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#packageModal">
            <i class="fas fa-plus"></i> Add Package
        </button>
    </div>
</div>

<!-- Packages Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Speed</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="packages-table-body">
                    <!-- Package rows will be inserted here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Package Modal -->
<div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="packageModalLabel">Add/Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="packageForm">
                    <input type="hidden" id="packageId" name="id">
                    <div class="mb-3">
                        <label for="packageCode" class="form-label">Code</label>
                        <input type="text" class="form-control" id="packageCode" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="packageName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="packageName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="packageSpeed" class="form-label">Speed</label>
                        <input type="text" class="form-control" id="packageSpeed" name="speed" required>
                    </div>
                    <div class="mb-3">
                        <label for="packagePrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="packagePrice" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="packagePppoeProfile" class="form-label">PPPoE Profile</label>
                        <input type="text" class="form-control" id="packagePppoeProfile" name="pppoe_profile">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="packageIsActive" name="is_active" value="1" checked>
                        <label class="form-check-label" for="packageIsActive">Active</label>
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
    function loadPackages() {
        $.get('/api/admin/packages.php?action=list', function(data) {
            let tableBody = '';
            if (data.length === 0) {
                tableBody = '<tr><td colspan="6" class="text-center">No packages found.</td></tr>';
            }
            data.forEach(function(pkg) {
                tableBody += `
                    <tr>
                        <td>${pkg.code}</td>
                        <td>${pkg.name}</td>
                        <td>${pkg.speed}</td>
                        <td>${pkg.price}</td>
                        <td><span class="badge bg-${pkg.is_active ? 'success' : 'danger'}">${pkg.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${pkg.id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${pkg.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#packages-table-body').html(tableBody);
        });
    }

    loadPackages();

    $('#packageForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let packageId = $('#packageId').val();
        let url = packageId ? '/api/admin/packages.php?action=update' : '/api/admin/packages.php?action=create';
        $.post(url, formData, function() {
            $('#packageModal').modal('hide');
            loadPackages();
        });
    });

    $('#packages-table-body').on('click', '.btn-edit', function() {
        let packageId = $(this).data('id');
        $.get('/api/admin/packages.php?action=get&id=' + packageId, function(pkg) {
            $('#packageForm')[0].reset();
            $('#packageId').val(pkg.id);
            $('#packageCode').val(pkg.code);
            $('#packageName').val(pkg.name);
            $('#packageSpeed').val(pkg.speed);
            $('#packagePrice').val(pkg.price);
            $('#packagePppoeProfile').val(pkg.pppoe_profile);
            $('#packageIsActive').prop('checked', pkg.is_active == 1);
            $('#packageModal').modal('show');
        });
    });

    $('#packages-table-body').on('click', '.btn-delete', function() {
        if (confirm('Are you sure you want to delete this package?')) {
            let packageId = $(this).data('id');
            $.post('/api/admin/packages.php?action=delete&id=' + packageId, function() {
                loadPackages();
            });
        }
    });

    $('#packageModal').on('hidden.bs.modal', function () {
        $('#packageForm')[0].reset();
        $('#packageId').val('');
    });
});
</script>
EOT;

require_once '../includes/admin_footer.php'; 
?>
