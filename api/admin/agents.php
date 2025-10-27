<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check: Ensure user is an admin and has permission
if (!hasPermission('admin.agents')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        $agents = db_query("SELECT a.*, u.nama as user_name FROM isp_agents a JOIN pengguna u ON a.user_id = u.id ORDER BY u.nama");
        echo json_encode($agents);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            break;
        }
        $agent = db_query_one("SELECT * FROM isp_agents WHERE id = ?", [$id]);
        echo json_encode($agent);
        break;

    case 'create':
        $user_id = $_POST['user_id'] ?? null;
        $balance = $_POST['balance'] ?? 0;
        $commission_rate = $_POST['commission_rate'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user ID']);
            break;
        }

        db_exec("INSERT INTO isp_agents (user_id, balance, commission_rate, status) VALUES (?, ?, ?, ?)", [
            $user_id, $balance, $commission_rate, $status
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $balance = $_POST['balance'] ?? 0;
        $commission_rate = $_POST['commission_rate'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        if (!$id || !$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        db_exec("UPDATE isp_agents SET user_id = ?, balance = ?, commission_rate = ?, status = ? WHERE id = ?", [
            $user_id, $balance, $commission_rate, $status, $id
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

        db_exec("DELETE FROM isp_agents WHERE id = ?", [$id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
