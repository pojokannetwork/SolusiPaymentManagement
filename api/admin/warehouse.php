<?php
// Admin API - Warehouse (Inventory)
require_once __DIR__ . '/../../includes/bootstrap.php';

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.warehouse');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Demo data mode (default enabled until real tables exist)
$demoEnabled = filter_var(getSetting('warehouse_demo', '1'), FILTER_VALIDATE_BOOL);

// Helpers
function getDbDriver() {
    $pdo = Database::getInstance()->getConnection();
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function tableExists($tableName) {
    $driver = getDbDriver();
    $db = Database::getInstance();
    if ($driver === 'mysql') {
        $row = $db->fetchOne("SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [DB_NAME, $tableName]);
        return ($row && (int)$row['c'] > 0);
    }
    if ($driver === 'sqlite') {
        $row = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$tableName]);
        return !empty($row);
    }
    return false;
}

function demo_items() {
    $now = date('Y-m-d H:i:s');
    return [
        ['code' => 'CBL-FO-001', 'name' => 'Kabel FO 48 Core', 'category' => 'Material FO', 'qty' => 1200, 'unit' => 'm', 'location' => 'RACK-A1', 'updated_at' => $now],
        ['code' => 'ODP-16P', 'name' => 'ODP 16 Port', 'category' => 'Perangkat', 'qty' => 18, 'unit' => 'pcs', 'location' => 'RACK-B2', 'updated_at' => $now],
        ['code' => 'PLC-1x8', 'name' => 'PLC Splitter 1x8', 'category' => 'Material FO', 'qty' => 32, 'unit' => 'pcs', 'location' => 'RACK-B1', 'updated_at' => $now],
        ['code' => 'RJ45-CAT6', 'name' => 'Konektor RJ45 CAT6', 'category' => 'Aksesoris', 'qty' => 250, 'unit' => 'pcs', 'location' => 'BIN-C3', 'updated_at' => $now],
        ['code' => 'ONT-ZTE', 'name' => 'ONT ZTE F660', 'category' => 'Perangkat', 'qty' => 10, 'unit' => 'pcs', 'location' => 'RACK-C1', 'updated_at' => $now],
    ];
}

function demo_receipts() {
    $now = date('Y-m-d');
    return [
        ['date' => $now, 'doc_no' => 'RCV-2025-0001', 'supplier' => 'PT Fiberindo', 'items' => 3, 'status' => 'Posted'],
        ['date' => $now, 'doc_no' => 'RCV-2025-0002', 'supplier' => 'PT Sinar Kabel', 'items' => 1, 'status' => 'Draft'],
    ];
}

function demo_issues() {
    $now = date('Y-m-d');
    return [
        ['date' => $now, 'doc_no' => 'ISS-2025-0003', 'target' => 'Teknisi A - STO Barat', 'items' => 5, 'status' => 'Posted'],
        ['date' => $now, 'doc_no' => 'TRF-2025-0004', 'target' => 'Transfer ke Gudang 2', 'items' => 2, 'status' => 'Posted'],
    ];
}

function demo_adjustments() {
    $now = date('Y-m-d');
    return [
        ['date' => $now, 'doc_no' => 'ADJ-2025-0005', 'item' => 'Kabel FO 48 Core', 'change' => '+50 m', 'note' => 'Koreksi hitung'],
    ];
}

function demo_categories() {
    $now = date('Y-m-d H:i:s');
    return [
        ['code' => 'FO', 'name' => 'Material FO', 'description' => 'Material fiber optik', 'updated_at' => $now],
        ['code' => 'DEV', 'name' => 'Perangkat', 'description' => 'Perangkat aktif/pasif', 'updated_at' => $now],
        ['code' => 'ACC', 'name' => 'Aksesoris', 'description' => 'Aksesoris jaringan', 'updated_at' => $now],
    ];
}

