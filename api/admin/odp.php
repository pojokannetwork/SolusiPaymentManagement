<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check: Ensure user is an admin and has permission
if (!hasPermission('admin.odp')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        $odps = db_query("SELECT * FROM isp_odp ORDER BY name");
        echo json_encode($odps);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }
        $odp = db_query_one("SELECT * FROM isp_odp WHERE id = ?", [$id]);
        echo json_encode($odp);
        break;

    case 'create':
        $code = $_POST['code'] ?? null;
        $name = $_POST['name'] ?? null;
        $address = $_POST['address'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $total_ports = $_POST['total_ports'] ?? null;
        $status = $_POST['status'] ?? 'active';
        $notes = $_POST['notes'] ?? null;

        if (!$code || !$name || !$total_ports) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("INSERT INTO isp_odp (code, name, address, latitude, longitude, total_ports, available_ports, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $code, $name, $address, $latitude, $longitude, $total_ports, $total_ports, $status, $notes
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $code = $_POST['code'] ?? null;
        $name = $_POST['name'] ?? null;
        $address = $_POST['address'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $total_ports = $_POST['total_ports'] ?? null;
        $status = $_POST['status'] ?? 'active';
        $notes = $_POST['notes'] ?? null;

        if (!$id || !$code || !$name || !$total_ports) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }
        
        // Recalculate available ports
        $connections = db_query_one("SELECT COUNT(*) as count FROM isp_odp_connections WHERE odp_id = ?", [$id]);
        $available_ports = $total_ports - $connections['count'];


        db_exec("UPDATE isp_odp SET code = ?, name = ?, address = ?, latitude = ?, longitude = ?, total_ports = ?, available_ports = ?, status = ?, notes = ? WHERE id = ?", [
            $code, $name, $address, $latitude, $longitude, $total_ports, $available_ports, $status, $notes, $id
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }

        db_exec("DELETE FROM isp_odp WHERE id = ?", [$id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
