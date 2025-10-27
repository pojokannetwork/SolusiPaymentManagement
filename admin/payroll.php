<?php
$page_title = 'Payroll Management';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.payroll');
?>

<!-- Page Content -->
<div class="p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="mb-2 mb-md-0">
            <h2 class="heading-responsive mb-1">
                <i class="fas fa-money-bill-wave me-2 text-success"></i>Payroll Management
            </h2>
            <p class="text-muted mb-0">Manage employee salaries, bonuses, and payment records</p>
        </div>
        <div class="btn-group-responsive">
            <button class="btn btn-success btn-responsive" disabled>
                <i class="fas fa-plus me-2"></i>Process Payroll
            </button>
            <button class="btn btn-outline-primary btn-responsive" disabled>
                <i class="fas fa-file-csv me-2"></i>Export Reports
            </button>
        </div>
    </div>

    <!-- Coming Soon Alert -->
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <div>
            <strong>Coming Soon!</strong> Payroll management system is under development. 
            Features will include salary processing, tax calculations, and payment tracking.
        </div>
    </div>

    <!-- Preview Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calculator fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Salary Processing</h5>
                    <p class="card-text text-muted">Calculate monthly salaries and bonuses</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Tax Calculations</h5>
                    <p class="card-text text-muted">Automatic tax deductions and reporting</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-history fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Payment History</h5>
                    <p class="card-text text-muted">Track all payroll transactions</p>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
// Mobile Sidebar Toggle Functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar, .offcanvas');
    if (!sidebar) return;
    
    if (sidebar.classList.contains('offcanvas')) {
        // Bootstrap offcanvas
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebar);
        bsOffcanvas.toggle();
    } else {
        // Custom sidebar
        sidebar.classList.toggle('show');
        
        if (window.innerWidth <= 991) {
            let overlay = document.querySelector('.sidebar-overlay');
            if (sidebar.classList.contains('show')) {
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    overlay.style.cssText = `
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                        background: rgba(0, 0, 0, 0.5); z-index: 1040; display: block;
                    `;
                    overlay.onclick = toggleSidebar;
                    document.body.appendChild(overlay);
                }
            } else if (overlay) {
                overlay.remove();
            }
        }
    }
}

// Add responsive classes to tables
document.addEventListener('DOMContentLoaded', function() {
    // Make tables responsive
    document.querySelectorAll('table').forEach(table => {
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        table.classList.add('table-mobile');
    });
    
    // Add data-label attributes to table cells
    document.querySelectorAll('table.table-mobile').forEach(table => {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach(row => {
            row.querySelectorAll('td').forEach((cell, index) => {
                if (headers[index] && !cell.hasAttribute('data-label')) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
    
    // Add responsive classes to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        if (!btn.classList.contains('btn-responsive')) {
            btn.classList.add('btn-responsive');
        }
    });
    
    // Add responsive classes to cards
    document.querySelectorAll('.card').forEach(card => {
        if (!card.classList.contains('card-responsive')) {
            card.classList.add('card-responsive');
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (window.innerWidth > 991 && sidebar) {
            sidebar.classList.remove('show');
            if (overlay) overlay.remove();
        }
    });

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
