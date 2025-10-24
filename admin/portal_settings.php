<?php
// SolusiPaymentManagement Admin Portal Settings

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$user = getCurrentUser();

// Get current portal settings
global $db;
$settings = $db->fetchOne("SELECT * FROM settings WHERE setting_key = 'portal_config'");
$config = $settings ? json_decode($settings['setting_value'], true) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Settings - SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; background: #0d6efd; }
        .main-content { margin-left: 0; }
        @media (min-width: 768px) { .main-content { margin-left: 250px; } }
        
        .preview-card {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            background: #f8f9fa;
        }
        
        .logo-preview {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar position-fixed" style="width: 250px;">
        <div class="p-3">
            <h5 class="text-white mb-4">
                <i class="fas fa-cogs me-2"></i>
                SolusiPaymentManagement
            </h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="invoices.php">
                        <i class="fas fa-file-invoice me-2"></i>Invoices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payment_gateways.php">
                        <i class="fas fa-money-check me-2"></i>Payment Gateways
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers_map.php">
                        <i class="fas fa-map-marked-alt me-2"></i>Customer Map
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="portal_settings.php">
                        <i class="fas fa-palette me-2"></i>Portal Settings
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="#" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-palette me-2"></i>Portal Login Settings</h2>
                    <p class="text-muted">Customize halaman portal login perusahaan Anda</p>
                </div>
                <a href="/" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-eye me-2"></i>Preview Portal
                </a>
            </div>

            <div class="row">
                <!-- Settings Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Portal Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="portalSettingsForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo getCsrfToken(); ?>">
                                
                                <!-- Company Logo -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-image me-2"></i>Company Logo
                                    </label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    <small class="text-muted">Recommended: PNG/JPG, max 2MB, 500x500px</small>
                                    
                                    <?php if (!empty($config['logo']) && file_exists(__DIR__ . '/../assets/uploads/' . $config['logo'])): ?>
                                        <div class="mt-3">
                                            <img src="/assets/uploads/<?php echo htmlspecialchars($config['logo']); ?>" 
                                                 alt="Current Logo" class="logo-preview" id="currentLogo">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeLogo()">
                                                <i class="fas fa-trash me-1"></i>Remove Logo
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div id="logoPreview" class="mt-3" style="display:none;">
                                        <img src="" alt="Logo Preview" class="logo-preview">
                                    </div>
                                </div>

                                <hr>

                                <!-- Company Title -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-heading me-2"></i>Company Title
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($config['title'] ?? 'SolusiPaymentManagement'); ?>" 
                                           placeholder="Your Company Name">
                                    <small class="text-muted">Nama perusahaan yang ditampilkan di portal login</small>
                                </div>

                                <!-- Tagline -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-tag me-2"></i>Tagline
                                    </label>
                                    <input type="text" class="form-control" id="tagline" name="tagline" 
                                           value="<?php echo htmlspecialchars($config['tagline'] ?? 'Sistem Manajemen Pembayaran & ISP Terdepan'); ?>" 
                                           placeholder="Your Company Tagline">
                                    <small class="text-muted">Slogan atau tagline singkat perusahaan</small>
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Deskripsi singkat tentang perusahaan..."><?php echo htmlspecialchars($config['description'] ?? 'Platform terintegrasi untuk manajemen pelanggan, pembayaran, dan operasional ISP.'); ?></textarea>
                                    <small class="text-muted">Deskripsi perusahaan yang ditampilkan di slide pertama</small>
                                </div>

                                <hr>

                                <!-- Slide Images (Optional) -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-images me-2"></i>Carousel Images (Optional)
                                    </label>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small">Slide 2 Image</label>
                                            <input type="file" class="form-control form-control-sm" name="slide2" accept="image/*">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Slide 3 Image</label>
                                            <input type="file" class="form-control form-control-sm" name="slide3" accept="image/*">
                                        </div>
                                    </div>
                                    <small class="text-muted">Upload gambar untuk mengganti icon pada carousel (optional)</small>
                                </div>

                                <hr>

                                <!-- Color Theme -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-palette me-2"></i>Primary Color
                                    </label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                               value="<?php echo $config['primary_color'] ?? '#2563eb'; ?>">
                                        <input type="text" class="form-control" id="primary_color_text" 
                                               value="<?php echo $config['primary_color'] ?? '#2563eb'; ?>" readonly>
                                    </div>
                                    <small class="text-muted">Warna utama untuk gradient background dan buttons</small>
                                </div>

                                <!-- Save Button -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                        <i class="fas fa-save me-2"></i>Save Portal Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                        </div>
                        <div class="card-body">
                            <div class="preview-card text-center">
                                <div id="previewLogo">
                                    <?php if (!empty($config['logo']) && file_exists(__DIR__ . '/../assets/uploads/' . $config['logo'])): ?>
                                        <img src="/assets/uploads/<?php echo htmlspecialchars($config['logo']); ?>" 
                                             alt="Logo" style="max-width: 150px; margin-bottom: 1rem;">
                                    <?php else: ?>
                                        <i class="fas fa-building" style="font-size: 3rem; color: #2563eb; margin-bottom: 1rem;"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <h4 id="previewTitle"><?php echo htmlspecialchars($config['title'] ?? 'SolusiPaymentManagement'); ?></h4>
                                <p class="text-muted" id="previewTagline"><?php echo htmlspecialchars($config['tagline'] ?? 'Sistem Manajemen Pembayaran & ISP Terdepan'); ?></p>
                                <p class="small" id="previewDescription"><?php echo htmlspecialchars($config['description'] ?? 'Platform terintegrasi untuk manajemen pelanggan, pembayaran, dan operasional ISP.'); ?></p>
                                
                                <div class="mt-3 p-3 rounded" id="previewColor" style="background: linear-gradient(135deg, <?php echo $config['primary_color'] ?? '#2563eb'; ?>, <?php echo $config['primary_color'] ?? '#2563eb'; ?>);">
                                    <span class="text-white fw-bold">Color Preview</span>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Changes will be reflected in the actual login portal after saving.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script>
        // Live Preview Updates
        $('#title').on('input', function() {
            $('#previewTitle').text($(this).val());
        });

        $('#tagline').on('input', function() {
            $('#previewTagline').text($(this).val());
        });

        $('#description').on('input', function() {
            $('#previewDescription').text($(this).val());
        });

        $('#primary_color').on('input', function() {
            const color = $(this).val();
            $('#primary_color_text').val(color);
            $('#previewColor').css('background', `linear-gradient(135deg, ${color}, ${color})`);
        });

        // Logo Preview
        $('#logo').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logoPreview').show().find('img').attr('src', e.target.result);
                    $('#previewLogo').html(`<img src="${e.target.result}" style="max-width: 150px; margin-bottom: 1rem;">`);
                }
                reader.readAsDataURL(file);
            }
        });

        // Form Submission
        $('#portalSettingsForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = $('#saveBtn');
            
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            
            $.ajax({
                url: '/api/admin/portal_settings',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-Token': $('#csrf_token').val()
                }
            })
            .done(function(response) {
                if (response.success) {
                    alert('Portal settings saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save settings'));
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                alert('Error: ' + (response?.message || 'Failed to save settings'));
            })
            .always(function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save Portal Settings');
            });
        });

        function removeLogo() {
            if (confirm('Are you sure you want to remove the logo?')) {
                $.ajax({
                    url: '/api/admin/portal_settings?action=remove_logo',
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': $('#csrf_token').val()
                    }
                })
                .done(function(response) {
                    if (response.success) {
                        $('#currentLogo').parent().remove();
                        $('#previewLogo').html('<i class="fas fa-building" style="font-size: 3rem; color: #2563eb; margin-bottom: 1rem;"></i>');
                        alert('Logo removed successfully!');
                    }
                });
            }
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                $.post('/api/public/logout')
                    .done(function() {
                        window.location.href = '/';
                    });
            }
        }
    </script>
</body>
</html>
