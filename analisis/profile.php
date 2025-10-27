<?php
// SolusiPaymentManagement Employee - Profile

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('employee.profile');

$user = getCurrentUser();
$pageTitle = 'My Profile';

// Get database instance
global $db;

// Get current employee
$employee = $db->fetchOne(
    "SELECT k.*, p.nama, p.email FROM karyawan k 
     LEFT JOIN pengguna p ON k.user_id = p.id 
     WHERE k.user_id = ?",
    [$user['id']]
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h3>My Profile</h3>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($employee['nama']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($employee['posisi'] ?? 'Employee'); ?></p>
                <p class="text-muted"><?php echo htmlspecialchars($employee['departemen'] ?? 'N/A'); ?></p>
                <hr>
                <p class="small">
                    <strong>Employee ID:</strong><br>
                    <?php echo htmlspecialchars($employee['nip'] ?? 'N/A'); ?>
                </p>
                <p class="small">
                    <strong>Join Date:</strong><br>
                    <?php echo $employee['tgl_masuk'] ? date('d M Y', strtotime($employee['tgl_masuk'])) : 'N/A'; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Personal Information</h5>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['nama']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">NIP</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['nip'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['departemen'] ?? ''); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['posisi'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Join Date</label>
                            <input type="date" class="form-control" value="<?php echo $employee['tgl_masuk'] ?? ''; ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Base Salary</label>
                            <input type="text" class="form-control" value="Rp <?php echo number_format($employee['gaji_pokok'], 0, ',', '.'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($employee['status']); ?>" readonly>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5>Account Security</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-warning" onclick="changePassword()">
                    <i class="fas fa-key me-2"></i>Change Password
                </button>
                <button class="btn btn-danger" onclick="logout()">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePassword()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function changePassword() {
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

function savePassword() {
    alert('Change password functionality coming soon');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/logout';
    }
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

