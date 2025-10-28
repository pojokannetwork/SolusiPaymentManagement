<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.payroll');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    case 'generate':
        handleGenerate();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'mark_paid':
        handleMarkPaid();
        break;
    case 'summary':
        handleSummary();
        break;
    case 'employees_for_period':
        handleEmployeesForPeriod();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $status = $_GET['status'] ?? '';
    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');

    $query = "SELECT p.*, k.nip, u.nama as employee_name, k.departemen, k.posisi
              FROM payroll p
              LEFT JOIN karyawan k ON p.karyawan_id = k.id
              LEFT JOIN pengguna u ON k.user_id = u.id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }

    if (!empty($month)) {
        $query .= " AND p.periode_bulan = ?";
        $params[] = $month;
    }

    if (!empty($year)) {
        $query .= " AND p.periode_tahun = ?";
        $params[] = $year;
    }

    $query .= " ORDER BY p.created_at DESC";

    $payrolls = $db->fetchAll($query, $params);
    successResponse(['data' => $payrolls]);
}

function handleGet() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        errorResponse('Payroll ID is required', 400);
    }

    $payroll = $db->fetchOne(
        "SELECT p.*, k.nip, u.nama as employee_name, k.departemen, k.posisi, u.email, u.telepon
         FROM payroll p
         LEFT JOIN karyawan k ON p.karyawan_id = k.id
         LEFT JOIN pengguna u ON k.user_id = u.id
         WHERE p.id = ?",
        [$id]
    );

    if (!$payroll) {
        errorResponse('Payroll not found', 404);
    }

    successResponse(['data' => $payroll]);
}

function handleGenerate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $month = $data['month'] ?? date('n');
    $year = $data['year'] ?? date('Y');

    try {
        // Get all active employees
        $employees = $db->fetchAll(
            "SELECT k.*, u.nama FROM karyawan k
             LEFT JOIN pengguna u ON k.user_id = u.id
             WHERE k.status = 'active'"
        );

        $generatedCount = 0;

        foreach ($employees as $emp) {
            // Check if payroll already exists
            $existing = $db->fetchOne(
                "SELECT id FROM payroll
                 WHERE karyawan_id = ? AND periode_bulan = ? AND periode_tahun = ?",
                [$emp['id'], $month, $year]
            );

            if ($existing) continue;

            // Calculate total
            $gajiPokok = $emp['gaji_pokok'] ?? 0;
            $lembur = 0; // Can be calculated from attendance
            $bonus = 0;
            $potongan = 0;
            $pajak = $gajiPokok * 0.05; // 5% tax
            $totalBayar = $gajiPokok + $lembur + $bonus - $potongan - $pajak;

            // Create payroll
            $db->execute(
                "INSERT INTO payroll (karyawan_id, periode_bulan, periode_tahun, gaji_pokok, lembur, bonus, potongan, pajak, total_bayar, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', datetime('now'), datetime('now'))",
                [$emp['id'], $month, $year, $gajiPokok, $lembur, $bonus, $potongan, $pajak, $totalBayar]
            );

            $generatedCount++;
        }

        global $logger;
        if (isset($logger)) {
            $logger->log('payroll_generate', ['month' => $month, 'year' => $year, 'count' => $generatedCount]);
        }

        successResponse([
            'message' => 'Payroll generated successfully',
            'count' => $generatedCount
        ]);

    } catch (Exception $e) {
        errorResponse('Failed to generate payroll: ' . $e->getMessage(), 500);
    }
}

function handleUpdate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Payroll ID is required', 400);
    }

    $payroll = $db->fetchOne("SELECT * FROM payroll WHERE id = ?", [$id]);
    if (!$payroll) {
        errorResponse('Payroll not found', 404);
    }

    if ($payroll['status'] === 'paid') {
        errorResponse('Cannot edit paid payroll', 400);
    }

    try {
        $gajiPokok = $data['gaji_pokok'] ?? $payroll['gaji_pokok'];
        $lembur = $data['lembur'] ?? $payroll['lembur'];
        $bonus = $data['bonus'] ?? $payroll['bonus'];
        $potongan = $data['potongan'] ?? $payroll['potongan'];
        $pajak = $data['pajak'] ?? $payroll['pajak'];
        $totalBayar = $gajiPokok + $lembur + $bonus - $potongan - $pajak;

        $db->execute(
            "UPDATE payroll SET gaji_pokok = ?, lembur = ?, bonus = ?, potongan = ?, pajak = ?, total_bayar = ?, status = ?, updated_at = datetime('now')
             WHERE id = ?",
            [$gajiPokok, $lembur, $bonus, $potongan, $pajak, $totalBayar, $data['status'] ?? $payroll['status'], $id]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('payroll_update', ['payroll_id' => $id]);
        }

        successResponse(['message' => 'Payroll updated successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to update payroll: ' . $e->getMessage(), 500);
    }
}

