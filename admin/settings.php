<?php
$page_title = 'Settings';
require_once __DIR__ . '/../includes/admin_header.php';
// Ensure permission after header for consistency
$guard->requirePermission('admin.settings');

$sessionLifetimeSetting = (int) getSetting('session_lifetime_minutes', SESSION_LIFETIME / 60);
if ($sessionLifetimeSetting < 5 || $sessionLifetimeSetting > 480) {
    $sessionLifetimeSetting = SESSION_LIFETIME / 60;
}

$loginAttemptsSetting = (int) getSetting('login_attempts', RATE_LIMIT_LOGIN_ATTEMPTS);
if ($loginAttemptsSetting < 3 || $loginAttemptsSetting > 10) {
    $loginAttemptsSetting = RATE_LIMIT_LOGIN_ATTEMPTS;
}
?>

        <div class="p-4">
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
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="server-tools-tab" data-bs-toggle="tab" data-bs-target="#server-tools" type="button" role="tab">
                                <i class="fas fa-power-off me-2"></i>Server Tools
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
                                        <div class="col-md-6">
                                            <label class="form-label">Admin Theme</label>
                                            <select class="form-select" name="admin_theme">
                                                <?php $curTheme = getSetting('admin_theme', 'tabler'); ?>
                                                <option value="default" <?php echo $curTheme==='default' ? 'selected' : ''; ?>>Default (Bootstrap)</option>
                                                <option value="tabler" <?php echo $curTheme==='tabler' ? 'selected' : ''; ?>>Tabler</option>
                                                <option value="sneat" <?php echo $curTheme==='sneat' ? 'selected' : ''; ?>>Sneat (requires assets)</option>
                                                <option value="materio" <?php echo $curTheme==='materio' ? 'selected' : ''; ?>>Materio (requires assets)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <h4>Security Settings</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Session Lifetime (minutes)</label>
                                            <input type="number" class="form-control" name="session_lifetime" value="<?php echo htmlspecialchars((string) $sessionLifetimeSetting, ENT_QUOTES, 'UTF-8'); ?>" min="5" max="480">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Login Attempts Limit</label>
                                            <input type="number" class="form-control" name="login_attempts" value="<?php echo htmlspecialchars((string) $loginAttemptsSetting, ENT_QUOTES, 'UTF-8'); ?>" min="3" max="10">
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

                            <hr class="my-4">
                            <div class="settings-section">
                                <h4>RADIUS CoA Test</h4>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" id="coa-username" placeholder="pppoe_user">
                                        <div class="form-text">Kirim CoA ke NAS untuk user tertentu (disconnect/authorize ulang).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-warning" onclick="sendRadiusCoA()">
                                            <i class="fas fa-bolt me-2"></i>Send CoA
                                        </button>
                                    </div>
                                </div>
                            </div>
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

                        <!-- Server Tools -->
                        <div class="tab-pane fade" id="server-tools" role="tabpanel">
                            <div class="settings-section">
                                <h4>Apache Web Server</h4>
                                <p>Restart Apache to apply changes that require reloading the web server. Active connections may be interrupted.</p>
                                <button type="button" class="btn btn-warning" id="restart-apache-btn" onclick="restartApache()">
                                    <i class="fas fa-sync-alt me-2"></i>Restart Apache
                                </button>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Ensure the web server user has passwordless sudo access to restart Apache (e.g. via <code>sudo visudo</code>). Otherwise, this action will fail and you must restart manually.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php // Page-specific scripts will follow and footer will close containers ?>

<?php $page_specific_scripts = <<<'EOT'
<script>
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        $.ajaxSetup({
            headers: { 'X-CSRF-Token': csrfToken }
        });

        $(document).ready(function() {
            setupFormHandlers();
            selectSettingsTabFromUrl();
            // Update URL hash when switching tabs
            const settingsTabsEl = document.getElementById('settingsTabs');
            if (settingsTabsEl) {
                settingsTabsEl.addEventListener('shown.bs.tab', function (event) {
                    const target = event.target?.getAttribute('data-bs-target') || '';
                    if (target) {
                        history.replaceState(null, '', target);
                    }
                });
            }
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

        function selectSettingsTabFromUrl() {
            // Support hash (#radius) and query (?tab=radius)
            let tab = (location.hash || '').replace('#', '');
            if (!tab) {
                const params = new URLSearchParams(location.search);
                tab = params.get('tab') || '';
            }
            const valid = ['general','radius','mikrotik','backup','ai','server-tools'];
            if (!valid.includes(tab)) return;
            const btn = document.querySelector(`#settingsTabs button[data-bs-target="#${tab}"]`);
            if (btn) {
                const bsTab = new bootstrap.Tab(btn);
                bsTab.show();
            }
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

        function sendRadiusCoA() {
            const btn = $('button[onclick="sendRadiusCoA()"]');
            const username = $('#coa-username').val().trim();
            if (!username) { alert('Masukkan username terlebih dahulu.'); return; }
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
            $.post('/api/admin/settings_radius', { send_coa: 1, coa_username: username })
                .done(function(response){
                    if (response.test_result?.success) {
                        showAlert('CoA terkirim. Respon: ' + (response.test_result.response_received ? 'received' : 'no response'), 'success');
                    } else {
                        showAlert('Gagal CoA: ' + (response.test_result?.error || 'Unknown error'), 'danger');
                    }
                })
                .fail(function(xhr){
                    showAlert(xhr.responseJSON?.message || 'Gagal mengirim CoA', 'danger');
                })
                .always(function(){
                    btn.prop('disabled', false).html('<i class="fas fa-bolt me-2"></i>Send CoA');
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

        function restartApache() {
            if (!confirm('Restart Apache now? Active users may experience a brief interruption.')) {
                return;
            }

            const btn = $('#restart-apache-btn');
            const originalLabel = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Restarting...');

            $.post('/api/admin/server_control', { action: 'restart_apache' })
                .done(function(response) {
                    const extra = response.data && response.data.output ? '\n\n' + response.data.output : '';
                    showAlert((response.message || 'Apache restart command executed') + extra, 'success');
                })
                .fail(function(xhr) {
                    const response = xhr.responseJSON || {};
                    const attemptsInfo = response.errors ? formatAttempts(response.errors.attempts) : '';
                    const extra = attemptsInfo ? '\n\n' + attemptsInfo : '';
                    showAlert((response.message || 'Error restarting Apache') + extra, 'danger');
                })
                .always(function() {
                    btn.prop('disabled', false).html(originalLabel);
                });
        }

        function formatAttempts(attempts) {
            if (!Array.isArray(attempts) || attempts.length === 0) {
                return '';
            }

            return attempts.map(function(attempt, index) {
                const command = attempt.command || '';
                const status = typeof attempt.status !== 'undefined' ? attempt.status : '';
                const output = attempt.output || '';
                return 'Attempt ' + (index + 1) + ':\nCommand: ' + command + '\nStatus: ' + status + '\nOutput: ' + output;
            }).join('\n\n');
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
EOT;
?>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
