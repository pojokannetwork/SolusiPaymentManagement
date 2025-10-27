<?php
$page_title = 'MikroTik';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard->requirePermission('admin.settings');
?>

        <div class="p-4">
            <style>
                .mikro-section { display: none; }
                .mikro-section.active { display: block; }
                .summary-card { border: none; border-radius: 16px; box-shadow: 0 15px 45px rgba(15, 23, 42, 0.08); background: #fff; }
                .summary-card .icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; }
                .status-badge { padding: .35rem .65rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
                .badge-active { background: rgba(34,197,94,.15); color: #15803d; }
                .badge-inactive { background: rgba(248,113,113,.15); color: #b91c1c; }
                .table thead th { text-transform: uppercase; font-size: .72rem; letter-spacing: .05em; }
                .nav-pills .nav-link { border-radius: 999px; }
                .nav-pills .nav-link.active { box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2); }
                .card-table { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }
                .table-actions .btn { min-width: 90px; }
                .modal label { font-weight: 600; }
            </style>
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Manajemen MikroTik</h2>
                <p class="text-muted mb-0">Monitor performa router, kelola pelanggan PPPoE dan hotspot, serta konfigurasi router perusahaan.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary" id="btn-refresh-all">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Semua
                </button>
                <button class="btn btn-primary" data-section="routers" id="btn-open-router">
                    <i class="fas fa-plus me-2"></i>Tambah Router
                </button>
                <button class="btn btn-success" data-section="pppoe-users" id="btn-open-pppoe-user">
                    <i class="fas fa-user-plus me-2"></i>Tambah User PPPoE
                </button>
                <button class="btn btn-success" data-section="hotspot-users" id="btn-open-hotspot-user">
                    <i class="fas fa-wifi me-2"></i>Tambah User Hotspot
                </button>
            </div>
        </div>

        <div id="alert-container" class="mb-3"></div>

        <ul class="nav nav-pills gap-2 mb-4" id="mikrotik-nav">
            <li class="nav-item"><button class="nav-link active" data-section="dashboard">Dashboard</button></li>
            <li class="nav-item"><button class="nav-link" data-section="pppoe-users">User PPPoE</button></li>
            <li class="nav-item"><button class="nav-link" data-section="pppoe-profiles">Profil PPPoE</button></li>
            <li class="nav-item"><button class="nav-link" data-section="hotspot-users">User Hotspot</button></li>
            <li class="nav-item"><button class="nav-link" data-section="hotspot-profiles">Profil Hotspot</button></li>
            <li class="nav-item"><button class="nav-link" data-section="routers">Router</button></li>
        </ul>

        <div class="mikro-section active" id="section-dashboard">
            <div class="row g-3" id="dashboard-summary">
                <div class="col-xl-3 col-md-6">
                    <div class="summary-card p-3 h-100">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-microchip"></i></div>
                            <div>
                                <div class="text-muted small text-uppercase">Router</div>
                                <div class="h5 mb-0" id="summary-identity">-</div>
                                <div class="text-muted small" id="summary-model">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="summary-card p-3 h-100">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="icon bg-success bg-opacity-10 text-success"><i class="fas fa-bolt"></i></div>
                            <div>
                                <div class="text-muted small text-uppercase">CPU Load</div>
                                <div class="h5 mb-0" id="summary-cpu">- %</div>
                                <div class="text-muted small" id="summary-traffic">RX - / TX -</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="summary-card p-3 h-100">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-memory"></i></div>
                            <div>
                                <div class="text-muted small text-uppercase">Memori</div>
                                <div class="h5 mb-0" id="summary-memory">-</div>
                                <div class="text-muted small" id="summary-disk">Storage -</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="summary-card p-3 h-100">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="icon bg-info bg-opacity-10 text-info"><i class="fas fa-users"></i></div>
                            <div>
                                <div class="text-muted small text-uppercase">User PPPoE</div>
                                <div class="h5 mb-0" id="summary-pppoe">-</div>
                                <div class="text-muted small"><span id="summary-pppoe-active">0</span> aktif • <span id="summary-pppoe-inactive">0</span> tidak aktif</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-table mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Koneksi PPPoE Aktif</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-dashboard">
                            <i class="fas fa-sync me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="dashboard-pppoe-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Uptime</th>
                                    <th>Service</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mikro-section" id="section-pppoe-users">
            <div class="card card-table">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Daftar User PPPoE</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-pppoe">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-primary" id="btn-create-pppoe">
                                <i class="fas fa-user-plus me-1"></i>Tambah User
                            </button>
                        </div>
                    </div>
                    <div id="alert-pppoe"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="pppoe-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Profil</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mikro-section" id="section-pppoe-profiles">
            <div class="card card-table">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Profil PPPoE</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-pppoe-profiles">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-primary" id="btn-create-pppoe-profile">
                                <i class="fas fa-plus me-1"></i>Profil Baru
                            </button>
                        </div>
                    </div>
                    <div id="alert-pppoe-profile"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="pppoe-profiles-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Rate Limit</th>
                                    <th>Local Addr</th>
                                    <th>Remote Addr</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mikro-section" id="section-hotspot-users">
            <div class="card card-table">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">User Hotspot Aktif</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-hotspot">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-primary" id="btn-create-hotspot">
                                <i class="fas fa-user-plus me-1"></i>Tambah User
                            </button>
                            <button class="btn btn-sm btn-success" id="btn-voucher-hotspot">
                                <i class="fas fa-ticket me-1"></i>Generate Voucher
                            </button>
                        </div>
                    </div>
                    <div id="alert-hotspot"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="hotspot-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Uptime</th>
                                    <th>Profil</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mikro-section" id="section-hotspot-profiles">
            <div class="card card-table">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Profil Hotspot</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-hotspot-profiles">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-primary" id="btn-create-hotspot-profile">
                                <i class="fas fa-plus me-1"></i>Profil Baru
                            </button>
                        </div>
                    </div>
                    <div id="alert-hotspot-profile"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="hotspot-profiles-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Rate Limit</th>
                                    <th>Session Timeout</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mikro-section" id="section-routers">
            <div class="card card-table">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Daftar Router MikroTik</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btn-refresh-routers">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            <button class="btn btn-sm btn-primary" id="btn-create-router">
                                <i class="fas fa-plus me-1"></i>Tambah Router
                            </button>
                        </div>
                    </div>
                    <div id="alert-router"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="routers-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Host</th>
                                    <th>Port</th>
                                    <th>User</th>
                                    <th>TLS</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" class="text-center text-muted">Memuat data router...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- PPPoE User Modal -->
<div class="modal fade" id="pppoeUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="pppoe-user-form">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Tambah User PPPoE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pppoe-user-identifier">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="pppoe-username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="pppoe-password" placeholder="Biarkan kosong untuk mempertahankan password saat edit">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#pppoe-password" aria-label="Tampilkan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profil</label>
                        <select class="form-select" id="pppoe-profile" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control" id="pppoe-comment" placeholder="Opsional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="pppoe-user-submit">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PPPoE Profile Modal -->
<div class="modal fade" id="pppoeProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="pppoe-profile-form">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i>Profil PPPoE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pppoe-profile-identifier">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Profil</label>
                        <input type="text" class="form-control" id="pppoe-profile-name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rate Limit (contoh: 5M/5M)</label>
                        <input type="text" class="form-control" id="pppoe-profile-rate">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Local Address</label>
                        <input type="text" class="form-control" id="pppoe-profile-local">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remote Address</label>
                        <input type="text" class="form-control" id="pppoe-profile-remote">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" id="pppoe-profile-comment" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="pppoe-profile-submit">
                    <i class="fas fa-save me-2"></i>Simpan Profil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hotspot Profile Modal -->
<div class="modal fade" id="hotspotProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="hotspot-profile-form">
            <div class="modal-header">
                <h5 class="modal-title" id="hotspotProfileModalLabel"><i class="fas fa-layer-group me-2"></i>Profil Hotspot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="hotspot-profile-identifier">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Profil</label>
                        <input type="text" class="form-control" id="hotspot-profile-name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rate Limit (misal: 5M/5M)</label>
                        <input type="text" class="form-control" id="hotspot-profile-rate">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Session Timeout</label>
                        <input type="text" class="form-control" id="hotspot-profile-session">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" id="hotspot-profile-comment" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="hotspot-profile-submit">
                    <i class="fas fa-save me-2"></i>Simpan Profil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hotspot User Modal -->
<div class="modal fade" id="hotspotUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="hotspot-user-form">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-wifi me-2"></i>User Hotspot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="hotspot-user-identifier">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="hotspot-username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="hotspot-password" placeholder="Biarkan kosong untuk mempertahankan password saat edit">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#hotspot-password" aria-label="Tampilkan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profil</label>
                        <select class="form-select" id="hotspot-profile" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Server</label>
                        <select class="form-select" id="hotspot-server"></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" id="hotspot-comment" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="hotspot-user-submit">
                    <i class="fas fa-save me-2"></i>Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hotspot Voucher Modal -->
<div class="modal fade" id="hotspotVoucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="hotspot-voucher-form">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ticket me-2"></i>Generate Voucher Hotspot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Jumlah Voucher</label>
                        <input type="number" class="form-control" id="voucher-count" value="5" min="1" max="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prefix</label>
                        <input type="text" class="form-control" id="voucher-prefix" value="VCHR">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Panjang Kode</label>
                        <input type="number" class="form-control" id="voucher-length" value="6" min="4" max="10">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profil</label>
                        <select class="form-select" id="voucher-profile" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Server</label>
                        <select class="form-select" id="voucher-server"></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="voucher-comment" rows="2"></textarea>
                    </div>
                    <div class="col-12" id="voucher-result" style="display:none;">
                        <label class="form-label">Hasil</label>
                        <pre class="form-control" style="height:200px; overflow:auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-success" id="voucher-generate-btn">
                    <i class="fas fa-bolt me-2"></i>Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Router Modal (reuse) -->
<div class="modal fade" id="routerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="router-form">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-network-wired me-2"></i>Router MikroTik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="router_id" id="router-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Router</label>
                        <input type="text" class="form-control" name="name" id="router-name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Host / IP</label>
                        <input type="text" class="form-control" name="host" id="router-host" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port</label>
                        <input type="number" class="form-control" name="port" id="router-port" min="1" max="65535" value="<?php echo (int) (defined('MIKROTIK_DEFAULT_PORT') ? constant('MIKROTIK_DEFAULT_PORT') : 8728); ?>">
                        <div class="form-text">8728 (default) atau 8729 untuk TLS</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="router-username" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="router-password" placeholder="••••••">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#router-password" aria-label="Tampilkan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Biarkan kosong untuk mempertahankan password saat edit</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">TLS</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="router-use-tls">
                            <label class="form-check-label" for="router-use-tls">Gunakan koneksi TLS (port 8729)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" id="router-comment" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="router-submit-btn">
                    <i class="fas fa-save me-2"></i>Simpan Router
                </button>
            </div>
        </form>
    </div>
        </div>

<?php $page_specific_scripts = <<<'EOT'
<script>
const csrfToken = '<?php echo getCsrfToken(); ?>';
const DEFAULT_MIKROTIK_PORT = <?php echo (int) (defined('MIKROTIK_DEFAULT_PORT') ? constant('MIKROTIK_DEFAULT_PORT') : 8728); ?>;
let routerModal, pppoeUserModal, pppoeProfileModal, hotspotProfileModal, hotspotUserModal, hotspotVoucherModal;
let routers = [];
let pppUsers = [];
let pppProfiles = [];
let hotspotUsers = [];
let hotspotProfiles = [];
let hotspotServers = [];

$.ajaxSetup({ headers: { 'X-CSRF-Token': csrfToken } });

$(document).ready(function () {
    routerModal = new bootstrap.Modal(document.getElementById('routerModal'));
    pppoeUserModal = new bootstrap.Modal(document.getElementById('pppoeUserModal'));
    pppoeProfileModal = new bootstrap.Modal(document.getElementById('pppoeProfileModal'));
    hotspotProfileModal = new bootstrap.Modal(document.getElementById('hotspotProfileModal'));
    hotspotUserModal = new bootstrap.Modal(document.getElementById('hotspotUserModal'));
    hotspotVoucherModal = new bootstrap.Modal(document.getElementById('hotspotVoucherModal'));

    bindEvents();
    initPasswordToggles();
    refreshAll();
});

function safelyHideModal(modalInstance) {
    if (!modalInstance || typeof modalInstance.hide !== 'function') {
        return;
    }

    let modalElement = modalInstance._element;
    if (!modalElement && modalInstance._dialog && modalInstance._dialog.parentNode) {
        modalElement = modalInstance._dialog.parentNode;
    }

    if (modalElement) {
        const activeElement = document.activeElement;
        // Blur active controls inside the modal before hiding to keep accessibility tooling happy.
        if (activeElement && modalElement.contains(activeElement) && typeof activeElement.blur === 'function') {
            activeElement.blur();
        }
    }

    modalInstance.hide();
}

function bindEvents() {
    $('#mikrotik-nav .nav-link').on('click', function () {
        const section = $(this).data('section');
        setActiveSection(section);
    });

    $('#btn-refresh-all').on('click', refreshAll);
    $('#btn-refresh-dashboard').on('click', loadDashboard);
    $('#btn-refresh-pppoe').on('click', loadPppUsers);
    $('#btn-refresh-pppoe-profiles').on('click', loadPppProfiles);
    $('#btn-refresh-hotspot').on('click', loadHotspotUsers);
    $('#btn-refresh-hotspot-profiles').on('click', loadHotspotProfiles);
    $('#btn-refresh-routers').on('click', loadRouters);

    $('#btn-create-router, #btn-open-router').on('click', () => openRouterModal());
    $('#btn-create-pppoe, #btn-open-pppoe-user').on('click', () => openPppoeUserModal());
    $('#btn-create-pppoe-profile').on('click', () => openPppoeProfileModal());
    $('#btn-create-hotspot-profile').on('click', () => openHotspotProfileModal());
    $('#btn-create-hotspot, #btn-open-hotspot-user').on('click', () => openHotspotUserModal());
    $('#btn-voucher-hotspot').on('click', openVoucherModal);

    $('#router-form').on('submit', submitRouterForm);
    $('#pppoe-user-form').on('submit', submitPppoeUserForm);
    $('#pppoe-profile-form').on('submit', submitPppoeProfileForm);
    $('#hotspot-profile-form').on('submit', submitHotspotProfileForm);
    $('#hotspot-user-form').on('submit', submitHotspotUserForm);
    $('#hotspot-voucher-form').on('submit', submitVoucherForm);
}

function initPasswordToggles() {
    $('.toggle-password').on('click', function () {
        const targetSelector = $(this).data('target');
        const $input = $(targetSelector);
        if ($input.length === 0) return;

        const isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');

        const $icon = $(this).find('i');
        if (isPassword) {
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).attr('aria-label', 'Sembunyikan password');
        } else {
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).attr('aria-label', 'Tampilkan password');
        }
    });
}

