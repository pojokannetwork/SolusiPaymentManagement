<?php
$page_title = 'Payment Cancelled';
require_once __DIR__ . '/../includes/header.php';

$invoiceId = (int)($_GET['invoice'] ?? 0);

if ($invoiceId > 0) {
    // Get invoice details
    $invoice = $db->fetchOne(
        "SELECT f.*, p.nama as customer_name 
         FROM faktur f 
         JOIN pelanggan p ON f.pelanggan_id = p.id 
         WHERE f.id = ?",
        [$invoiceId]
    );
    
    if ($invoice && isset($_SESSION['customer_id']) && $invoice['pelanggan_id'] != $_SESSION['customer_id']) {
        $invoice = null; // Security check
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card text-center">
                <div class="card-body p-5">
                    <div class="cancel-icon mb-4">
                        <i class="fas fa-times-circle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h3 class="text-warning mb-3">Payment Cancelled</h3>
                    
                    <?php if (isset($invoice)): ?>
                    <p class="text-muted mb-4">
                        Payment for invoice <strong>#<?php echo htmlspecialchars($invoice['nomor']); ?></strong> 
                        has been cancelled.
                    </p>
                    
                    <div class="alert alert-info">
                        <h6>Invoice Details</h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>Invoice:</strong></td>
                                <td>#<?php echo htmlspecialchars($invoice['nomor']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>Rp <?php echo number_format($invoice['total'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-warning">Unpaid</span></td>
                            </tr>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-4">
                        Your payment has been cancelled. No charges were made to your account.
                    </p>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Payment Required</strong><br>
                        This invoice still needs to be paid. Your service may be suspended if payment is not received by the due date.
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if (isset($invoice)): ?>
                        <a href="/customer/payment.php?invoice=<?php echo $invoiceId; ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card me-2"></i>Try Payment Again
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="/customer/invoices.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-invoice me-2"></i>View Invoices
                        </a>
                        <a href="/customer/dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <?php else: ?>
                        <a href="/" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cancel-icon {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>