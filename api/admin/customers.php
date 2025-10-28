<?php
require_once '../../includes/bootstrap.php';

// Ensure we send JSON response
header('Content-Type: application/json');

// Security check
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$customerId = $_GET['id'] ?? $_POST['id'] ?? null;

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet($customerId);
        break;
    case 'create':
        handleCreate();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'delete':
        handleDelete($customerId);
        break;
    case 'isolir':
    case 'activate':
        handleToggleStatus($customerId, $action);
        break;
    default:
        errorResponse('Invalid action specified', 400);
}

function handleList() {
    global $db;
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $package = $_GET['package'] ?? '';

    $baseQuery = "SELECT p.*, r.name as router_name FROM pelanggan p LEFT JOIN mikrotik_routers r ON p.router_id = r.id";
    $whereClauses = [];
    $params = [];

    if (!empty($status)) {
        $whereClauses[] = "p.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $searchWildcard = "%{$search}%";
        $whereClauses[] = "(p.nama LIKE ? OR p.kode_pelanggan LIKE ? OR p.email LIKE ? OR p.telp LIKE ? OR p.pppoe_user LIKE ?)";
        array_push($params, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
    }

    if (!empty($package)) {
        $whereClauses[] = "p.paket = ?";
        $params[] = $package;
    }

    if (!empty($whereClauses)) {
        $baseQuery .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $baseQuery .= " ORDER BY p.created_at DESC";

    try {
        $customers = $db->fetchAll($baseQuery, $params);
        successResponse(['customers' => $customers]);
    } catch (Exception $e) {
        error_log("Customer List Error: " . $e->getMessage());
        errorResponse('Failed to retrieve customer list.', 500);
    }
}

function handleGet($customerId) {
    global $db;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }
    $customer = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$customerId]);
    // Attach mitra mapping if available
    try {
        ensureMitraMappingTable();
        $map = $db->fetchOne('SELECT agent_id, region, share_percent FROM isp_agent_customers WHERE customer_id = ?', [$customerId]);
        if ($map) {
            $customer['mitra_id'] = (int)$map['agent_id'];
            $customer['mitra_region'] = $map['region'];
            $customer['mitra_share'] = $map['share_percent'];
        }
    } catch (Throwable $e) {
        // ignore mapping if table not available
    }
    if ($customer) {
        successResponse(['customer' => $customer]);
    } else {
        errorResponse('Customer not found', 404);
    }
}