function handleDelete() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Payroll ID is required', 400);
    }

    $payroll = $db->fetchOne("SELECT * FROM payroll WHERE id = ?", [$id]);
    if (!$payroll) {
        errorResponse('Payroll not found', 404);
    }

    if ($payroll['status'] === 'paid') {
        errorResponse('Cannot delete paid payroll', 400);
    }

    try {
        $db->execute("DELETE FROM payroll WHERE id = ?", [$id]);

        global $logger;
        if (isset($logger)) {
            $logger->log('payroll_delete', ['payroll_id' => $id]);
        }

        successResponse(['message' => 'Payroll deleted successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to delete payroll: ' . $e->getMessage(), 500);
    }
}

function handleMarkPaid() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Payroll ID is required', 400);
    }

    $payroll = $db->fetchOne("SELECT * FROM payroll WHERE id = ?", [$id]);
    if (!$payroll) {
        errorResponse('Payroll not found', 404);
    }

    if ($payroll['status'] === 'paid') {
        errorResponse('Payroll is already paid', 400);
    }

    try {
        $db->execute(
            "UPDATE payroll SET status = 'paid', paid_at = datetime('now'), metode = ?, updated_at = datetime('now')
             WHERE id = ?",
            [$data['metode'] ?? 'transfer', $id]
        );

        global $logger;
        if (isset($logger)) {
            $logger->log('payroll_paid', ['payroll_id' => $id]);
        }

        successResponse(['message' => 'Payroll marked as paid']);

    } catch (Exception $e) {
        errorResponse('Failed to mark payroll as paid: ' . $e->getMessage(), 500);
    }
}

function handleSummary() {
    global $db;

    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');

    $summary = [];

    // Count by status
    $summary['total'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM payroll WHERE periode_bulan = ? AND periode_tahun = ?",
        [$month, $year]
    )['count'];

    $summary['draft'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM payroll WHERE periode_bulan = ? AND periode_tahun = ? AND status = 'draft'",
        [$month, $year]
    )['count'];

    $summary['approved'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM payroll WHERE periode_bulan = ? AND periode_tahun = ? AND status = 'approved'",
        [$month, $year]
    )['count'];

    $summary['paid'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM payroll WHERE periode_bulan = ? AND periode_tahun = ? AND status = 'paid'",
        [$month, $year]
    )['count'];

    // Total amounts
    $summary['total_amount'] = $db->fetchOne(
        "SELECT COALESCE(SUM(total_bayar), 0) as total FROM payroll WHERE periode_bulan = ? AND periode_tahun = ?",
        [$month, $year]
    )['total'];

    $summary['paid_amount'] = $db->fetchOne(
        "SELECT COALESCE(SUM(total_bayar), 0) as total FROM payroll WHERE periode_bulan = ? AND periode_tahun = ? AND status = 'paid'",
        [$month, $year]
    )['total'];

    $summary['pending_amount'] = $db->fetchOne(
        "SELECT COALESCE(SUM(total_bayar), 0) as total FROM payroll WHERE periode_bulan = ? AND periode_tahun = ? AND status IN ('draft', 'approved')",
        [$month, $year]
    )['total'];

    successResponse(['data' => $summary]);
}

function handleEmployeesForPeriod() {
    global $db;

    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');

    // Get employees who don't have payroll for this period
    $employees = $db->fetchAll(
        "SELECT k.id, k.nip, u.nama, k.departemen, k.posisi, k.gaji_pokok
         FROM karyawan k
         LEFT JOIN pengguna u ON k.user_id = u.id
         LEFT JOIN payroll p ON k.id = p.karyawan_id AND p.periode_bulan = ? AND p.periode_tahun = ?
         WHERE k.status = 'active' AND p.id IS NULL",
        [$month, $year]
    );

    successResponse(['data' => $employees]);
}
