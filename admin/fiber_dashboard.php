<?php
$page_title = 'Fiber Optic Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

// Check permissions
$guard->requirePermission('admin.assets');

// Get fiber optic statistics
global $db;

$stats = [
    'total_closures' => $db->fetchOne("SELECT COUNT(*) as count FROM fiber_joint_closures")['count'] ?? 0,
    'total_connections' => $db->fetchOne("SELECT COUNT(*) as count FROM fiber_core_connections")['count'] ?? 0,
    'active_networks' => $db->fetchOne("SELECT COUNT(DISTINCT network_name) as count FROM fiber_core_connections WHERE network_name IS NOT NULL AND network_name != ''")['count'] ?? 0,
    'avg_attenuation' => $db->fetchOne("SELECT AVG(attenuation_after) as avg FROM fiber_core_connections WHERE attenuation_after IS NOT NULL")['avg'] ?? 0
];

// Recent closures
$recent_closures = $db->fetchAll(
    "SELECT * FROM fiber_joint_closures ORDER BY created_at DESC LIMIT 5"
);

// Network utilization
$network_usage = $db->fetchAll(
    "SELECT network_name, COUNT(*) as connections, 
            AVG(attenuation_after) as avg_attenuation
     FROM fiber_core_connections 
     WHERE network_name IS NOT NULL AND network_name != ''
     GROUP BY network_name 
     ORDER BY connections DESC LIMIT 10"
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-sitemap me-2 text-primary"></i>Fiber Optic Dashboard</h2>
        <p class="text-muted mb-0">Overview dan monitoring infrastruktur fiber optic</p>
    </div>
    <div class="btn-group">
        <a href="/admin/fiber_management.php" class="btn btn-outline-primary">
            <i class="fas fa-cog me-2"></i>Management
        </a>
        <button class="btn btn-primary" onclick="window.location.reload()">
            <i class="fas fa-sync me-2"></i>Refresh
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; background: linear-gradient(45deg, #667eea, #764ba2);">
                            <i class="fas fa-project-diagram text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Joint Closures</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $stats['total_closures']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; background: linear-gradient(45deg, #f093fb, #f5576c);">
                            <i class="fas fa-link text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Core Connections</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $stats['total_connections']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; background: linear-gradient(45deg, #4facfe, #00f2fe);">
                            <i class="fas fa-network-wired text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Active Networks</div>
                        <div class="h4 mb-0 fw-bold"><?php echo $stats['active_networks']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; background: linear-gradient(45deg, #43e97b, #38f9d7);">
                            <i class="fas fa-signal text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Avg Attenuation</div>
                        <div class="h4 mb-0 fw-bold"><?php echo number_format($stats['avg_attenuation'], 2); ?> dB</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Joint Closures -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Recent Joint Closures</h6>
                <a href="/admin/fiber_management.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_closures)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada joint closures</p>
                    <a href="/admin/fiber_management.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Joint Closure
                    </a>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_closures as $closure): ?>
                    <div class="list-group-item border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-project-diagram text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($closure['name']); ?></h6>
                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($closure['address'] ?: 'No address'); ?></p>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($closure['label']): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info"><?php echo htmlspecialchars($closure['label']); ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($closure['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Network Utilization -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Network Utilization</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($network_usage)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada data network</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 fw-semibold">Network Name</th>
                                <th class="border-0 fw-semibold text-center">Connections</th>
                                <th class="border-0 fw-semibold text-center">Avg Attenuation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($network_usage as $network): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-network-wired text-primary me-2"></i>
                                        <span class="fw-medium"><?php echo htmlspecialchars($network['network_name']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $network['connections']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $attenuation = (float)$network['avg_attenuation'];
                                    $class = $attenuation <= 0.3 ? 'success' : ($attenuation <= 0.5 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>"><?php echo number_format($attenuation, 2); ?> dB</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tools me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="/admin/fiber_management.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-plus fa-2x mb-2"></i>
                            <span class="fw-medium">Add Joint Closure</span>
                            <small class="text-muted">Create new closure point</small>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/fiber_management.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-link fa-2x mb-2"></i>
                            <span class="fw-medium">Manage Connections</span>
                            <small class="text-muted">View & edit core connections</small>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/fiber_management.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                            <span class="fw-medium">View Map</span>
                            <small class="text-muted">Interactive fiber map</small>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/fiber_management.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <span class="fw-medium">Reports</span>
                            <small class="text-muted">Generate reports</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>