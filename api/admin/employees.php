<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.employees');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    case 'create':
        handleCreate();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'summary':
        handleSummary();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $status = $_GET['status'] ?? '';
    $departemen = $_GET['departemen'] ?? '';
    $search = $_GET['search'] ?? '';

    $query = "SELECT k.*, u.nama, u.email FROM karyawan k
              LEFT JOIN pengguna u ON k.user_id = u.id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND k.status = ?";
        $params[] = $status;
    }

    if (!empty($departemen)) {
        $query .= " AND k.departemen = ?";
        $params[] = $departemen;
    }

    if (!empty($search)) {
        $query .= " AND (k.nip LIKE ? OR u.nama LIKE ? OR k.posisi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY k.created_at DESC";

    $employees = $db->fetchAll($query, $params);
    successResponse(['data' => $employees]);
}

function handleGet() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        errorResponse('Employee ID is required', 400);
    }

    $employee = $db->fetchOne(
        "SELECT k.*, u.nama, u.email, u.telepon FROM karyawan k
         LEFT JOIN pengguna u ON k.user_id = u.id
         WHERE k.id = ?",
        [$id]
    );

    if (!$employee) {
        errorResponse('Employee not found', 404);
    }

    successResponse(['data' => $employee]);
}

function handleCreate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['nama']) || empty($data['email'])) {
        errorResponse('Name and email are required', 400);
    }

    try {
        // Create user first
        $userId = $db->execute(
            "INSERT INTO pengguna (nama, email, password, telepon, created_at, updated_at)
             VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $data['nama'],
                $data['email'],
                password_hash($data['password'] ?? 'password123', PASSWORD_BCRYPT),
                $data['telepon'] ?? ''
            ]
        );

        // Create employee record
        $employeeId = $db->execute(
            "INSERT INTO karyawan (user_id, nip, departemen, posisi, gaji_pokok, tgl_masuk, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $userId,
                $data['nip'] ?? null,
                $data['departemen'] ?? null,
                $data['posisi'] ?? null,
                $data['gaji_pokok'] ?? 0,
                $data['tgl_masuk'] ?? date('Y-m-d'),
                $data['status'] ?? 'active'
            ]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('employee_create', ['employee_id' => $employeeId, 'nip' => $data['nip']]);
        }

        successResponse(['message' => 'Employee created successfully', 'employee_id' => $employeeId]);

    } catch (Exception $e) {
        errorResponse('Failed to create employee: ' . $e->getMessage(), 500);
    }
}

function handleUpdate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Employee ID is required', 400);
    }

    $employee = $db->fetchOne("SELECT * FROM karyawan WHERE id = ?", [$id]);
    if (!$employee) {
        errorResponse('Employee not found', 404);
    }

    try {
        // Update user info
        $db->execute(
            "UPDATE pengguna SET nama = ?, email = ?, telepon = ?, updated_at = datetime('now')
             WHERE id = ?",
            [
                $data['nama'],
                $data['email'],
                $data['telepon'] ?? '',
                $employee['user_id']
            ]
        );

        // Update employee info
        $db->execute(
            "UPDATE karyawan SET nip = ?, departemen = ?, posisi = ?, gaji_pokok = ?,
             tgl_masuk = ?, status = ?, updated_at = datetime('now')
             WHERE id = ?",
            [
                $data['nip'] ?? $employee['nip'],
                $data['departemen'] ?? $employee['departemen'],
                $data['posisi'] ?? $employee['posisi'],
                $data['gaji_pokok'] ?? $employee['gaji_pokok'],
                $data['tgl_masuk'] ?? $employee['tgl_masuk'],
                $data['status'] ?? $employee['status'],
                $id
            ]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('employee_update', ['employee_id' => $id]);
        }

        successResponse(['message' => 'Employee updated successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to update employee: ' . $e->getMessage(), 500);
    }
}

function handleDelete() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Employee ID is required', 400);
    }

    $employee = $db->fetchOne("SELECT * FROM karyawan WHERE id = ?", [$id]);
    if (!$employee) {
        errorResponse('Employee not found', 404);
    }

    try {
        // Delete employee (user will be deleted via CASCADE)
        $db->execute("DELETE FROM pengguna WHERE id = ?", [$employee['user_id']]);

        global $logger;
        if (isset($logger)) {
            $logger->log('employee_delete', ['employee_id' => $id]);
        }

        successResponse(['message' => 'Employee deleted successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to delete employee: ' . $e->getMessage(), 500);
    }
}

function handleSummary() {
    global $db;

    $summary = [];
    $summary['total'] = $db->fetchOne("SELECT COUNT(*) as count FROM karyawan")['count'];
    $summary['active'] = $db->fetchOne("SELECT COUNT(*) as count FROM karyawan WHERE status = 'active'")['count'];
    $summary['inactive'] = $db->fetchOne("SELECT COUNT(*) as count FROM karyawan WHERE status = 'inactive'")['count'];
    $summary['total_salary'] = $db->fetchOne("SELECT COALESCE(SUM(gaji_pokok), 0) as total FROM karyawan WHERE status = 'active'")['total'];

    // Department breakdown
    $departments = $db->fetchAll("SELECT departemen, COUNT(*) as count FROM karyawan WHERE departemen IS NOT NULL GROUP BY departemen");
    $summary['departments'] = $departments;

    successResponse(['data' => $summary]);
}
