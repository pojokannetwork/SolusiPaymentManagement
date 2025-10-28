<?php
// LUSI Assistant API: Connects Ollama AI and WhatsApp queue/link
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.customers');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        handleStatus();
    } elseif ($method === 'POST') {
        handleChat();
    } else {
        errorResponse('Method not allowed', 405);
    }
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}

function handleStatus(): void
{
    $ollamaRunning = false;
    if (class_exists('OllamaAI')) {
        $ollamaRunning = OllamaAI::getInstance()->isRunning();
    }

    $waSummary = [];
    $waSessions = [];
    if (class_exists('WhatsAppQueue')) {
        try { $waSummary = WhatsAppQueue::getQueueSummary(); } catch (Throwable $e) { $waSummary = []; }
        try { $waSessions = WhatsAppQueue::getSessions(); } catch (Throwable $e) { $waSessions = []; }
    }

    successResponse([
        'ollama' => ['running' => (bool)$ollamaRunning],
        'whatsapp' => ['summary' => $waSummary, 'sessions' => $waSessions]
    ]);
}

function handleChat(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $message = trim((string)($input['message'] ?? ''));
    if ($message === '') {
        errorResponse('Message cannot be empty', 400);
    }

    $customerId = isset($input['customer_id']) ? (int)$input['customer_id'] : null;
    $rawPhone   = isset($input['phone']) ? (string)$input['phone'] : '';
    $send       = (bool)($input['send'] ?? false);

    $customerInfo = [];
    $phone = '';

    if ($customerId) {
        $customer = loadCustomer($customerId);
        if ($customer) {
            $customerInfo = $customer;
            $phone = (string)($customer['telp'] ?? '');
        }
    }

    if ($phone === '' && $rawPhone !== '') {
        $phone = $rawPhone;
    }

    $aiReply = '[LUSI is not available]';
    $aiOk = false;
    if (class_exists('OllamaAI')) {
        $ollama = OllamaAI::getInstance();
        $ai = $ollama->callCenterAssistant($message, $customerInfo);
        if (!empty($ai['success'])) {
            $aiReply = (string)($ai['message'] ?? '');
            $aiOk = true;
        } else {
            $aiReply = '[LUSI error] ' . (string)($ai['error'] ?? 'Unknown error');
        }
    } else {
        $aiReply = '[OllamaAI class not found]';
    }

    $preview = buildPreview($phone, $aiReply);

    $queued_id = null;
    if ($send && $preview && $preview['phone'] !== '') {
        if ($customerId && class_exists('WhatsAppNotificationService')) {
            // Queue as general message using existing helper
            $queued_id = WhatsAppNotificationService::queueGeneralMessage($customerId, $aiReply);
        } elseif (class_exists('WhatsAppQueue')) {
            // Direct enqueue by phone
            $queued_id = WhatsAppQueue::enqueue([
                'session_name' => 'default',
                'customer_id' => null,
                'phone' => $preview['phone'],
                'message' => $aiReply,
                'message_type' => 'lusi_reply',
                'meta' => ['source' => 'lusi_assistant']
            ]);
        }
    }

    successResponse([
        'ai_ok' => $aiOk,
        'ai_reply' => $aiReply,
        'preview' => $preview,
        'queued_id' => $queued_id
    ], 'LUSI response generated');
}

function loadCustomer(int $id): ?array
{
    global $db;
    return $db->fetchOne(
        "SELECT id, kode_pelanggan, nama, email, telp, alamat, paket, status, created_at FROM pelanggan WHERE id = ?",
        [$id]
    ) ?: null;
}

function buildPreview(string $rawPhone, string $message): array
{
    $phone = '';
    if ($rawPhone !== '' && class_exists('WhatsAppNotificationService')) {
        $phone = WhatsAppNotificationService::normalizePhone($rawPhone);
    }
    if ($phone === '' && $rawPhone !== '') {
        // basic normalization fallback (ID): 0xxxx -> 62xxxx, + -> strip
        $digits = preg_replace('/[^0-9+]/', '', $rawPhone) ?? '';
        if ($digits !== '') {
            if (strpos($digits, '+') === 0) $digits = substr($digits, 1);
            if (strpos($digits, '0') === 0) $digits = '62' . ltrim($digits, '0');
            $phone = trim($digits);
        }
    }

    $link = '';
    $qr = null;
    if ($phone !== '') {
        $link = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
        if (function_exists('generateQRCode')) {
            try { $qr = generateQRCode($link, 320); } catch (Throwable $e) { $qr = null; }
        }
    }

    return [
        'phone' => $phone,
        'message' => $message,
        'link' => $link,
        'qr' => $qr,
    ];
}
