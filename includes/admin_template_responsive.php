<?php
/**
 * Responsive Admin Template
 * Include this file to create responsive admin pages
 */
require_once __DIR__ . '/logo_config.php';

function renderResponsiveAdminPage($title, $content, $user = null) {
    if (!$user) {
        $user = ['name' => 'Administrator', 'role' => 'Super Admin'];
    }
    
    $pageScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars(getLogoConfig()['company_name']) ?></title>
    
    <?= renderFavicon() ?>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Responsive Styles -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">
    <link href="/assets/css/logo.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0284c7;
            --sidebar-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: 280px;
            transition: var(--transition);
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            margin: 0.25rem 1rem;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(8px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
        }
        
        .main-content {
            margin-left: 280px;
            transition: var(--transition);
            min-height: 100vh;
        }
        
        @media (max-width: 991px) {
            .sidebar {
                left: -280px;
                z-index: 1050;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        .sidebar-toggle {
            display: none;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: var(--card-shadow);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .btn {
            border-radius: 12px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
        
        .top-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: var(--card-shadow);
            min-height: 70px;
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .breadcrumb-item.active {
            color: #374151;
            font-weight: 600;
        }
        
        .toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: auto;
        }
        
        /* Logo Styles */
        .logo-container {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .logo-image {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        
        .logo-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }
        
        .logo-text h5 {
            color: white;
            font-size: 1.1rem;
            line-height: 1.2;
        }
        
        .logo-text small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
        }
        
        @media (max-width: 576px) {
            .logo-container {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }
            
            .logo-text h5 {
                font-size: 1rem;
            }
            
            .logo-icon {
                width: 40px;
                height: 40px;
            }
            
            .top-bar .logo-container {
                border: none;
                margin: 0;
                padding: 0;
            }
        }
    
     /* Force static sidebar on all viewports */
     #sidebar, .sidebar { left: 0 !important; transform: none !important; width: 280px !important; position: fixed !important; }
     .main-content { margin-left: 280px !important; }
     .sidebar-toggle, .sidebar-overlay, .mobile-toggle { display: none !important; }
 </style>
</head>
<body>
    <!-- Responsive Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Logo Section -->
        <?= renderLogo('dark', 'normal') ?>
        
        <!-- User Info -->
        <div class="p-3">
            <div class="d-flex align-items-center mb-4">
                <div class="user-avatar bg-white text-primary me-3">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h6 class="mb-0 text-white"><?= htmlspecialchars($user['name']) ?></h6>
                    <small class="text-white-50"><?= htmlspecialchars($user['role']) ?></small>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/admin_sidebar.php'; ?>
        
        <div class="p-3 mt-auto">
            <a href="#" class="nav-link text-white-50" onclick="logout()">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Static Top Navbar -->
        <nav class="top-navbar">
            <div class="breadcrumb-container">
                <button class="sidebar-toggle btn btn-outline-primary btn-sm me-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-lg-none me-3">
                    <?= renderLogo('light', 'small') ?>
                </div>
                
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/admin/dashboard.php" class="text-decoration-none">
                                <i class="fas fa-home"></i> Admin
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($title) ?>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <div class="toolbar">
                <!-- Search Box -->
                <div class="search-box d-none d-md-block">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="Quick search..." style="border-radius: 20px 0 0 20px;">
                        <button class="btn btn-outline-secondary" type="button" style="border-radius: 0 20px 20px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Theme Toggle -->
                <button class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                
                <!-- User Profile -->
                <div class="user-profile d-flex align-items-center">
                    <div class="user-avatar bg-primary text-white me-2">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="d-none d-lg-block">
                        <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($user['role']) ?></small>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid" style="padding: 0;">
            <?= $content ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // Mobile Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                if (overlay) overlay.remove();
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('show');
                
                // Create overlay for mobile
                if (window.innerWidth <= 991) {
                    const overlayDiv = document.createElement('div');
                    overlayDiv.className = 'sidebar-overlay';
                    overlayDiv.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.5);
                        z-index: 1040;
                        display: block;
                    `;
                    overlayDiv.onclick = toggleSidebar;
                    document.body.appendChild(overlayDiv);
                    document.body.style.overflow = 'hidden';
                }
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (window.innerWidth > 991) {
                sidebar.classList.remove('show');
                if (overlay) overlay.remove();
                document.body.style.overflow = '';
            }
        });

        // Close sidebar when clicking on sidebar links in mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    setTimeout(toggleSidebar, 100);
                }
            });
        });

        // Touch/swipe gestures for mobile
        let startX = 0;
        let startY = 0;
        
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchmove', function(e) {
            if (!startX || !startY) return;
            
            const diffX = e.touches[0].clientX - startX;
            const diffY = e.touches[0].clientY - startY;
            
            if (Math.abs(diffX) > Math.abs(diffY) && window.innerWidth <= 991) {
                const sidebar = document.getElementById('sidebar');
                
                if (diffX > 50 && startX < 20 && !sidebar.classList.contains('show')) {
                    // Swipe right from left edge to open
                    toggleSidebar();
                } else if (diffX < -50 && sidebar.classList.contains('show')) {
                    // Swipe left to close
                    toggleSidebar();
                }
            }
            
            startX = 0;
            startY = 0;
        });

        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = document.querySelector('.fa-moon, .fa-sun');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            
            // Update icon
            const icon = document.querySelector('.fa-moon, .fa-sun');
            if (icon) {
                icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
            
            // Add fade-in animation
            document.querySelectorAll('.card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        function logout(){
            if(confirm('Apakah Anda yakin ingin keluar?')){
                $.post('/api/public/logout').done(function(){ window.location.href = '/'; });
            }
        }
    </script>
</body>
</html>
<?php
}
?>