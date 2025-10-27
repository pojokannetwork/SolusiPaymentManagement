<?php
// SolusiPaymentManagement Employee - Attendance

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('employee.attendance');

$user = getCurrentUser();
$pageTitle = 'Attendance';

// Get database instance
global $db;

// Get current employee
$employee = $db->fetchOne(
    "SELECT * FROM karyawan WHERE user_id = ?",
    [$user['id']]
);

// Get attendance records for current month
$currentMonth = date('Y-m');
$attendance = $db->fetchAll(
    "SELECT * FROM kehadiran WHERE karyawan_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ? ORDER BY tanggal DESC",
    [$employee['id'], $currentMonth]
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h3>Attendance</h3>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Present</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Absent</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-times-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Late</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Overtime Hours</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-hourglass-end fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Clock In/Out</h5>
                <div>
                    <button class="btn btn-success btn-sm" onclick="clockIn()">
                        <i class="fas fa-sign-in-alt me-2"></i>Clock In
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="clockOut()">
                        <i class="fas fa-sign-out-alt me-2"></i>Clock Out
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Current Time:</strong> <span id="currentTime">--:--:--</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span id="clockStatus" class="badge bg-secondary">Not Clocked In</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Attendance Records - <?php echo date('F Y'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Overtime Hours</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendance)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No attendance records for this month</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($attendance as $att): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($att['tanggal'])); ?></strong>
                                    </td>
                                    <td><?php echo $att['clock_in'] ? date('H:i', strtotime($att['clock_in'])) : '-'; ?></td>
                                    <td><?php echo $att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : '-'; ?></td>
                                    <td><?php echo $att['lembur_jam'] > 0 ? $att['lembur_jam'] . ' hrs' : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($att['catatan'] ?? ''); ?></td>
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

<script>
// Update current time
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID');
}

// Clock in
function clockIn() {
    alert('Clock in functionality coming soon');
}

// Clock out
function clockOut() {
    alert('Clock out functionality coming soon');
}

// Update time every second
setInterval(updateTime, 1000);
updateTime();
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

