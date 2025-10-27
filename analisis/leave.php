<?php
// SolusiPaymentManagement Employee - Leave Requests

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('employee.leave');

$user = getCurrentUser();
$pageTitle = 'Leave Requests';

// Get database instance
global $db;

// Get current employee
$employee = $db->fetchOne(
    "SELECT * FROM karyawan WHERE user_id = ?",
    [$user['id']]
);

// Get leave requests
$leaves = $db->fetchAll(
    "SELECT * FROM cuti_permintaan WHERE karyawan_id = ? ORDER BY tgl_mulai DESC",
    [$employee['id']]
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Leave Requests</h3>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
                <i class="fas fa-plus me-2"></i>Request Leave
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Approved</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
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
    <div class="col-md-4">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Rejected</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-times-circle fa-2x opacity-75"></i>
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
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaves)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No leave requests found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($leave['tgl_mulai'])); ?></strong>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($leave['tgl_selesai'])); ?></td>
                                    <td>
                                        <?php 
                                            $start = new DateTime($leave['tgl_mulai']);
                                            $end = new DateTime($leave['tgl_selesai']);
                                            $diff = $end->diff($start);
                                            echo ($diff->days + 1) . ' days';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($leave['alasan'], 0, 50)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $leave['status'] === 'approved' ? 'success' : 
                                                 ($leave['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewLeave(<?php echo $leave['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelLeave(<?php echo $leave['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addLeaveForm">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="tgl_mulai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="tgl_selesai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="alasan" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveLeave()">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewLeave(id) {
    alert('View details functionality coming soon');
}

function cancelLeave(id) {
    if (confirm('Are you sure you want to cancel this leave request?')) {
        alert('Cancel functionality coming soon');
    }
}

function saveLeave() {
    alert('Submit functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

