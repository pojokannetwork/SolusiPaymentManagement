<?php
require_once '../../includes/bootstrap.php';

header('Content-Type: application/json');

// Security check
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'summary':
        handleSummary();
        break;
    case 'auto_isolir':
        handleAutoIsolir();
        break;
    case 'generate_bills':
        handleGenerateBills();
        break;
    case 'generate_single_bill':
        handleGenerateSingleBill();
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;
    
    $statusFilter = $_GET['status_filter'] ?? '';
    $billingFilter = $_GET['billing_filter'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    
    $baseQuery = "SELECT * FROM pelanggan WHERE 1=1";
    $params = [];
    
    // Apply filters
    if (!empty($billingFilter)) {
        $baseQuery .= " AND sistem_bayar = ?";
        $params[] = $billingFilter;
    }
    
    if (!empty($statusFilter)) {
        switch ($statusFilter) {
            case 'overdue':
                $baseQuery .= " AND tanggal_isolir <= date('now') AND status = 'active' AND sistem_bayar = 'postpaid'";
                break;
            case 'due-week':
                $baseQuery .= " AND tanggal_isolir BETWEEN date('now') AND date('now', '+7 days') AND sistem_bayar = 'postpaid'";
                break;
            case 'prepaid':
                $baseQuery .= " AND sistem_bayar = 'prepaid'";
                break;
            case 'auto-isolir':
                $baseQuery .= " AND auto_isolir = 1";
                break;
        }
    }
    
    if (!empty($startDate)) {
        $baseQuery .= " AND tanggal_isolir >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $baseQuery .= " AND tanggal_isolir <= ?";
        $params[] = $endDate;
    }
    
    $baseQuery .= " ORDER BY tanggal_isolir ASC, sistem_bayar DESC";
    
    $customers = $db->fetchAll($baseQuery, $params);
    
    successResponse(['data' => $customers]);
}

function handleSummary() {
    global $db;
    
    $summary = [];
    
    // Overdue today
    $summary['overdue'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pelanggan 
         WHERE tanggal_isolir <= date('now') AND status = 'active' AND sistem_bayar = 'postpaid'"
    )['count'];
    
    // Due this week
    $summary['due_week'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pelanggan 
         WHERE tanggal_isolir BETWEEN date('now') AND date('now', '+7 days') AND sistem_bayar = 'postpaid'"
    )['count'];
    
    // Prepaid active
    $summary['prepaid'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pelanggan 
         WHERE sistem_bayar = 'prepaid' AND status = 'active'"
    )['count'];
    
    // Auto isolir enabled
    $summary['auto_isolir'] = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pelanggan 
         WHERE auto_isolir = 1"
    )['count'];
    
    successResponse(['data' => $summary]);
}

