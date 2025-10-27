<?php
$page_title = 'Transactions';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard->requirePermission('admin.transactions');
?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transactions</h2>
    </div>
    <div class="alert alert-info">Transactions module coming soon.</div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
