<?php
// WhatsApp Queue Worker: send queued messages via local QR gateway
// Run: php scripts/wa_queue_worker.php
require_once __DIR__ . '/../includes/bootstrap.php';

set_time_limit(120);

$gateway = rtrim(getSetting('wa_gateway_http_url', 'http://127.0.0.1:3001'), '/');

function http_json($method, $url, $payload = null, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload ?? []));
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return [false, "cURL error: $err", null, $code];
    $data = json_decode($resp, true);
    return [($code >= 200 && $code < 300), $resp, $data, $code];
}

list($ok, $raw, $status) = http_json('GET', $gateway . '/status');
if (!$ok) {
    echo "[WA Worker] Gateway not reachable: $raw\n";
    exit(1);
}
if (($status['status'] ?? '') !== 'ready') {
    echo "[WA Worker] Gateway not ready (status=" . ($status['status'] ?? 'unknown') . ")\n";
    exit(0);
}

// Ensure schema exists
if (class_exists('WhatsAppQueue')) { WhatsAppQueue::boot(); }

global $db;
$rows = $db->fetchAll(
    "SELECT * FROM whatsapp_queue WHERE status = 'pending' AND scheduled_at <= NOW() ORDER BY id ASC LIMIT 50"
);

if (!$rows) { echo "[WA Worker] No pending messages.\n"; exit(0); }

echo '[WA Worker] Sending ' . count($rows) . " messages...\n";

foreach ($rows as $q) {
    $id = (int)$q['id'];
    $phone = $q['phone'];
    $message = $q['message'];

    $db->execute("UPDATE whatsapp_queue SET status='processing', updated_at=NOW() WHERE id=? AND status='pending'", [$id]);

    list($sOk, $sRaw, $sData, $sCode) = http_json('POST', $gateway . '/send', [
        'to' => $phone,
        'message' => $message,
    ]);

    if ($sOk && !empty($sData['success'])) {
        $db->execute("UPDATE whatsapp_queue SET status='sent', sent_at=NOW(), updated_at=NOW(), last_error=NULL WHERE id=?", [$id]);
        $db->execute("INSERT INTO whatsapp_logs (queue_id, level, message, context_json, created_at) VALUES (?, 'info', 'sent', ?, NOW())",
            [$id, json_encode(['gateway_code' => $sCode])]);
        echo "[WA Worker] #{$id} sent\n";
    } else {
        $attempts = (int)$q['attempts'] + 1;
        $failed = $attempts >= 3;
        $db->execute("UPDATE whatsapp_queue SET status=?, attempts=?, last_error=?, updated_at=NOW() WHERE id=?",
            [$failed ? 'failed' : 'pending', $attempts, substr((string)$sRaw, 0, 500), $id]);
        $db->execute("INSERT INTO whatsapp_logs (queue_id, level, message, context_json, created_at) VALUES (?, 'error', 'send_failed', ?, NOW())",
            [$id, json_encode(['gateway_code' => $sCode, 'resp' => $sRaw])]);
        echo "[WA Worker] #{$id} failed (attempt {$attempts})\n";
    }
}
