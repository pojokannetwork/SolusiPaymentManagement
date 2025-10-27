<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/fiber_management.php';

header('Content-Type: application/json; charset=utf-8');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.assets'); // reuse asset management privilege

FiberManagement::boot();

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
            errorResponse('Method tidak diizinkan', 405);
    }
} catch (InvalidArgumentException $e) {
    errorResponse($e->getMessage(), 400);
} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 500);
} catch (Throwable $e) {
    error_log('[Fiber API] ' . $e->getMessage());
    errorResponse('Terjadi kesalahan internal', 500);
}

function handleGet(string $action): void
{
    switch ($action) {
        case 'summary':
            successResponse(FiberManagement::getSummary());
            break;
        case 'closures':
            $filters = [
                'search' => $_GET['search'] ?? null,
                'label' => $_GET['label'] ?? null,
            ];
            successResponse(['closures' => FiberManagement::listClosures($filters)]);
            break;
        case 'closure':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                errorResponse('ID closure tidak valid', 400);
            }
            $closure = FiberManagement::getClosure($id);
            if (!$closure) {
                errorResponse('Data tidak ditemukan', 404);
            }
            successResponse(['closure' => $closure]);
            break;
        case 'connections':
            $filters = [
                'closure_id' => $_GET['closure_id'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];
            successResponse(['connections' => FiberManagement::listConnections($filters)]);
            break;
        case 'connection':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                errorResponse('ID koneksi tidak valid', 400);
            }
            $connection = FiberManagement::getConnection($id);
            if (!$connection) {
                errorResponse('Data tidak ditemukan', 404);
            }
            successResponse(['connection' => $connection]);
            break;
        default:
            errorResponse('Aksi tidak dikenali', 400);
    }
}

function handlePost(string $action): void
{
    switch ($action) {
        case 'create_closure':
            $payload = collectInput();
            validateRequired($payload, ['name']);
            if (FiberManagement::closureNameExists($payload['name'])) {
                errorResponse('Nama joint closure sudah digunakan', 400);
            }
            if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $payload['photo_path'] = FiberManagement::storeUploadedPhoto($_FILES['photo']);
            }
            $payload['created_by'] = getCurrentUser()['id'] ?? null;
            $closureId = FiberManagement::createClosure($payload);
            successResponse(['closure_id' => $closureId], 'Joint closure berhasil ditambahkan');
            break;

        case 'update_closure':
            $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                errorResponse('ID closure tidak valid', 400);
            }
            $existing = FiberManagement::getClosure($id);
            if (!$existing) {
                errorResponse('Data tidak ditemukan', 404);
            }
            $payload = collectInput();
            if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $payload['photo_path'] = FiberManagement::storeUploadedPhoto($_FILES['photo']);
            }
            if (!empty($payload['name']) && FiberManagement::closureNameExists($payload['name'], $id)) {
                errorResponse('Nama joint closure sudah digunakan', 400);
            }
            FiberManagement::updateClosure($id, $payload);
            successResponse([], 'Joint closure diperbarui');
            break;

        case 'delete_closure':
            $id = (int) (collectInput()['id'] ?? 0);
            if (!$id) {
                errorResponse('ID closure tidak valid', 400);
            }
            FiberManagement::deleteClosure($id);
            successResponse([], 'Joint closure dihapus');
            break;

        case 'create_connection':
            $payload = collectInput();
            validateRequired($payload, ['closure_id']);
            $connectionId = FiberManagement::createConnection($payload);
            successResponse(['connection_id' => $connectionId], 'Koneksi core berhasil ditambahkan');
            break;

        case 'update_connection':
            $payload = collectInput();
            $id = (int) ($payload['id'] ?? 0);
            if (!$id) {
                errorResponse('ID koneksi tidak valid', 400);
            }
            unset($payload['id']);
            FiberManagement::updateConnection($id, $payload);
            successResponse([], 'Koneksi core diperbarui');
            break;

        case 'delete_connection':
            $id = (int) (collectInput()['id'] ?? 0);
            if (!$id) {
                errorResponse('ID koneksi tidak valid', 400);
            }
            FiberManagement::deleteConnection($id);
            successResponse([], 'Koneksi core dihapus');
            break;

        default:
            errorResponse('Aksi tidak dikenali', 400);
    }
}

function collectInput(): array
{
    $input = $_POST;
    if (empty($input)) {
        $json = json_decode(file_get_contents('php://input'), true);
        if (is_array($json)) {
            $input = $json;
        }
    }
    return $input;
}

function validateRequired(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '') {
            errorResponse("Field {$field} wajib diisi", 400);
        }
    }
}
