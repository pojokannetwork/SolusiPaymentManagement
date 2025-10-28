<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.assets');

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
    case 'assign':
        handleAssign();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $status = $_GET['status'] ?? '';
    $kategori = $_GET['kategori'] ?? '';
    $kondisi = $_GET['kondisi'] ?? '';
    $search = $_GET['search'] ?? '';

    $query = "SELECT a.*, u.nama as assigned_to_name
              FROM aset a
              LEFT JOIN karyawan k ON a.assigned_to = k.id
              LEFT JOIN pengguna u ON k.user_id = u.id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND a.status = ?";
        $params[] = $status;
    }

    if (!empty($kategori)) {
        $query .= " AND a.kategori = ?";
        $params[] = $kategori;
    }

    if (!empty($kondisi)) {
        $query .= " AND a.kondisi = ?";
        $params[] = $kondisi;
    }

    if (!empty($search)) {
        $query .= " AND (a.kode LIKE ? OR a.nama LIKE ? OR a.lokasi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY a.created_at DESC";

    $assets = $db->fetchAll($query, $params);
    successResponse(['data' => $assets]);
}

function handleGet() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        errorResponse('Asset ID is required', 400);
    }

    $asset = $db->fetchOne(
        "SELECT a.*, u.nama as assigned_to_name, k.nip
         FROM aset a
         LEFT JOIN karyawan k ON a.assigned_to = k.id
         LEFT JOIN pengguna u ON k.user_id = u.id
         WHERE a.id = ?",
        [$id]
    );

    if (!$asset) {
        errorResponse('Asset not found', 404);
    }

    successResponse(['data' => $asset]);
}

function handleCreate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['kode']) || empty($data['nama'])) {
        errorResponse('Asset code and name are required', 400);
    }

    try {
        $assetId = $db->execute(
            "INSERT INTO aset (kode, nama, kategori, nilai_perolehan, tgl_beli, kondisi, status, lokasi, umur_ekonomis_bulan, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $data['kode'],
                $data['nama'],
                $data['kategori'] ?? null,
                $data['nilai_perolehan'] ?? 0,
                $data['tgl_beli'] ?? date('Y-m-d'),
                $data['kondisi'] ?? 'baik',
                $data['status'] ?? 'tersedia',
                $data['lokasi'] ?? null,
                $data['umur_ekonomis_bulan'] ?? 60
            ]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('asset_create', ['asset_id' => $assetId, 'kode' => $data['kode']]);
        }

        successResponse(['message' => 'Asset created successfully', 'asset_id' => $assetId]);

    } catch (Exception $e) {
        errorResponse('Failed to create asset: ' . $e->getMessage(), 500);
    }
}

function handleUpdate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Asset ID is required', 400);
    }

    $asset = $db->fetchOne("SELECT * FROM aset WHERE id = ?", [$id]);
    if (!$asset) {
        errorResponse('Asset not found', 404);
    }

    try {
        $db->execute(
            "UPDATE aset SET kode = ?, nama = ?, kategori = ?, nilai_perolehan = ?, tgl_beli = ?, kondisi = ?, status = ?, lokasi = ?, umur_ekonomis_bulan = ?, updated_at = datetime('now')
             WHERE id = ?",
            [
                $data['kode'] ?? $asset['kode'],
                $data['nama'] ?? $asset['nama'],
                $data['kategori'] ?? $asset['kategori'],
                $data['nilai_perolehan'] ?? $asset['nilai_perolehan'],
                $data['tgl_beli'] ?? $asset['tgl_beli'],
                $data['kondisi'] ?? $asset['kondisi'],
                $data['status'] ?? $asset['status'],
                $data['lokasi'] ?? $asset['lokasi'],
                $data['umur_ekonomis_bulan'] ?? $asset['umur_ekonomis_bulan'],
                $id
            ]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('asset_update', ['asset_id' => $id]);
        }

        successResponse(['message' => 'Asset updated successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to update asset: ' . $e->getMessage(), 500);
    }
}

function handleDelete() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Asset ID is required', 400);
    }

    $asset = $db->fetchOne("SELECT * FROM aset WHERE id = ?", [$id]);
    if (!$asset) {
        errorResponse('Asset not found', 404);
    }

    try {
        $db->execute("DELETE FROM aset WHERE id = ?", [$id]);

        global $logger;
        if (isset($logger)) {
            $logger->log('asset_delete', ['asset_id' => $id, 'kode' => $asset['kode']]);
        }

        successResponse(['message' => 'Asset deleted successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to delete asset: ' . $e->getMessage(), 500);
    }
}

function handleSummary() {
    global $db;

    $summary = [];

    $summary['total'] = $db->fetchOne("SELECT COUNT(*) as count FROM aset")['count'];
    $summary['tersedia'] = $db->fetchOne("SELECT COUNT(*) as count FROM aset WHERE status = 'tersedia'")['count'];
    $summary['digunakan'] = $db->fetchOne("SELECT COUNT(*) as count FROM aset WHERE status = 'digunakan'")['count'];
    $summary['rusak'] = $db->fetchOne("SELECT COUNT(*) as count FROM aset WHERE status = 'rusak'")['count'];
    $summary['maintenance'] = $db->fetchOne("SELECT COUNT(*) as count FROM aset WHERE status = 'maintenance'")['count'];
    $summary['total_value'] = $db->fetchOne("SELECT COALESCE(SUM(nilai_perolehan), 0) as total FROM aset")['total'];

    // Category breakdown
    $categories = $db->fetchAll("SELECT kategori, COUNT(*) as count, COALESCE(SUM(nilai_perolehan), 0) as total_value FROM aset WHERE kategori IS NOT NULL GROUP BY kategori");
    $summary['categories'] = $categories;

    successResponse(['data' => $summary]);
}

function handleAssign() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $employeeId = $data['employee_id'] ?? null;

    if (!$id) {
        errorResponse('Asset ID is required', 400);
    }

    $asset = $db->fetchOne("SELECT * FROM aset WHERE id = ?", [$id]);
    if (!$asset) {
        errorResponse('Asset not found', 404);
    }

    try {
        if ($employeeId) {
            // Assign to employee
            $employee = $db->fetchOne("SELECT * FROM karyawan WHERE id = ?", [$employeeId]);
            if (!$employee) {
                errorResponse('Employee not found', 404);
            }

            $db->execute(
                "UPDATE aset SET assigned_to = ?, status = 'digunakan', updated_at = datetime('now') WHERE id = ?",
                [$employeeId, $id]
            );

            $message = 'Asset assigned successfully';
        } else {
            // Unassign
            $db->execute(
                "UPDATE aset SET assigned_to = NULL, status = 'tersedia', updated_at = datetime('now') WHERE id = ?",
                [$id]
            );

            $message = 'Asset unassigned successfully';
        }

        global $logger;
        if (isset($logger)) {
            $logger->log('asset_assign', ['asset_id' => $id, 'employee_id' => $employeeId]);
        }

        successResponse(['message' => $message]);

    } catch (Exception $e) {
        errorResponse('Failed to assign asset: ' . $e->getMessage(), 500);
    }
}
