<?php
$page_title = 'Fiber Optic Map';
require_once __DIR__ . '/../includes/admin_header.php';

// Check permissions
$guard->requirePermission('admin.assets');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-map-marked-alt me-2 text-primary"></i>Fiber Optic Map</h2>
        <p class="text-muted mb-0">Visualisasi lokasi joint closures dan infrastruktur fiber optic</p>
    </div>
    <div class="btn-group">
        <a href="/admin/fiber_dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-chart-line me-2"></i>Dashboard
        </a>
        <a href="/admin/fiber_management.php" class="btn btn-outline-primary">
            <i class="fas fa-cog me-2"></i>Management
        </a>
        <button class="btn btn-primary" onclick="refreshMap()">
            <i class="fas fa-sync me-2"></i>Refresh
        </button>
    </div>
</div>

<!-- Map Container -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="fas fa-globe me-2"></i>Interactive Map</h6>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="toggleMapType('roadmap')">Road</button>
                    <button class="btn btn-outline-secondary" onclick="toggleMapType('satellite')">Satellite</button>
                    <button class="btn btn-outline-secondary" onclick="toggleMapType('hybrid')">Hybrid</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="fiber-map" style="height: 600px; width: 100%;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Map Legend -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Map Legend</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="legend-marker bg-primary rounded-circle me-3" style="width: 12px; height: 12px;"></div>
                    <span>Main Distribution Points</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="legend-marker bg-success rounded-circle me-3" style="width: 12px; height: 12px;"></div>
                    <span>Secondary Distribution</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="legend-marker bg-warning rounded-circle me-3" style="width: 12px; height: 12px;"></div>
                    <span>Backup Points</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-marker bg-danger rounded-circle me-3" style="width: 12px; height: 12px;"></div>
                    <span>Emergency/Maintenance</span>
                </div>
            </div>
        </div>

        <!-- Selected Closure Info -->
        <div class="card border-0 shadow-sm" id="closure-info" style="display: none;">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Closure Information</h6>
            </div>
            <div class="card-body" id="closure-details">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm" id="map-stats">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Map Statistics</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Closures:</span>
                    <span class="fw-bold" id="total-closures">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>With GPS Coordinates:</span>
                    <span class="fw-bold" id="gps-closures">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Connections:</span>
                    <span class="fw-bold" id="total-connections">-</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Coverage Area:</span>
                    <span class="fw-bold" id="coverage-area">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Google Maps API -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBGne_c7W8TQ7mfNjCjQCgGjtQ1MfJHHvQ&callback=initMap&libraries=geometry"></script>

<script>
let map;
let markers = [];
let infoWindow;
let closures = [];

function initMap() {
    // Default to Jakarta center
    const defaultCenter = { lat: -6.2088, lng: 106.8456 };
    
    map = new google.maps.Map(document.getElementById('fiber-map'), {
        zoom: 11,
        center: defaultCenter,
        mapTypeId: 'roadmap',
        streetViewControl: true,
        fullscreenControl: true,
        mapTypeControl: true,
        zoomControl: true,
        scaleControl: true
    });
    
    infoWindow = new google.maps.InfoWindow();
    
    loadFiberClosures();
}

function loadFiberClosures() {
    fetch('/api/admin/fiber_map.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closures = data.closures;
                displayMarkersOnMap();
                updateMapStats();
            } else {
                console.error('Failed to load closures:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading closures:', error);
        });
}

function displayMarkersOnMap() {
    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    markers = [];
    
    if (closures.length === 0) return;
    
    const bounds = new google.maps.LatLngBounds();
    
    closures.forEach(closure => {
        const position = { lat: closure.lat, lng: closure.lng };
        
        // Determine marker color based on label
        let markerColor = '#007bff'; // Default blue
        if (closure.label) {
            switch (closure.label.toLowerCase()) {
                case 'main distribution':
                    markerColor = '#007bff'; // Blue
                    break;
                case 'secondary':
                    markerColor = '#28a745'; // Green
                    break;
                case 'backup':
                    markerColor = '#ffc107'; // Yellow
                    break;
                case 'emergency':
                case 'maintenance':
                    markerColor = '#dc3545'; // Red
                    break;
            }
        }
        
        const marker = new google.maps.Marker({
            position: position,
            map: map,
            title: closure.name,
            icon: {
                url: `https://maps.google.com/mapfiles/ms/icons/${getMarkerIcon(closure.label)}.png`,
                scaledSize: new google.maps.Size(32, 32)
            }
        });
        
        marker.addListener('click', () => {
            showClosureInfo(closure);
            map.panTo(position);
        });
        
        markers.push(marker);
        bounds.extend(position);
    });
    
    // Fit map to show all markers
    if (closures.length > 0) {
        map.fitBounds(bounds);
        if (closures.length === 1) {
            map.setZoom(15);
        }
    }
}

