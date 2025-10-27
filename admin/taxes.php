<?php
$page_title = 'Taxes';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.taxes');
?>

<!-- Page Content -->
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="mb-2 mb-md-0">
            <h2 class="heading-responsive mb-1">Tax Management</h2>
            <p class="text-muted mb-0">Kelola pajak dan perhitungan tarif pajak</p>
        </div>
        <div class="btn-group-responsive">
            <button class="btn btn-primary btn-responsive" disabled>
                <i class="fas fa-plus me-2"></i>Add Tax
            </button>
        </div>
    </div>

    <!-- Tax Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-percentage fa-2x text-primary"></i>
                    </div>
                    <h5 class="card-title">Total Tax Types</h5>
                    <h3 class="text-primary mb-0">3</h3>
                    <small class="text-muted">PPN, PPh, PBB</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                    </div>
                    <h5 class="card-title">This Month</h5>
                    <h3 class="text-success mb-0">Rp 2.5M</h3>
                    <small class="text-muted">+12% from last month</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-file-invoice-dollar fa-2x text-warning"></i>
                    </div>
                    <h5 class="card-title">Pending Payment</h5>
                    <h3 class="text-warning mb-0">Rp 890K</h3>
                    <small class="text-muted">5 invoices</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                    <h5 class="card-title">Tax Rate</h5>
                    <h3 class="text-info mb-0">11%</h3>
                    <small class="text-muted">Average rate</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Configuration -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Tax Configuration
                    </h5>
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-mobile table-hover">
                            <thead>
                                <tr>
                                    <th>Tax Type</th>
                                    <th>Rate</th>
                                    <th>Applied To</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Tax Type">
                                        <i class="fas fa-percentage text-primary me-2"></i>
                                        <strong>PPN</strong>
                                        <br><small class="text-muted">Pajak Pertambahan Nilai</small>
                                    </td>
                                    <td data-label="Rate">
                                        <span class="badge bg-primary">11%</span>
                                    </td>
                                    <td data-label="Applied To">Services, Products</td>
                                    <td data-label="Status">
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td data-label="Action">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" disabled>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" disabled>
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Tax Type">
                                        <i class="fas fa-user-tie text-success me-2"></i>
                                        <strong>PPh 21</strong>
                                        <br><small class="text-muted">Pajak Penghasilan Pasal 21</small>
                                    </td>
                                    <td data-label="Rate">
                                        <span class="badge bg-success">5%</span>
                                    </td>
                                    <td data-label="Applied To">Employee Salary</td>
                                    <td data-label="Status">
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td data-label="Action">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" disabled>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" disabled>
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="Tax Type">
                                        <i class="fas fa-building text-warning me-2"></i>
                                        <strong>PBB</strong>
                                        <br><small class="text-muted">Pajak Bumi dan Bangunan</small>
                                    </td>
                                    <td data-label="Rate">
                                        <span class="badge bg-warning">0.5%</span>
                                    </td>
                                    <td data-label="Applied To">Property</td>
                                    <td data-label="Status">
                                        <span class="badge bg-secondary">Inactive</span>
                                    </td>
                                    <td data-label="Action">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" disabled>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-success" disabled>
                                                <i class="fas fa-toggle-off"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Tax Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="tax-breakdown">
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-circle text-primary me-2"></i>PPN (11%)</span>
                                <strong>Rp 1.8M</strong>
                            </div>
                            <small class="text-muted">72% of total tax</small>
                        </div>
                        
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-circle text-success me-2"></i>PPh 21 (5%)</span>
                                <strong>Rp 650K</strong>
                            </div>
                            <small class="text-muted">26% of total tax</small>
                        </div>
                        
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-circle text-warning me-2"></i>PBB (0.5%)</span>
                                <strong>Rp 50K</strong>
                            </div>
                            <small class="text-muted">2% of total tax</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total Tax Collected:</strong>
                        <strong class="text-primary">Rp 2.5M</strong>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" disabled>
                            <i class="fas fa-plus me-2"></i>Add New Tax Type
                        </button>
                        <button class="btn btn-outline-info" disabled>
                            <i class="fas fa-file-export me-2"></i>Export Tax Report
                        </button>
                        <button class="btn btn-outline-success" disabled>
                            <i class="fas fa-calculator me-2"></i>Tax Calculator
                        </button>
                        <button class="btn btn-outline-warning" disabled>
                            <i class="fas fa-cog me-2"></i>Tax Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Development Notice -->
    <div class="alert alert-info border-0 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-lg me-3"></i>
            <div>
                <h6 class="mb-1">Development in Progress</h6>
                <p class="mb-0">Tax management module is currently under development. All buttons are disabled for now.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
