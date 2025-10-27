<?php
$page_title = 'Customer Map';
require_once __DIR__ . '/../includes/admin_header.php';

// Check authentication and permissions
$guard->requirePermission('admin.customers_map');
?>

<style>
    #map { 
        height: 600px; 
        width: 100%; 
        border-radius: 12px;
        box-shadow: var(--card-shadow);
    }
    
    .map-controls { 
        background: white; 
        padding: 15px; 
        border-radius: 12px; 
        box-shadow: var(--card-shadow);
        margin-bottom: 1rem;
    }
    
    .customer-marker { 
        border-radius: 50%; 
        width: 20px; 
        height: 20px; 
        border: 2px solid white; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.3); 
    }
    
    .status-active { background: #198754; }
    .status-isolir { background: #dc3545; }
    .status-suspended { background: #ffc107; }
    .status-terminated { background: #6c757d; }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        margin-right: 8px;
        border: 1px solid #ddd;
    }
    
    .map-stat {
        text-align: center;
        padding: 10px;
        border-radius: 8px;
        background: rgba(79, 70, 229, 0.1);
    }
    
    @media (max-width: 768px) {
        #map {
            height: 400px;
        }
        
        .map-controls {
            padding: 10px;
        }
        
        .btn-group-responsive {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn-group-responsive .btn {
            font-size: 0.875rem;
        }
    }
</style>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<!-- Page Content -->
<div class="p-4")

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="mb-2 mb-md-0">
            <h2 class="heading-responsive mb-1">Customer Map</h2>
            <p class="text-muted mb-0">Visualisasi lokasi pelanggan dalam peta interaktif</p>
        </div>
        <div class="btn-group-responsive">
            <button class="btn btn-primary btn-responsive" onclick="refreshMap()">
                <i class="fas fa-sync-alt me-2"></i>Refresh Map
            </button>
            <button class="btn btn-outline-secondary btn-responsive" onclick="fitBounds()">
                <i class="fas fa-expand me-2"></i>Fit All
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                    <h6 class="card-title mb-1">Total Customers</h6>
                    <h4 class="text-primary mb-0" id="total-customers">-</h4>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <h6 class="card-title mb-1">Active</h6>
                    <h4 class="text-success mb-0" id="active-customers">-</h4>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <h6 class="card-title mb-1">Issues</h6>
                    <h4 class="text-warning mb-0" id="issue-customers">-</h4>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-map-marker-alt fa-2x text-info"></i>
                    </div>
                    <h6 class="card-title mb-1">Mapped</h6>
                    <h4 class="text-info mb-0" id="mapped-customers">-</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Controls -->
    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Map Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <label class="form-label">Status Filter</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="isolir">Isolir</option>
                                <option value="suspended">Suspended</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label">Package Filter</label>
                            <select class="form-select" id="package-filter">
                                <option value="">All Packages</option>
                                <!-- Packages will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search Customer</label>
                            <input type="text" class="form-control" id="search-input" placeholder="Search by name, ID, address...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear Filters">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="updateCoordinates()" title="Update Coordinates">
                                    <i class="fas fa-map-marker-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-palette me-2"></i>Legend
                    </h5>
                </div>
                <div class="card-body">
                    <div class="legend-item">
                        <div class="legend-color status-active"></div>
                        <small>Active</small>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color status-isolir"></div>
                        <small>Isolir</small>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color status-suspended"></div>
                        <small>Suspended</small>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color status-terminated"></div>
                        <small>Terminated</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Map -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-map me-2"></i>Customer Locations
            </h5>
            <div class="map-stats d-none d-md-block">
                <small class="text-muted">
                    <span id="visible-markers">0</span> markers visible
                </small>
            </div>
        </div>
        <div class="card-body p-2">
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const csrfToken = '<?php echo getCsrfToken(); ?>';

        $.ajaxSetup({
            headers: { 'X-CSRF-Token': csrfToken }
        });

        let map;
        let markers = [];
        let allCustomers = [];

        $(document).ready(function() {
            initializeMap();
            loadCustomers();
            loadPackages();
            setupEventListeners();
        });

        function initializeMap() {
            // Initialize map centered on Indonesia
            map = L.map('map').setView([-2.5489, 118.0149], 5);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            // Add scale control
            L.control.scale().addTo(map);
        }

        function loadCustomers() {
            $.get('/api/admin/customers?action=list&limit=10000&map=true')
                .done(function(response) {
                    if (response.success) {
                        allCustomers = (response.data?.customers || []).filter(customer => customer.lat && customer.lon);
                        renderMarkers(allCustomers);
                        fitBounds();
                    }
                });
        }

        function loadPackages() {
            $.get('/api/admin/customers?action=packages')
                .done(function(response) {
                    if (response.success) {
                        let options = '<option value="">All Packages</option>';
                        (response.data?.packages || []).forEach(function(pkg) {
                            options += `<option value="${pkg}">${pkg}</option>`;
                        });
                        $('#package-filter').html(options);
                    }
                });
        }

        function renderMarkers(customers) {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            customers.forEach(function(customer) {
                const marker = createCustomerMarker(customer);
                markers.push(marker);
                marker.addTo(map);
            });
        }

        function createCustomerMarker(customer) {
            const statusClass = `status-${customer.status}`;
            const icon = L.divIcon({
                className: 'customer-marker ' + statusClass,
                html: '<i class="fas fa-wifi text-white"></i>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            const marker = L.marker([customer.lat, customer.lon], { icon: icon });

            marker.bindPopup(createPopupContent(customer));
            marker.customerData = customer;

            return marker;
        }

        function createPopupContent(customer) {
            const statusBadge = getStatusBadge(customer.status);

            return `
                <div class="map-popup" style="min-width: 250px;">
                    <h6 class="mb-2">${customer.nama}</h6>
                    <p class="mb-1"><strong>Code:</strong> ${customer.kode_pelanggan}</p>
                    <p class="mb-1"><strong>Package:</strong> ${customer.paket}</p>
                    <p class="mb-1"><strong>Status:</strong> ${statusBadge}</p>
                    <p class="mb-2"><strong>Address:</strong> ${customer.alamat || 'N/A'}</p>
                    <div class="d-flex gap-1">
                        <a href="/admin/customers.php?id=${customer.id}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <button class="btn btn-sm btn-warning" onclick="toggleCustomerStatus(${customer.id}, '${customer.status}')">
                            <i class="fas fa-${customer.status === 'active' ? 'ban' : 'check'}"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success">Active</span>',
                'isolir': '<span class="badge bg-danger">Isolir</span>',
                'suspended': '<span class="badge bg-warning">Suspended</span>',
                'terminated': '<span class="badge bg-secondary">Terminated</span>'
            };
            return badges[status] || status;
        }

        function setupEventListeners() {
            $('#status-filter, #package-filter').on('change', filterMarkers);
            $('#search-input').on('input', filterMarkers);
        }

        function filterMarkers() {
            const statusFilter = $('#status-filter').val();
            const packageFilter = $('#package-filter').val();
            const searchFilter = $('#search-input').val().toLowerCase();

            let filteredCustomers = allCustomers.filter(customer => {
                if (statusFilter && customer.status !== statusFilter) return false;
                if (packageFilter && customer.paket !== packageFilter) return false;
                if (searchFilter) {
                    const searchText = (customer.nama + customer.kode_pelanggan + customer.alamat).toLowerCase();
                    if (!searchText.includes(searchFilter)) return false;
                }
                return true;
            });

            renderMarkers(filteredCustomers);
        }

        function fitBounds() {
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function refreshMap() {
            loadCustomers();
        }

        function clearFilters() {
            $('#status-filter').val('');
            $('#package-filter').val('');
            $('#search-input').val('');
            renderMarkers(allCustomers);
        }

        function updateCoordinates() {
            if (!confirm('This will attempt to update coordinates for customers without them. Continue?')) return;

            $.post('/api/admin/customers?action=update_coordinates')
                .done(function(response) {
                    if (response.success) {
                        alert(`Coordinates updated for ${response.updated} customers`);
                        refreshMap();
                    } else {
                        alert('Error updating coordinates: ' + response.error);
                    }
                });
        }

        function toggleCustomerStatus(customerId, currentStatus) {
            const action = currentStatus === 'active' ? 'isolir' : 'activate';
            const confirmMsg = `Are you sure you want to ${action} this customer?`;

            if (!confirm(confirmMsg)) return;

            $.ajax({
                url: `/api/admin/customers?action=${action}&id=${customerId}`,
                method: 'POST'
            })
            .done(function(response) {
                if (response.success) {
                    // Update marker status
                    markers.forEach(marker => {
                        if (marker.customerData.id == customerId) {
                            marker.customerData.status = action === 'isolir' ? 'isolir' : 'active';
                            marker.setIcon(createCustomerMarker(marker.customerData).getIcon());
                            marker.setPopupContent(createPopupContent(marker.customerData));
                        }
                    });
                    alert(`Customer ${action}d successfully`);
                } else {
                    alert(`Error ${action}ing customer: ` + response.message);
                }
            });
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
?><?php require_once __DIR__ . "/../includes/admin_footer.php"; ?>
