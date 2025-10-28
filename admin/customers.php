<?php
$page_title = 'Customers';
require_once __DIR__ . '/../includes/admin_header.php';

// Add page-specific stylesheets
function add_styles() {
    echo '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom Responsive Styles -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">
';
}

// Check authentication and permissions
$guard->requirePermission('admin.customers');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Customers</h2>
    <button class="btn btn-primary" onclick="showCreateModal()">
        <i class="fas fa-plus me-2"></i>Add Customer
    </button>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" id="search-input" placeholder="Search customers...">
            </div>
            <div class="col-md-2">
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="isolir">Isolir</option>
                    <option value="suspended">Suspended</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="package-filter">
                    <option value="">All Packages</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                    <i class="fas fa-times me-2"></i>Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customers-table" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Billing</th>
                        <th>Active Date</th>
                        <th>Isolir Date</th>
                        <th>PPPoE User</th>
                        <th>Auth Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals -->
<?php include '../templates/admin/customer_modals.php'; ?>

<?php
$page_specific_scripts = <<<'EOT'
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let customersTable;
    let customerModal;
    let whatsAppModal;
    let currentWhatsAppCustomerId = null;
    let customerMap = null;
    let customerMarker = null;

    $(document).ready(function() {
        customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
        whatsAppModal = new bootstrap.Modal(document.getElementById('whatsAppModal'));
        initializeTable();
        loadRouters();
        loadMitraDropdown();
        setupEventListeners();
        // Init map when customer modal is shown
        $('#customerModal').on('shown.bs.modal', function(){
            initCustomerMap();
            syncMapFromFields();
        });
    });

    function initializeTable() {
        customersTable = $('#customers-table').DataTable({
            ajax: {
                url: '/api/admin/customers?action=list',
                dataSrc: 'data.customers',
                data: function(d) {
                    d.search = $('#search-input').val();
                    d.status = $('#status-filter').val();
                    d.package = $('#package-filter').val();
                }
            },
            columns: [
                { data: 'kode_pelanggan' },
                { data: 'nama' },
                { data: 'email' },
                { data: 'telp' },
                { data: 'paket' },
                {
                    data: 'status',
                    render: function(data) {
                        const badges = {
                            'active': '<span class="badge bg-success">Active</span>',
                            'isolir': '<span class="badge bg-danger">Isolir</span>',
                            'suspended': '<span class="badge bg-warning">Suspended</span>',
                            'terminated': '<span class="badge bg-secondary">Terminated</span>'
                        };
                        return badges[data] || data;
                    }
                },
                {
                    data: 'sistem_bayar',
                    render: function(data) {
                        const badges = {
                            'postpaid': '<span class="badge bg-info">Postpaid</span>',
                            'prepaid': '<span class="badge bg-warning">Prepaid</span>'
                        };
                        return badges[data] || data;
                    }
                },
                {
                    data: 'tanggal_aktif',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('id-ID') : '-';
                    }
                },
                {
                    data: 'tanggal_isolir',
                    render: function(data, type, row) {
                        if (!data || row.sistem_bayar === 'prepaid') return '-';
                        const isolirDate = new Date(data);
                        const today = new Date();
                        const isOverdue = isolirDate < today;
                        const dateStr = isolirDate.toLocaleDateString('id-ID');
                        return isOverdue ? `<span class="text-danger fw-bold">${dateStr}</span>` : dateStr;
                    }
                },
                { data: 'pppoe_user' },
                {
                    data: null,
                    render: function(data) {
                        if (!data.pppoe_user) return '-';
                        
                        if (!data.router_id || data.router_id === '') {
                            return '<span class="badge bg-info"><i class="fas fa-server me-1"></i>RADIUS</span>';
                        } else {
                            return '<span class="badge bg-primary"><i class="fas fa-router me-1"></i>Mikrotik</span>';
                        }
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data) {
                        return `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editCustomer(${data.id})" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-danger" onclick="deleteCustomer(${data.id})" title="Delete"><i class="fas fa-trash"></i></button>
                                <button class="btn btn-outline-warning" onclick="toggleCustomerStatus(${data.id}, '${data.status}')" title="Toggle Status"><i class="fas fa-${data.status === 'active' ? 'ban' : 'check'}"></i></button>
                                <button class="btn btn-outline-success" onclick="openWhatsAppModal(${data.id})" title="Send WhatsApp"><i class="fab fa-whatsapp"></i></button>
                            </div>
                        `;
                    }
                }
            ],
            pageLength: 25,
            responsive: true,
            language: { emptyTable: "No customers found" }
        });
    }