function demo_locations() {
    $now = date('Y-m-d H:i:s');
    return [
        ['code' => 'RACK-A1', 'name' => 'Rak A1', 'description' => 'Rak utama A1', 'updated_at' => $now],
        ['code' => 'RACK-B1', 'name' => 'Rak B1', 'description' => 'Rak sekunder B1', 'updated_at' => $now],
        ['code' => 'BIN-C3', 'name' => 'Bin C3', 'description' => 'Kotak kecil C3', 'updated_at' => $now],
    ];
}

switch ($action) {
    // Categories CRUD
    case 'create_category':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $code = sanitizeInput($payload['code'] ?? '');
        $name = sanitizeInput($payload['name'] ?? '');
        $description = sanitizeInput($payload['description'] ?? '');
        if ($code === '' || $name === '') errorResponse('Kode dan nama kategori wajib diisi', 422);
        try {
            $id = Database::getInstance()->insert(
                "INSERT INTO inventory_categories (code, name, description) VALUES (?, ?, ?)",
                [$code, $name, $description]
            );
            successResponse(['id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode kategori sudah ada', 409);
            throw $e;
        }
        break;

    case 'update_category':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($payload['id'] ?? 0);
        $code = sanitizeInput($payload['code'] ?? '');
        $name = sanitizeInput($payload['name'] ?? '');
        $description = sanitizeInput($payload['description'] ?? '');
        if ($id <= 0 || $code === '' || $name === '') errorResponse('Data tidak lengkap', 422);
        try {
            Database::getInstance()->execute(
                "UPDATE inventory_categories SET code=?, name=?, description=? WHERE id=?",
                [$code, $name, $description, $id]
            );
            successResponse();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode kategori sudah ada', 409);
            throw $e;
        }
        break;

    case 'delete_category':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        try {
            Database::getInstance()->execute("DELETE FROM inventory_categories WHERE id=?", [$id]);
            successResponse();
        } catch (PDOException $e) {
            errorResponse('Kategori sedang digunakan dan tidak dapat dihapus', 409);
        }
        break;

    // Locations CRUD
    case 'create_location':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $code = sanitizeInput($payload['code'] ?? '');
        $name = sanitizeInput($payload['name'] ?? '');
        $description = sanitizeInput($payload['description'] ?? '');
        if ($code === '' || $name === '') errorResponse('Kode dan nama lokasi wajib diisi', 422);
        try {
            $id = Database::getInstance()->insert(
                "INSERT INTO inventory_locations (code, name, description) VALUES (?, ?, ?)",
                [$code, $name, $description]
            );
            successResponse(['id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode lokasi sudah ada', 409);
            throw $e;
        }
        break;

    case 'update_location':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($payload['id'] ?? 0);
        $code = sanitizeInput($payload['code'] ?? '');
        $name = sanitizeInput($payload['name'] ?? '');
        $description = sanitizeInput($payload['description'] ?? '');
        if ($id <= 0 || $code === '' || $name === '') errorResponse('Data tidak lengkap', 422);
        try {
            Database::getInstance()->execute(
                "UPDATE inventory_locations SET code=?, name=?, description=? WHERE id=?",
                [$code, $name, $description, $id]
            );
            successResponse();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode lokasi sudah ada', 409);
            throw $e;
        }
        break;

    case 'delete_location':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $payload = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        try {
            Database::getInstance()->execute("DELETE FROM inventory_locations WHERE id=?", [$id]);
            successResponse();
        } catch (PDOException $e) {
            errorResponse('Lokasi sedang digunakan dan tidak dapat dihapus', 409);
        }
        break;

    // Items CRUD
    case 'create_item':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $code = sanitizeInput($p['code'] ?? '');
        $name = sanitizeInput($p['name'] ?? '');
        $unit = sanitizeInput($p['unit'] ?? 'pcs');
        $category_id = (int)($p['category_id'] ?? 0);
        $location_id = (int)($p['location_id'] ?? 0);
        $stock_qty = (float)($p['stock_qty'] ?? 0);
        if ($code === '' || $name === '') errorResponse('Kode dan nama barang wajib diisi', 422);
        try {
            $id = Database::getInstance()->insert(
                "INSERT INTO inventory_items (code, name, category_id, unit, stock_qty, location_id) VALUES (?, ?, ?, ?, ?, ?)",
                [$code, $name, $category_id ?: null, $unit, $stock_qty, $location_id ?: null]
            );
            successResponse(['id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode barang sudah ada', 409);
            throw $e;
        }
        break;

    // Receipt documents
    case 'get_receipt':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        if (!tableExists('inventory_receipts')) successResponse(['receipt' => null]);
        $dbi = Database::getInstance();
        $header = $dbi->fetchOne("SELECT id, doc_no, date, supplier, status FROM inventory_receipts WHERE id=?", [$id]);
        if (!$header) successResponse(['receipt' => null]);
        $items = $dbi->fetchAll("SELECT id, item_id, qty, unit, note, (SELECT code FROM inventory_items WHERE id=ri.item_id) AS item_code, (SELECT name FROM inventory_items WHERE id=ri.item_id) AS item_name FROM inventory_receipt_items ri WHERE receipt_id=?", [$id]);
        successResponse(['receipt' => ['header' => $header, 'items' => $items]]);
        break;

    case 'create_receipt':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        if (!tableExists('inventory_receipts')) errorResponse('Tabel belum tersedia. Terapkan skema database.', 500);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $doc_no = sanitizeInput($p['doc_no'] ?? '');
        $date = sanitizeInput($p['date'] ?? date('Y-m-d'));
        $supplier = sanitizeInput($p['supplier'] ?? '');
        $items = $p['items'] ?? [];
        if ($doc_no === '') $doc_no = 'RCV-' . date('Ymd-His');
        if (!is_array($items) || count($items) === 0) errorResponse('Item penerimaan kosong', 422);
        $dbi = Database::getInstance();
        try {
            $dbi->beginTransaction();
            $receipt_id = $dbi->insert("INSERT INTO inventory_receipts (doc_no, date, supplier, status) VALUES (?,?,?, 'Draft')", [$doc_no, $date, $supplier]);
            foreach ($items as $it) {
                $item_id = (int)($it['item_id'] ?? 0);
                $qty = (float)($it['qty'] ?? 0);
                $unit = sanitizeInput($it['unit'] ?? 'pcs');
                $note = sanitizeInput($it['note'] ?? '');
                if ($item_id <= 0 || $qty <= 0) { $dbi->rollback(); errorResponse('Baris item tidak valid', 422); }
                // Ensure item exists
                $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
                if (!$exists) { $dbi->rollback(); errorResponse('Item tidak ditemukan', 404); }
                $dbi->insert("INSERT INTO inventory_receipt_items (receipt_id, item_id, qty, unit, note) VALUES (?,?,?,?,?)", [$receipt_id, $item_id, $qty, $unit, $note]);
            }
            $dbi->commit();
            successResponse(['id' => $receipt_id]);
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal membuat penerimaan: ' . $e->getMessage(), 500);
        }
        break;

    case 'update_receipt':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        if (!tableExists('inventory_receipts')) errorResponse('Tabel belum tersedia. Terapkan skema database.', 500);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT * FROM inventory_receipts WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya dokumen Draft yang bisa diubah', 409);
        $doc_no = sanitizeInput($p['doc_no'] ?? $hdr['doc_no']);
        $date = sanitizeInput($p['date'] ?? $hdr['date']);
        $supplier = sanitizeInput($p['supplier'] ?? $hdr['supplier']);
        $items = $p['items'] ?? [];
        if (!is_array($items) || count($items) === 0) errorResponse('Item penerimaan kosong', 422);
        try {
            $dbi->beginTransaction();
            $dbi->execute("UPDATE inventory_receipts SET doc_no=?, date=?, supplier=? WHERE id=?", [$doc_no, $date, $supplier, $id]);
            // Replace all items
            $dbi->execute("DELETE FROM inventory_receipt_items WHERE receipt_id=?", [$id]);
            foreach ($items as $it) {
                $item_id = (int)($it['item_id'] ?? 0);
                $qty = (float)($it['qty'] ?? 0);
                $unit = sanitizeInput($it['unit'] ?? 'pcs');
                $note = sanitizeInput($it['note'] ?? '');
                if ($item_id <= 0 || $qty <= 0) { $dbi->rollback(); errorResponse('Baris item tidak valid', 422); }
                $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
                if (!$exists) { $dbi->rollback(); errorResponse('Item tidak ditemukan', 404); }
                $dbi->insert("INSERT INTO inventory_receipt_items (receipt_id, item_id, qty, unit, note) VALUES (?,?,?,?,?)", [$id, $item_id, $qty, $unit, $note]);
            }
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal menyimpan penerimaan: ' . $e->getMessage(), 500);
        }
        break;

    case 'delete_receipt':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_receipts WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat dihapus', 409);
        $dbi->execute("DELETE FROM inventory_receipts WHERE id=?", [$id]);
        successResponse();
        break;

    case 'post_receipt':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_receipts WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat diposting', 409);
        try {
            $dbi->beginTransaction();
            $rows = $dbi->fetchAll("SELECT item_id, qty FROM inventory_receipt_items WHERE receipt_id=?", [$id]);
            foreach ($rows as $r) {
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty + ? WHERE id=?", [(float)$r['qty'], (int)$r['item_id']]);
            }
            $dbi->execute("UPDATE inventory_receipts SET status='Posted' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal posting: ' . $e->getMessage(), 500);
        }
        break;

    case 'unpost_receipt':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_receipts WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'posted') errorResponse('Hanya Posted yang dapat di-unpost', 409);
        try {
            $dbi->beginTransaction();
            $rows = $dbi->fetchAll("SELECT item_id, qty FROM inventory_receipt_items WHERE receipt_id=?", [$id]);
            foreach ($rows as $r) {
                // Prevent negative stock
                $cur = $dbi->fetchOne("SELECT stock_qty FROM inventory_items WHERE id=?", [(int)$r['item_id']]);
                if ($cur && (float)$cur['stock_qty'] - (float)$r['qty'] < 0) {
                    $dbi->rollback();
                    errorResponse('Tidak dapat unpost: stok akan negatif', 409);
                }
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty - ? WHERE id=?", [(float)$r['qty'], (int)$r['item_id']]);
            }
            $dbi->execute("UPDATE inventory_receipts SET status='Draft' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal unpost: ' . $e->getMessage(), 500);
        }
        break;

    case 'item_lookup':
        $q = trim($_GET['q'] ?? '');
        if (!tableExists('inventory_items')) successResponse(['items' => []]);
        $dbi = Database::getInstance();
        if ($q === '') {
            $rows = $dbi->fetchAll("SELECT id, code, name, unit FROM inventory_items ORDER BY name LIMIT 50");
        } else {
            $like = '%' . $q . '%';
            $rows = $dbi->fetchAll("SELECT id, code, name, unit FROM inventory_items WHERE code LIKE ? OR name LIKE ? ORDER BY name LIMIT 50", [$like, $like]);
        }
        successResponse(['items' => $rows]);
        break;

    // Issue documents
    case 'get_issue':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        if (!tableExists('inventory_issues')) successResponse(['issue' => null]);
        $dbi = Database::getInstance();
        $header = $dbi->fetchOne("SELECT id, doc_no, date, target, status FROM inventory_issues WHERE id=?", [$id]);
        if (!$header) successResponse(['issue' => null]);
        $items = $dbi->fetchAll("SELECT id, item_id, qty, unit, note, (SELECT code FROM inventory_items WHERE id=ii.item_id) AS item_code, (SELECT name FROM inventory_items WHERE id=ii.item_id) AS item_name FROM inventory_issue_items ii WHERE issue_id=?", [$id]);
        successResponse(['issue' => ['header' => $header, 'items' => $items]]);
        break;

    case 'create_issue':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        if (!tableExists('inventory_issues')) errorResponse('Tabel belum tersedia. Terapkan skema database.', 500);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $doc_no = sanitizeInput($p['doc_no'] ?? '');
        $date = sanitizeInput($p['date'] ?? date('Y-m-d'));
        $target = sanitizeInput($p['target'] ?? '');
        $items = $p['items'] ?? [];
        if ($doc_no === '') $doc_no = 'ISS-' . date('Ymd-His');
        if (!is_array($items) || count($items) === 0) errorResponse('Item pengeluaran kosong', 422);
        $dbi = Database::getInstance();
        try {
            $dbi->beginTransaction();
            $issue_id = $dbi->insert("INSERT INTO inventory_issues (doc_no, date, target, status) VALUES (?,?,?, 'Draft')", [$doc_no, $date, $target]);
            foreach ($items as $it) {
                $item_id = (int)($it['item_id'] ?? 0);
                $qty = (float)($it['qty'] ?? 0);
                $unit = sanitizeInput($it['unit'] ?? 'pcs');
                $note = sanitizeInput($it['note'] ?? '');
                if ($item_id <= 0 || $qty <= 0) { $dbi->rollback(); errorResponse('Baris item tidak valid', 422); }
                $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
                if (!$exists) { $dbi->rollback(); errorResponse('Item tidak ditemukan', 404); }
                $dbi->insert("INSERT INTO inventory_issue_items (issue_id, item_id, qty, unit, note) VALUES (?,?,?,?,?)", [$issue_id, $item_id, $qty, $unit, $note]);
            }
            $dbi->commit();
            successResponse(['id' => $issue_id]);
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal membuat pengeluaran: ' . $e->getMessage(), 500);
        }
        break;

    case 'update_issue':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        if (!tableExists('inventory_issues')) errorResponse('Tabel belum tersedia. Terapkan skema database.', 500);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT * FROM inventory_issues WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya dokumen Draft yang bisa diubah', 409);
        $doc_no = sanitizeInput($p['doc_no'] ?? $hdr['doc_no']);
        $date = sanitizeInput($p['date'] ?? $hdr['date']);
        $target = sanitizeInput($p['target'] ?? $hdr['target']);
        $items = $p['items'] ?? [];
        if (!is_array($items) || count($items) === 0) errorResponse('Item pengeluaran kosong', 422);
        try {
            $dbi->beginTransaction();
            $dbi->execute("UPDATE inventory_issues SET doc_no=?, date=?, target=? WHERE id=?", [$doc_no, $date, $target, $id]);
            $dbi->execute("DELETE FROM inventory_issue_items WHERE issue_id=?", [$id]);
            foreach ($items as $it) {
                $item_id = (int)($it['item_id'] ?? 0);
                $qty = (float)($it['qty'] ?? 0);
                $unit = sanitizeInput($it['unit'] ?? 'pcs');
                $note = sanitizeInput($it['note'] ?? '');
                if ($item_id <= 0 || $qty <= 0) { $dbi->rollback(); errorResponse('Baris item tidak valid', 422); }
                $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
                if (!$exists) { $dbi->rollback(); errorResponse('Item tidak ditemukan', 404); }
                $dbi->insert("INSERT INTO inventory_issue_items (issue_id, item_id, qty, unit, note) VALUES (?,?,?,?,?)", [$id, $item_id, $qty, $unit, $note]);
            }
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal menyimpan pengeluaran: ' . $e->getMessage(), 500);
        }
        break;

    case 'delete_issue':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_issues WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat dihapus', 409);
        $dbi->execute("DELETE FROM inventory_issues WHERE id=?", [$id]);
        successResponse();
        break;

    case 'post_issue':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_issues WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat diposting', 409);
        try {
            $dbi->beginTransaction();
            $rows = $dbi->fetchAll("SELECT item_id, qty FROM inventory_issue_items WHERE issue_id=?", [$id]);
            foreach ($rows as $r) {
                $cur = $dbi->fetchOne("SELECT stock_qty FROM inventory_items WHERE id=?", [(int)$r['item_id']]);
                if (!$cur || (float)$cur['stock_qty'] - (float)$r['qty'] < 0) {
                    $dbi->rollback();
                    errorResponse('Stok tidak mencukupi untuk item', 409);
                }
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty - ? WHERE id=?", [(float)$r['qty'], (int)$r['item_id']]);
            }
            $dbi->execute("UPDATE inventory_issues SET status='Posted' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal posting: ' . $e->getMessage(), 500);
        }
        break;

    case 'unpost_issue':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_issues WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'posted') errorResponse('Hanya Posted yang dapat di-unpost', 409);
        try {
            $dbi->beginTransaction();
            $rows = $dbi->fetchAll("SELECT item_id, qty FROM inventory_issue_items WHERE issue_id=?", [$id]);
            foreach ($rows as $r) {
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty + ? WHERE id=?", [(float)$r['qty'], (int)$r['item_id']]);
            }
            $dbi->execute("UPDATE inventory_issues SET status='Draft' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback();
            errorResponse('Gagal unpost: ' . $e->getMessage(), 500);
        }
        break;

    // Adjustments CRUD
    case 'get_adjustment':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        if (!tableExists('inventory_adjustments')) successResponse(['adjustment' => null]);
        $dbi = Database::getInstance();
        $row = $dbi->fetchOne("SELECT id, doc_no, date, item_id, change_qty, note, status FROM inventory_adjustments WHERE id=?", [$id]);
        successResponse(['adjustment' => $row]);
        break;

    case 'create_adjustment':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        if (!tableExists('inventory_adjustments')) errorResponse('Tabel belum tersedia. Terapkan skema database.', 500);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $doc_no = sanitizeInput($p['doc_no'] ?? '');
        $date = sanitizeInput($p['date'] ?? date('Y-m-d'));
        $item_id = (int)($p['item_id'] ?? 0);
        $change_qty = (float)($p['change_qty'] ?? 0);
        $note = sanitizeInput($p['note'] ?? '');
        if ($doc_no === '') $doc_no = 'ADJ-' . date('Ymd-His');
        if ($item_id <= 0 || $change_qty == 0) errorResponse('Data penyesuaian tidak lengkap', 422);
        $dbi = Database::getInstance();
        $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
        if (!$exists) errorResponse('Item tidak ditemukan', 404);
        $id = $dbi->insert("INSERT INTO inventory_adjustments (doc_no, date, item_id, change_qty, note, status) VALUES (?,?,?,?,?, 'Draft')", [$doc_no, $date, $item_id, $change_qty, $note]);
        successResponse(['id' => $id]);
        break;

    case 'update_adjustment':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT * FROM inventory_adjustments WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat diubah', 409);
        $doc_no = sanitizeInput($p['doc_no'] ?? $hdr['doc_no']);
        $date = sanitizeInput($p['date'] ?? $hdr['date']);
        $item_id = (int)($p['item_id'] ?? $hdr['item_id']);
        $change_qty = (float)($p['change_qty'] ?? $hdr['change_qty']);
        $note = sanitizeInput($p['note'] ?? $hdr['note']);
        $exists = $dbi->fetchOne("SELECT id FROM inventory_items WHERE id=?", [$item_id]);
        if (!$exists) errorResponse('Item tidak ditemukan', 404);
        $dbi->execute("UPDATE inventory_adjustments SET doc_no=?, date=?, item_id=?, change_qty=?, note=? WHERE id=?", [$doc_no, $date, $item_id, $change_qty, $note, $id]);
        successResponse();
        break;

    case 'delete_adjustment':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT status FROM inventory_adjustments WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat dihapus', 409);
        $dbi->execute("DELETE FROM inventory_adjustments WHERE id=?", [$id]);
        successResponse();
        break;

    case 'post_adjustment':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT * FROM inventory_adjustments WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'draft') errorResponse('Hanya Draft yang dapat diposting', 409);
        $change = (float)$hdr['change_qty'];
        try {
            $dbi->beginTransaction();
            if ($change < 0) {
                $cur = $dbi->fetchOne("SELECT stock_qty FROM inventory_items WHERE id=?", [$hdr['item_id']]);
                if (!$cur || (float)$cur['stock_qty'] + $change < 0) { $dbi->rollback(); errorResponse('Stok tidak mencukupi', 409); }
            }
            $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty + ? WHERE id=?", [$change, $hdr['item_id']]);
            $dbi->execute("UPDATE inventory_adjustments SET status='Posted' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback(); errorResponse('Gagal posting: ' . $e->getMessage(), 500);
        }
        break;

    case 'unpost_adjustment':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        $dbi = Database::getInstance();
        $hdr = $dbi->fetchOne("SELECT * FROM inventory_adjustments WHERE id=?", [$id]);
        if (!$hdr) errorResponse('Dokumen tidak ditemukan', 404);
        if (strtolower($hdr['status']) !== 'posted') errorResponse('Hanya Posted yang dapat di-unpost', 409);
        $change = (float)$hdr['change_qty'];
        try {
            $dbi->beginTransaction();
            // Revert previous change
            if ($change > 0) {
                $cur = $dbi->fetchOne("SELECT stock_qty FROM inventory_items WHERE id=?", [$hdr['item_id']]);
                if (!$cur || (float)$cur['stock_qty'] - $change < 0) { $dbi->rollback(); errorResponse('Tidak dapat unpost: stok akan negatif', 409); }
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty - ? WHERE id=?", [$change, $hdr['item_id']]);
            } else {
                $dbi->execute("UPDATE inventory_items SET stock_qty = stock_qty - ? WHERE id=?", [$change, $hdr['item_id']]);
            }
            $dbi->execute("UPDATE inventory_adjustments SET status='Draft' WHERE id=?", [$id]);
            $dbi->commit();
            successResponse();
        } catch (Exception $e) {
            $dbi->rollback(); errorResponse('Gagal unpost: ' . $e->getMessage(), 500);
        }
        break;

    case 'update_item':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        $code = sanitizeInput($p['code'] ?? '');
        $name = sanitizeInput($p['name'] ?? '');
        $unit = sanitizeInput($p['unit'] ?? 'pcs');
        $category_id = (int)($p['category_id'] ?? 0);
        $location_id = (int)($p['location_id'] ?? 0);
        $stock_qty = (float)($p['stock_qty'] ?? 0);
        if ($id <= 0 || $code === '' || $name === '') errorResponse('Data tidak lengkap', 422);
        try {
            Database::getInstance()->execute(
                "UPDATE inventory_items SET code=?, name=?, category_id=?, unit=?, stock_qty=?, location_id=? WHERE id=?",
                [$code, $name, $category_id ?: null, $unit, $stock_qty, $location_id ?: null, $id]
            );
            successResponse();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '19') errorResponse('Kode barang sudah ada', 409);
            throw $e;
        }
        break;

    case 'delete_item':
        if ($method !== 'POST') errorResponse('Method not allowed', 405);
        $p = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) errorResponse('ID tidak valid', 422);
        try {
            Database::getInstance()->execute("DELETE FROM inventory_items WHERE id=?", [$id]);
            successResponse();
        } catch (PDOException $e) {
            errorResponse('Barang sedang digunakan dalam dokumen dan tidak dapat dihapus', 409);
        }
        break;
    case 'list_items':
        if (!$demoEnabled && tableExists('inventory_items')) {
            $sql = "SELECT i.id, i.code, i.name, c.name AS category, i.stock_qty AS qty, i.unit, l.code AS location, i.updated_at
                    FROM inventory_items i
                    LEFT JOIN inventory_categories c ON i.category_id = c.id
                    LEFT JOIN inventory_locations l ON i.location_id = l.id
                    ORDER BY i.name";
            $items = Database::getInstance()->fetchAll($sql);
            successResponse(['items' => $items, 'total' => count($items)]);
        }
        if ($demoEnabled) {
            $items = array_map(function($row, $idx){ $row['id']=$idx+1; return $row; }, demo_items(), array_keys(demo_items()));
            successResponse(['items' => $items, 'total' => count($items)]);
        }
        successResponse(['items' => [], 'total' => 0]);
        break;

    case 'list_receipts':
        if (!$demoEnabled && tableExists('inventory_receipts')) {
            $sql = "SELECT r.id, date AS date, doc_no, supplier,
                           (SELECT COUNT(*) FROM inventory_receipt_items ri WHERE ri.receipt_id = r.id) AS items,
                           status
                    FROM inventory_receipts r ORDER BY date DESC, doc_no DESC";
            $rows = Database::getInstance()->fetchAll($sql);
            successResponse(['receipts' => $rows, 'total' => count($rows)]);
        }
        if ($demoEnabled) {
            $rows = demo_receipts();
            foreach ($rows as $i => &$row) { $row['id'] = $i + 1; }
            successResponse(['receipts' => $rows, 'total' => count($rows)]);
        }
        successResponse(['receipts' => [], 'total' => 0]);
        break;

    case 'list_issues':
        if (!$demoEnabled && tableExists('inventory_issues')) {
            $sql = "SELECT i.id, date AS date, doc_no, target,
                           (SELECT COUNT(*) FROM inventory_issue_items ii WHERE ii.issue_id = i.id) AS items,
                           status
                    FROM inventory_issues i ORDER BY date DESC, doc_no DESC";
            $rows = Database::getInstance()->fetchAll($sql);
            successResponse(['issues' => $rows, 'total' => count($rows)]);
        }
        if ($demoEnabled) {
            $rows = demo_issues();
            foreach ($rows as $i => &$row) { $row['id'] = $i + 1; }
            successResponse(['issues' => $rows, 'total' => count($rows)]);
        }
        successResponse(['issues' => [], 'total' => 0]);
        break;

    case 'list_adjustments':
        if (!$demoEnabled && tableExists('inventory_adjustments')) {
            $sql = "SELECT a.id, date AS date, doc_no,
                           (SELECT name FROM inventory_items WHERE id = a.item_id) AS item,
                           change_qty AS `change`,
                           note,
                           status
                    FROM inventory_adjustments a ORDER BY date DESC, doc_no DESC";
            $rows = Database::getInstance()->fetchAll($sql);
            successResponse(['adjustments' => $rows, 'total' => count($rows)]);
        }
        if ($demoEnabled) {
            $rows = demo_adjustments();
            foreach ($rows as $i => &$row) { $row['id'] = $i + 1; $row['status'] = 'Draft'; }
            successResponse(['adjustments' => $rows, 'total' => count($rows)]);
        }
        successResponse(['adjustments' => [], 'total' => 0]);
        break;

    case 'list_categories':
        if (!$demoEnabled && tableExists('inventory_categories')) {
            $sql = "SELECT id, code, name, description, updated_at FROM inventory_categories ORDER BY name";
            $rows = Database::getInstance()->fetchAll($sql);
            successResponse(['categories' => $rows, 'total' => count($rows)]);
        }
        if ($demoEnabled) {
            $rows = demo_categories();
            successResponse(['categories' => $rows, 'total' => count($rows)]);
        }
        successResponse(['categories' => [], 'total' => 0]);
        break;

    case 'list_locations':
        if (!$demoEnabled && tableExists('inventory_locations')) {
            $sql = "SELECT id, code, name, description, updated_at FROM inventory_locations ORDER BY name";
            $rows = Database::getInstance()->fetchAll($sql);
            successResponse(['locations' => $rows, 'total' => count($rows)]);
        }
        if ($demoEnabled) {
            $rows = demo_locations();
            successResponse(['locations' => $rows, 'total' => count($rows)]);
        }
        successResponse(['locations' => [], 'total' => 0]);
        break;

    default:
        errorResponse('Unknown or unsupported action', 400);
}
