<?php
// Demo Responsive Invoice Page (No Authentication Required)

include __DIR__ . '/../includes/admin_template_responsive.php';

$user = ['name' => 'Administrator', 'role' => 'Super Admin'];

$content = '
<div class="p-4">
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div class="mb-2 mb-md-0">
        <h2 class="heading-responsive mb-1">Invoice Management</h2>
        <p class="text-muted mb-0">Kelola invoice dan tagihan pelengkap dengan interface responsif</p>
    </div>
    <div class="btn-group-responsive">
        <button class="btn btn-primary btn-responsive">
            <i class="fas fa-plus me-2"></i>Tambah Invoice
        </button>
        <button class="btn btn-outline-secondary btn-responsive">
            <i class="fas fa-download me-2"></i>Export
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card text-center">
            <div class="stat-icon bg-primary text-white mx-auto">
                <i class="fas fa-file-invoice"></i>
            </div>
            <h3 class="mb-1">1,234</h3>
            <p class="text-muted mb-0">Total Invoices</p>
            <small class="text-success"><i class="fas fa-arrow-up"></i> +12% this month</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card text-center">
            <div class="stat-icon bg-success text-white mx-auto">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="mb-1">987</h3>
            <p class="text-muted mb-0">Paid Invoices</p>
            <small class="text-success"><i class="fas fa-arrow-up"></i> +8% this month</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card text-center">
            <div class="stat-icon bg-warning text-white mx-auto">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="mb-1">156</h3>
            <p class="text-muted mb-0">Pending</p>
            <small class="text-warning"><i class="fas fa-minus"></i> -2% this month</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card text-center">
            <div class="stat-icon bg-danger text-white mx-auto">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="mb-1">23</h3>
            <p class="text-muted mb-0">Overdue</p>
            <small class="text-danger"><i class="fas fa-arrow-down"></i> -5% this month</small>
        </div>
    </div>
</div>

<!-- Invoice Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Recent Invoices
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-mobile table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td data-label="Invoice ID">#INV-2025-001</td>
                                <td data-label="Customer">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-primary text-white me-2">JD</div>
                                        <div>
                                            <div class="fw-semibold">John Doe</div>
                                            <small class="text-muted">john@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Amount">
                                    <span class="fw-bold text-success">Rp 2,500,000</span>
                                </td>
                                <td data-label="Due Date">30 Oct 2025</td>
                                <td data-label="Status">
                                    <span class="badge bg-success">Paid</span>
                                </td>
                                <td data-label="Actions">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td data-label="Invoice ID">#INV-2025-002</td>
                                <td data-label="Customer">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-success text-white me-2">JS</div>
                                        <div>
                                            <div class="fw-semibold">Jane Smith</div>
                                            <small class="text-muted">jane@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Amount">
                                    <span class="fw-bold text-warning">Rp 1,800,000</span>
                                </td>
                                <td data-label="Due Date">28 Oct 2025</td>
                                <td data-label="Status">
                                    <span class="badge bg-warning">Pending</span>
                                </td>
                                <td data-label="Actions">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-warning">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td data-label="Invoice ID">#INV-2025-003</td>
                                <td data-label="Customer">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar bg-info text-white me-2">BJ</div>
                                        <div>
                                            <div class="fw-semibold">Bob Johnson</div>
                                            <small class="text-muted">bob@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Amount">
                                    <span class="fw-bold text-danger">Rp 3,200,000</span>
                                </td>
                                <td data-label="Due Date">25 Oct 2025</td>
                                <td data-label="Status">
                                    <span class="badge bg-danger">Overdue</span>
                                </td>
                                <td data-label="Actions">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Invoice pagination" class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center">
                        <li class="page-item disabled">
                            <span class="page-link">Previous</span>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">1</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">3</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-outline-primary btn-responsive w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            New Invoice
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-outline-success btn-responsive w-100">
                            <i class="fas fa-file-excel me-2"></i>
                            Export CSV
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-outline-warning btn-responsive w-100">
                            <i class="fas fa-bell me-2"></i>
                            Send Reminders
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <button class="btn btn-outline-info btn-responsive w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            View Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
';

renderResponsiveAdminPage('Invoice Management Demo', $content, $user);
?>