function setActiveSection(section) {
    $('#mikrotik-nav .nav-link').removeClass('active');
    $(`#mikrotik-nav .nav-link[data-section="${section}"]`).addClass('active');
    $('.mikro-section').removeClass('active');
    $(`#section-${section}`).addClass('active');
}

function showAlert(message, type = 'info', container = '#alert-container', timeout = 4000) {
    const id = 'alert-' + Date.now();
    const html = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${id}">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    $(container).html(html);
    if (timeout > 0) {
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) bootstrap.Alert.getOrCreateInstance(el).close();
        }, timeout);
    }
}

function getErrorMessage(xhr, fallbackMessage = 'Terjadi kesalahan. Silakan coba lagi.') {
    if (xhr && xhr.responseJSON) {
        const { message, errors } = xhr.responseJSON;
        if (message) {
            return message;
        }
        if (errors) {
            if (Array.isArray(errors)) {
                const joined = errors.filter(Boolean).join('<br>');
                if (joined) return joined;
            } else if (typeof errors === 'object') {
                const parts = [];
                Object.values(errors).forEach(value => {
                    if (Array.isArray(value)) {
                        value.filter(Boolean).forEach(item => parts.push(item));
                    } else if (value) {
                        parts.push(value);
                    }
                });
                if (parts.length > 0) {
                    return parts.join('<br>');
                }
            }
        }
    }

    if (xhr && xhr.responseText) {
        try {
            const parsed = JSON.parse(xhr.responseText);
            if (parsed && parsed.message) {
                return parsed.message;
            }
        } catch (error) {
            // ignore invalid JSON
        }
    }

    if (xhr && xhr.status === 0) {
        return 'Tidak dapat terhubung ke server. Periksa koneksi jaringan Anda.';
    }

    return fallbackMessage;
}

