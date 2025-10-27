<?php
// SolusiPaymentManagement Admin - Employees Management

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.employees');

$user = getCurrentUser();
$pageTitle = 'Employees Management';

// Get database instance
global $db;

// Get all employees
$employees = $db->fetchAll(
    "SELECT k.*, p.nama, p.email FROM karyawan k 
     LEFT JOIN pengguna p ON k.user_id = p.id 
     ORDER BY k.created_at DESC"
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Employees</h3>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fas fa-plus me-2"></i>Add Employee
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
                                <th>Name</th>
                                <th>Email</th>
                                <th>NIP</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No employees found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($emp['nama']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['nip'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['departemen'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['posisi'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editEmployee(<?php echo $emp['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $emp['id']; ?>)">
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIP</label>
                        <input type="text" class="form-control" name="nip">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="departemen">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" name="posisi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Base Salary</label>
                        <input type="number" class="form-control" name="gaji_pokok" step="0.01">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEmployee()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function editEmployee(id) {
    alert('Edit functionality coming soon');
}

function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        alert('Delete functionality coming soon');
    }
}

function saveEmployee() {
    alert('Save functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