function handleCreate() {
    global $db;
    $data = $_POST;
    
    // Basic validation
    if (empty($data['nama']) || empty($data['paket'])) {
        errorResponse('Name and Package are required.', 400);
    }

    $data['kode_pelanggan'] = generateCustomerCode();
    if (empty($data['status'])) {
        $data['status'] = 'active';
    }
    if (!empty($data['pppoe_pass'])) {
        $data['pppoe_pass_enc'] = encryptData($data['pppoe_pass']);
    }

    // Set billing system defaults
    if (empty($data['sistem_bayar'])) {
        $data['sistem_bayar'] = 'postpaid';
    }
    if (empty($data['tanggal_aktif'])) {
        $data['tanggal_aktif'] = date('Y-m-d');
    }
    if (empty($data['cycle_billing'])) {
        $data['cycle_billing'] = 1;
    }
    if (empty($data['grace_period'])) {
        $data['grace_period'] = 7;
    }
    if (!isset($data['auto_isolir'])) {
        $data['auto_isolir'] = 1;
    }
    
    // Auto-calculate tanggal_isolir if not set and postpaid
    if (empty($data['tanggal_isolir']) && $data['sistem_bayar'] === 'postpaid') {
        $data['tanggal_isolir'] = calculateTanggalIsolir(
            $data['tanggal_aktif'], 
            $data['cycle_billing'], 
            $data['grace_period']
        );
    }

    $fields = ['kode_pelanggan', 'nama', 'email', 'telp', 'alamat', 'lat', 'lon', 'paket', 'status', 'router_id', 'pppoe_user', 'pppoe_pass_enc', 'profile_aktif', 'profile_isolir', 'tanggal_aktif', 'sistem_bayar', 'tanggal_isolir', 'cycle_billing', 'auto_isolir', 'grace_period'];
    $insertData = [];
    foreach ($fields as $field) {
        $insertData[$field] = $data[$field] ?? null;
    }

    $columns = implode(', ', array_keys($insertData));
    $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
    $values = array_values($insertData);

    try {
        $newId = $db->insert("INSERT INTO pelanggan ({$columns}) VALUES ({$placeholders})", $values);

        // Save mitra mapping if provided
        if (!empty($data['mitra_id']) || !empty($data['mitra_region']) || !empty($data['mitra_share'])) {
            try {
                ensureMitraMappingTable();
                upsertMitraMapping($newId, (int)($data['mitra_id'] ?? 0), (string)($data['mitra_region'] ?? ''), (float)($data['mitra_share'] ?? 0));
            } catch (Throwable $te) { error_log('Mitra mapping (create) failed: ' . $te->getMessage()); }
        }

        // Provisioning depending on Source of Truth
        $source = getSetting('source_of_truth', 'radius');
        $hasRouter = !empty($data['router_id']);
        $hasPPP = !empty($data['pppoe_user']) && !empty($data['pppoe_pass']);
        if ($hasPPP) {
            if ($source === 'mikrotik' && $hasRouter) {
                try {
                    $api = MtkFactory::createFromRouter((int)$data['router_id']);
                    $profile = !empty($data['profile_aktif']) ? $data['profile_aktif'] : getSetting('profile_default', 'default');
                    $api->createSecret($data['pppoe_user'], $data['pppoe_pass'], $profile, 'pppoe');
                    if (($data['status'] ?? 'active') === 'active') {
                        $api->enable($data['pppoe_user']);
                    }
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logISP('provision_create', (int)$newId, [
                            'pppoe_user' => $data['pppoe_user'],
                            'router_id' => (int)$data['router_id'],
                            'profile' => $profile,
                            'source' => 'mikrotik',
                        ]);
                    }
                } catch (Throwable $te) {
                    error_log('MikroTik provisioning (create) failed: ' . $te->getMessage());
                }
            } elseif ($source === 'radius') {
                try {
                    $radius = new RadiusSqlCoa();
                    $radius->upsertUser($data['pppoe_user'], $data['pppoe_pass']);
                    $profile = !empty($data['profile_aktif']) ? $data['profile_aktif'] : getSetting('profile_default', 'default');
                    $radius->setGroup($data['pppoe_user'], $profile);
                    // Apply rate-limit based on package, if available
                    if (!empty($data['paket'])) {
                        $rate = getRateLimitForPackage($data['paket']);
                        if ($rate) {
                            $radius->setRateLimit($data['pppoe_user'], $rate);
                        }
                    }
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logISP('provision_create', (int)$newId, [
                            'pppoe_user' => $data['pppoe_user'],
                            'profile' => $profile,
                            'source' => 'radius',
                        ]);
                    }
                } catch (Throwable $te) {
                    error_log('RADIUS provisioning (create) failed: ' . $te->getMessage());
                }
            }
        }

        successResponse(['id' => $newId], 'Customer created successfully.');
    } catch (Exception $e) {
        error_log("Customer Create Error: " . $e->getMessage());
        errorResponse('Database error while creating customer.', 500);
    }
}

