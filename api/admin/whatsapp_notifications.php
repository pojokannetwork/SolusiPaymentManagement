<?php
// Admin API - WhatsApp notification helper

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/whatsapp_notifications.php';

header('Content-Type: application/json; charset=utf-8');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? '') : '');

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            errorResponse('Method not allowed', 405);
    }
} catch (InvalidArgumentException $e) {
    errorResponse($e->getMessage(), 400);
} catch (Throwable $e) {
    error_log('[WhatsApp API] ' . $e->getMessage());
    errorResponse('Terjadi kesalahan saat memproses permintaan.', 500);
}

function handleGetRequest(string $action): void
{
    switch ($action) {
        case 'templates':
            successResponse([
                'templates' => WhatsAppNotificationService::getTemplates(),
                'placeholders' => WhatsAppNotificationService::getPlaceholders(),
            ]);
            return;

        case 'invoices':
            successResponse(['invoices' => getLatestInvoices()]);
            return;

        case 'preview':
            $params = $_GET;
            $type = $params['type'] ?? 'registration';
            $payload = buildPreview($type, $params);
            successResponse($payload, 'Preview berhasil dibuat');
            return;

        case 'queue_summary':
            successResponse([
                'summary' => WhatsAppQueue::getQueueSummary(),
                'sessions' => WhatsAppQueue::getSessions(),
                'pending' => getQueueItems('pending'),
                'failed' => getQueueItems('failed'),
            ]);
            return;

        default:
            errorResponse('Aksi GET tidak dikenali', 400);
    }
}

function handlePostRequest(string $action): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || empty($input)) {
        $input = $_POST;
    }

    switch ($action) {
        case 'save_templates':
            if (empty($input['templates']) || !is_array($input['templates'])) {
                throw new InvalidArgumentException('Data template tidak valid.');
            }
            WhatsAppNotificationService::saveTemplates($input['templates']);
            successResponse([], 'Template berhasil disimpan');
            return;

        case 'preview':
            $type = $input['type'] ?? 'registration';
            $payload = buildPreview($type, $input);
            successResponse($payload, 'Preview berhasil dibuat');
            return;

        case 'queue_registration':
            $customerId = (int) ($input['customer_id'] ?? 0);
            if ($customerId <= 0) {
                throw new InvalidArgumentException('Pilih pelanggan terlebih dahulu.');
            }
            $queueId = WhatsAppNotificationService::queueRegistrationMessage($customerId, $input['schedule_at'] ?? null);
            successResponse(['queue_id' => $queueId], 'Pesan registrasi dimasukkan ke antrean');
            return;

        case 'queue_due':
            $invoiceId = (int) ($input['invoice_id'] ?? 0);
            if ($invoiceId <= 0) {
                throw new InvalidArgumentException('Pilih invoice terlebih dahulu.');
            }
            $queueId = WhatsAppNotificationService::queueDueReminderMessage($invoiceId, $input['schedule_at'] ?? null);
            successResponse(['queue_id' => $queueId], 'Pengingat jatuh tempo dimasukkan ke antrean');
            return;

        case 'queue_general':
            $customerId = (int) ($input['customer_id'] ?? 0);
            $message = trim((string) ($input['custom_message'] ?? ''));
            if ($customerId <= 0) {
                throw new InvalidArgumentException('Pilih pelanggan terlebih dahulu.');
            }
            $queueId = WhatsAppNotificationService::queueGeneralMessage($customerId, $message, $input['schedule_at'] ?? null);
            successResponse(['queue_id' => $queueId], 'Pesan umum dimasukkan ke antrean');
            return;

        default:
            errorResponse('Aksi POST tidak dikenali', 400);
    }
}

function buildPreview(string $type, array $params): array
{
    switch ($type) {
        case 'registration':
            $customerId = (int) ($params['customer_id'] ?? 0);
            if ($customerId <= 0) {
                throw new InvalidArgumentException('Pilih pelanggan terlebih dahulu.');
            }
            return WhatsAppNotificationService::prepareRegistrationMessage($customerId);

        case 'due':
            $invoiceId = (int) ($params['invoice_id'] ?? 0);
            if ($invoiceId <= 0) {
                throw new InvalidArgumentException('Pilih invoice terlebih dahulu.');
            }
            return WhatsAppNotificationService::prepareDueReminder($invoiceId);

        case 'general':
            $customerId = (int) ($params['customer_id'] ?? 0);
            $customMessage = trim((string) ($params['custom_message'] ?? ''));
            if ($customerId <= 0) {
                throw new InvalidArgumentException('Pilih pelanggan terlebih dahulu.');
            }
            return WhatsAppNotificationService::prepareGeneralMessage($customerId, $customMessage);
    }

    throw new InvalidArgumentException('Jenis notifikasi tidak dikenali.');
}

function getLatestInvoices(): array
{
    global $db;

    $rows = $db->fetchAll(
        "SELECT f.id, f.nomor, f.total, f.jatuh_tempo, p.nama AS customer_name
         FROM faktur f
         JOIN pelanggan p ON p.id = f.pelanggan_id
         ORDER BY f.created_at DESC
         LIMIT 100"
    );

    return array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'nomor' => $row['nomor'],
            'customer_name' => $row['customer_name'],
            'total' => isset($row['total']) ? (float) $row['total'] : 0,
            'total_formatted' => isset($row['total']) ? formatCurrency((float) $row['total']) : formatCurrency(0),
            'jatuh_tempo' => $row['jatuh_tempo'],
            'jatuh_tempo_formatted' => $row['jatuh_tempo'] ? formatDateIndo('d F Y', $row['jatuh_tempo']) : '-',
        ];
    }, $rows);
}

function getQueueItems(string $status, int $limit = 10): array
{
    global $db;

    $rows = $db->fetchAll(
        "SELECT q.*, p.nama AS customer_name
         FROM whatsapp_queue q
         LEFT JOIN pelanggan p ON p.id = q.customer_id
         WHERE q.status = ?
         ORDER BY q.scheduled_at ASC
         LIMIT ?",
        [$status, $limit]
    );

    return array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'session_name' => $row['session_name'],
            'customer_id' => $row['customer_id'] ? (int) $row['customer_id'] : null,
            'customer_name' => $row['customer_name'] ?? null,
            'phone' => $row['phone'],
            'message' => $row['message'],
            'message_type' => $row['message_type'],
            'status' => $row['status'],
            'attempts' => (int) $row['attempts'],
            'scheduled_at' => $row['scheduled_at'],
            'last_error' => $row['last_error'],
        ];
    }, $rows);
}

function formatDateIndo(string $format, $time): string
{
    if ($time === null || $time === '') {
        return '';
    }

    if (!is_numeric($time)) {
        $timestamp = strtotime((string) $time);
    } else {
        $timestamp = (int) $time;
    }

    if (!$timestamp) {
        $timestamp = time();
    }

    $formatted = date($format, $timestamp);

    $monthsEn = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];
    $monthsId = [
        'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    ];

    return str_replace($monthsEn, $monthsId, $formatted);
}

