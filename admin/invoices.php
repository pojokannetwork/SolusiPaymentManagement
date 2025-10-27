<?php
// SolusiPaymentManagement Admin Invoices Page (Responsive Template)
$page_title = 'Invoices';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard->requirePermission('admin.invoices');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Invoices</h2>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5>Invoices Summary</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Module content coming soon.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

