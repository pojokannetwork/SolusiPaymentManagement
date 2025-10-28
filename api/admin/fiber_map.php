<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.assets');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleListClosures();
        break;
    case 'details':
        handleClosureDetails();
        break;
    case 'connections':
        handleClosureConnections();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleListClosures() {
    global $db;
    
    $closures = $db->fetchAll(
        "SELECT id, name, address, latitude, longitude, label, photo_path, created_at,
                (SELECT COUNT(*) FROM fiber_core_connections WHERE closure_id = fiber_joint_closures.id) as connection_count
         FROM fiber_joint_closures 
         WHERE latitude IS NOT NULL AND longitude IS NOT NULL
         ORDER BY name ASC"
    );
    
    // Add marker data for map
    $markers = [];
    foreach ($closures as $closure) {
        $markers[] = [
            'id' => $closure['id'],
            'name' => $closure['name'],
            'address' => $closure['address'] ?: 'No address specified',
            'lat' => (float)$closure['latitude'],
            'lng' => (float)$closure['longitude'],
            'label' => $closure['label'],
            'connections' => (int)$closure['connection_count'],
            'photo' => $closure['photo_path'] ? '/uploads/fiber_photos/' . basename($closure['photo_path']) : null,
            'created_at' => $closure['created_at']
        ];
    }
    
    successResponse(['closures' => $markers]);
}

function handleClosureDetails() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        errorResponse('Closure ID is required', 400);
    }
    
    $closure = $db->fetchOne(
        "SELECT * FROM fiber_joint_closures WHERE id = ?",
        [$id]
    );
    
    if (!$closure) {
        errorResponse('Closure not found', 404);
    }
    
    // Get connection statistics
    $stats = $db->fetchOne(
        "SELECT 
            COUNT(*) as total_connections,
            COUNT(DISTINCT network_name) as unique_networks,
            AVG(attenuation_after) as avg_attenuation,
            MIN(attenuation_after) as min_attenuation,
            MAX(attenuation_after) as max_attenuation
         FROM fiber_core_connections 
         WHERE closure_id = ? AND attenuation_after IS NOT NULL",
        [$id]
    );
    
    // Get recent connections
    $connections = $db->fetchAll(
        "SELECT * FROM fiber_core_connections 
         WHERE closure_id = ? 
         ORDER BY created_at DESC 
         LIMIT 10",
        [$id]
    );
    
    $result = [
        'closure' => $closure,
        'stats' => $stats,
        'recent_connections' => $connections
    ];
    
    successResponse($result);
}

function handleClosureConnections() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        errorResponse('Closure ID is required', 400);
    }
    
    $connections = $db->fetchAll(
        "SELECT * FROM fiber_core_connections 
         WHERE closure_id = ? 
         ORDER BY network_name ASC, source_tube_color ASC, source_core_color ASC",
        [$id]
    );
    
    // Group by network for better organization
    $grouped = [];
    foreach ($connections as $connection) {
        $network = $connection['network_name'] ?: 'Unnamed Network';
        if (!isset($grouped[$network])) {
            $grouped[$network] = [];
        }
        $grouped[$network][] = $connection;
    }
    
    successResponse(['connections' => $grouped]);
}