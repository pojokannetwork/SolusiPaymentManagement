<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check: Ensure user is an admin and has permission
if (!hasPermission('admin.vouchers')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        $vouchers = db_query("SELECT v.*, p.name as package_name FROM isp_voucher_pricing v JOIN isp_packages p ON v.package_id = p.id ORDER BY p.name, v.duration_hours");
        echo json_encode($vouchers);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }
        $voucher = db_query_one("SELECT * FROM isp_voucher_pricing WHERE id = ?", [$id]);
        echo json_encode($voucher);
        break;

    case 'create':
        $package_id = $_POST['package_id'] ?? null;
        $duration_hours = $_POST['duration_hours'] ?? null;
        $price = $_POST['price'] ?? null;
        $agent_price = $_POST['agent_price'] ?? null;
        $hotspot_profile = $_POST['hotspot_profile'] ?? null;
        $status = $_POST['status'] ?? 'active';

        if (!$package_id || !$duration_hours || !$price) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("INSERT INTO isp_voucher_pricing (package_id, duration_hours, price, agent_price, hotspot_profile, status) VALUES (?, ?, ?, ?, ?, ?)", [
            $package_id, $duration_hours, $price, $agent_price, $hotspot_profile, $status
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $package_id = $_POST['package_id'] ?? null;
        $duration_hours = $_POST['duration_hours'] ?? null;
        $price = $_POST['price'] ?? null;
        $agent_price = $_POST['agent_price'] ?? null;
        $hotspot_profile = $_POST['hotspot_profile'] ?? null;
        $status = $_POST['status'] ?? 'active';

        if (!$id || !$package_id || !$duration_hours || !$price) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("UPDATE isp_voucher_pricing SET package_id = ?, duration_hours = ?, price = ?, agent_price = ?, hotspot_profile = ?, status = ? WHERE id = ?", [
            $package_id, $duration_hours, $price, $agent_price, $hotspot_profile, $status, $id
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

        db_exec("DELETE FROM isp_voucher_pricing WHERE id = ?", [$id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