function handleAjaxError(xhr, fallbackMessage, container = '#alert-container') {
    const message = getErrorMessage(xhr, fallbackMessage);
    showAlert(message, 'danger', container, 0);
}

function refreshAll() {
    loadDashboard();
    loadPppProfiles();
    loadPppUsers();
    loadHotspotProfiles();
    loadHotspotServers();
    loadHotspotUsers();
    loadRouters();
}

// Dashboard
function loadDashboard() {
    $('#dashboard-pppoe-table tbody').html('<tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>');
    $.get('/api/admin/mikrotik?action=dashboard')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat dashboard', 'danger');
                return;
            }
            const summary = resp.data.summary || {};
            $('#summary-identity').text(summary.identity || '-');
            $('#summary-model').text(summary.model || '-');
            $('#summary-cpu').text((summary.cpu_load ?? '-') + ' %');
            const rx = formatBandwidth(summary.traffic_rx || 0);
            const tx = formatBandwidth(summary.traffic_tx || 0);
            $('#summary-traffic').text(`RX ${rx} • TX ${tx}`);
            const memTotal = formatBytes(summary.memory_total || 0);
            const memFree = formatBytes(summary.memory_free || 0);
            $('#summary-memory').text(`${memTotal} Total`);
            $('#summary-disk').text(`${formatBytes(summary.disk_free || 0)} bebas`);
            const ppp = resp.data.pppoe || { total: 0, active: 0, inactive: 0 };
            $('#summary-pppoe').text(`${ppp.total} pengguna`);
            $('#summary-pppoe-active').text(ppp.active);
            $('#summary-pppoe-inactive').text(ppp.inactive);

            const activeList = resp.data.active_connections || [];
            renderDashboardActiveConnections(activeList);
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat dashboard');
        });
}