function handleUpdate() {
    global $db;
    $data = $_POST;
    $id = $data['id'] ?? null;

    if (!$id) {
        errorResponse('Customer ID is required for update.', 400);
    }

    if (!empty($data['pppoe_pass'])) {
        $data['pppoe_pass_enc'] = encryptData($data['pppoe_pass']);
    }

    // Auto-calculate tanggal_isolir if billing fields changed and postpaid
    if (isset($data['sistem_bayar']) && $data['sistem_bayar'] === 'postpaid' && 
        (isset($data['tanggal_aktif']) || isset($data['cycle_billing']) || isset($data['grace_period']))) {
        
        // Get current customer data to fill missing values
        $current = $db->fetchOne("SELECT * FROM pelanggan WHERE id = ?", [$id]);
        $tanggal_aktif = $data['tanggal_aktif'] ?? $current['tanggal_aktif'];
        $cycle_billing = $data['cycle_billing'] ?? $current['cycle_billing'] ?? 1;
        $grace_period = $data['grace_period'] ?? $current['grace_period'] ?? 7;
        
        if (!isset($data['tanggal_isolir']) && $tanggal_aktif) {
            $data['tanggal_isolir'] = calculateTanggalIsolir($tanggal_aktif, $cycle_billing, $grace_period);
        }
    }

    $fields = ['nama', 'email', 'telp', 'alamat', 'lat', 'lon', 'paket', 'status', 'router_id', 'pppoe_user', 'pppoe_pass_enc', 'profile_aktif', 'profile_isolir', 'tanggal_aktif', 'sistem_bayar', 'tanggal_isolir', 'cycle_billing', 'auto_isolir', 'grace_period'];
    $setClauses = [];
    $params = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) {
            $setClauses[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($setClauses)) {
        errorResponse('No fields to update.', 400);
    }

    $params[] = $id;

    try {
        $db->execute('UPDATE pelanggan SET ' . implode(', ', $setClauses) . ' WHERE id = ?', $params);

        // Save mitra mapping if provided
        if (!empty($data['id']) && (isset($data['mitra_id']) || isset($data['mitra_region']) || isset($data['mitra_share']))) {
            try {
                ensureMitraMappingTable();
                upsertMitraMapping((int)$data['id'], (int)($data['mitra_id'] ?? 0), (string)($data['mitra_region'] ?? ''), (float)($data['mitra_share'] ?? 0));
            } catch (Throwable $te) { error_log('Mitra mapping (update) failed: ' . $te->getMessage()); }
        }

        // If PPP password provided, update according to Source of Truth
        if (!empty($data['pppoe_user']) && !empty($data['pppoe_pass'])) {
            $source = getSetting('source_of_truth', 'radius');
            if ($source === 'mikrotik' && !empty($data['router_id'])) {
                try {
                    $api = MtkFactory::createFromRouter((int)$data['router_id']);
                    $api->updateSecret($data['pppoe_user'], ['password' => $data['pppoe_pass']]);
                } catch (Throwable $te) {
                    error_log('MikroTik provisioning (update) failed: ' . $te->getMessage());
                }
            } elseif ($source === 'radius') {
                try {
                    // Re-upsert only password and re-apply group to preserve
                    $radius = new RadiusSqlCoa();
                    $radius->upsertUser($data['pppoe_user'], $data['pppoe_pass']);
                    $profile = !empty($data['profile_aktif']) ? $data['profile_aktif'] : getSetting('profile_default', 'default');
                    $radius->setGroup($data['pppoe_user'], $profile);
                    // Update rate-limit from current package
                    $row = $db->fetchOne('SELECT paket FROM pelanggan WHERE id = ?', [$id]);
                    if ($row && !empty($row['paket'])) {
                        $rate = getRateLimitForPackage($row['paket']);
                        if ($rate) {
                            $radius->setRateLimit($data['pppoe_user'], $rate);
                        }
                    }
                } catch (Throwable $te) {
                    error_log('RADIUS provisioning (update) failed: ' . $te->getMessage());
                }
            }
        }

        successResponse([], 'Customer updated successfully.');
    } catch (Exception $e) {
        error_log("Customer Update Error: " . $e->getMessage());
        errorResponse('Database error while updating customer.', 500);
    }
}

function handleDelete($customerId) {
    global $db;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }
    try {
        $db->execute('DELETE FROM pelanggan WHERE id = ?', [$customerId]);
        successResponse([], 'Customer deleted successfully.');
    } catch (Exception $e) {
        error_log("Customer Delete Error: " . $e->getMessage());
        errorResponse('Failed to delete customer.', 500);
    }
}

function handleToggleStatus($customerId, $action) {
    global $db;
    if (!$customerId) {
        errorResponse('Customer ID is required', 400);
    }

    $newStatus = ($action === 'isolir') ? 'isolir' : 'active';

    try {
        // Fetch provisioning info
        $customer = $db->fetchOne('SELECT id, router_id, pppoe_user, profile_aktif, profile_isolir FROM pelanggan WHERE id = ?', [$customerId]);

        // Update DB status first
        $db->execute('UPDATE pelanggan SET status = ? WHERE id = ?', [$newStatus, $customerId]);

        // Provision based on Source of Truth
        $source = getSetting('source_of_truth', 'radius');
        $username = $customer['pppoe_user'] ?? '';
        if ($username) {
            if ($source === 'mikrotik' && !empty($customer['router_id'])) {
                try {
                    $api = MtkFactory::createFromRouter((int)$customer['router_id']);
                    if ($newStatus === 'active') {
                        $profile = !empty($customer['profile_aktif']) ? $customer['profile_aktif'] : getSetting('profile_default', 'default');
                        $api->setPppSecretProfile($username, $profile);
                        $api->enable($username);
                    } else {
                        $profile = !empty($customer['profile_isolir']) ? $customer['profile_isolir'] : getSetting('profile_isolir', 'ISOLIR');
                        $api->setPppSecretProfile($username, $profile);
                        $api->disable($username);
                        $api->disconnectActive($username);
                    }
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logISP('provision_toggle', (int)$customerId, [
                            'status' => $newStatus,
                            'pppoe_user' => $username,
                            'router_id' => (int)$customer['router_id'],
                            'profile' => $profile ?? null,
                            'source' => 'mikrotik',
                        ]);
                    }
                } catch (Throwable $te) {
                    error_log('MikroTik provisioning (toggle) failed: ' . $te->getMessage());
                }
            } elseif ($source === 'radius') {
                try {
                    $radius = new RadiusSqlCoa();
                    if ($newStatus === 'active') {
                        $profile = !empty($customer['profile_aktif']) ? $customer['profile_aktif'] : getSetting('profile_default', 'default');
                        $radius->setGroup($username, $profile);
                        // Apply rate-limit based on package
                        $row = $db->fetchOne('SELECT paket FROM pelanggan WHERE id = ?', [$customerId]);
                        if ($row && !empty($row['paket'])) {
                            $rate = getRateLimitForPackage($row['paket']);
                            if ($rate) {
                                $radius->setRateLimit($username, $rate);
                            }
                        }
                    } else {
                        $profile = !empty($customer['profile_isolir']) ? $customer['profile_isolir'] : getSetting('profile_isolir', 'ISOLIR');
                        $radius->setGroup($username, $profile);
                        // Optional: limit isolir to minimal bandwidth
                        $radius->setRateLimit($username, '1M/1M');
                    }
                    $radius->sendCoA($username);
                    if (isset($GLOBALS['logger'])) {
                        $GLOBALS['logger']->logISP('provision_toggle', (int)$customerId, [
                            'status' => $newStatus,
                            'pppoe_user' => $username,
                            'profile' => $profile ?? null,
                            'source' => 'radius',
                        ]);
                    }
                } catch (Throwable $te) {
                    error_log('RADIUS provisioning (toggle) failed: ' . $te->getMessage());
                }
            }
        }

        successResponse([], "Customer status changed to {$newStatus}.");
    } catch (Exception $e) {
        error_log("Customer Status Toggle Error: " . $e->getMessage());
        errorResponse('Failed to toggle customer status.', 500);
    }
}

