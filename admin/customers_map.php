<?php
// SolusiPaymentManagement Admin Customers Map Page

require_once __DIR__ . '/../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers_map');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Map - SolusiPaymentManagement</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; background: #0d6efd; }
        .main-content { margin-left: 0; }
        @media (min-width: 768px) { .main-content { margin-left: 250px; } }
        #map { height: 600px; width: 100%; }
        .map-controls { background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .customer-marker { border-radius: 50%; width: 20px; height: 20px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        .status-active { background: #198754; }
        .status-isolir { background: #dc3545; }
        .status-suspended { background: #ffc107; }
        .status-terminated { background: #6c757d; }
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
                    <a class="nav-link active" href="customers_map.php">
                        <i class="fas fa-map-marked-alt me-2"></i>Customer Map
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
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
                <h2>Customer Map</h2>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="refreshMap()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                    <button class="btn btn-outline-secondary" onclick="fitBounds()">
                        <i class="fas fa-expand me-2"></i>Fit All
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status Filter</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="isolir">Isolir</option>
                                <option value="suspended">Suspended</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Package Filter</label>
                            <select class="form-select" id="package-filter">
                                <option value="">All Packages</option>
                                <!-- Packages will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="search-input" placeholder="Search customers...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-outline-secondary me-2" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Clear
                            </button>
                            <button class="btn btn-outline-info" onclick="updateCoordinates()">
                                <i class="fas fa-map-marker-alt me-2"></i>Update Coords
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="card">
                <div class="card-body">
                    <div id="map"></div>
                </div>
            </div>

            <!-- Legend -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6>Legend</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div class="customer-marker status-active me-2"></div>
                            <span>Active</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="customer-marker status-isolir me-2"></div>
                            <span>Isolir</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="customer-marker status-suspended me-2"></div>
                            <span>Suspended</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="customer-marker status-terminated me-2"></div>
                            <span>Terminated</span>
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
                        allCustomers = response.customers.filter(customer => customer.lat && customer.lon);
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
                        response.packages.forEach(function(pkg) {
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
</body>
</html>