function renderDashboardActiveConnections(activeList) {
    if (!Array.isArray(activeList) || activeList.length === 0) {
        $('#dashboard-pppoe-table tbody').html('<tr><td colspan="4" class="text-center text-muted">Tidak ada koneksi PPPoE aktif.</td></tr>');
        return;
    }
    const rows = activeList.map(item => {
        const service = escapeHtml(item.service || '-');
        const profileInfo = item.profile ? `<div class="text-muted small">${escapeHtml(item.profile)}</div>` : '';
        return `<tr>
            <td><strong>${escapeHtml(item.name || '')}</strong></td>
            <td>${escapeHtml(item.address || '-')}</td>
            <td>${escapeHtml(item.uptime || '-')}</td>
            <td>${service}${profileInfo}</td>
        </tr>`;
    }).join('');
    $('#dashboard-pppoe-table tbody').html(rows);
}

// PPP Users
function loadPppUsers() {
    $('#pppoe-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>');
    $.get('/api/admin/mikrotik?action=pppoe_users')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat user PPPoE', 'danger', '#alert-pppoe');
                return;
            }
            pppUsers = resp.data.users || [];
            renderPppUsers();
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat user PPPoE', '#alert-pppoe');
        });
}

function renderPppUsers() {
    if (pppUsers.length === 0) {
        $('#pppoe-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Belum ada user PPPoE.</td></tr>');
        return;
    }
    const rows = pppUsers.map(user => {
        const statusBadge = user.active
            ? '<span class="status-badge badge-active">Aktif</span>'
            : '<span class="status-badge badge-inactive">Offline</span>';
        const comment = user.comment ? `<span class="text-muted">${escapeHtml(user.comment)}</span>` : '-';
        const profile = user.profile || '-';
        return `<tr>
            <td><strong>${escapeHtml(user.name || '')}</strong></td>
            <td>${escapeHtml(profile)}</td>
            <td>${comment}</td>
            <td>${statusBadge}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="openPppoeUserModal('${escapeAttr(user.name)}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-outline-warning" onclick="disconnectPppoeUser('${escapeAttr(user.name)}')"><i class="fas fa-plug-circle-bolt"></i></button>
                    <button class="btn btn-outline-danger" onclick="deletePppoeUser('${escapeAttr(user.name)}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#pppoe-table tbody').html(rows);
}

function openPppoeUserModal(username = null) {
    $('#pppoe-user-form')[0].reset();
    $('#pppoe-user-identifier').val('');
    $('#pppoe-password').attr('placeholder', username ? 'Biarkan kosong untuk tetap' : '');
    resetPasswordInput('#pppoe-password');

    populateProfileSelect('#pppoe-profile', pppProfiles);

    if (username) {
        const user = pppUsers.find(u => u.name === username);
        if (!user) {
            showAlert('User tidak ditemukan', 'warning', '#alert-pppoe');
            return;
        }
        $('#pppoe-user-identifier').val(user.name);
        $('#pppoe-username').val(user.name).prop('readonly', true);
        $('#pppoe-profile').val(user.profile || '').trigger('change');
        $('#pppoe-comment').val(user.comment || '');
        $('#pppoe-user-submit').html('<i class="fas fa-save me-2"></i>Simpan Perubahan');
        $('#pppoeUserModal .modal-title').html('<i class="fas fa-user-edit me-2"></i>Edit User PPPoE');
    } else {
        $('#pppoe-username').prop('readonly', false);
        $('#pppoe-user-submit').html('<i class="fas fa-save me-2"></i>Simpan User');
        $('#pppoeUserModal .modal-title').html('<i class="fas fa-user-plus me-2"></i>Tambah User PPPoE');
    }

    pppoeUserModal.show();
}

function submitPppoeUserForm(e) {
    e.preventDefault();
    const identifier = $('#pppoe-user-identifier').val();
    const payload = {
        username: $('#pppoe-username').val().trim(),
        password: $('#pppoe-password').val().trim(),
        profile: $('#pppoe-profile').val(),
        comment: $('#pppoe-comment').val().trim()
    };

    const action = identifier ? 'pppoe_update' : 'pppoe_create';
    const body = identifier ? { identifier, updates: payload } : payload;
    if (identifier && !payload.password) {
        delete body.updates.password;
    }

    $('#pppoe-user-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: `/api/admin/mikrotik?action=${action}`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(body)
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menyimpan user PPPoE', 'danger', '#alert-pppoe');
            return;
        }
        safelyHideModal(pppoeUserModal);
        loadPppUsers();
        showAlert(resp.message || 'User PPPoE tersimpan', 'success', '#alert-pppoe');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menyimpan user PPPoE', '#alert-pppoe');
    }).always(function () {
        $('#pppoe-user-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan');
    });
}

function deletePppoeUser(username) {
    if (!confirm(`Hapus user PPPoE ${username}?`)) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=pppoe_delete',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier: username })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menghapus user', 'danger', '#alert-pppoe');
            return;
        }
        showAlert('User PPPoE dihapus', 'success', '#alert-pppoe');
        loadPppUsers();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menghapus user', '#alert-pppoe');
    });
}

function disconnectPppoeUser(username) {
    if (!confirm(`Putuskan koneksi ${username}?`)) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=pppoe_disconnect',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ username })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal memutus koneksi', 'danger', '#alert-pppoe');
            return;
        }
        showAlert('Koneksi diputus', 'success', '#alert-pppoe');
        loadPppUsers();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal memutus koneksi', '#alert-pppoe');
    });
}

// PPP Profiles
function loadPppProfiles() {
    $.get('/api/admin/mikrotik?action=pppoe_profiles')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat profil PPPoE', 'danger', '#alert-pppoe-profile');
                return;
            }
            pppProfiles = resp.data.profiles || [];
            renderPppProfiles();
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat profil PPPoE', '#alert-pppoe-profile');
        });
}

function renderPppProfiles() {
    if (pppProfiles.length === 0) {
        $('#pppoe-profiles-table tbody').html('<tr><td colspan="6" class="text-center text-muted">Belum ada profil PPPoE.</td></tr>');
        return;
    }
    const rows = pppProfiles.map(profile => {
        return `<tr>
            <td><strong>${escapeHtml(profile.name || '')}</strong></td>
            <td>${escapeHtml(profile['rate-limit'] || '-')}</td>
            <td>${escapeHtml(profile['local-address'] || '-')}</td>
            <td>${escapeHtml(profile['remote-address'] || '-')}</td>
            <td>${escapeHtml(profile.comment || '') || '-'}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="openPppoeProfileModal('${escapeAttr(profile.name)}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-outline-danger" onclick="deletePppoeProfile('${escapeAttr(profile.name)}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#pppoe-profiles-table tbody').html(rows);
}

function openPppoeProfileModal(identifier = null) {
    $('#pppoe-profile-form')[0].reset();
    $('#pppoe-profile-identifier').val('');

    if (identifier) {
        const profile = pppProfiles.find(p => p.name === identifier);
        if (!profile) {
            showAlert('Profil tidak ditemukan', 'warning', '#alert-pppoe-profile');
            return;
        }
        $('#pppoe-profile-identifier').val(profile.name);
        $('#pppoe-profile-name').val(profile.name).prop('readonly', true);
        $('#pppoe-profile-rate').val(profile['rate-limit'] || '');
        $('#pppoe-profile-local').val(profile['local-address'] || '');
        $('#pppoe-profile-remote').val(profile['remote-address'] || '');
        $('#pppoe-profile-comment').val(profile.comment || '');
        $('#pppoe-profile-submit').html('<i class="fas fa-save me-2"></i>Simpan Perubahan');
    } else {
        $('#pppoe-profile-name').prop('readonly', false);
        $('#pppoe-profile-submit').html('<i class="fas fa-save me-2"></i>Simpan Profil');
    }

    pppoeProfileModal.show();
}

function submitPppoeProfileForm(e) {
    e.preventDefault();
    const identifier = $('#pppoe-profile-identifier').val();
    const payload = {
        name: $('#pppoe-profile-name').val().trim(),
        'rate-limit': $('#pppoe-profile-rate').val().trim(),
        'local-address': $('#pppoe-profile-local').val().trim(),
        'remote-address': $('#pppoe-profile-remote').val().trim(),
        comment: $('#pppoe-profile-comment').val().trim()
    };

    $('#pppoe-profile-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: '/api/admin/mikrotik?action=pppoe_profile_save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier, profile: payload })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menyimpan profil', 'danger', '#alert-pppoe-profile');
            return;
        }
        safelyHideModal(pppoeProfileModal);
        loadPppProfiles();
        showAlert('Profil PPPoE tersimpan', 'success', '#alert-pppoe-profile');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menyimpan profil', '#alert-pppoe-profile');
    }).always(function () {
        $('#pppoe-profile-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Profil');
    });
}

