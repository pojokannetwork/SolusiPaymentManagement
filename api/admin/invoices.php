<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.invoices');

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
    case 'send':
        handleSend();
        break;
    case 'mark_paid':
        handleMarkPaid();
        break;
    case 'cancel':
        handleCancel();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;

    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    $query = "SELECT f.*, p.nama as customer_name, p.kode_pelanggan
              FROM faktur f
              LEFT JOIN pelanggan p ON f.pelanggan_id = p.id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
        $query .= " AND f.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $query .= " AND (f.nomor LIKE ? OR p.nama LIKE ? OR p.kode_pelanggan LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($startDate)) {
        $query .= " AND f.tanggal >= ?";
        $params[] = $startDate;
    }

    if (!empty($endDate)) {
        $query .= " AND f.tanggal <= ?";
        $params[] = $endDate;
    }

    $query .= " ORDER BY f.created_at DESC";

    $invoices = $db->fetchAll($query, $params);

    successResponse(['data' => $invoices]);
}

function handleGet() {
    global $db;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    $invoice = $db->fetchOne(
        "SELECT f.*, p.nama as customer_name, p.kode_pelanggan, p.alamat, p.telepon, p.email
         FROM faktur f
         LEFT JOIN pelanggan p ON f.pelanggan_id = p.id
         WHERE f.id = ?",
        [$id]
    );

    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    // Get invoice items
    $items = $db->fetchAll(
        "SELECT * FROM invoice_items WHERE faktur_id = ?",
        [$id]
    );

    $invoice['items'] = $items;

    successResponse(['data' => $invoice]);
}

function handleCreate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($data['pelanggan_id'])) {
        errorResponse('Customer ID is required', 400);
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        errorResponse('Invoice items are required', 400);
    }

    try {
        // Calculate totals
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $itemSubtotal = ($item['qty'] ?? 1) * ($item['harga'] ?? 0);
            $subtotal += $itemSubtotal;
        }

        $tax = $data['pajak'] ?? 0;
        $total = $subtotal + $tax;

        // Generate invoice number
        $lastInvoice = $db->fetchOne("SELECT MAX(id) as max_id FROM faktur");
        $nextId = ($lastInvoice['max_id'] ?? 0) + 1;
        $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // Create invoice
        $invoiceId = $db->execute(
            "INSERT INTO faktur (pelanggan_id, nomor, tanggal, jatuh_tempo, subtotal, pajak, total, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $data['pelanggan_id'],
                $invoiceNumber,
                $data['tanggal'] ?? date('Y-m-d'),
                $data['jatuh_tempo'] ?? date('Y-m-d', strtotime('+30 days')),
                $subtotal,
                $tax,
                $total,
                $data['status'] ?? 'draft'
            ]
        );

        // Create invoice items
        foreach ($data['items'] as $item) {
            $itemSubtotal = ($item['qty'] ?? 1) * ($item['harga'] ?? 0);
            $db->execute(
                "INSERT INTO invoice_items (faktur_id, deskripsi, qty, harga, subtotal)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $invoiceId,
                    $item['deskripsi'],
                    $item['qty'] ?? 1,
                    $item['harga'] ?? 0,
                    $itemSubtotal
                ]
            );
        }

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_create', [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'customer_id' => $data['pelanggan_id'],
                'total' => $total
            ]);
        }

        successResponse([
            'message' => 'Invoice created successfully',
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber
        ]);

    } catch (Exception $e) {
        errorResponse('Failed to create invoice: ' . $e->getMessage(), 500);
    }
}

function handleUpdate() {
    global $db;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    // Check if invoice exists
    $invoice = $db->fetchOne("SELECT * FROM faktur WHERE id = ?", [$id]);
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    // Don't allow editing paid invoices
    if ($invoice['status'] === 'paid') {
        errorResponse('Cannot edit paid invoice', 400);
    }

    try {
        // Calculate totals if items provided
        if (!empty($data['items']) && is_array($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemSubtotal = ($item['qty'] ?? 1) * ($item['harga'] ?? 0);
                $subtotal += $itemSubtotal;
            }

            $tax = $data['pajak'] ?? $invoice['pajak'];
            $total = $subtotal + $tax;

            // Delete old items
            $db->execute("DELETE FROM invoice_items WHERE faktur_id = ?", [$id]);

            // Create new items
            foreach ($data['items'] as $item) {
                $itemSubtotal = ($item['qty'] ?? 1) * ($item['harga'] ?? 0);
                $db->execute(
                    "INSERT INTO invoice_items (faktur_id, deskripsi, qty, harga, subtotal)
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $id,
                        $item['deskripsi'],
                        $item['qty'] ?? 1,
                        $item['harga'] ?? 0,
                        $itemSubtotal
                    ]
                );
            }
        } else {
            $subtotal = $invoice['subtotal'];
            $tax = $data['pajak'] ?? $invoice['pajak'];
            $total = $subtotal + $tax;
        }

        // Update invoice
        $db->execute(
            "UPDATE faktur SET jatuh_tempo = ?, subtotal = ?, pajak = ?, total = ?, status = ?, updated_at = datetime('now')
             WHERE id = ?",
            [
                $data['jatuh_tempo'] ?? $invoice['jatuh_tempo'],
                $subtotal,
                $tax,
                $total,
                $data['status'] ?? $invoice['status'],
                $id
            ]
        );

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_update', [
                'invoice_id' => $id,
                'invoice_number' => $invoice['nomor']
            ]);
        }

        successResponse(['message' => 'Invoice updated successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to update invoice: ' . $e->getMessage(), 500);
    }
}

