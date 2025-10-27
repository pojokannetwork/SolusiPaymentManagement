<?php
$page_title = 'Logo Settings';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/logo_config.php';

// Check authentication and permissions
$guard->requirePermission('admin.settings');

$config = getLogoConfig();
?>

<link href="/assets/css/logo.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Logo Settings</h2>
        <p class="text-muted mb-0">Kelola logo dan branding perusahaan Anda</p>
    </div>
    <button class="btn btn-primary" onclick="saveLogoConfig()">
        <i class="fas fa-save me-2"></i>Simpan Pengaturan
    </button>
</div>

<!-- Current Logo Preview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Preview Logo Saat Ini</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6>Sidebar (Dark Theme)</h6>
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1rem; border-radius: 12px;">
                            <?= renderLogo('dark', 'normal') ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>Mobile Header (Light Theme)</h6>
                        <div style="background: white; padding: 1rem; border-radius: 12px; border: 1px solid #e5e7eb;">
                            <?= renderLogo('light', 'small') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logo Management -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-images me-2"></i>Upload Logo Files</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Main Logo -->
                    <div class="col-md-6 mb-4">
                        <div class="logo-slot <?= logoExists($config['logo_main']) ? 'has-logo' : '' ?>">
                            <h6>Logo Utama</h6>
                            <small class="text-muted d-block mb-3">Untuk background terang (PNG/JPG, max 2MB)</small>
                            
                            <?php if (logoExists($config['logo_main'])): ?>
                                <img src="<?= getLogoUrl($config['logo_main']) ?>?v=<?= time() ?>" class="logo-preview mb-3" alt="Main Logo">
                                <div class="logo-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="uploadLogo('main')">
                                        <i class="fas fa-upload"></i> Ganti
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLogo('main')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="logo-upload-area" onclick="uploadLogo('main')">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Klik untuk upload logo utama</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="logo-main" accept="image/*" style="display: none;" onchange="handleLogoUpload('main', this)">
                        </div>
                    </div>

                    <!-- White Logo -->
                    <div class="col-md-6 mb-4">
                        <div class="logo-slot <?= logoExists($config['logo_white']) ? 'has-logo' : '' ?>">
                            <h6>Logo Putih</h6>
                            <small class="text-muted d-block mb-3">Untuk background gelap (PNG dengan transparent)</small>
                            
                            <?php if (logoExists($config['logo_white'])): ?>
                                <div style="background: #333; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <img src="<?= getLogoUrl($config['logo_white']) ?>?v=<?= time() ?>" class="logo-preview" alt="White Logo">
                                </div>
                                <div class="logo-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="uploadLogo('white')">
                                        <i class="fas fa-upload"></i> Ganti
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLogo('white')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="logo-upload-area" onclick="uploadLogo('white')">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Klik untuk upload logo putih</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="logo-white" accept="image/*" style="display: none;" onchange="handleLogoUpload('white', this)">
                        </div>
                    </div>

                    <!-- Small Logo -->
                    <div class="col-md-6 mb-4">
                        <div class="logo-slot <?= logoExists($config['logo_small']) ? 'has-logo' : '' ?>">
                            <h6>Logo Kecil</h6>
                            <small class="text-muted d-block mb-3">Untuk mobile/compact view (PNG/JPG)</small>
                            
                            <?php if (logoExists($config['logo_small'])): ?>
                                <img src="<?= getLogoUrl($config['logo_small']) ?>?v=<?= time() ?>" class="logo-preview mb-3" alt="Small Logo" style="max-height: 50px;">
                                <div class="logo-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="uploadLogo('small')">
                                        <i class="fas fa-upload"></i> Ganti
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLogo('small')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="logo-upload-area" onclick="uploadLogo('small')">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Klik untuk upload logo kecil</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="logo-small" accept="image/*" style="display: none;" onchange="handleLogoUpload('small', this)">
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div class="col-md-6 mb-4">
                        <div class="logo-slot <?= logoExists($config['favicon']) ? 'has-logo' : '' ?>">
                            <h6>Favicon</h6>
                            <small class="text-muted d-block mb-3">Icon browser (ICO/PNG, 32x32px)</small>
                            
                            <?php if (logoExists($config['favicon'])): ?>
                                <img src="<?= getLogoUrl($config['favicon']) ?>?v=<?= time() ?>" class="mb-3" alt="Favicon" style="width: 32px; height: 32px;">
                                <div class="logo-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="uploadLogo('favicon')">
                                        <i class="fas fa-upload"></i> Ganti
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLogo('favicon')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="logo-upload-area" onclick="uploadLogo('favicon')">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Klik untuk upload favicon</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="logo-favicon" accept="image/*" style="display: none;" onchange="handleLogoUpload('favicon', this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan Logo</h5>
            </div>
            <div class="card-body">
                <form id="logo-config-form">
                    <div class="form-group mb-3">
                        <label class="form-label">Nama Perusahaan</label>
                        <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($config['company_name']) ?>">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" class="form-control" name="company_tagline" value="<?= htmlspecialchars($config['company_tagline']) ?>">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Lebar Logo Desktop</label>
                        <input type="text" class="form-control" name="logo_width" value="<?= htmlspecialchars($config['logo_width']) ?>">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Lebar Logo Mobile</label>
                        <input type="text" class="form-control" name="logo_width_mobile" value="<?= htmlspecialchars($config['logo_width_mobile']) ?>">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="show_logo" <?= $config['show_logo'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Tampilkan Logo</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="show_text" <?= $config['show_text'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Tampilkan Teks</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="use_text_only" <?= $config['use_text_only'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Gunakan Teks Jika Tidak Ada Logo</label>
                    </div>
                </form>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Logo utama: Format PNG/JPG, resolusi tinggi</li>
                        <li>Logo putih: PNG dengan background transparan</li>
                        <li>Logo kecil: Versi sederhana untuk mobile</li>
                        <li>Favicon: 32x32px untuk icon browser</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function uploadLogo(type) {
    document.getElementById('logo-' + type).click();
}

function handleLogoUpload(type, input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File terlalu besar! Maksimal 2MB.');
            return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('File harus berupa gambar!');
            return;
        }
        
        const formData = new FormData();
        formData.append('logo_file', file);
        formData.append('logo_type', type);
        formData.append('csrf_token', csrfToken);
        
        // Show loading
        const slot = input.closest('.logo-slot');
        slot.classList.add('logo-loading');
        
        $.ajax({
            url: '/api/admin/logo_management?action=upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Upload gagal: ' + response.message);
                }
            },
            error: function() {
                alert('Upload gagal. Silakan coba lagi.');
            },
            complete: function() {
                slot.classList.remove('logo-loading');
            }
        });
    }
}

function deleteLogo(type) {
    if (confirm('Apakah Anda yakin ingin menghapus logo ini?')) {
        $.post('/api/admin/logo_management?action=delete', {
            logo_type: type,
            csrf_token: csrfToken
        }).done(function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Hapus gagal: ' + response.message);
            }
        }).fail(function() {
            alert('Hapus gagal. Silakan coba lagi.');
        });
    }
}

function saveLogoConfig() {
    const formData = new FormData(document.getElementById('logo-config-form'));
    formData.append('csrf_token', csrfToken);
    
    $.ajax({
        url: '/api/admin/logo_management?action=save_config',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Pengaturan berhasil disimpan!');
                location.reload();
            } else {
                alert('Simpan gagal: ' + response.message);
            }
        },
        error: function() {
            alert('Simpan gagal. Silakan coba lagi.');
        }
    });
}

// Drag and drop functionality
document.querySelectorAll('.logo-upload-area').forEach(area => {
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    area.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = this.parentElement.querySelector('input[type="file"]');
            input.files = files;
            input.dispatchEvent(new Event('change'));
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>