// Helpers for rate-limit from package
function getRateLimitForPackage($packageName) {
    global $db;
    if (empty($packageName)) return null;
    $pkg = $db->fetchOne('SELECT speed FROM isp_packages WHERE name = ? OR code = ?', [$packageName, $packageName]);
    if (!$pkg) return null;
    return parseSpeedToRateLimit($pkg['speed'] ?? '');
}

// Mitra mapping helpers
function ensureMitraMappingTable() {
    global $db;
    // Create mapping table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS isp_agent_customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        agent_id INT NULL,
        region VARCHAR(150) NULL,
        share_percent DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_customer (customer_id)
    )";
    $db->execute($sql);
}

function upsertMitraMapping(int $customerId, int $agentId, string $region, float $sharePercent) {
    global $db;
    $existing = $db->fetchOne('SELECT id FROM isp_agent_customers WHERE customer_id = ?', [$customerId]);
    if ($existing) {
        $db->execute('UPDATE isp_agent_customers SET agent_id = ?, region = ?, share_percent = ? WHERE customer_id = ?', [$agentId ?: null, $region ?: null, $sharePercent, $customerId]);
    } else {
        $db->execute('INSERT INTO isp_agent_customers (customer_id, agent_id, region, share_percent) VALUES (?, ?, ?, ?)', [$customerId, $agentId ?: null, $region ?: null, $sharePercent]);
    }
}

function parseSpeedToRateLimit($speed) {
    if (!$speed) return null;
    // Normalize: extract first number and unit
    $s = trim(strtolower($speed));
    if (!preg_match('/([0-9]+\.?[0-9]*)\s*([kmgt]?)(b|bps|bit|mbps|kbps|gbps|m|k|g)?/i', $s, $m)) {
        return null;
    }
    $num = (float)$m[1];
    $unit = isset($m[2]) ? $m[2] : '';
    $unit2 = isset($m[3]) ? strtolower($m[3]) : '';
    // Determine in M (Megabit)
    $mult = 1.0; // default assume M
    if ($unit === 'g' || $unit2 === 'gbps' || $unit2 === 'g') { $mult = 1000.0; }
    elseif ($unit === 'k' || $unit2 === 'kbps' || $unit2 === 'k') { $mult = 0.001; }
    elseif ($unit === 'm' || $unit2 === 'mbps' || $unit2 === 'm') { $mult = 1.0; }
    // Convert to M
    $mval = max(0.1, $num * $mult);
    // Format as Mikrotik-Rate-Limit string: "XM/XM"
    // Round to integer if >=1, else keep one decimal
    $fmt = ($mval >= 1) ? (string)round($mval) : number_format($mval, 1);
    return $fmt . 'M/' . $fmt . 'M';
}

// Billing System Helper Functions
function calculateTanggalIsolir($tanggalAktif, $cycleBilling = 1, $gracePeriod = 7) {
    if (!$tanggalAktif) return null;
    
    $aktifDate = new DateTime($tanggalAktif);
    
    // Calculate next billing date  
    $nextBilling = clone $aktifDate;
    $nextBilling->modify('+1 month');
    $nextBilling->setDate(
        $nextBilling->format('Y'),
        $nextBilling->format('n'), 
        $cycleBilling
    );
    
    // Add grace period
    $isolirDate = clone $nextBilling;
    $isolirDate->modify("+{$gracePeriod} days");
    
    return $isolirDate->format('Y-m-d');
}