function deletePppoeProfile(identifier) {
    if (!confirm(`Hapus profil ${identifier}?`)) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=pppoe_profile_delete',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menghapus profil', 'danger', '#alert-pppoe-profile');
            return;
        }
        showAlert('Profil PPPoE dihapus', 'success', '#alert-pppoe-profile');
        loadPppProfiles();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menghapus profil', '#alert-pppoe-profile');
    });
}

// Hotspot
function loadHotspotUsers() {
    $('#hotspot-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>');
    $.get('/api/admin/mikrotik?action=hotspot_users')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat user hotspot', 'danger', '#alert-hotspot');
                return;
            }
            hotspotUsers = resp.data.users || [];
            renderHotspotUsers();
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat user hotspot', '#alert-hotspot');
        });
}

function renderHotspotUsers() {
    if (hotspotUsers.length === 0) {
        $('#hotspot-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Tidak ada user hotspot aktif.</td></tr>');
        return;
    }
    const rows = hotspotUsers.map(user => {
        return `<tr>
            <td><strong>${escapeHtml(user.user || user.name || '')}</strong></td>
            <td>${escapeHtml(user.address || '-')}</td>
            <td>${escapeHtml(user.uptime || '-')}</td>
            <td>${escapeHtml(user.profile || '-')}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning" onclick="disconnectHotspotUser('${escapeAttr(user['.id'] ?? user.user)}')"><i class="fas fa-plug-circle-bolt"></i></button>
                    <button class="btn btn-outline-danger" onclick="removeHotspotUser('${escapeAttr(user.user ?? user.name)}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#hotspot-table tbody').html(rows);
}

function openHotspotUserModal(identifier = null) {
    $('#hotspot-user-form')[0].reset();
    $('#hotspot-user-identifier').val('');
    resetPasswordInput('#hotspot-password');
    populateProfileSelect('#hotspot-profile', hotspotProfiles);
    populateServerSelect('#hotspot-server', hotspotServers);

    if (identifier) {
        const user = hotspotUsers.find(u => u.user === identifier || u.name === identifier);
        if (!user) {
            showAlert('User hotspot tidak ditemukan', 'warning', '#alert-hotspot');
            return;
        }
        $('#hotspot-user-identifier').val(user.user || user.name);
        $('#hotspot-username').val(user.user || user.name).prop('readonly', true);
        $('#hotspot-profile').val(user.profile || '').trigger('change');
        $('#hotspot-comment').val(user.comment || '');
        $('#hotspot-user-submit').html('<i class="fas fa-save me-2"></i>Simpan Perubahan');
        document.querySelector('#hotspotUserModal .modal-title').innerHTML = '<i class="fas fa-wifi me-2"></i>Edit User Hotspot';
    } else {
        $('#hotspot-username').prop('readonly', false);
        $('#hotspot-user-submit').html('<i class="fas fa-save me-2"></i>Simpan User');
        document.querySelector('#hotspotUserModal .modal-title').innerHTML = '<i class="fas fa-wifi me-2"></i>Tambah User Hotspot';
    }

    hotspotUserModal.show();
}

function submitHotspotUserForm(e) {
    e.preventDefault();
    const identifier = $('#hotspot-user-identifier').val();
    const payload = {
        name: $('#hotspot-username').val().trim(),
        password: $('#hotspot-password').val().trim(),
        profile: $('#hotspot-profile').val(),
        server: $('#hotspot-server').val(),
        comment: $('#hotspot-comment').val().trim()
    };

    const action = identifier ? 'hotspot_update' : 'hotspot_create';
    const body = identifier ? { identifier, updates: payload } : payload;
    if (identifier && !payload.password) {
        delete body.updates.password;
    }

    $('#hotspot-user-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: `/api/admin/mikrotik?action=${action}`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(body)
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menyimpan user hotspot', 'danger', '#alert-hotspot');
            return;
        }
        safelyHideModal(hotspotUserModal);
        loadHotspotUsers();
        showAlert('User hotspot tersimpan', 'success', '#alert-hotspot');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menyimpan user hotspot', '#alert-hotspot');
    }).always(function () {
        $('#hotspot-user-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan User');
    });
}

function removeHotspotUser(identifier) {
    if (!confirm('Hapus user hotspot ini?')) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=hotspot_delete',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menghapus user hotspot', 'danger', '#alert-hotspot');
            return;
        }
        showAlert('User hotspot dihapus', 'success', '#alert-hotspot');
        loadHotspotUsers();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menghapus user hotspot', '#alert-hotspot');
    });
}

function disconnectHotspotUser(identifier) {
    if (!confirm('Putuskan user hotspot ini?')) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=hotspot_disconnect',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal memutus user hotspot', 'danger', '#alert-hotspot');
            return;
        }
        showAlert('User hotspot diputus', 'success', '#alert-hotspot');
        loadHotspotUsers();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal memutus user hotspot', '#alert-hotspot');
    });
}

function loadHotspotProfiles() {
    $.get('/api/admin/mikrotik?action=hotspot_profiles')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat profil hotspot', 'danger', '#alert-hotspot-profile');
                return;
            }
            hotspotProfiles = resp.data.profiles || [];
            renderHotspotProfiles();
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat profil hotspot', '#alert-hotspot-profile');
        });
}

function renderHotspotProfiles() {
    if (hotspotProfiles.length === 0) {
        $('#hotspot-profiles-table tbody').html('<tr><td colspan="5" class="text-center text-muted">Belum ada profil hotspot.</td></tr>');
        return;
    }
    const rows = hotspotProfiles.map(profile => {
        return `<tr>
            <td><strong>${escapeHtml(profile.name || '')}</strong></td>
            <td>${escapeHtml(profile['rate-limit'] || '-')}</td>
            <td>${escapeHtml(profile['session-timeout'] || '-')}</td>
            <td>${escapeHtml(profile.comment || '') || '-'}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="openHotspotProfileModal('${escapeAttr(profile.name)}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-outline-danger" onclick="deleteHotspotProfile('${escapeAttr(profile.name)}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#hotspot-profiles-table tbody').html(rows);
}

function openHotspotProfileModal(identifier = null) {
    $('#hotspotProfileModalLabel').html(`<i class="fas fa-layer-group me-2"></i>${identifier ? 'Edit Profil Hotspot' : 'Profil Hotspot Baru'}`);
    $('#hotspot-profile-form')[0].reset();
    $('#hotspot-profile-identifier').val('');
    $('#hotspot-profile-name').prop('readonly', false);

    if (identifier) {
        const profile = hotspotProfiles.find(p => p.name === identifier);
        if (!profile) {
            showAlert('Profil hotspot tidak ditemukan', 'warning', '#alert-hotspot-profile');
            return;
        }
        $('#hotspot-profile-identifier').val(profile.name);
        $('#hotspot-profile-name').val(profile.name).prop('readonly', true);
        $('#hotspot-profile-rate').val(profile['rate-limit'] || '');
        $('#hotspot-profile-session').val(profile['session-timeout'] || '');
        $('#hotspot-profile-comment').val(profile.comment || '');
        $('#hotspot-profile-submit').html('<i class="fas fa-save me-2"></i>Simpan Perubahan');
    } else {
        $('#hotspot-profile-submit').html('<i class="fas fa-save me-2"></i>Simpan Profil');
    }

    hotspotProfileModal.show();
}

function submitHotspotProfileForm(e) {
    e.preventDefault();
    const identifier = $('#hotspot-profile-identifier').val();
    const payload = {
        name: $('#hotspot-profile-name').val().trim(),
        'rate-limit': $('#hotspot-profile-rate').val().trim(),
        'session-timeout': $('#hotspot-profile-session').val().trim(),
        comment: $('#hotspot-profile-comment').val().trim()
    };

    $('#hotspot-profile-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: '/api/admin/mikrotik?action=hotspot_profile_save',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier, profile: payload })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menyimpan profil hotspot', 'danger', '#alert-hotspot-profile');
            return;
        }
        safelyHideModal(hotspotProfileModal);
        loadHotspotProfiles();
        showAlert('Profil hotspot tersimpan', 'success', '#alert-hotspot-profile');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menyimpan profil hotspot', '#alert-hotspot-profile');
    }).always(function () {
        $('#hotspot-profile-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Profil');
    });
}

