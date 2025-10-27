<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check: Ensure user is an admin and has permission
if (!hasPermission('admin.onu')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        $onus = db_query("SELECT o.*, p.nama as customer_name FROM isp_onu_devices o LEFT JOIN pelanggan p ON o.customer_id = p.id ORDER BY o.serial_number");
        echo json_encode($onus);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }
        $onu = db_query_one("SELECT * FROM isp_onu_devices WHERE id = ?", [$id]);
        echo json_encode($onu);
        break;

    case 'create':
        $serial_number = $_POST['serial_number'] ?? null;
        $model = $_POST['model'] ?? null;
        $status = $_POST['status'] ?? 'available';
        $customer_id = $_POST['customer_id'] ?: null;
        $notes = $_POST['notes'] ?? null;

        if (!$serial_number) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing serial number']);
            break;
        }

        db_exec("INSERT INTO isp_onu_devices (serial_number, model, status, customer_id, notes) VALUES (?, ?, ?, ?, ?)", [
            $serial_number, $model, $status, $customer_id, $notes
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $serial_number = $_POST['serial_number'] ?? null;
        $model = $_POST['model'] ?? null;
        $status = $_POST['status'] ?? 'available';
        $customer_id = $_POST['customer_id'] ?: null;
        $notes = $_POST['notes'] ?? null;

        if (!$id || !$serial_number) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("UPDATE isp_onu_devices SET serial_number = ?, model = ?, status = ?, customer_id = ?, notes = ? WHERE id = ?", [
            $serial_number, $model, $status, $customer_id, $notes, $id
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

        db_exec("DELETE FROM isp_onu_devices WHERE id = ?", [$id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
