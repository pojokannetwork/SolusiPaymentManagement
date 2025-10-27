<?php
// SolusiPaymentManagement Customer - Invoices

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('customer.invoices');

$user = getCurrentUser();
$pageTitle = 'Invoices';

// Get database instance
global $db;

// Get customer
$customer = $db->fetchOne(
    "SELECT * FROM pelanggan WHERE email = ?",
    [$user['email']]
);

// Get invoices
$invoices = $db->fetchAll(
    "SELECT * FROM faktur WHERE pelanggan_id = ? ORDER BY tanggal DESC",
    [$customer['id']]
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h3>My Invoices</h3>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Invoices</h6>
                        <h3><?php echo count($invoices); ?></h3>
                    </div>
                    <i class="fas fa-file-invoice fa-2x opacity-75"></i>
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
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Overdue</h6>
                        <h3>0</h3>
                    </div>
                    <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
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
                                <th>Invoice Number</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No invoices found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($invoice['nomor']); ?></strong>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($invoice['tanggal'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($invoice['jatuh_tempo'])); ?></td>
                                    <td>
                                        <strong>Rp <?php echo number_format($invoice['total'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $invoice['status'] === 'paid' ? 'success' : 
                                                 ($invoice['status'] === 'sent' ? 'info' : 
                                                  ($invoice['status'] === 'overdue' ? 'danger' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($invoice['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="downloadInvoice(<?php echo $invoice['id']; ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <?php if ($invoice['status'] !== 'paid'): ?>
                                        <button class="btn btn-sm btn-success" onclick="payInvoice(<?php echo $invoice['id']; ?>)">
                                            <i class="fas fa-credit-card"></i>
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

<script>
function viewInvoice(id) {
    alert('View invoice functionality coming soon');
}

function downloadInvoice(id) {
    alert('Download invoice functionality coming soon');
}

function payInvoice(id) {
    alert('Payment gateway functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

