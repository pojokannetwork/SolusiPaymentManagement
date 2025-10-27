<?php
// SolusiPaymentManagement Customer - Payments

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('customer.payments');

$user = getCurrentUser();
$pageTitle = 'Payment History';

// Get database instance
global $db;

// Get customer
$customer = $db->fetchOne(
    "SELECT * FROM pelanggan WHERE email = ?",
    [$user['email']]
);

// Get payment transactions
$payments = $db->fetchAll(
    "SELECT t.*, f.nomor as invoice_number FROM transaksi t
     LEFT JOIN faktur f ON t.faktur_id = f.id
     WHERE f.pelanggan_id = ? ORDER BY t.created_at DESC",
    [$customer['id']]
);

// Start output buffering for content
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h3>Payment History</h3>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Total Paid</h6>
                        <h3>Rp 0</h3>
                    </div>
                    <i class="fas fa-money-bill fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">Successful</h6>
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
                        <h6 class="card-title">Failed</h6>
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
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No payment history found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['invoice_number']); ?></strong>
                                    </td>
                                    <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['gateway_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['external_ref'] ?? '-'); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $payment['status'] === 'paid' ? 'success' : 
                                                 ($payment['status'] === 'pending' ? 'warning' : 
                                                  ($payment['status'] === 'failed' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
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

<script>
function viewPayment(id) {
    alert('View payment details functionality coming soon');
}
</script>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../templates/layout.php';
?>

