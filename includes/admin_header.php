<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/logo_config.php';

// This header should be included in all admin pages.

// Perform security checks
$guard = RouterGuard::getInstance();
$user = getCurrentUser();

// We can't call requirePermission here because we don't know the page yet.
// The individual page is responsible for checking its own permissions.

?>
<!DOCTYPE html>
<?php $APP_LANG = htmlspecialchars(getSetting('language', 'id')); ?>
<html lang="<?= $APP_LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitizeOutput($page_title) : 'Admin'; ?> - <?= htmlspecialchars(getLogoConfig()['company_name']) ?></title>
    
    <?= renderFavicon() ?>

    <!-- Bootstrap 5 (CDN) -->
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
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1051; /* Above backdrop */
            min-height: 100vh;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            width: 280px;
            transition: var(--transition);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            margin: 0.25rem 1rem;
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
            position: relative;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(4px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        
        /* Sub Navigation */
        .nav-item.has-submenu .nav-link::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            transition: transform 0.3s ease;
        }
        
        .nav-item.has-submenu.open .nav-link::after {
            transform: rotate(180deg);
        }
        
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: rgba(0, 0, 0, 0.1);
            margin: 0 1rem 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .submenu.open {
            max-height: 500px;
        }
        
        .submenu .nav-link {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .submenu .nav-link i {
            width: 16px;
            margin-right: 8px;
            font-size: 0.75rem;
        }
        
        /* Logo Styles */
        .logo-container {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0;
            min-height: 80px;
            display: flex;
            align-items: center;
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
        }
        
        .main-content {
            margin-left: 280px;
            transition: var(--transition);
            min-height: 100vh;
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
        }
        


        /* Enhanced Mobile Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1050;
                width: 100%;
            }
            
            .sidebar.show,
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
            
            .top-navbar {
                padding: 0.75rem 1rem;
            }
            
            .search-box {
                display: none !important;
            }
        }
        
        @media (min-width: 992px) {
            .mobile-toggle {
                display: none !important;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                padding: 0 !important;
            }
            
            .top-navbar {
                padding: 0.5rem 0.75rem;
                min-height: 60px;
            }
            
            .breadcrumb-item {
                font-size: 0.75rem;
            }
            
            .toolbar > * {
                font-size: 0.875rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .row > div {
                margin-bottom: 1rem;
            }
        }
        
        /* Backdrop for sidebar on mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1040;
        }
        
        body.sidebar-open .sidebar-backdrop {
            display: block;
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

        /* So the screen is not too dark when a modal is open */
        /* disable modal backdrop dim and clicks */
.modal-backdrop, .modal-backdrop.show {
            opacity: 0 !important;
            pointer-events: none !important;
        }
        .sidebar-overlay, .sidebar-backdrop {
            display: none !important;
            pointer-events: none !important;
        }
        /* Avoid accidental sidebar overlay when a modal is open */
        .modal-open .sidebar-backdrop { display: none !important; }
    
        /* Force static sidebar on all viewports */
        #sidebar, .sidebar { left: 0 !important; transform: none !important; width: 280px !important; position: fixed !important; }
        .main-content { margin-left: 280px !important; }
        .mobile-toggle, .sidebar-toggle { display: none !important; }
        .sidebar-backdrop { display: none !important; }
    </style>
    <script>
        // Minimal fallback to ensure submenu toggles even if footer JS is cached/missing
        window.addEventListener('DOMContentLoaded', function() {
            if (!window.toggleSubmenu) {
                window.toggleSubmenu = function(element) {
                    var navItem = element && element.closest ? element.closest('.nav-item.has-submenu') : null;
                    if (!navItem) return false;
                    var submenu = navItem.querySelector('.submenu');
                    if (!submenu) return false;
                    var isOpen = navItem.classList.contains('open');
                    if (isOpen) {
                        navItem.classList.remove('open');
                        submenu.classList.remove('open');
                    } else {
                        navItem.classList.add('open');
                        submenu.classList.add('open');
                    }
                    return false;
                };
            }
            document.querySelectorAll('.nav-item.has-submenu > .nav-link').forEach(function(link){
                if (!link.__submenuBound) {
                    link.__submenuBound = true;
                    link.addEventListener('click', function(e){ e.preventDefault(); window.toggleSubmenu(this); });
                }
            });
        });
    </script>

    <!-- jQuery (required for Bootstrap and custom scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Responsive Sidebar -->
<nav class="sidebar position-fixed" id="sidebar">
    <div class="sidebar-content">
        <!-- Logo Section -->
        <?= renderLogo('dark', 'normal') ?>
        
        <!-- User Info -->
        <div class="p-3">
            <div class="d-flex align-items-center mb-3">
                <div class="user-avatar bg-white text-primary me-3" style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h6 class="mb-0 text-white"><?= htmlspecialchars($user['nama'] ?? 'Admin') ?></h6>
                    <small class="text-white-50"><?= htmlspecialchars($user['role'] ?? 'Administrator') ?></small>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="flex-fill">
            <?php include __DIR__ . '/admin_sidebar.php'; ?>
        </div>
        
        <!-- Logout Section -->
        <div class="p-3 border-top border-light border-opacity-10">
            <a href="#" class="nav-link text-white-50" onclick="logout()">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>
</nav>
<div class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

<!-- Main Content -->
<div class="main-content">
    <!-- Static Top Navbar -->
    <nav class="top-navbar">
        <div class="breadcrumb-container">
            <button class="mobile-toggle btn btn-outline-primary btn-sm me-3" onclick="toggleSidebar(true)">
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
                        <?= htmlspecialchars($page_title ?? 'Dashboard') ?>
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
            
            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                        3
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle text-info me-2"></i>System update available</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user text-success me-2"></i>New user registered</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Payment pending</a></li>
                </ul>
            </div>
            
            <!-- Theme Toggle -->
            <button class="btn btn-outline-secondary btn-sm" onclick="toggleTheme()" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            
            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-1"></i>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($user['nama'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Account</h6></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-lock me-2"></i>Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="container-fluid" style="padding: 0;">