function handleAutoIsolir() {
    global $db;
    
    // Get customers that should be isolated
    $overdueCustomers = $db->fetchAll(
        "SELECT * FROM pelanggan 
         WHERE tanggal_isolir <= date('now') 
         AND status = 'active' 
         AND sistem_bayar = 'postpaid'
         AND auto_isolir = 1"
    );
    
    $isolatedCount = 0;
    
    foreach ($overdueCustomers as $customer) {
        try {
            // Update status to isolir
            $db->execute(
                "UPDATE pelanggan SET status = 'isolir', updated_at = datetime('now') WHERE id = ?",
                [$customer['id']]
            );
            
            // Provision customer (isolate service)
            provisionCustomerOnFailure($customer['id']);
            
            $isolatedCount++;
            
            // Log the action
            global $logger;
            if (isset($logger)) {
                $logger->log('auto_isolir', [
                    'customer_id' => $customer['id'],
                    'customer_code' => $customer['kode_pelanggan'],
                    'isolir_date' => $customer['tanggal_isolir']
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Auto isolir failed for customer ' . $customer['id'] . ': ' . $e->getMessage());
        }
    }
    
    successResponse([
        'message' => "Auto isolir completed",
        'count' => $isolatedCount
    ]);
}

function handleGenerateBills() {
    global $db;
    
    // Get postpaid customers that need billing
    $today = date('j'); // Day of month
    
    $customers = $db->fetchAll(
        "SELECT * FROM pelanggan 
         WHERE sistem_bayar = 'postpaid' 
         AND status IN ('active', 'isolir')
         AND cycle_billing = ?",
        [$today]
    );
    
    $billCount = 0;
    
    foreach ($customers as $customer) {
        try {
            // Check if bill already exists for this month
            $currentMonth = date('Y-m');
            $existingInvoice = $db->fetchOne(
                "SELECT id FROM faktur 
                 WHERE pelanggan_id = ? 
                 AND strftime('%Y-%m', created_at) = ?",
                [$customer['id'], $currentMonth]
            );
            
            if ($existingInvoice) continue; // Skip if already billed this month
            
            // Generate invoice
            $invoiceId = generateInvoiceForCustomer($customer);
            if ($invoiceId) {
                $billCount++;
            }
            
        } catch (Exception $e) {
            error_log('Bill generation failed for customer ' . $customer['id'] . ': ' . $e->getMessage());
        }
    }
    
    successResponse([
        'message' => "Bills generated",
        'count' => $billCount
    ]);
}

function handleGenerateSingleBill() {
    global $db;
    
    $customerId = $_POST['customer_id'] ?? null;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }
    
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) {
        errorResponse('Customer not found', 404);
    }
    
    if ($customer['sistem_bayar'] !== 'postpaid') {
        errorResponse('Only postpaid customers can have bills generated', 400);
    }
    
    try {
        $invoiceId = generateInvoiceForCustomer($customer);
        
        if ($invoiceId) {
            successResponse([
                'message' => 'Bill generated successfully',
                'invoice_id' => $invoiceId
            ]);
        } else {
            errorResponse('Failed to generate bill', 500);
        }
    } catch (Exception $e) {
        errorResponse('Bill generation failed: ' . $e->getMessage(), 500);
    }
}

// Helper Functions
function generateInvoiceForCustomer($customer) {
    global $db;
    
    // Get package details for pricing
    $package = $db->fetchOne(
        "SELECT * FROM isp_packages WHERE name = ? OR code = ?",
        [$customer['paket'], $customer['paket']]
    );
    
    if (!$package) {
        error_log('Package not found for customer ' . $customer['id']);
        return false;
    }
    
    $amount = $package['price'] ?? 0;
    if ($amount <= 0) {
        error_log('Invalid package price for customer ' . $customer['id']);
        return false;
    }
    
    // Generate invoice number
    $invoiceNumber = 'INV-' . date('Ym') . '-' . str_pad($customer['id'], 4, '0', STR_PAD_LEFT);
    
    // Calculate due date (next month + grace period)
    $nextMonth = date('Y-m-d', strtotime('+1 month'));
    $dueDate = date('Y-m-d', strtotime($nextMonth . ' +' . ($customer['grace_period'] ?? 7) . ' days'));
    
    // Calculate tax if applicable
    $subtotal = $amount;
    $tax = 0; // Can be calculated based on tax settings if needed
    $total = $subtotal + $tax;

    // Create invoice
    $invoiceId = $db->execute(
        "INSERT INTO faktur (pelanggan_id, nomor, tanggal, jatuh_tempo, subtotal, pajak, total, status, created_at, updated_at)
         VALUES (?, ?, date('now'), ?, ?, ?, ?, 'sent', datetime('now'), datetime('now'))",
        [
            $customer['id'],
            $invoiceNumber,
            $dueDate,
            $subtotal,
            $tax,
            $total
        ]
    );

    // Create invoice item for the package
    if ($invoiceId) {
        $db->execute(
            "INSERT INTO invoice_items (faktur_id, deskripsi, qty, harga, subtotal)
             VALUES (?, ?, ?, ?, ?)",
            [
                $invoiceId,
                'Tagihan bulanan paket ' . $customer['paket'],
                1,
                $amount,
                $amount
            ]
        );
    }
    
    return $invoiceId;
}

function provisionCustomerOnFailure($customerId) {
    global $db;
    
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    if (!$customer) return;
    
    $sourceOfTruth = getSetting('source_of_truth', 'radius');
    
    if ($sourceOfTruth === 'radius') {
        try {
            $radius = new RadiusSqlCoa();
            $radius->setGroup($customer['pppoe_user'], $customer['profile_isolir']);
            $radius->sendCoA($customer['pppoe_user']);
        } catch (Exception $e) {
            error_log('RADIUS isolation failed: ' . $e->getMessage());
        }
    } else {
        if (!empty($customer['router_id'])) {
            try {
                $mtk = MtkFactory::createFromRouter($customer['router_id']);
                $mtk->setPppSecretProfile($customer['pppoe_user'], $customer['profile_isolir']);
            } catch (Exception $e) {
                error_log('Mikrotik isolation failed: ' . $e->getMessage());
            }
        }
    }
}