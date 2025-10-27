<?php
$page_title = 'Assets Management';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.assets');
?>
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Assets</h2>
                <button class="btn btn-primary" disabled><i class="fas fa-plus me-2"></i>Add Asset</button>
            </div>
            <div class="alert alert-info">Assets module coming soon.</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        function logout(){ if(confirm('Are you sure you want to logout?')){ $.post('/api/public/logout').done(()=>location.href='/'); } }
    </script>

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
});
</script>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