function loadRouters() {
    // Routers for modal
    $.get('/api/admin/mikrotik?action=routers').done(function(response) {
        if (response.success) {
            let options = '<option value="">Select Router/Method</option>';
            options += '<option value="radius"><i class="fas fa-server"></i> RADIUS (Central Authentication)</option>';
            (response.data?.routers || []).forEach(router => {
                options += `<option value="${router.id}"><i class="fas fa-router"></i> ${router.name} (${router.host || 'Mikrotik'})</option>`;
            });
            $('#router_id').html(options);
        }
    }).fail(function() {
        // Fallback if mikrotik API fails
        let options = '<option value="">Select Router/Method</option>';
        options += '<option value="radius">RADIUS (Central Authentication)</option>';
        $('#router_id').html(options);
    });
    // Packages for table filter
    $.get('/api/admin/packages.php?action=list').done(function(data){
        let options = '<option value="">All Packages</option>';
        (data || []).forEach(pkg => options += `<option value="${pkg.name}">${pkg.name}</option>`);
        $('#package-filter').html(options);
    });
}

// Load packages for the modal form
function loadPackagesForForm(selectedName = '') {
    $.get('/api/admin/packages.php?action=list').done(function(data){
        const sel = $('#paket');
        const prev = selectedName || sel.val();
        let opts = '<option value="">Select Package</option>';
        (data || []).forEach(pkg => {
            const text = `${pkg.name} (${pkg.speed})`;
            const selected = (pkg.name === prev) ? 'selected' : '';
            const profile = pkg.pppoe_profile || '';
            opts += `<option value="${pkg.name}" data-profile="${profile}" ${selected}>${text}</option>`;
        });
        sel.html(opts);
        const sopt = sel.find('option:selected');
        const profile = sopt.data('profile');
        if (profile) {
            $('#profile_aktif').val(profile);
        }
    });
}

function loadMitraDropdown(selectedId = '') {
    $.get('/api/admin/agents.php?action=list').done(function(data){
        let options = '<option value="">Pilih Mitra</option>';
        (data || []).forEach(m => {
            const sel = (String(m.id) === String(selectedId)) ? 'selected' : '';
            options += `<option value="${m.id}" ${sel}>${m.user_name} (rate ${m.commission_rate || 0}%)</option>`;
        });
        $('#mitra_id').html(options);
    });
}

// Load PPP profiles for the modal form
function loadProfilesForForm() {
    $.get('/api/admin/mikrotik?action=pppoe_profiles').done(function(response){
        if (!response.success) return;
        const items = response.data?.profiles || response.profiles || response.data || [];
        const activeSel = $('#profile_aktif');
        const isolirSel = $('#profile_isolir');
        const currentActive = activeSel.val();
        const currentIsolir = isolirSel.val();
        let options = '';
        items.forEach(p => {
            const name = p.name || p['name'] || p.profile || '';
            if (!name) return;
            options += `<option value="${name}">${name}</option>`;
        });
        if (options) {
            const defaultActive = currentActive || 'default';
            const defaultIsolir = currentIsolir || 'ISOLIR';
            activeSel.html(options).val(defaultActive);
            isolirSel.html(options).val(defaultIsolir);
        }
    });
    // When package changes, adopt its pppoe_profile value into active profile
    $(document).off('change.paketProfile').on('change.paketProfile', '#paket', function(){
        const prof = $(this).find('option:selected').data('profile');
        if (prof) $('#profile_aktif').val(prof);
    });
}

