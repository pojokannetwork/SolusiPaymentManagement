<?php
// OLT Monitoring admin API endpoints

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/olt_monitoring.php';

header('Content-Type: application/json; charset=utf-8');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.dashboard');

OltMonitoring::boot();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'summary';

try {
    switch ($method) {
        case 'GET':
            handleGet($action);
            break;
        case 'POST':
            handlePost($action);
            break;
        default:
            errorResponse('Method not allowed', 405);
    }
} catch (Throwable $e) {
    error_log('[OLT Monitoring API] ' . $e->getMessage());
    errorResponse('Terjadi kesalahan internal: ' . $e->getMessage(), 500);
}

function handleGet(string $action): void
{
    switch ($action) {
        case 'summary':
            successResponse(OltMonitoring::getSummary());
            break;
        case 'devices':
            successResponse(['devices' => OltMonitoring::listDevices()]);
            break;
        case 'device':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                errorResponse('ID perangkat tidak valid', 400);
            }
            $device = OltMonitoring::getDevice($id);
            if (!$device) {
                errorResponse('Perangkat tidak ditemukan', 404);
            }
            successResponse(['device' => $device]);
            break;
        case 'onts':
            $filters = [
                'olt_id' => $_GET['olt_id'] ?? null,
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];
            successResponse(['onts' => OltMonitoring::listOnts($filters)]);
            break;
        case 'events':
            $limit = (int) ($_GET['limit'] ?? 50);
            $filters = [
                'severity' => $_GET['severity'] ?? null,
                'olt_id' => $_GET['olt_id'] ?? null,
                'since' => $_GET['since'] ?? null,
            ];
            successResponse(['events' => OltMonitoring::listEvents($limit, $filters)]);
            break;
        case 'settings':
            successResponse(['settings' => OltMonitoring::getSettings()]);
            break;
        case 'thresholds':
            successResponse(['thresholds' => OltMonitoring::getThresholds()]);
            break;
        default:
            errorResponse('Aksi tidak dikenali', 400);
    }
}

function handlePost(string $action): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    switch ($action) {
        case 'create_device':
            validateRequired($input, ['name', 'ip_address']);
            $deviceId = OltMonitoring::createDevice([
                'name' => trim($input['name']),
                'location' => $input['location'] ?? null,
                'ip_address' => trim($input['ip_address']),
                'port' => $input['port'] ?? 22,
                'username' => $input['username'] ?? null,
                'password' => $input['password'] ?? null,
                'device_type' => $input['device_type'] ?? 'epon',
                'polling_method' => $input['polling_method'] ?? 'ssh',
                'snmp_community' => $input['snmp_community'] ?? 'public',
                'snmp_version' => $input['snmp_version'] ?? 'v2c',
                'total_ports' => $input['total_ports'] ?? 4,
            ]);
            successResponse(['device_id' => $deviceId], 'Perangkat berhasil ditambahkan');
            break;
        case 'update_device':
            $id = (int) ($input['id'] ?? 0);
            if (!$id) {
                errorResponse('ID perangkat tidak valid', 400);
            }
            OltMonitoring::updateDevice($id, $input);
            successResponse([], 'Perangkat berhasil diperbarui');
            break;
        case 'delete_device':
            $id = (int) ($input['id'] ?? 0);
            if (!$id) {
                errorResponse('ID perangkat tidak valid', 400);
            }
            OltMonitoring::deleteDevice($id);
            successResponse([], 'Perangkat berhasil dihapus');
            break;
        case 'refresh_sample':
            $deviceId = isset($input['device_id']) ? (int) $input['device_id'] : null;
            $result = OltMonitoring::generateSampleData($deviceId);
            successResponse($result, 'Data sampel diperbarui');
            break;
        case 'save_settings':
            if (empty($input['settings']) || !is_array($input['settings'])) {
                errorResponse('Data pengaturan tidak valid', 400);
            }
            OltMonitoring::saveSettings($input['settings']);
            successResponse([], 'Pengaturan berhasil disimpan');
            break;
        case 'save_thresholds':
            if (empty($input['thresholds']) || !is_array($input['thresholds'])) {
                errorResponse('Data ambang batas tidak valid', 400);
            }
            OltMonitoring::saveThresholds($input['thresholds']);
            successResponse([], 'Ambang batas berhasil diperbarui');
            break;
        default:
            errorResponse('Aksi tidak dikenali', 400);
    }
}

function validateRequired(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            errorResponse("Field {$field} wajib diisi", 400);
        }
    }
}

