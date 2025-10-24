<?php
// SolusiPaymentManagement Admin Settings Page

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SolusiPaymentManagement</title>

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
        .settings-section { margin-bottom: 2rem; }
        .settings-section h4 { border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem; }
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
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-credit-card me-2"></i>Transactions
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
                    <a class="nav-link active" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
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
                <h2>Settings</h2>
            </div>

            <!-- Settings Tabs -->
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-cog me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="radius-tab" data-bs-toggle="tab" data-bs-target="#radius" type="button" role="tab">
                                <i class="fas fa-server me-2"></i>RADIUS & NAS
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="mikrotik-tab" data-bs-toggle="tab" data-bs-target="#mikrotik" type="button" role="tab">
                                <i class="fas fa-router me-2"></i>MikroTik
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                                <i class="fas fa-database me-2"></i>Backup & Restore
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai" type="button" role="tab">
                                <i class="fas fa-brain me-2"></i>AI Settings
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-4" id="settingsTabsContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form id="general-form">
                                <div class="settings-section">
                                    <h4>Application Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Application Name</label>
                                            <input type="text" class="form-control" name="app_name" value="<?php echo getSetting('app_name', 'SolusiPaymentManagement'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Version</label>
                                            <input type="text" class="form-control" name="app_version" value="<?php echo getSetting('app_version', '1.0.0'); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-select" name="timezone">
                                                <option value="Asia/Jakarta" <?php echo getSetting('timezone') === 'Asia/Jakarta' ? 'selected' : ''; ?>>Asia/Jakarta</option>
                                                <option value="UTC" <?php echo getSetting('timezone') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Currency</label>
                                            <select class="form-select" name="currency">
                                                <option value="IDR" <?php echo getSetting('currency') === 'IDR' ? 'selected' : ''; ?>>IDR (Indonesian Rupiah)</option>
                                                <option value="USD" <?php echo getSetting('currency') === 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <h4>Security Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Session Lifetime (minutes)</label>
                                            <input type="number" class="form-control" name="session_lifetime" value="<?php echo SESSION_LIFETIME / 60; ?>" min="5" max="480">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Login Attempts Limit</label>
                                            <input type="number" class="form-control" name="login_attempts" value="<?php echo RATE_LIMIT_LOGIN_ATTEMPTS; ?>" min="3" max="10">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </form>
                        </div>

                        <!-- RADIUS Settings -->
                        <div class="tab-pane fade" id="radius" role="tabpanel">
                            <form id="radius-form">
                                <div class="settings-section">
                                    <h4>RADIUS Database Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">RADIUS DB Host</label>
                                            <input type="text" class="form-control" name="radius_db_host" value="<?php echo getSetting('radius_db_host', 'localhost'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">RADIUS DB Name</label>
                                            <input type="text" class="form-control" name="radius_db_name" value="<?php echo getSetting('radius_db_name', 'radius'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">RADIUS DB User</label>
                                            <input type="text" class="form-control" name="radius_db_user" value="<?php echo getSetting('radius_db_user', 'radius'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">RADIUS DB Password</label>
                                            <input type="password" class="form-control" name="radius_db_pass" placeholder="Enter password">
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <h4>NAS Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">NAS IP Address</label>
                                            <input type="text" class="form-control" name="nas_ip" value="<?php echo getSetting('nas_ip', '192.168.1.1'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NAS Secret</label>
                                            <input type="password" class="form-control" name="nas_secret" placeholder="Enter NAS secret">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Default Profile</label>
                                            <input type="text" class="form-control" name="profile_default" value="<?php echo getSetting('profile_default', 'default'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Isolir Profile</label>
                                            <input type="text" class="form-control" name="profile_isolir" value="<?php echo getSetting('profile_isolir', 'ISOLIR'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <h4>Source of Truth</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Provisioning Method</label>
                                            <select class="form-select" name="source_of_truth">
                                                <option value="radius" <?php echo getSetting('source_of_truth') === 'radius' ? 'selected' : ''; ?>>RADIUS (Primary)</option>
                                                <option value="mikrotik" <?php echo getSetting('source_of_truth') === 'mikrotik' ? 'selected' : ''; ?>>MikroTik (Fallback)</option>
                                            </select>
                                            <div class="form-text">
                                                Choose which system is the source of truth for customer provisioning.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save RADIUS Settings
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="testRadiusConnection()">
                                        <i class="fas fa-vial me-2"></i>Test Connection
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- MikroTik Settings -->
                        <div class="tab-pane fade" id="mikrotik" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                MikroTik routers are configured individually. Go to the MikroTik management page to add/edit routers.
                            </div>
                            <a href="mikrotik.php" class="btn btn-primary">
                                <i class="fas fa-router me-2"></i>Manage MikroTik Routers
                            </a>
                        </div>

                        <!-- Backup & Restore -->
                        <div class="tab-pane fade" id="backup" role="tabpanel">
                            <div class="settings-section">
                                <h4>Database Backup</h4>
                                <p>Create a backup of your database that can be downloaded and restored later.</p>
                                <button type="button" class="btn btn-success" onclick="createBackup()">
                                    <i class="fas fa-download me-2"></i>Create Backup
                                </button>
                            </div>

                            <div class="settings-section">
                                <h4>Database Restore</h4>
                                <p>Restore database from a previously created backup file.</p>
                                <form id="restore-form" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Backup File (.sql)</label>
                                        <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                                    </div>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Warning:</strong> Restoring will overwrite all current data. Make sure you have a backup!
                                    </div>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-upload me-2"></i>Restore Database
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- AI Settings -->
                        <div class="tab-pane fade" id="ai" role="tabpanel">
                            <form id="ai-form">
                                <div class="settings-section">
                                    <h4>Ollama AI Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Ollama Host</label>
                                            <input type="text" class="form-control" name="ollama_host" value="<?php echo getSetting('ollama_host', 'http://localhost:11434'); ?>">
                                            <div class="form-text">URL where Ollama is running</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">AI Model</label>
                                            <input type="text" class="form-control" name="ollama_model" value="<?php echo getSetting('ollama_model', 'llama3'); ?>">
                                            <div class="form-text">Model to use for AI features</div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save AI Settings
                                </button>
                                <button type="button" class="btn btn-info ms-2" onclick="testOllamaConnection()">
                                    <i class="fas fa-vial me-2"></i>Test Connection
                                </button>
                            </form>
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
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        $.ajaxSetup({
            headers: { 'X-CSRF-Token': csrfToken }
        });

        $(document).ready(function() {
            setupFormHandlers();
        });

        function setupFormHandlers() {
            $('#general-form').on('submit', function(e) {
                e.preventDefault();
                saveSettings('general', $(this).serialize());
            });

            $('#radius-form').on('submit', function(e) {
                e.preventDefault();
                saveSettings('radius', $(this).serialize());
            });

            $('#ai-form').on('submit', function(e) {
                e.preventDefault();
                saveSettings('ai', $(this).serialize());
            });

            $('#restore-form').on('submit', function(e) {
                e.preventDefault();
                restoreDatabase();
            });
        }

        function saveSettings(section, data) {
            const submitBtn = $(`#${section}-form button[type="submit"]`);
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

            $.post(`/api/admin/settings_${section === 'general' ? 'general' : section}`, data)
                .done(function(response) {
                    if (response.success) {
                        showAlert('Settings saved successfully', 'success');
                    } else {
                        showAlert(response.message || 'Error saving settings', 'danger');
                    }
                })
                .fail(function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response?.message || 'Error saving settings', 'danger');
                })
                .always(function() {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save ' + section.charAt(0).toUpperCase() + section.slice(1) + ' Settings');
                });
        }

        function testRadiusConnection() {
            const btn = $('button[onclick="testRadiusConnection()"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');

            $.post('/api/admin/settings_radius', { test_connection: true })
                .done(function(response) {
                    if (response.test_result && response.test_result.success) {
                        showAlert('RADIUS connection successful!', 'success');
                    } else {
                        showAlert('RADIUS connection failed: ' + (response.test_result?.error || 'Unknown error'), 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Error testing RADIUS connection', 'danger');
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-vial me-2"></i>Test Connection');
                });
        }

        function testOllamaConnection() {
            const btn = $('button[onclick="testOllamaConnection()"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');

            $.post('/api/admin/settings_ai', { test_connection: true })
                .done(function(response) {
                    if (response.test_result && response.test_result.success) {
                        showAlert('Ollama connection successful!', 'success');
                    } else {
                        showAlert('Ollama connection failed: ' + (response.test_result?.error || 'Unknown error'), 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Error testing Ollama connection', 'danger');
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-vial me-2"></i>Test Connection');
                });
        }

        function createBackup() {
            const btn = $('button[onclick="createBackup()"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creating...');

            $.post('/api/admin/backup', { action: 'create' })
                .done(function(response) {
                    if (response.success) {
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.download_url;
                        link.download = response.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        showAlert('Backup created successfully', 'success');
                    } else {
                        showAlert(response.message || 'Error creating backup', 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Error creating backup', 'danger');
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-download me-2"></i>Create Backup');
                });
        }

        function restoreDatabase() {
            if (!confirm('Are you sure you want to restore the database? This will overwrite all current data!')) {
                return;
            }

            const formData = new FormData(document.getElementById('restore-form'));
            const btn = $('#restore-form button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Restoring...');

            $.ajax({
                url: '/api/admin/backup',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    showAlert('Database restored successfully. Please refresh the page.', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(response.message || 'Error restoring database', 'danger');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                showAlert(response?.message || 'Error restoring database', 'danger');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-upload me-2"></i>Restore Database');
            });
        }

        function showAlert(message, type) {
            alert(message);
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
