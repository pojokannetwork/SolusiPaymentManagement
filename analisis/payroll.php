<?php
// SolusiPaymentManagement Admin - Payroll Management

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.payroll');

$user = getCurrentUser();
$pageTitle = 'Payroll Management';

// Get database instance
global $db;

// Get payroll records
$payroll = $db->fetchAll(
    "SELECT p.*, k.nip, peng.nama FROM payroll p
     LEFT JOIN karyawan k ON p.karyawan_id = k.id
     LEFT JOIN pengguna peng ON k.user_id = peng.id
     ORDER BY p.periode_tahun DESC, p.periode_bulan DESC"
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Payroll</h3>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayrollModal">
                <i class="fas fa-plus me-2"></i>Add Payroll
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Payroll</h6>
                        <h3>Rp 0</h3>
                    </div>
                    <i class="fas fa-money-bill fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Paid</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Pending</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-hourglass-half fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Approved</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-thumbs-up fa-2x opacity-75"></i>
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
                                <th>Employee</th>
                                <th>Period</th>
                                <th>Base Salary</th>
                                <th>Overtime</th>
                                <th>Bonus</th>
                                <th>Deductions</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payroll)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No payroll records found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($payroll as $pay): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($pay['nama'] ?? 'Unknown'); ?></strong>
                                    </td>
                                    <td><?php echo $pay['periode_bulan'] . '/' . $pay['periode_tahun']; ?></td>
                                    <td>Rp <?php echo number_format($pay['gaji_pokok'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($pay['lembur'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($pay['bonus'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($pay['potongan'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($pay['pajak'], 0, ',', '.'); ?></td>
                                    <td>
                                        <strong>Rp <?php echo number_format($pay['total_bayar'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $pay['status'] === 'paid' ? 'success' : 
                                                 ($pay['status'] === 'approved' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($pay['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editPayroll(<?php echo $pay['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deletePayroll(<?php echo $pay['id']; ?>)">
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

<!-- Add Payroll Modal -->
<div class="modal fade" id="addPayrollModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPayrollForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="karyawan_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Period</label>
                            <input type="month" class="form-control" name="periode" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Salary</label>
                            <input type="number" class="form-control" name="gaji_pokok" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Overtime</label>
                            <input type="number" class="form-control" name="lembur" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bonus</label>
                            <input type="number" class="form-control" name="bonus" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deductions</label>
                            <input type="number" class="form-control" name="potongan" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax</label>
                            <input type="number" class="form-control" name="pajak" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="draft">Draft</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePayroll()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function editPayroll(id) {
    alert('Edit functionality coming soon');
}

function deletePayroll(id) {
    if (confirm('Are you sure you want to delete this payroll record?')) {
        alert('Delete functionality coming soon');
    }
}

function savePayroll() {
    alert('Save functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

