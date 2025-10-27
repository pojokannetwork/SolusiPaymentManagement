<?php
$page_title = 'Fiber Optic Management';
require_once __DIR__ . '/../includes/admin_header.php';
// Permission check after header for consistency
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.assets');

require_once __DIR__ . '/../includes/fiber_management.php';
FiberManagement::boot();
$csrfToken = getCsrfToken();
?>

        <div class="p-4">
            <link rel="stylesheet" href="/assets/vendor/simpleleaflet.css">
            <style>
                .summary-card { border: none; border-radius: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
                .summary-card .icon { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; }
                .map-container { min-height: 320px; border-radius: 18px; overflow: hidden; box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .15); }
                .table thead th { text-transform: uppercase; font-size: .72rem; letter-spacing: .04em; }
                .badge-label { background: rgba(59, 130, 246, .15); color: #2563eb; border-radius: 999px; padding: .35rem .75rem; font-size: .75rem; }
                .badge-label.secondary { background: rgba(236, 72, 153, .12); color: #db2777; }
                .leaflet-popup-content { min-width: 220px; }
                .photo-thumb { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; background: #e2e8f0; }
            </style>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Fiber Optic Management</h2>
                <span class="text-muted">Kelola joint closure dan koneksi core fiber optik • <?= sanitizeOutput($user['nama']); ?></span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary" id="btn-refresh">
                    <i class="fas fa-rotate me-2"></i>Pembaruan Data
                </button>
                <button class="btn btn-primary" id="btn-add-closure">
                    <i class="fas fa-plus-circle me-2"></i>Joint Closure Baru
                </button>
            </div>
        </div>

        <div id="alert-container"></div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card summary-card p-3">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold">Joint Closures</div>
                                <div class="display-6 fw-bold" id="summary-closures">0</div>
                            </div>
                            <div class="icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-cubes"></i>
                            </div>
                        </div>
                        <div class="text-muted small">Total joint closure terdaftar di jaringan</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card p-3">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold">Connections</div>
                                <div class="display-6 fw-bold" id="summary-connections">0</div>
                            </div>
                            <div class="icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-link"></i>
                            </div>
                        </div>
                        <div class="text-muted small">Jumlah sambungan core yang tercatat</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card p-3">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold">Avg Attenuation</div>
                                <div class="display-6 fw-bold" id="summary-attenuation">-</div>
                            </div>
                            <div class="icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-wave-square"></i>
                            </div>
                        </div>
                        <div class="text-muted small">Rata-rata redaman pasca sambungan</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card p-3">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase text-muted small fw-semibold">Networks</div>
                                <div class="display-6 fw-bold" id="summary-networks">0</div>
                            </div>
                            <div class="icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-network-wired"></i>
                            </div>
                        </div>
                        <div class="text-muted small">Segment jaringan aktif yang tercatat</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-semibold mb-1">Joint Closures</h5>
                        <span class="text-muted">Kelola koordinat, dokumentasi, dan status setiap closure.</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Cari nama atau alamat..." id="closure-search">
                        </div>
                        <select class="form-select" id="closure-label-filter">
                            <option value="">Semua label</option>
                        </select>
                        <button class="btn btn-outline-secondary" id="btn-toggle-map">
                            <i class="fas fa-map-marked-alt me-2"></i>Tampilkan Peta
                        </button>
                    </div>
                </div>

                <div class="collapse mb-4" id="mapSection">
                    <div class="map-container" id="closuresMap"></div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="closures-table">
                        <thead class="table-light">
                        <tr>
                            <th>Closure</th>
                            <th>Lokasi</th>
                            <th>Koordinat</th>
                            <th>Koneksi</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Memuat data joint closure...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-semibold mb-1">Core Connections</h5>
                        <span class="text-muted">Detail sambungan antar core lengkap dengan redaman.</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select" id="connection-closure-filter" style="min-width: 180px;">
                            <option value="">Semua joint closure</option>
                        </select>
                        <input type="text" class="form-control" placeholder="Cari network atau core..." id="connection-search">
                        <button class="btn btn-outline-primary" id="btn-add-connection">
                            <i class="fas fa-plus me-2"></i>Koneksi Baru
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="connections-table">
                        <thead class="table-light">
                        <tr>
                            <th>Joint Closure</th>
                            <th>Sumber</th>
                            <th>Tujuan</th>
                            <th>Network</th>
                            <th>Redaman (dB)</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Memuat data koneksi...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Closure Modal -->
<div class="modal fade" id="closureModal" tabindex="-1" aria-labelledby="closureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="closure-form" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="closureModalLabel">Tambah Joint Closure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="closure-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Joint Closure</label>
                            <input type="text" class="form-control" name="name" id="closure-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Alamat / Deskripsi Lokasi</label>
                            <input type="text" class="form-control" name="address" id="closure-address">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Latitude</label>
                            <input type="number" step="0.000001" class="form-control" name="latitude" id="closure-latitude">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Longitude</label>
                            <input type="number" step="0.000001" class="form-control" name="longitude" id="closure-longitude">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Altitude (m)</label>
                            <input type="number" step="0.1" class="form-control" name="altitude" id="closure-altitude">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Label / Tags</label>
                            <input type="text" class="form-control" name="labels" id="closure-labels" placeholder="Misal: Backbone, STO, POP">
                            <small class="text-muted">Pisahkan dengan koma untuk menambahkan lebih dari satu label.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" rows="3" name="description" id="closure-description"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto Dokumentasi</label>
                            <input type="file" class="form-control" name="photo" id="closure-photo" accept="image/*">
                            <small class="text-muted">Unggah foto terbaru perawatan / inspeksi.</small>
                        </div>
                        <div class="col-12 d-none" id="closure-photo-preview-wrapper">
                            <label class="form-label fw-semibold">Foto Saat Ini</label>
                            <div><img src="" alt="Preview" id="closure-photo-preview" class="rounded-3" style="max-width: 220px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Connection Modal -->
<div class="modal fade" id="connectionModal" tabindex="-1" aria-labelledby="connectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="connection-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="connectionModalLabel">Tambah Koneksi Core</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="connection-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Joint Closure</label>
                            <select class="form-select" name="closure_id" id="connection-closure" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tube Sumber</label>
                            <input type="text" class="form-control" name="tube_source" id="connection-tube-source" placeholder="Contoh: Biru">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Core Sumber</label>
                            <input type="text" class="form-control" name="core_source" id="connection-core-source" placeholder="Contoh: Merah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tube Tujuan</label>
                            <input type="text" class="form-control" name="tube_dest" id="connection-tube-dest">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Core Tujuan</label>
                            <input type="text" class="form-control" name="core_dest" id="connection-core-dest">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Network</label>
                            <input type="text" class="form-control" name="network_name" id="connection-network">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Redaman Sebelum (dB)</label>
                            <input type="number" step="0.01" class="form-control" name="attenuation_before" id="connection-atten-before">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Redaman Sesudah (dB)</label>
                            <input type="number" step="0.01" class="form-control" name="attenuation_after" id="connection-atten-after">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea class="form-control" rows="3" name="notes" id="connection-notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Closure Details Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="closureDetails" aria-labelledby="closureDetailsLabel">
    <div class="offcanvas-header">
        <div>
            <h5 class="offcanvas-title" id="closureDetailsLabel">Detail Joint Closure</h5>
            <small class="text-muted" id="closure-details-updated"></small>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="closure-details-content"></div>
    </div>
        </div>

<?php $page_specific_scripts = <<<'EOT'
<script src="/assets/vendor/simpleleaflet.js"></script>
<script>
const csrfToken = '<?= $csrfToken; ?>';
let closureModal, connectionModal, closureDetails;
let mapInstance = null;
let markersLayer = null;
let mapCollapse = null;
let leafletAvailable = false;

document.addEventListener('DOMContentLoaded', () => {
    closureModal = new bootstrap.Modal(document.getElementById('closureModal'));
    connectionModal = new bootstrap.Modal(document.getElementById('connectionModal'));
    closureDetails = new bootstrap.Offcanvas(document.getElementById('closureDetails'));

    document.getElementById('btn-add-closure').addEventListener('click', () => openClosureModal());
    document.getElementById('btn-add-connection').addEventListener('click', () => openConnectionModal());
    document.getElementById('closure-form').addEventListener('submit', submitClosureForm);
    document.getElementById('connection-form').addEventListener('submit', submitConnectionForm);
    document.getElementById('btn-refresh').addEventListener('click', refreshAll);
    document.getElementById('closure-search').addEventListener('input', debounce(loadClosures, 350));
    document.getElementById('connection-search').addEventListener('input', debounce(loadConnections, 350));
    document.getElementById('closure-label-filter').addEventListener('change', loadClosures);
    document.getElementById('connection-closure-filter').addEventListener('change', loadConnections);
    document.getElementById('btn-toggle-map').addEventListener('click', toggleMap);

    initMap();
    refreshAll();
});

function buildHeaders() {
    return { 'X-CSRF-Token': csrfToken };
}

function refreshAll() {
    loadSummary();
    loadClosures();
    loadConnections();
}

function loadSummary() {
    fetch('/api/admin/fiber_management.php?action=summary', { headers: buildHeaders() })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat ringkasan');
            const data = response.data;
            document.getElementById('summary-closures').textContent = data.closures ?? 0;
            document.getElementById('summary-connections').textContent = data.connections ?? 0;
            document.getElementById('summary-attenuation').textContent = data.avg_attenuation !== null ? data.avg_attenuation + ' dB' : '-';
            document.getElementById('summary-networks').textContent = data.active_networks ?? 0;
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function loadClosures() {
    const params = new URLSearchParams({
        search: document.getElementById('closure-search').value,
        label: document.getElementById('closure-label-filter').value
    });

    fetch('/api/admin/fiber_management.php?action=closures&' + params.toString(), { headers: buildHeaders() })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat closures');
            renderClosures(response.data.closures || []);
            populateClosureFilters(response.data.closures || []);
            updateMapMarkers(response.data.closures || []);
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function loadConnections() {
    const params = new URLSearchParams({
        closure_id: document.getElementById('connection-closure-filter').value,
        search: document.getElementById('connection-search').value
    });

    fetch('/api/admin/fiber_management.php?action=connections&' + params.toString(), { headers: buildHeaders() })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat connections');
            renderConnections(response.data.connections || []);
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function renderClosures(closures) {
    const tbody = document.querySelector('#closures-table tbody');
    if (!closures.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data joint closure</td></tr>';
        return;
    }

    const rows = closures.map(closure => {
        const photo = closure.photo_url ? `<img src="${closure.photo_url}" class="photo-thumb" alt="">` : '<div class="photo-thumb"></div>';
        const coords = closure.latitude && closure.longitude
            ? `${Number(closure.latitude).toFixed(6)}, ${Number(closure.longitude).toFixed(6)}`
            : '<span class="text-muted">-</span>';
        const labels = (closure.labels || []).map(label => `<span class="badge-label me-1">${escapeHtml(label)}</span>`).join('');
        const updated = closure.updated_at ? new Date(closure.updated_at).toLocaleString('id-ID') : '-';
        const avgAtten = closure.avg_attenuation !== null ? Number(closure.avg_attenuation).toFixed(2) + ' dB' : '-';

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${photo}
                        <div>
                            <strong class="d-block">${escapeHtml(closure.name)}</strong>
                            <span class="text-muted small">${labels || 'Tidak ada label'}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <div>${closure.address ? escapeHtml(closure.address) : '<span class="text-muted">-</span>'}</div>
                    <div class="text-muted small">Avg attenuation: ${avgAtten}</div>
                </td>
                <td>${coords}</td>
                <td>
                    <span class="badge bg-primary-subtle text-primary-emphasis">${closure.connection_count ?? 0} koneksi</span>
                </td>
                <td>
                    <span class="text-muted small">${updated}</span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="viewClosure(${closure.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-primary" onclick="openClosureModal(${closure.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteClosure(${closure.id}, '${escapeHtml(closure.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rows.join('');
}

function renderConnections(connections) {
    const tbody = document.querySelector('#connections-table tbody');
    if (!connections.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data koneksi core</td></tr>';
        return;
    }

    const rows = connections.map(connection => {
        const before = connection.attenuation_before !== null ? Number(connection.attenuation_before).toFixed(2) : '-';
        const after = connection.attenuation_after !== null ? Number(connection.attenuation_after).toFixed(2) : '-';

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(connection.closure_name ?? '-')}</strong>
                    <div class="text-muted small">${formatCoordinate(connection.latitude, connection.longitude)}</div>
                </td>
                <td>
                    <div>${escapeHtml(connection.tube_source ?? '-')} • ${escapeHtml(connection.core_source ?? '-')}</div>
                </td>
                <td>
                    <div>${escapeHtml(connection.tube_dest ?? '-')} • ${escapeHtml(connection.core_dest ?? '-')}</div>
                </td>
                <td>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis">${escapeHtml(connection.network_name ?? '-')}</span>
                </td>
                <td>
                    <div class="text-success small">Sesudah: ${after}</div>
                    <div class="text-muted small">Sebelum: ${before}</div>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="openConnectionModal(${connection.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteConnection(${connection.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rows.join('');
}

function populateClosureFilters(closures) {
    const closureFilter = document.getElementById('connection-closure-filter');
    const connectionSelect = document.getElementById('connection-closure');
    const labelFilter = document.getElementById('closure-label-filter');

    const options = ['<option value="">Semua joint closure</option>'];
    const modalOptions = ['<option value="" disabled selected>Pilih joint closure</option>'];
    const labelSet = new Set();

    closures.forEach(closure => {
        options.push(`<option value="${closure.id}">${escapeHtml(closure.name)}</option>`);
        modalOptions.push(`<option value="${closure.id}">${escapeHtml(closure.name)}</option>`);
        (closure.labels || []).forEach(label => labelSet.add(label));
    });

    closureFilter.innerHTML = options.join('');
    connectionSelect.innerHTML = modalOptions.join('');

    const labelOptions = ['<option value="">Semua label</option>'];
    Array.from(labelSet).sort().forEach(label => {
        labelOptions.push(`<option value="${escapeHtml(label)}">${escapeHtml(label)}</option>`);
    });
    labelFilter.innerHTML = labelOptions.join('');
}

function openClosureModal(id = null) {
    const form = document.getElementById('closure-form');
    form.reset();
    document.getElementById('closure-id').value = '';
    document.getElementById('closureModalLabel').textContent = id ? 'Edit Joint Closure' : 'Tambah Joint Closure';
    document.getElementById('closure-photo-preview-wrapper').classList.add('d-none');

    if (id) {
        fetch(`/api/admin/fiber_management.php?action=closure&id=${id}`, { headers: buildHeaders() })
            .then(res => res.json())
            .then(response => {
                if (!response.success) throw new Error(response.message || 'Gagal memuat data closure');
                const closure = response.data.closure;
                document.getElementById('closure-id').value = closure.id;
                document.getElementById('closure-name').value = closure.name ?? '';
                document.getElementById('closure-address').value = closure.address ?? '';
                document.getElementById('closure-latitude').value = closure.latitude ?? '';
                document.getElementById('closure-longitude').value = closure.longitude ?? '';
                document.getElementById('closure-altitude').value = closure.altitude ?? '';
                document.getElementById('closure-labels').value = (closure.labels || []).join(', ');
                document.getElementById('closure-description').value = closure.description ?? '';
                if (closure.photo_url) {
                    document.getElementById('closure-photo-preview').src = closure.photo_url;
                    document.getElementById('closure-photo-preview-wrapper').classList.remove('d-none');
                }
                closureModal.show();
            })
            .catch(error => showAlert(error.message, 'danger'));
    } else {
        closureModal.show();
    }
}

function submitClosureForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const isEdit = !!formData.get('id');
    const action = isEdit ? 'update_closure' : 'create_closure';

    fetch('/api/admin/fiber_management.php?action=' + action, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: formData
    })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal menyimpan joint closure');
            closureModal.hide();
            showAlert(response.message || 'Joint closure disimpan', 'success');
            refreshAll();
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function deleteClosure(id, name) {
    if (!confirm(`Hapus joint closure "${name}" beserta semua koneksi?`)) return;
    fetch('/api/admin/fiber_management.php?action=delete_closure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...buildHeaders() },
        body: JSON.stringify({ id })
    })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal menghapus joint closure');
            showAlert('Joint closure dihapus', 'success');
            refreshAll();
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function openConnectionModal(id = null) {
    const form = document.getElementById('connection-form');
    form.reset();
    document.getElementById('connection-id').value = '';
    document.getElementById('connectionModalLabel').textContent = id ? 'Edit Koneksi Core' : 'Tambah Koneksi Core';

    if (id) {
        fetch(`/api/admin/fiber_management.php?action=connection&id=${id}`, { headers: buildHeaders() })
            .then(res => res.json())
            .then(response => {
                if (!response.success) throw new Error(response.message || 'Gagal memuat data koneksi');
                const connection = response.data.connection;
                document.getElementById('connection-id').value = connection.id;
                document.getElementById('connection-closure').value = connection.closure_id;
                document.getElementById('connection-tube-source').value = connection.tube_source ?? '';
                document.getElementById('connection-core-source').value = connection.core_source ?? '';
                document.getElementById('connection-tube-dest').value = connection.tube_dest ?? '';
                document.getElementById('connection-core-dest').value = connection.core_dest ?? '';
                document.getElementById('connection-network').value = connection.network_name ?? '';
                document.getElementById('connection-atten-before').value = connection.attenuation_before ?? '';
                document.getElementById('connection-atten-after').value = connection.attenuation_after ?? '';
                document.getElementById('connection-notes').value = connection.notes ?? '';
                connectionModal.show();
            })
            .catch(error => showAlert(error.message, 'danger'));
    } else {
        connectionModal.show();
    }
}

function submitConnectionForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    const isEdit = !!data.id;
    const action = isEdit ? 'update_connection' : 'create_connection';

    fetch('/api/admin/fiber_management.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...buildHeaders() },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal menyimpan koneksi');
            connectionModal.hide();
            showAlert('Koneksi fiber disimpan', 'success');
            loadConnections();
            loadClosures();
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function deleteConnection(id) {
    if (!confirm('Hapus koneksi core ini?')) return;
    fetch('/api/admin/fiber_management.php?action=delete_connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...buildHeaders() },
        body: JSON.stringify({ id })
    })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal menghapus koneksi');
            showAlert('Koneksi dihapus', 'success');
            loadConnections();
            loadClosures();
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function viewClosure(id) {
    fetch(`/api/admin/fiber_management.php?action=closure&id=${id}`, { headers: buildHeaders() })
        .then(res => res.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat detail closure');
            const closure = response.data.closure;
            document.getElementById('closureDetailsLabel').textContent = closure.name ?? 'Joint Closure';
            document.getElementById('closure-details-updated').textContent = closure.updated_at ? 'Diperbarui ' + new Date(closure.updated_at).toLocaleString('id-ID') : '';

            const detailsHtml = `
                <div class="mb-3">
                    <div class="fw-semibold text-uppercase text-muted small">Lokasi</div>
                    <div>${closure.address ? escapeHtml(closure.address) : '-'}</div>
                    <div class="text-muted small">${formatCoordinate(closure.latitude, closure.longitude)}</div>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold text-uppercase text-muted small">Deskripsi</div>
                    <div>${closure.description ? escapeHtml(closure.description) : '-'}</div>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold text-uppercase text-muted small">Label</div>
                    <div>${(closure.labels || []).map(label => `<span class="badge-label me-1">${escapeHtml(label)}</span>`).join('') || '-'}</div>
                </div>
                ${closure.photo_url ? `
                    <div class="mb-3">
                        <div class="fw-semibold text-uppercase text-muted small">Foto Dokumentasi</div>
                        <img src="${closure.photo_url}" class="img-fluid rounded-4 shadow-sm" alt="Foto closure">
                    </div>
                ` : ''}
            `;

            document.getElementById('closure-details-content').innerHTML = detailsHtml;
            closureDetails.show();
        })
        .catch(error => showAlert(error.message, 'danger'));
}

function showAlert(message, type = 'info') {
    const container = document.getElementById('alert-container');
    const wrapper = document.createElement('div');
    wrapper.className = `alert alert-${type} alert-dismissible fade show`;
    wrapper.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    container.appendChild(wrapper);
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(wrapper);
        alert.close();
    }, 6000);
}

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function debounce(fn, delay) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

function formatCoordinate(lat, lng) {
    if (lat === null || lng === null || lat === undefined || lng === undefined) return '-';
    return `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
}

function initMap() {
    const mapWrapper = document.getElementById('mapSection');
    const toggleButton = document.getElementById('btn-toggle-map');

    if (typeof L === 'undefined') {
        leafletAvailable = false;
        toggleButton.classList.add('d-none');
        mapWrapper.innerHTML = '<div class="p-4 text-center text-muted">Peta tidak tersedia karena library Leaflet gagal dimuat. Pastikan koneksi internet ke CDN diizinkan.</div>';
        return;
    }

    leafletAvailable = true;
    mapCollapse = new bootstrap.Collapse(mapWrapper, { toggle: false });

    mapInstance = L.map('closuresMap', {
        center: [-6.2, 106.8],
        zoom: 6,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    markersLayer = L.layerGroup().addTo(mapInstance);
}

function updateMapMarkers(closures) {
    if (!leafletAvailable || !mapInstance || !markersLayer) {
        return;
    }

    markersLayer.clearLayers();
    const validClosures = closures.filter(c => c.latitude && c.longitude);
    if (!validClosures.length) return;

    const bounds = [];
    validClosures.forEach(closure => {
        const marker = L.marker([closure.latitude, closure.longitude]);
        marker.bindPopup(`
            <strong>${escapeHtml(closure.name)}</strong><br>
            ${escapeHtml(closure.address ?? '-')}.
            <div class="text-muted small mt-1">${formatCoordinate(closure.latitude, closure.longitude)}</div>
        `);
        marker.addTo(markersLayer);
        bounds.push([closure.latitude, closure.longitude]);
    });

    if (bounds.length === 1) {
        mapInstance.setView(bounds[0], 14);
    } else {
        mapInstance.fitBounds(bounds, { padding: [30, 30] });
    }

    if (!document.getElementById('mapSection').classList.contains('show')) {
        // Automatically open the map the first time we have valid closures
        mapCollapse.show();
        setTimeout(() => mapInstance.invalidateSize(), 300);
    }
}

function toggleMap() {
    if (!leafletAvailable || !mapCollapse) {
        showAlert('Peta belum dapat ditampilkan. Pastikan koneksi ke CDN Leaflet tersedia.', 'warning');
        return;
    }

    if (document.getElementById('mapSection').classList.contains('show')) {
        mapCollapse.hide();
    } else {
        mapCollapse.show();
        setTimeout(() => mapInstance.invalidateSize(), 300);
    }
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
