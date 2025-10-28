<?php
$page_title = 'Assets Management';
require_once __DIR__ . '/../includes/admin_header.php';
$guard->requirePermission('admin.assets');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-box me-2"></i>Assets Management</h2>
    <button class="btn btn-primary" onclick="showCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Asset
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Assets</h6>
                <h3 id="total-count">0</h3>
                <p class="mb-0" id="total-value">Rp 0</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Available</h6>
                <h3 id="available-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>In Use</h6>
                <h3 id="used-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6>Maintenance</h6>
                <h3 id="maintenance-count">0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6>Damaged</h6>
                <h3 id="damaged-count">0</h3>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="tersedia">Available</option>
                    <option value="digunakan">In Use</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="rusak">Damaged</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="kategori-filter">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="kondisi-filter">
                    <option value="">All Conditions</option>
                    <option value="baik">Good</option>
                    <option value="cukup">Fair</option>
                    <option value="buruk">Poor</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" id="search-filter" placeholder="Search by code, name, location...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="fas fa-undo"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Value</th>
                        <th>Purchase Date</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Location/Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="assets-tbody">
                    <tr><td colspan="9" class="text-center"><div class="spinner-border"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Asset Modal -->
<div class="modal fade" id="assetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assetForm">
                    <input type="hidden" id="asset-id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Code *</label>
                            <input type="text" class="form-control" id="asset-kode" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Name *</label>
                            <input type="text" class="form-control" id="asset-nama" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" id="asset-kategori" list="kategori-list">
                            <datalist id="kategori-list">
                                <option value="Computer">
                                <option value="Furniture">
                                <option value="Vehicle">
                                <option value="Equipment">
                                <option value="Electronics">
                                <option value="Network">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Value</label>
                            <input type="number" class="form-control" id="asset-nilai" value="0" step="100000">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="asset-tgl" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Condition</label>
                            <select class="form-select" id="asset-kondisi">
                                <option value="baik">Good</option>
                                <option value="cukup">Fair</option>
                                <option value="buruk">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="asset-status">
                                <option value="tersedia">Available</option>
                                <option value="digunakan">In Use</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="rusak">Damaged</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" id="asset-lokasi" placeholder="e.g. Office 2nd Floor">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Economic Life (months)</label>
                            <input type="number" class="form-control" id="asset-umur" value="60" min="1">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAsset()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign-asset-id">
                <div class="mb-3">
                    <label class="form-label">Asset</label>
                    <input type="text" class="form-control" id="assign-asset-name" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assign To Employee</label>
                    <select class="form-select" id="assign-employee">
                        <option value="">Select Employee</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignAsset()">Assign</button>
            </div>
        </div>
    </div>
</div>

<script>
let assetModal, assignModal;

$(document).ready(function() {
    assetModal = new bootstrap.Modal(document.getElementById('assetModal'));
    assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    loadSummary();
    loadAssets();
    loadEmployees();
    $('#status-filter, #kategori-filter, #kondisi-filter, #search-filter').on('change keyup', loadAssets);
});

function loadSummary() {
    $.get('/api/admin/assets.php?action=summary')
    .done(function(response) {
        if (response.success && response.data) {
            const d = response.data;
            $('#total-count').text(d.total || 0);
            $('#total-value').text(formatRupiah(d.total_value || 0));
            $('#available-count').text(d.tersedia || 0);
            $('#used-count').text(d.digunakan || 0);
            $('#maintenance-count').text(d.maintenance || 0);
            $('#damaged-count').text(d.rusak || 0);

            // Populate category filter
            const kategoriFilter = $('#kategori-filter');
            kategoriFilter.find('option:not(:first)').remove();
            if (d.categories && d.categories.length > 0) {
                d.categories.forEach(cat => {
                    kategoriFilter.append(`<option value="${cat.kategori}">${cat.kategori} (${cat.count})</option>`);
                });
            }
        }
    });
}

