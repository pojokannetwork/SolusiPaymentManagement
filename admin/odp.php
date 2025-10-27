<?php
$page_title = 'ODP Management';
require_once '../includes/admin_header.php';

// Security check: Ensure user is an admin and has permission
$guard->requirePermission('admin.odp');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">ODP Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#odpModal">
            <i class="fas fa-plus"></i> Add ODP
        </button>
    </div>
</div>

<!-- ODP Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Ports (Avail/Total)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="odp-table-body">
                    <!-- ODP rows will be inserted here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ODP Modal -->
<div class="modal fade" id="odpModal" tabindex="-1" aria-labelledby="odpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odpModalLabel">Add/Edit ODP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="odpForm">
                    <input type="hidden" id="odpId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="odpCode" class="form-label">Code</label>
                                <input type="text" class="form-control" id="odpCode" name="code" required>
                            </div>
                            <div class="mb-3">
                                <label for="odpName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="odpName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="odpAddress" class="form-label">Address</label>
                                <textarea class="form-control" id="odpAddress" name="address" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="odpLatitude" class="form-label">Latitude</label>
                                        <input type="text" class="form-control" id="odpLatitude" name="latitude">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="odpLongitude" class="form-label">Longitude</label>
                                        <input type="text" class="form-control" id="odpLongitude" name="longitude">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="odpTotalPorts" class="form-label">Total Ports</label>
                                <input type="number" class="form-control" id="odpTotalPorts" name="total_ports" required>
                            </div>
                            <div class="mb-3">
                                <label for="odpStatus" class="form-label">Status</label>
                                <select class="form-select" id="odpStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="full">Full</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odpNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="odpNotes" name="notes" rows="2"></textarea>
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
    function loadOdps() {
        $.get('/api/admin/odp.php?action=list', function(data) {
            let tableBody = '';
            if(data.length === 0) {
                tableBody = '<tr><td colspan="6" class="text-center">No ODPs found.</td></tr>';
            }
            data.forEach(function(odp) {
                tableBody += `
                    <tr>
                        <td>${odp.code}</td>
                        <td>${odp.name}</td>
                        <td>${odp.address || ''}</td>
                        <td>${odp.available_ports}/${odp.total_ports}</td>
                        <td><span class="badge bg-${getBootstrapClassForStatus(odp.status)}">${odp.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-edit" data-id="${odp.id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${odp.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#odp-table-body').html(tableBody);
        });
    }

    function getBootstrapClassForStatus(status) {
        switch (status) {
            case 'active': return 'success';
            case 'inactive': return 'secondary';
            case 'full': return 'warning';
            case 'maintenance': return 'info';
            default: return 'dark';
        }
    }

    loadOdps();

    $('#odpForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let odpId = $('#odpId').val();
        let url = odpId ? '/api/admin/odp.php?action=update' : '/api/admin/odp.php?action=create';
        $.post(url, formData, function() {
            $('#odpModal').modal('hide');
            loadOdps();
        });
    });

    $('#odp-table-body').on('click', '.btn-edit', function() {
        let odpId = $(this).data('id');
        $.get('/api/admin/odp.php?action=get&id=' + odpId, function(odp) {
            $('#odpForm')[0].reset();
            $('#odpId').val(odp.id);
            $('#odpCode').val(odp.code);
            $('#odpName').val(odp.name);
            $('#odpAddress').val(odp.address);
            $('#odpLatitude').val(odp.latitude);
            $('#odpLongitude').val(odp.longitude);
            $('#odpTotalPorts').val(odp.total_ports);
            $('#odpStatus').val(odp.status);
            $('#odpNotes').val(odp.notes);
            $('#odpModal').modal('show');
        });
    });

    $('#odp-table-body').on('click', '.btn-delete', function() {
        if (confirm('Are you sure you want to delete this ODP?')) {
            let odpId = $(this).data('id');
            $.post('/api/admin/odp.php?action=delete&id=' + odpId, function() {
                loadOdps();
            });
        }
    });

    $('#odpModal').on('hidden.bs.modal', function () {
        $('#odpForm')[0].reset();
        $('#odpId').val('');
    });
});
</script>
EOT;

require_once '../includes/admin_footer.php';
?>