function setupEventListeners() {
        $('#search-input, #status-filter, #package-filter').on('input change', () => customersTable.ajax.reload());
        $('#customer-form').on('submit', e => { e.preventDefault(); saveCustomer(); });
        // Toggle show/hide PPPoE password
        $(document).on('click', '#pppoe_toggle', function(){
            const input = $('#pppoe_pass');
            const isPwd = input.attr('type') === 'password';
            input.attr('type', isPwd ? 'text' : 'password');
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        // Enforce PPP requirements based on input
        const enforcePPPRequirements = () => {
            const isCreate = ($('#customer-id').val() || '') === '';
            const hasUser = ($('#pppoe_user').val() || '').trim().length > 0;
            const routerValue = $('#router_id').val();
            
            $('#pppoe_pass').prop('required', isCreate && hasUser);
            
            // Router/Authentication method is required if PPPoE user is specified
            $('#router_id').prop('required', hasUser);
            
            // Show helpful text based on selection
            updateRouterHelperText(routerValue);
        };
        
        function updateRouterHelperText(routerValue) {
            let helpText = '';
            if (routerValue === 'radius') {
                helpText = '<small><strong>RADIUS selected:</strong> Customer will be authenticated via central RADIUS server</small>';
            } else if (routerValue && routerValue !== '') {
                helpText = '<small><strong>Mikrotik Router selected:</strong> Customer will be managed directly on this router</small>';
            } else {
                helpText = '<small><strong>RADIUS:</strong> Central authentication server<br><strong>Mikrotik Router:</strong> Direct router management</small>';
            }
            $('#router_id').closest('.col-md-6').find('.form-text').html(helpText);
        }
        $(document).on('input change', '#pppoe_user, #customer-id, #router_id', enforcePPPRequirements);
        $(document).on('change', '#router_id', function() {
            updateRouterHelperText($(this).val());
        });
        enforcePPPRequirements();
        
        // Billing system logic
        setupBillingSystemLogic();
        // Map interactions
        $(document).on('change', '#lat, #lon', () => syncMapFromFields());
        $(document).on('click', '#btn-current-location', function(){
            if (!navigator.geolocation) { alert('Geolocation tidak didukung browser ini.'); return; }
            navigator.geolocation.getCurrentPosition(function(pos){
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                $('#lat').val(lat.toFixed(6));
                $('#lon').val(lon.toFixed(6));
                setMapView(lat, lon);
            }, function(){
                alert('Gagal mendapatkan lokasi saat ini.');
            });
        });
        $('#wa-copy-message').on('click', function() {
            const message = $('#wa-message').text().trim();
            if (!message) return showWhatsAppAlert('No message to copy.', 'warning');
            navigator.clipboard.writeText(message)
                .then(() => showWhatsAppAlert('Message copied successfully.', 'success', 2000))
                .catch(() => showWhatsAppAlert('Failed to copy message.', 'danger'));
        });
        $('#wa-open-link').on('click', () => { const link = $('#wa-link').val(); if(link) window.open(link, '_blank'); });
        $('#wa-download-qr').on('click', function() {
            const dataUrl = $(this).data('qr');
            if (!dataUrl) return showWhatsAppAlert('QR not available.', 'warning');
            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = 'whatsapp-qr.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        $('#wa-queue').on('click', function() {
            if (!currentWhatsAppCustomerId) return showWhatsAppAlert('Customer ID not found.', 'danger');
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
            $.post('/api/admin/whatsapp_notifications.php?action=queue_registration', JSON.stringify({ customer_id: currentWhatsAppCustomerId }), null, 'json')
            .done(response => {
                if (!response.success) throw new Error(response.message || 'Failed to add to queue');
                showWhatsAppAlert('Registration message queued.', 'success');
            })
            .fail(xhr => showWhatsAppAlert(xhr.responseJSON?.message || 'Server error.', 'danger'))
            .always(() => $(this).prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Queue Sending'));
    });
    // Address search via Nominatim
    let searchTimer = null;
    $(document).on('input', '#address-search', function(){
        clearTimeout(searchTimer);
        const q = $(this).val().trim();
        if (q.length < 3) { $('#search-results').empty(); return; }
        searchTimer = setTimeout(() => doGeocode(q), 400);
    });
}

// Billing System Logic
function setupBillingSystemLogic() {
    // Handle sistem bayar change
    $(document).on('change', '#sistem_bayar', function() {
        const sistemBayar = $(this).val();
        toggleBillingFields(sistemBayar);
    });
    
    // Handle tanggal aktif change for auto isolir calculation
    $(document).on('change', '#tanggal_aktif, #cycle_billing, #grace_period', function() {
        calculateTanggalIsolir();
    });
    
    // Set default tanggal aktif to today when modal opens
    $('#customerModal').on('show.bs.modal', function() {
        if (!$('#tanggal_aktif').val()) {
            const today = new Date().toISOString().split('T')[0];
            $('#tanggal_aktif').val(today);
        }
        calculateTanggalIsolir();
    });
}

function toggleBillingFields(sistemBayar) {
    if (sistemBayar === 'prepaid') {
        // Prepaid: Hide postpaid fields, show prepaid specific
        $('#postpaid-fields').hide();
        $('#isolir-fields').hide();
        $('#cycle_billing').prop('required', false);
    } else {
        // Postpaid: Show billing cycle and isolir fields
        $('#postpaid-fields').show();
        $('#isolir-fields').show();
        $('#cycle_billing').prop('required', true);
    }
}

function calculateTanggalIsolir() {
    const sistemBayar = $('#sistem_bayar').val();
    const tanggalAktif = $('#tanggal_aktif').val();
    const cycleBilling = parseInt($('#cycle_billing').val()) || 1;
    const gracePeriod = parseInt($('#grace_period').val()) || 7;
    
    if (sistemBayar === 'postpaid' && tanggalAktif) {
        const aktifDate = new Date(tanggalAktif);
        
        // Calculate next billing date
        const nextBilling = new Date(aktifDate);
        nextBilling.setMonth(nextBilling.getMonth() + 1);
        nextBilling.setDate(cycleBilling);
        
        // Add grace period
        const isolirDate = new Date(nextBilling);
        isolirDate.setDate(isolirDate.getDate() + gracePeriod);
        
        // Set tanggal isolir if not manually set
        if (!$('#tanggal_isolir').val()) {
            $('#tanggal_isolir').val(isolirDate.toISOString().split('T')[0]);
        }
    }
}

// Geocoding helper
function doGeocode(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(query)}&accept-language=id`;
    fetch(url, { headers: { 'Accept': 'application/json' }})
      .then(r => r.json())
      .then(list => {
        const $res = $('#search-results');
        $res.empty();
        (list || []).forEach(item => {
            const name = item.display_name;
            const lat = parseFloat(item.lat);
            const lon = parseFloat(item.lon);
            const a = $(`<a href="#" class="list-group-item list-group-item-action">${name}</a>`);
            a.on('click', function(e){ e.preventDefault(); $('#lat').val(lat.toFixed(6)); $('#lon').val(lon.toFixed(6)); setMapView(lat, lon, 16); $('#address-search').val(name); $('#search-results').empty(); });
            $res.append(a);
        });
      })
      .catch(() => {});
}

function showCreateModal() {
        $('#modal-title').text('Add Customer');
        $('#customer-form')[0].reset();
        $('#customer-id').val('');
        
        // Set billing system defaults
        $('#sistem_bayar').val('postpaid');
        $('#cycle_billing').val('1');
        $('#grace_period').val('7');
        $('#auto_isolir').prop('checked', true);
        
        // Set default tanggal aktif to today
        const today = new Date().toISOString().split('T')[0];
        $('#tanggal_aktif').val(today);
        
        customerModal.show();
        // Re-evaluate PPP requirements in create mode
        $('#pppoe_pass').prop('required', false);
    // Set default map view
    setTimeout(() => { initCustomerMap(); setMapView(-6.200000, 106.816666, 12); }, 100);
    // Load dynamic selects
    loadPackagesForForm('');
    loadProfilesForForm();
    
    // Initialize billing fields
    toggleBillingFields('postpaid');
    calculateTanggalIsolir();
}

    function openWhatsAppModal(customerId) {
        if (!customerId) return;
        resetWhatsAppModal();
        currentWhatsAppCustomerId = customerId;
        whatsAppModal.show();
        $.post('/api/admin/whatsapp_notifications.php?action=preview', JSON.stringify({ type: 'registration', customer_id: customerId }), null, 'json')
        .done(response => {
            if (!response.success) return showWhatsAppAlert(response.message || 'Failed to create WhatsApp message.', 'danger');
            renderWhatsAppPreview(response.data);
        })
        .fail(xhr => showWhatsAppAlert(xhr.responseJSON?.message || 'Server error.', 'danger'));
    }

    function resetWhatsAppModal() {
        $('#whatsapp-alert').empty();
        $('#wa-phone').text('-');
        $('#wa-link').val('');
        $('#wa-open-link').prop('disabled', true);
        $('#wa-message').text('Loading message...');
        $('#wa-qr').hide().attr('src', '');
        $('#wa-qr-placeholder').show().text('QR will appear here');
        $('#wa-download-qr').prop('disabled', true).removeData('qr');
        $('#wa-queue').prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Queue Sending');
        currentWhatsAppCustomerId = null;
    }

    function renderWhatsAppPreview(data) {
        $('#wa-phone').text(data.phone || '-');
        $('#wa-link').val(data.link || '');
        $('#wa-message').text(data.message || '-');
        $('#wa-open-link').prop('disabled', !data.link);
        if (data.qr) {
            $('#wa-qr').attr('src', data.qr).show();
            $('#wa-qr-placeholder').hide();
            $('#wa-download-qr').prop('disabled', false).data('qr', data.qr);
        } else {
            $('#wa-qr').hide();
            $('#wa-qr-placeholder').show().text('QR not available');
            $('#wa-download-qr').prop('disabled', true).removeData('qr');
        }
    }

    function showWhatsAppAlert(message, type = 'info', timeout = 4000) {
        const alert = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
        $('#whatsapp-alert').append(alert);
        if (timeout > 0) setTimeout(() => alert.alert('close'), timeout);
    }

    function editCustomer(id) {
        $.get(`/api/admin/customers?action=get&id=${id}`).done(response => {
            if (response.success && response.data?.customer) {
                const cust = response.data.customer;
                $('#modal-title').text('Edit Customer');
                $('#customer-id').val(cust.id);
                $('#nama').val(cust.nama);
                $('#email').val(cust.email);
                $('#telp').val(cust.telp);
                $('#paket').val(cust.paket);
                $('#alamat').val(cust.alamat);
                $('#lat').val(cust.lat || '');
                $('#lon').val(cust.lon || '');
                $('#pppoe_user').val(cust.pppoe_user);
            // Handle RADIUS selection (if router_id is null/empty and has pppoe_user)
            if (cust.pppoe_user && (!cust.router_id || cust.router_id === '')) {
                $('#router_id').val('radius');
            } else {
                $('#router_id').val(cust.router_id);
            }
            $('#status').val(cust.status);
            $('#profile_aktif').val(cust.profile_aktif);
            $('#profile_isolir').val(cust.profile_isolir);
            
            // Billing system fields
            $('#sistem_bayar').val(cust.sistem_bayar || 'postpaid');
            $('#tanggal_aktif').val(cust.tanggal_aktif || '');
            $('#tanggal_isolir').val(cust.tanggal_isolir || '');
            $('#cycle_billing').val(cust.cycle_billing || 1);
            $('#grace_period').val(cust.grace_period || 7);
            $('#auto_isolir').prop('checked', cust.auto_isolir == 1);
            
            // Toggle billing fields based on sistem_bayar
            toggleBillingFields(cust.sistem_bayar || 'postpaid');
            
            // Mitra mapping (if available)
            if (cust.mitra_id) {
                loadMitraDropdown(cust.mitra_id);
                $('#mitra_region').val(cust.mitra_region || '');
                $('#mitra_share').val(cust.mitra_share || '');
            } else {
                loadMitraDropdown('');
                $('#mitra_region').val('');
                $('#mitra_share').val('');
            }
            customerModal.show();
                setTimeout(() => { initCustomerMap(); syncMapFromFields(); }, 150);
                // Load and keep selections
                loadPackagesForForm(cust.paket || '');
                loadProfilesForForm();
            }
        });
    }

    function saveCustomer() {
        const form = document.getElementById('customer-form');
        const formData = new FormData(form);
        const isEdit = formData.get('id') !== '';
        $('#save-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
        // Trigger native validation and abort if invalid
        if (!form.reportValidity()) {
            $('#save-btn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save');
            return;
        }
        $.ajax({ url: '/api/admin/customers?action=' + (isEdit ? 'update' : 'create'), method: 'POST', data: formData, processData: false, contentType: false })
        .done(response => {
            if (response.success) {
                customerModal.hide();
                customersTable.ajax.reload();
                alert('Customer ' + (isEdit ? 'updated' : 'created') + ' successfully');
            } else {
                alert(response.message || 'Error saving customer');
            }
        })
        .fail(xhr => alert(xhr.responseJSON?.message || 'Error saving customer'))
        .always(() => $('#save-btn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save'));
    }

    function deleteCustomer(id) {
        if (!confirm('Are you sure?')) return;
        $.post(`/api/admin/customers?action=delete&id=${id}`)
        .done(response => {
            if (response.success) {
                customersTable.ajax.reload();
                alert('Customer deleted');
            } else {
                alert(response.message || 'Error deleting customer');
            }
        });
    }

    function toggleCustomerStatus(id, currentStatus) {
        const action = currentStatus === 'active' ? 'isolir' : 'activate';
        if (!confirm(`Are you sure you want to ${action} this customer?`)) return;
        $.post(`/api/admin/customers?action=${action}&id=${id}`)
        .done(response => {
            if (response.success) {
                customersTable.ajax.reload();
                alert(`Customer ${action}d successfully`);
            } else {
                alert(response.message || `Error ${action}ing customer`);
            }
        });
    }

    function clearFilters() {
        $('#search-input').val('');
        $('#status-filter').val('');
        $('#package-filter').val('');
        customersTable.ajax.reload();
    }

    // Map helpers
    function initCustomerMap() {
        const mapEl = document.getElementById('customer-map');
        if (!mapEl) return;
        if (!customerMap) {
            customerMap = L.map('customer-map');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(customerMap);
            customerMap.on('click', function(e) {
                const { lat, lng } = e.latlng;
                $('#lat').val(lat.toFixed(6));
                $('#lon').val(lng.toFixed(6));
                setMarker(lat, lng);
            });
        }
        setTimeout(() => customerMap.invalidateSize(), 120);
    }

    function setMarker(lat, lon) {
        if (!customerMap) return;
        if (customerMarker) {
            customerMarker.setLatLng([lat, lon]);
        } else {
            customerMarker = L.marker([lat, lon], { draggable: true }).addTo(customerMap);
            customerMarker.on('dragend', function(){
                const ll = customerMarker.getLatLng();
                $('#lat').val(ll.lat.toFixed(6));
                $('#lon').val(ll.lng.toFixed(6));
            });
        }
    }

    function setMapView(lat, lon, zoom = 14) {
        if (!customerMap) return;
        customerMap.setView([lat, lon], zoom);
        setMarker(lat, lon);
    }

    function syncMapFromFields() {
        const lat = parseFloat($('#lat').val());
        const lon = parseFloat($('#lon').val());
        if (!isNaN(lat) && !isNaN(lon)) {
            setMapView(lat, lon);
        } else {
            setMapView(-6.200000, 106.816666, 12);
        }
    }
</script>
EOT;

require_once __DIR__ . '/../includes/admin_footer.php';
?>
