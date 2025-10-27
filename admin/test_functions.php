<?php
$page_title = 'Function Test';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.dashboard');
?>

<!-- Test Page Content -->
<div class="p-4">
    <h2>Function Test Page</h2>
    <p>Test apakah semua onclick functions bekerja dengan benar.</p>
    
    <!-- Test Buttons -->
    <div class="card">
        <div class="card-header">
            <h5>JavaScript Function Tests</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <button class="btn btn-primary w-100" onclick="alert('Direct onclick works!')">
                        Direct Alert Test
                    </button>
                </div>
                
                <div class="col-md-4 mb-3">
                    <button class="btn btn-success w-100" onclick="toggleTheme()">
                        Theme Toggle Test
                    </button>
                </div>
                
                <div class="col-md-4 mb-3">
                    <button class="btn btn-info w-100" onclick="toggleSidebar()">
                        Sidebar Toggle Test
                    </button>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <button class="btn btn-warning w-100" onclick="testFunction()">
                        Custom Function Test
                    </button>
                </div>
                
                <div class="col-md-6 mb-3">
                    <button class="btn btn-danger w-100" onclick="logout()">
                        Logout Test (will ask confirmation)
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submenu Test -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Submenu Simulation Test</h5>
        </div>
        <div class="card-body">
            <div class="nav-item has-submenu">
                <a class="nav-link btn btn-outline-secondary" href="#" onclick="toggleSubmenu(this)">
                    <i class="fas fa-users me-2"></i>Test Submenu Toggle
                </a>
                <ul class="submenu" style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <li><a class="nav-link" href="#"><i class="fas fa-circle me-2"></i>Submenu Item 1</a></li>
                    <li><a class="nav-link" href="#"><i class="fas fa-circle me-2"></i>Submenu Item 2</a></li>
                    <li><a class="nav-link" href="#"><i class="fas fa-circle me-2"></i>Submenu Item 3</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Console Output -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Console Output</h5>
        </div>
        <div class="card-body">
            <div id="console-output" class="alert alert-light">
                <small>Check browser console (F12) for detailed logs...</small>
            </div>
        </div>
    </div>
</div>

<script>
    // Custom test function
    function testFunction() {
        console.log('testFunction called');
        alert('Custom function works!');
        document.getElementById('console-output').innerHTML += '<div>Custom function executed at ' + new Date().toLocaleTimeString() + '</div>';
    }
    
    // Test jQuery
    $(document).ready(function() {
        console.log('Test page loaded, jQuery ready');
        $('#console-output').append('<div><strong>jQuery is working!</strong> Page loaded at ' + new Date().toLocaleTimeString() + '</div>');
        
        // Test if functions exist
        console.log('toggleTheme function exists:', typeof window.toggleTheme);
        console.log('toggleSidebar function exists:', typeof window.toggleSidebar);
        console.log('toggleSubmenu function exists:', typeof window.toggleSubmenu);
        console.log('logout function exists:', typeof window.logout);
    });
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>