function handleDelete() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    // Check if invoice exists
    $invoice = $db->fetchOne("SELECT * FROM faktur WHERE id = ?", [$id]);
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    // Don't allow deleting paid invoices
    if ($invoice['status'] === 'paid') {
        errorResponse('Cannot delete paid invoice', 400);
    }

    try {
        // Delete invoice (items will be deleted automatically via CASCADE)
        $db->execute("DELETE FROM faktur WHERE id = ?", [$id]);

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_delete', [
                'invoice_id' => $id,
                'invoice_number' => $invoice['nomor']
            ]);
        }

        successResponse(['message' => 'Invoice deleted successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to delete invoice: ' . $e->getMessage(), 500);
    }
}

function handleSummary() {
    global $db;

    $summary = [];

    // Count by status
    $summary['draft'] = $db->fetchOne("SELECT COUNT(*) as count FROM faktur WHERE status = 'draft'")['count'];
    $summary['sent'] = $db->fetchOne("SELECT COUNT(*) as count FROM faktur WHERE status = 'sent'")['count'];
    $summary['paid'] = $db->fetchOne("SELECT COUNT(*) as count FROM faktur WHERE status = 'paid'")['count'];
    $summary['overdue'] = $db->fetchOne("SELECT COUNT(*) as count FROM faktur WHERE status = 'overdue'")['count'];
    $summary['cancelled'] = $db->fetchOne("SELECT COUNT(*) as count FROM faktur WHERE status = 'cancelled'")['count'];

    // Total amounts
    $summary['total_draft'] = $db->fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM faktur WHERE status = 'draft'")['total'];
    $summary['total_sent'] = $db->fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM faktur WHERE status = 'sent'")['total'];
    $summary['total_paid'] = $db->fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM faktur WHERE status = 'paid'")['total'];
    $summary['total_overdue'] = $db->fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM faktur WHERE status = 'overdue'")['total'];

    successResponse(['data' => $summary]);
}

function handleSend() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    // Check if invoice exists
    $invoice = $db->fetchOne("SELECT * FROM faktur WHERE id = ?", [$id]);
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    if ($invoice['status'] !== 'draft') {
        errorResponse('Only draft invoices can be sent', 400);
    }

    try {
        // Update status to sent
        $db->execute("UPDATE faktur SET status = 'sent', updated_at = datetime('now') WHERE id = ?", [$id]);

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_send', [
                'invoice_id' => $id,
                'invoice_number' => $invoice['nomor']
            ]);
        }

        // TODO: Send notification to customer (WhatsApp, email, etc.)

        successResponse(['message' => 'Invoice sent successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to send invoice: ' . $e->getMessage(), 500);
    }
}

function handleMarkPaid() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    // Check if invoice exists
    $invoice = $db->fetchOne("SELECT * FROM faktur WHERE id = ?", [$id]);
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    if ($invoice['status'] === 'paid') {
        errorResponse('Invoice is already paid', 400);
    }

    try {
        // Update status to paid
        $db->execute("UPDATE faktur SET status = 'paid', updated_at = datetime('now') WHERE id = ?", [$id]);

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_mark_paid', [
                'invoice_id' => $id,
                'invoice_number' => $invoice['nomor']
            ]);
        }

        successResponse(['message' => 'Invoice marked as paid']);

    } catch (Exception $e) {
        errorResponse('Failed to mark invoice as paid: ' . $e->getMessage(), 500);
    }
}

function handleCancel() {
    global $db;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        errorResponse('Invoice ID is required', 400);
    }

    // Check if invoice exists
    $invoice = $db->fetchOne("SELECT * FROM faktur WHERE id = ?", [$id]);
    if (!$invoice) {
        errorResponse('Invoice not found', 404);
    }

    if ($invoice['status'] === 'paid') {
        errorResponse('Cannot cancel paid invoice', 400);
    }

    try {
        // Update status to cancelled
        $db->execute("UPDATE faktur SET status = 'cancelled', updated_at = datetime('now') WHERE id = ?", [$id]);

        // Log activity
        global $logger;
        if (isset($logger)) {
            $logger->log('invoice_cancel', [
                'invoice_id' => $id,
                'invoice_number' => $invoice['nomor']
            ]);
        }

        successResponse(['message' => 'Invoice cancelled successfully']);

    } catch (Exception $e) {
        errorResponse('Failed to cancel invoice: ' . $e->getMessage(), 500);
    }
}