function deleteHotspotProfile(identifier) {
    if (!confirm(`Hapus profil hotspot ${identifier}?`)) return;
    $.ajax({
        url: '/api/admin/mikrotik?action=hotspot_profile_delete',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ identifier })
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menghapus profil hotspot', 'danger', '#alert-hotspot-profile');
            return;
        }
        showAlert('Profil hotspot dihapus', 'success', '#alert-hotspot-profile');
        loadHotspotProfiles();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menghapus profil hotspot', '#alert-hotspot-profile');
    });
}

function loadHotspotServers() {
    $.get('/api/admin/mikrotik?action=hotspot_servers')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat server hotspot', 'warning', '#alert-hotspot');
                return;
            }
            hotspotServers = resp.data.servers || [];
            populateServerSelect('#hotspot-server', hotspotServers);
            populateServerSelect('#voucher-server', hotspotServers, true);
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat server hotspot', '#alert-hotspot');
        });
}

function openVoucherModal() {
    $('#hotspot-voucher-form')[0].reset();
    $('#voucher-count').val(5);
    $('#voucher-prefix').val('VCHR');
    $('#voucher-length').val(6);
    $('#voucher-result').hide().find('pre').text('');
    populateProfileSelect('#voucher-profile', hotspotProfiles);
    populateServerSelect('#voucher-server', hotspotServers, true);
    hotspotVoucherModal.show();
}

