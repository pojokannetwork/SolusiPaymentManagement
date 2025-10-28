<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.transactions');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    case 'summary':
        handleSummary();
        break;
    case 'revenue_chart':
        handleRevenueChart();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $status = $_GET['status'] ?? '';
    $gateway = $_GET['gateway'] ?? '';
    $search = $_GET['search'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    $query = "SELECT t.*,
              f.nomor as invoice_number,
              p.nama as customer_name,
              p.kode_pelanggan,
              pg.name as gateway_name
              FROM transaksi t
              LEFT JOIN faktur f ON t.faktur_id = f.id
              LEFT JOIN pelanggan p ON f.pelanggan_id = p.id
              LEFT JOIN payment_gateways pg ON t.gateway_id = pg.id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }

    if (!empty($gateway)) {
        $query .= " AND t.gateway_id = ?";
        $params[] = $gateway;
    }

    if (!empty($search)) {
        $query .= " AND (t.external_ref LIKE ? OR f.nomor LIKE ? OR p.nama LIKE ? OR p.kode_pelanggan LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($startDate)) {
        $query .= " AND DATE(t.created_at) >= ?";
        $params[] = $startDate;
    }

    if (!empty($endDate)) {
        $query .= " AND DATE(t.created_at) <= ?";
        $params[] = $endDate;
    }

    $query .= " ORDER BY t.created_at DESC";

    $transactions = $db->fetchAll($query, $params);

    successResponse(['data' => $transactions]);
}

function handleGet() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        errorResponse('Transaction ID is required', 400);
    }

    $transaction = $db->fetchOne(
        "SELECT t.*,
         f.nomor as invoice_number,
         f.total as invoice_total,
         p.nama as customer_name,
         p.kode_pelanggan,
         p.alamat,
         p.telepon,
         p.email,
         pg.name as gateway_name,
         pg.code as gateway_code
         FROM transaksi t
         LEFT JOIN faktur f ON t.faktur_id = f.id
         LEFT JOIN pelanggan p ON f.pelanggan_id = p.id
         LEFT JOIN payment_gateways pg ON t.gateway_id = pg.id
         WHERE t.id = ?",
        [$id]
    );

    if (!$transaction) {
        errorResponse('Transaction not found', 404);
    }

    // Decode raw callback JSON if available
    if (!empty($transaction['raw_callback_json'])) {
        $transaction['callback_data'] = json_decode($transaction['raw_callback_json'], true);
    }

    successResponse(['data' => $transaction]);
}

function handleSummary() {
    global $db;

    $summary = [];

    // Count by status
    $summary['pending'] = $db->fetchOne("SELECT COUNT(*) as count FROM transaksi WHERE status = 'pending'")['count'];
    $summary['paid'] = $db->fetchOne("SELECT COUNT(*) as count FROM transaksi WHERE status = 'paid'")['count'];
    $summary['failed'] = $db->fetchOne("SELECT COUNT(*) as count FROM transaksi WHERE status = 'failed'")['count'];
    $summary['expired'] = $db->fetchOne("SELECT COUNT(*) as count FROM transaksi WHERE status = 'expired'")['count'];
    $summary['refunded'] = $db->fetchOne("SELECT COUNT(*) as count FROM transaksi WHERE status = 'refunded'")['count'];

    // Total amounts
    $summary['total_pending'] = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM transaksi WHERE status = 'pending'")['total'];
    $summary['total_paid'] = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM transaksi WHERE status = 'paid'")['total'];
    $summary['total_failed'] = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM transaksi WHERE status = 'failed'")['total'];

    // Today's revenue
    $summary['today_revenue'] = $db->fetchOne(
        "SELECT COALESCE(SUM(amount), 0) as total FROM transaksi
         WHERE status = 'paid' AND DATE(paid_at) = DATE('now')"
    )['total'];

    // This month's revenue
    $summary['month_revenue'] = $db->fetchOne(
        "SELECT COALESCE(SUM(amount), 0) as total FROM transaksi
         WHERE status = 'paid' AND strftime('%Y-%m', paid_at) = strftime('%Y-%m', 'now')"
    )['total'];

    successResponse(['data' => $summary]);
}

function handleRevenueChart() {
    global $db;

    $period = $_GET['period'] ?? 'week'; // week, month, year

    $data = [];

    switch ($period) {
        case 'week':
            // Last 7 days
            $data = $db->fetchAll(
                "SELECT DATE(paid_at) as date, COALESCE(SUM(amount), 0) as total
                 FROM transaksi
                 WHERE status = 'paid' AND paid_at >= DATE('now', '-7 days')
                 GROUP BY DATE(paid_at)
                 ORDER BY date ASC"
            );
            break;

        case 'month':
            // Last 30 days
            $data = $db->fetchAll(
                "SELECT DATE(paid_at) as date, COALESCE(SUM(amount), 0) as total
                 FROM transaksi
                 WHERE status = 'paid' AND paid_at >= DATE('now', '-30 days')
                 GROUP BY DATE(paid_at)
                 ORDER BY date ASC"
            );
            break;

        case 'year':
            // Last 12 months
            $data = $db->fetchAll(
                "SELECT strftime('%Y-%m', paid_at) as date, COALESCE(SUM(amount), 0) as total
                 FROM transaksi
                 WHERE status = 'paid' AND paid_at >= DATE('now', '-12 months')
                 GROUP BY strftime('%Y-%m', paid_at)
                 ORDER BY date ASC"
            );
            break;
    }

    successResponse(['data' => $data]);
}
