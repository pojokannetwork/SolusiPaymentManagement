<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check: Ensure user is an admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        $packages = db_query("SELECT * FROM isp_packages ORDER BY name");
        echo json_encode($packages);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }
        $package = db_query_one("SELECT * FROM isp_packages WHERE id = ?", [$id]);
        echo json_encode($package);
        break;

    case 'create':
        $code = $_POST['code'] ?? null;
        $name = $_POST['name'] ?? null;
        $speed = $_POST['speed'] ?? null;
        $price = $_POST['price'] ?? null;
        $pppoe_profile = $_POST['pppoe_profile'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$code || !$name || !$speed || !$price) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("INSERT INTO isp_packages (code, name, speed, price, pppoe_profile, is_active) VALUES (?, ?, ?, ?, ?, ?)", [
            $code, $name, $speed, $price, $pppoe_profile, $is_active
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $code = $_POST['code'] ?? null;
        $name = $_POST['name'] ?? null;
        $speed = $_POST['speed'] ?? null;
        $price = $_POST['price'] ?? null;
        $pppoe_profile = $_POST['pppoe_profile'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$id || !$code || !$name || !$speed || !$price) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("UPDATE isp_packages SET code = ?, name = ?, speed = ?, price = ?, pppoe_profile = ?, is_active = ? WHERE id = ?", [
            $code, $name, $speed, $price, $pppoe_profile, $is_active, $id
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

        db_exec("DELETE FROM isp_packages WHERE id = ?", [$id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