function submitVoucherForm(e) {
    e.preventDefault();

    const payload = {
        count: parseInt($('#voucher-count').val(), 10) || 0,
        prefix: $('#voucher-prefix').val().trim() || 'VCHR',
        length: parseInt($('#voucher-length').val(), 10) || 6,
        profile: $('#voucher-profile').val(),
        server: $('#voucher-server').val(),
        comment: $('#voucher-comment').val().trim()
    };

    $('#voucher-generate-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Membuat...');
    $.ajax({
        url: '/api/admin/mikrotik?action=hotspot_generate_voucher',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal membuat voucher', 'danger', '#alert-hotspot');
            return;
        }
        const vouchers = resp.data.vouchers || [];
        const text = vouchers.map(row => row.success
            ? `${row.username} : ${row.password}`
            : `${row.username} : error (${row.error || 'unknown'})`
        ).join('\n');
        $('#voucher-result').show().find('pre').text(text || 'Tidak ada voucher yang dibuat.');
        showAlert('Voucher hotspot berhasil dibuat', 'success', '#alert-hotspot');
        loadHotspotUsers();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal membuat voucher hotspot', '#alert-hotspot');
    }).always(function () {
        $('#voucher-generate-btn').prop('disabled', false).html('<i class="fas fa-bolt me-2"></i>Generate');
    });
}

// Router
function loadRouters() {
    $('#routers-table tbody').html('<tr><td colspan="7" class="text-center text-muted">Memuat data router...</td></tr>');
    $.get('/api/admin/mikrotik?action=routers')
        .done(function (resp) {
            if (!resp.success) {
                showAlert(resp.message || 'Gagal memuat router', 'danger', '#alert-router');
                return;
            }
            routers = resp.data.routers || [];
            renderRouters();
        })
        .fail(function (xhr) {
            handleAjaxError(xhr, 'Gagal memuat router', '#alert-router');
        });
}

