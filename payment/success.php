<?php
$page_title = 'Payment Success';
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
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h3 class="text-success mb-3">Payment Successful!</h3>
                    
                    <?php if (isset($invoice)): ?>
                    <p class="text-muted mb-4">
                        Your payment for invoice <strong>#<?php echo htmlspecialchars($invoice['nomor']); ?></strong> 
                        has been processed successfully.
                    </p>
                    
                    <div class="alert alert-info">
                        <h6>Payment Details</h6>
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
                                <td><strong>Customer:</strong></td>
                                <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-4">
                        Your payment has been processed successfully. You will receive a confirmation email shortly.
                    </p>
                    <?php endif; ?>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>What's next?</strong><br>
                        Your service will be activated automatically within a few minutes. 
                        You'll receive a confirmation via WhatsApp and email.
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="/customer/invoices.php" class="btn btn-primary">
                            <i class="fas fa-file-invoice me-2"></i>View Invoices
                        </a>
                        <a href="/customer/dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <?php else: ?>
                        <a href="/" class="btn btn-primary">
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
.success-icon {
    animation: bounce 1s ease-in-out;
}

@keyframes bounce {
    0%, 20%, 60%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    80% {
        transform: translateY(-5px);
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>