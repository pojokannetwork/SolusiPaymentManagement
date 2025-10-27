<?php
// SolusiPaymentManagement Admin - Assets Management

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.assets');

$user = getCurrentUser();
$pageTitle = 'Assets Management';

// Get database instance
global $db;

// Get all assets
$assets = $db->fetchAll(
    "SELECT * FROM aset ORDER BY created_at DESC"
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Assets</h3>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                <i class="fas fa-plus me-2"></i>Add Asset
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Purchase Value</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No assets found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asset['kode']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($asset['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['kategori'] ?? '-'); ?></td>
                                    <td>Rp <?php echo number_format($asset['nilai_perolehan'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $asset['kondisi'] === 'baik' ? 'success' : 
                                                 ($asset['kondisi'] === 'rusak_ringan' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $asset['kondisi'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $asset['status'] === 'tersedia' ? 'success' : 
                                                 ($asset['status'] === 'digunakan' ? 'info' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($asset['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($asset['lokasi'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editAsset(<?php echo $asset['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAsset(<?php echo $asset['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAssetForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Code</label>
                            <input type="text" class="form-control" name="kode" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="kategori">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Value</label>
                            <input type="number" class="form-control" name="nilai_perolehan" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="tgl_beli">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condition</label>
                            <select class="form-select" name="kondisi">
                                <option value="baik">Good</option>
                                <option value="rusak_ringan">Minor Damage</option>
                                <option value="rusak_berat">Major Damage</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="tersedia">Available</option>
                                <option value="digunakan">In Use</option>
                                <option value="dijual">For Sale</option>
                                <option value="rusak">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="lokasi">
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

<script>
function editAsset(id) {
    alert('Edit functionality coming soon');
}

function deleteAsset(id) {
    if (confirm('Are you sure you want to delete this asset?')) {
        alert('Delete functionality coming soon');
    }
}

function saveAsset() {
    alert('Save functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