function loadAssets() {
    const params = new URLSearchParams({
        action: 'list',
        status: $('#status-filter').val(),
        kategori: $('#kategori-filter').val(),
        kondisi: $('#kondisi-filter').val(),
        search: $('#search-filter').val()
    });

    $.get('/api/admin/assets.php?' + params.toString())
    .done(function(response) {
        if (response.success) {
            const tbody = $('#assets-tbody');
            tbody.empty();

            if (response.data.length === 0) {
                tbody.append('<tr><td colspan="9" class="text-center text-muted">No assets found</td></tr>');
                return;
            }

            response.data.forEach(asset => {
                const statusBadge = getStatusBadge(asset.status);
                const kondisiBadge = getKondisiBadge(asset.kondisi);
                const locationInfo = asset.assigned_to_name || asset.lokasi || '-';

                tbody.append(`
                    <tr>
                        <td><strong>${asset.kode}</strong></td>
                        <td>${asset.nama}</td>
                        <td>${asset.kategori || '-'}</td>
                        <td>${formatRupiah(asset.nilai_perolehan || 0)}</td>
                        <td>${formatDate(asset.tgl_beli)}</td>
                        <td>${kondisiBadge}</td>
                        <td>${statusBadge}</td>
                        <td>${locationInfo}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editAsset(${asset.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="showAssignModal(${asset.id}, '${asset.nama}')" title="Assign">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteAsset(${asset.id})" title="Delete">
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

function loadEmployees() {
    $.get('/api/admin/employees.php?action=list&status=active')
    .done(function(response) {
        if (response.success && response.data) {
            const select = $('#assign-employee');
            select.empty().append('<option value="">Select Employee</option>');
            response.data.forEach(emp => {
                select.append(`<option value="${emp.id}">${emp.nama} (${emp.nip})</option>`);
            });
        }
    });
}

function showCreateModal() {
    $('#modalTitle').text('Add Asset');
    $('#assetForm')[0].reset();
    $('#asset-id').val('');
    assetModal.show();
}

function editAsset(id) {
    $.get('/api/admin/assets.php?action=get&id=' + id)
    .done(function(response) {
        if (response.success && response.data) {
            const asset = response.data;
            $('#modalTitle').text('Edit Asset');
            $('#asset-id').val(asset.id);
            $('#asset-kode').val(asset.kode);
            $('#asset-nama').val(asset.nama);
            $('#asset-kategori').val(asset.kategori);
            $('#asset-nilai').val(asset.nilai_perolehan);
            $('#asset-tgl').val(asset.tgl_beli);
            $('#asset-kondisi').val(asset.kondisi);
            $('#asset-status').val(asset.status);
            $('#asset-lokasi').val(asset.lokasi);
            $('#asset-umur').val(asset.umur_ekonomis_bulan);
            assetModal.show();
        }
    });
}

function saveAsset() {
    const form = $('#assetForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const data = {
        id: $('#asset-id').val(),
        kode: $('#asset-kode').val(),
        nama: $('#asset-nama').val(),
        kategori: $('#asset-kategori').val(),
        nilai_perolehan: parseFloat($('#asset-nilai').val()) || 0,
        tgl_beli: $('#asset-tgl').val(),
        kondisi: $('#asset-kondisi').val(),
        status: $('#asset-status').val(),
        lokasi: $('#asset-lokasi').val(),
        umur_ekonomis_bulan: parseInt($('#asset-umur').val()) || 60
    };

    const url = data.id ? '/api/admin/assets.php?action=update' : '/api/admin/assets.php?action=create';

    $.ajax({
        url: url,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                assetModal.hide();
                loadAssets();
                loadSummary();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

function showAssignModal(id, name) {
    $('#assign-asset-id').val(id);
    $('#assign-asset-name').val(name);
    $('#assign-employee').val('');
    assignModal.show();
}

function assignAsset() {
    const data = {
        id: $('#assign-asset-id').val(),
        employee_id: $('#assign-employee').val() || null
    };

    $.ajax({
        url: '/api/admin/assets.php?action=assign',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                alert(response.message);
                assignModal.hide();
                loadAssets();
                loadSummary();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

function deleteAsset(id) {
    if (!confirm('Delete this asset?')) return;

    $.post('/api/admin/assets.php?action=delete', { id: id })
    .done(function(response) {
        if (response.success) {
            alert(response.message);
            loadAssets();
            loadSummary();
        } else {
            alert('Error: ' + response.message);
        }
    });
}

function clearFilters() {
    $('#status-filter, #kategori-filter, #kondisi-filter, #search-filter').val('');
    loadAssets();
}

function getStatusBadge(status) {
    const badges = {
        'tersedia': '<span class="badge bg-success">Available</span>',
        'digunakan': '<span class="badge bg-info">In Use</span>',
        'maintenance': '<span class="badge bg-warning">Maintenance</span>',
        'rusak': '<span class="badge bg-danger">Damaged</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function getKondisiBadge(kondisi) {
    const badges = {
        'baik': '<span class="badge bg-success">Good</span>',
        'cukup': '<span class="badge bg-warning">Fair</span>',
        'buruk': '<span class="badge bg-danger">Poor</span>'
    };
    return badges[kondisi] || '<span class="badge bg-secondary">' + kondisi + '</span>';
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
