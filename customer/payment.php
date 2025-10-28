<?php
$page_title = 'Payment Gateway';
require_once __DIR__ . '/../includes/header.php';

$invoiceId = (int)($_GET['invoice'] ?? 0);

if ($invoiceId <= 0) {
    redirect('/customer/invoices.php');
    exit;
}

// Get invoice details
$invoice = $db->fetchOne(
    "SELECT f.*, p.nama as customer_name, p.email, p.telp 
     FROM faktur f 
     JOIN pelanggan p ON f.pelanggan_id = p.id 
     WHERE f.id = ? AND p.id = ?",
    [$invoiceId, $_SESSION['customer_id'] ?? 0]
);

if (!$invoice) {
    redirect('/customer/invoices.php');
    exit;
}

if ($invoice['status'] === 'paid') {
    redirect('/customer/invoices.php?msg=already_paid');
    exit;
}

// Get available gateways
$gateways = $db->fetchAll(
    "SELECT id, nama, provider FROM payment_gateways WHERE is_active = 1 ORDER BY nama ASC"
);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Gateway - Invoice #<?php echo htmlspecialchars($invoice['nomor']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Invoice Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Invoice Details</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Invoice Number:</strong></td>
                                            <td><?php echo htmlspecialchars($invoice['nomor']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount:</strong></td>
                                            <td class="fw-bold text-primary">Rp <?php echo number_format($invoice['total'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Due Date:</strong></td>
                                            <td><?php echo date('d M Y', strtotime($invoice['jatuh_tempo'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <h6>Select Payment Method</h6>
                    
                    <?php if (empty($gateways)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No payment gateways are currently available. Please contact administrator.
                    </div>
                    <?php else: ?>
                    
                    <div class="row">
                        <?php foreach ($gateways as $gateway): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card gateway-card h-100" onclick="selectGateway(<?php echo $gateway['id']; ?>, '<?php echo htmlspecialchars($gateway['nama']); ?>')">
                                <div class="card-body text-center">
                                    <div class="gateway-icon mb-3">
                                        <?php 
                                        $icon = getProviderIcon($gateway['provider']);
                                        echo $icon;
                                        ?>
                                    </div>
                                    <h6><?php echo htmlspecialchars($gateway['nama']); ?></h6>
                                    <small class="text-muted"><?php echo ucfirst($gateway['provider']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Processing Payment</h5>
            </div>
            <div class="modal-body text-center">
                <div id="loading-section">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Creating payment link...</p>
                </div>
                <div id="success-section" style="display: none;">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p>Payment link created successfully!</p>
                    <p class="small text-muted">You will be redirected to the payment page.</p>
                </div>
                <div id="error-section" style="display: none;">
                    <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                    <p id="error-message">An error occurred while creating payment.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gateway-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.gateway-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,123,255,0.2);
    transform: translateY(-2px);
}

.gateway-icon {
    font-size: 2.5rem;
    color: #007bff;
}
</style>

<script>
function selectGateway(gatewayId, gatewayName) {
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
    
    // Create payment
    fetch('/api/create_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            invoice_id: <?php echo $invoiceId; ?>,
            gateway_id: gatewayId,
            amount: <?php echo $invoice['total']; ?>,
            customer_name: '<?php echo addslashes($invoice['customer_name']); ?>',
            customer_email: '<?php echo addslashes($invoice['email']); ?>',
            customer_phone: '<?php echo addslashes($invoice['telp']); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading-section').style.display = 'none';
        
        if (data.success) {
            document.getElementById('success-section').style.display = 'block';
            
            // Redirect to payment URL
            if (data.payment_url) {
                setTimeout(() => {
                    window.location.href = data.payment_url;
                }, 2000);
            } else {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } else {
            document.getElementById('error-section').style.display = 'block';
            document.getElementById('error-message').textContent = data.message || 'Payment creation failed';
        }
    })
    .catch(error => {
        document.getElementById('loading-section').style.display = 'none';
        document.getElementById('error-section').style.display = 'block';
        document.getElementById('error-message').textContent = 'Network error occurred';
    });
}
</script>

<?php 

function getProviderIcon($provider) {
    $icons = [
        'midtrans' => '<i class="fab fa-cc-visa"></i>',
        'xendit' => '<i class="fas fa-credit-card"></i>',
        'tripay' => '<i class="fas fa-wallet"></i>',
        'duitku' => '<i class="fas fa-university"></i>',
        'doku' => '<i class="fas fa-building"></i>',
        'ovo' => '<i class="fas fa-mobile-alt"></i>',
        'gopay' => '<i class="fas fa-motorcycle"></i>'
    ];
    
    return $icons[$provider] ?? '<i class="fas fa-credit-card"></i>';
}

require_once __DIR__ . '/../includes/footer.php'; 
?>