function getMarkerIcon(label) {
    if (!label) return 'blue';
    
    switch (label.toLowerCase()) {
        case 'main distribution': return 'blue';
        case 'secondary': return 'green';
        case 'backup': return 'yellow';
        case 'emergency':
        case 'maintenance': return 'red';
        default: return 'blue';
    }
}

function showClosureInfo(closure) {
    // Show info window on map
    const infoContent = `
        <div style="min-width: 200px;">
            <h6 class="fw-bold mb-2">${closure.name}</h6>
            <p class="mb-1"><small><strong>Address:</strong> ${closure.address}</small></p>
            <p class="mb-1"><small><strong>Label:</strong> ${closure.label || 'No label'}</small></p>
            <p class="mb-1"><small><strong>Connections:</strong> ${closure.connections}</small></p>
            <div class="mt-2">
                <button class="btn btn-sm btn-primary" onclick="showClosureDetails(${closure.id})">
                    View Details
                </button>
            </div>
        </div>
    `;
    
    infoWindow.setContent(infoContent);
    infoWindow.setPosition({ lat: closure.lat, lng: closure.lng });
    infoWindow.open(map);
    
    // Load detailed info in sidebar
    loadClosureDetails(closure.id);
}

function loadClosureDetails(closureId) {
    fetch(`/api/admin/fiber_map.php?action=details&id=${closureId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayClosureDetails(data);
            }
        })
        .catch(error => {
            console.error('Error loading closure details:', error);
        });
}

function displayClosureDetails(data) {
    const closure = data.closure;
    const stats = data.stats;
    const connections = data.recent_connections;
    
    const detailsHTML = `
        <div class="closure-details">
            <h6 class="fw-bold mb-3">${closure.name}</h6>
            
            <div class="mb-3">
                <small class="text-muted">Address:</small>
                <div>${closure.address || 'No address specified'}</div>
            </div>
            
            <div class="mb-3">
                <small class="text-muted">Coordinates:</small>
                <div>${closure.latitude}, ${closure.longitude}</div>
            </div>
            
            ${closure.label ? `
            <div class="mb-3">
                <small class="text-muted">Label:</small>
                <div><span class="badge bg-info">${closure.label}</span></div>
            </div>
            ` : ''}
            
            <div class="mb-3">
                <small class="text-muted">Statistics:</small>
                <div class="mt-1">
                    <div class="d-flex justify-content-between">
                        <span>Total Connections:</span>
                        <span class="fw-bold">${stats.total_connections || 0}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Unique Networks:</span>
                        <span class="fw-bold">${stats.unique_networks || 0}</span>
                    </div>
                    ${stats.avg_attenuation ? `
                    <div class="d-flex justify-content-between">
                        <span>Avg Attenuation:</span>
                        <span class="fw-bold">${parseFloat(stats.avg_attenuation).toFixed(2)} dB</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            ${connections.length > 0 ? `
            <div class="mb-3">
                <small class="text-muted">Recent Connections:</small>
                <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                    ${connections.map(conn => `
                        <div class="border-bottom py-2">
                            <div class="fw-medium">${conn.network_name || 'Unnamed'}</div>
                            <small class="text-muted">
                                ${conn.source_tube_color}/${conn.source_core_color} → 
                                ${conn.dest_tube_color}/${conn.dest_core_color}
                            </small>
                        </div>
                    `).join('')}
                </div>
            </div>
            ` : ''}
            
            <div class="d-grid">
                <a href="/admin/fiber_management.php?closure=${closure.id}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit me-1"></i>Edit Closure
                </a>
            </div>
        </div>
    `;
    
    document.getElementById('closure-details').innerHTML = detailsHTML;
    document.getElementById('closure-info').style.display = 'block';
}

function updateMapStats() {
    const totalClosures = closures.length;
    const gpsClosures = closures.filter(c => c.lat && c.lng).length;
    const totalConnections = closures.reduce((sum, c) => sum + c.connections, 0);
    
    document.getElementById('total-closures').textContent = totalClosures;
    document.getElementById('gps-closures').textContent = gpsClosures;
    document.getElementById('total-connections').textContent = totalConnections;
    document.getElementById('coverage-area').textContent = 'Jakarta Area';
}

function toggleMapType(type) {
    if (map) {
        map.setMapTypeId(type);
    }
}

function refreshMap() {
    loadFiberClosures();
}

// Handle case when Google Maps fails to load
window.addEventListener('load', function() {
    setTimeout(function() {
        if (typeof google === 'undefined') {
            document.getElementById('fiber-map').innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h6>Maps Not Available</h6>
                        <p class="text-muted">Google Maps could not be loaded. Please check your internet connection.</p>
                    </div>
                </div>
            `;
        }
    }, 5000);
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>