function renderRouters() {
    if (routers.length === 0) {
        $('#routers-table tbody').html('<tr><td colspan="7" class="text-center text-muted">Belum ada router yang terdaftar.</td></tr>');
        return;
    }
    const rows = routers.map(router => {
        const tlsBadge = router.use_tls ? '<span class="badge bg-success-subtle text-success">Ya</span>' : '<span class="badge bg-secondary-subtle text-secondary">Tidak</span>';
        return `<tr>
            <td>${escapeHtml(router.name || '')}</td>
            <td>${escapeHtml(router.host || '')}</td>
            <td>${router.port || '-'}</td>
            <td>${escapeHtml(router.username || '')}</td>
            <td>${tlsBadge}</td>
            <td>${escapeHtml(router.comment || '') || '-'}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="openRouterModal(${router.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-outline-info" onclick="testRouter(${router.id})"><i class="fas fa-signal"></i></button>
                    <button class="btn btn-outline-danger" onclick="deleteRouter(${router.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    $('#routers-table tbody').html(rows);
}

function openRouterModal(id = null) {
    $('#router-form')[0].reset();
    $('#router-id').val('');
    $('#router-use-tls').prop('checked', false);
    $('#router-password').attr('placeholder', id ? 'Biarkan kosong untuk tetap' : '••••••');
    resetPasswordInput('#router-password');
    $('#router-submit-btn').html('<i class="fas fa-save me-2"></i>Simpan Router');
    $('#routerModal .modal-title').html('<i class="fas fa-network-wired me-2"></i>Router MikroTik');

    if (id) {
        const router = routers.find(r => r.id === id);
        if (!router) {
            showAlert('Router tidak ditemukan', 'warning', '#alert-router');
            return;
        }
        $('#router-id').val(router.id);
        $('#router-name').val(router.name || '');
        $('#router-host').val(router.host || '');
        $('#router-port').val(router.port || DEFAULT_MIKROTIK_PORT);
        $('#router-username').val(router.username || '');
        $('#router-use-tls').prop('checked', Number(router.use_tls) === 1);
        $('#router-comment').val(router.comment || '');
        $('#router-submit-btn').html('<i class="fas fa-save me-2"></i>Simpan Perubahan');
        $('#routerModal .modal-title').html('<i class="fas fa-network-wired me-2"></i>Edit Router MikroTik');
    }

    routerModal.show();
}

function submitRouterForm(e) {
    e.preventDefault();
    const id = $('#router-id').val();
    const payload = {
        name: $('#router-name').val().trim(),
        host: $('#router-host').val().trim(),
        port: parseInt($('#router-port').val(), 10) || DEFAULT_MIKROTIK_PORT,
        username: $('#router-username').val().trim(),
        password: $('#router-password').val(),
        use_tls: $('#router-use-tls').is(':checked') ? 1 : 0,
        comment: $('#router-comment').val().trim()
    };

    const action = id ? 'update_router&id=' + id : 'create_router';
    $('#router-submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: `/api/admin/mikrotik?action=${action}`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menyimpan router', 'danger', '#alert-router');
            return;
        }
        safelyHideModal(routerModal);
        loadRouters();
        showAlert(resp.message || 'Router tersimpan', 'success', '#alert-router');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menyimpan router', '#alert-router');
    }).always(function () {
        $('#router-submit-btn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Router');
    });
}

function deleteRouter(id) {
    if (!confirm('Hapus router ini?')) return;
    $.ajax({
        url: `/api/admin/mikrotik?action=delete_router&id=${id}`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({})
    }).done(function (resp) {
        if (!resp.success) {
            showAlert(resp.message || 'Gagal menghapus router', 'danger', '#alert-router');
            return;
        }
        showAlert('Router dihapus', 'success', '#alert-router');
        loadRouters();
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Gagal menghapus router', '#alert-router');
    });
}

function testRouter(id) {
    $('#alert-router').html('<div class="alert alert-info alert-dismissible fade show" role="alert">Menghubungkan ke router...<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
    $.ajax({
        url: '/api/admin/mikrotik?action=test',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ router_id: id })
    }).done(function (resp) {
        if (!resp.success || !resp.data) {
            showAlert(resp.message || 'Tes koneksi gagal', 'danger', '#alert-router');
            return;
        }
        const identity = resp.data && resp.data.identity ? resp.data.identity : 'unknown';
        showAlert(`Terhubung ke router (${escapeHtml(identity)})`, 'success', '#alert-router');
    }).fail(function (xhr) {
        handleAjaxError(xhr, 'Tes koneksi router gagal', '#alert-router');
    });
}

// Helpers
function populateProfileSelect(selector, profiles) {
    const $select = $(selector);
    const options = profiles.map(profile => `<option value="${escapeAttr(profile.name)}">${escapeHtml(profile.name)}</option>`);
    $select.html('<option value="">Pilih Profil...</option>' + options.join(''));
}

function resetPasswordInput(selector) {
    const $input = $(selector);
    if ($input.length) {
        $input.attr('type', 'password').val('');
    }
    const $toggle = $(`.toggle-password[data-target="${selector}"]`);
    if ($toggle.length) {
        $toggle.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        $toggle.attr('aria-label', 'Tampilkan password');
    }
}

function populateServerSelect(selector, servers, allowEmpty = false) {
    const $select = $(selector);
    let options = servers.map(server => `<option value="${escapeAttr(server.name || server['.id'] || '')}">${escapeHtml(server.name || server['.id'] || '')}</option>`);
    if (allowEmpty) {
        options = ['<option value="">(Default)</option>'].concat(options);
    }
    $select.html(options.join(''));
}

function formatBandwidth(value) {
    const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
    let v = Number(value) || 0;
    let unitIndex = 0;
    while (v >= 1000 && unitIndex < units.length - 1) {
        v /= 1000;
        unitIndex++;
    }
    return `${v.toFixed(v >= 10 || v === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let v = Number(bytes) || 0;
    let unitIndex = 0;
    while (v >= 1024 && unitIndex < units.length - 1) {
        v /= 1024;
        unitIndex++;
    }
    return `${v.toFixed(v >= 10 || v === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttr(str) {
    return escapeHtml(str).replace(/"/g, '&quot;');
}
</script>

<script>
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
