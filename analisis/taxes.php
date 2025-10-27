<?php
// SolusiPaymentManagement Admin - Taxes Management

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.taxes');

$user = getCurrentUser();
$pageTitle = 'Taxes Management';

// Get database instance
global $db;

// Get all tax records
$taxes = $db->fetchAll(
    "SELECT * FROM pajak ORDER BY periode_tahun DESC, periode_bulan DESC"
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Taxes</h3>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaxModal">
                <i class="fas fa-plus me-2"></i>Add Tax Record
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Tax Base</h6>
                        <h3>Rp 0</h3>
                    </div>
                    <i class="fas fa-percent fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Tax Amount</h6>
                        <h3>Rp 0</h3>
                    </div>
                    <i class="fas fa-money-bill fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Draft Records</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-file-alt fa-2x opacity-75"></i>
                </div>
            </div>
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
                                <th>Period</th>
                                <th>Type</th>
                                <th>Tax Base</th>
                                <th>Rate</th>
                                <th>Tax Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($taxes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No tax records found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($taxes as $tax): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $tax['periode_bulan'] . '/' . $tax['periode_tahun']; ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($tax['jenis']); ?></td>
                                    <td>Rp <?php echo number_format($tax['dasar_pengenaan'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($tax['tarif'] * 100, 2); ?>%</td>
                                    <td>
                                        <strong>Rp <?php echo number_format($tax['nilai'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $tax['status'] === 'final' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($tax['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editTax(<?php echo $tax['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteTax(<?php echo $tax['id']; ?>)">
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

<!-- Add Tax Modal -->
<div class="modal fade" id="addTaxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Tax Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTaxForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month</label>
                            <select class="form-select" name="periode_bulan" required>
                                <option value="">Select Month</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year</label>
                            <select class="form-select" name="periode_tahun" required>
                                <option value="">Select Year</option>
                                <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Type</label>
                        <input type="text" class="form-control" name="jenis" placeholder="e.g., PPh, PPN" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Base</label>
                        <input type="number" class="form-control" name="dasar_pengenaan" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" class="form-control" name="tarif" step="0.0001" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="draft">Draft</option>
                            <option value="final">Final</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTax()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function editTax(id) {
    alert('Edit functionality coming soon');
}

function deleteTax(id) {
    if (confirm('Are you sure you want to delete this tax record?')) {
        alert('Delete functionality coming soon');
    }
}

function saveTax() {
    alert('Save functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

