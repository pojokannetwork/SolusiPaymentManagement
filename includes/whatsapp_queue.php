<?php

class WhatsAppQueue
{
    private static bool $schemaEnsured = false;

    public static function boot(): void
    {
        if (!self::$schemaEnsured) {
            self::ensureSchema();
            self::$schemaEnsured = true;
        }
    }

    private static function ensureSchema(): void
    {
        global $db;
        /** @var PDO $pdo */
        $pdo = $db->getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');

        $pdo->exec(self::schemaQueue($isSqlite));
        $pdo->exec(self::schemaSession($isSqlite));
        $pdo->exec(self::schemaLog($isSqlite));
    }

    private static function schemaQueue(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_name TEXT DEFAULT 'default',
    customer_id INTEGER,
    phone TEXT NOT NULL,
    message TEXT NOT NULL,
    message_type TEXT DEFAULT 'custom',
    meta_json TEXT,
    status TEXT DEFAULT 'pending',
    attempts INTEGER DEFAULT 0,
    last_error TEXT,
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL DEFAULT 'default',
    customer_id INT UNSIGNED NULL,
    phone VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(50) NOT NULL DEFAULT 'custom',
    meta_json JSON NULL,
    status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_session_status (session_name, status),
    KEY idx_scheduled_at (scheduled_at),
    KEY idx_customer (customer_id),
    KEY idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    private static function schemaSession(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_name TEXT UNIQUE NOT NULL,
    status TEXT DEFAULT 'disconnected',
    qr_base64 TEXT,
    info_json TEXT,
    error_message TEXT,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('disconnected','qr','connecting','ready','error') NOT NULL DEFAULT 'disconnected',
    qr_base64 LONGTEXT NULL,
    info_json JSON NULL,
    error_message TEXT NULL,
    last_seen DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    private static function schemaLog(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue_id INTEGER,
    level TEXT,
    message TEXT,
    context_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue_id BIGINT UNSIGNED NULL,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_queue (queue_id),
    KEY idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    }

    public static function enqueue(array $payload): int
    {
        self::boot();
        global $db;

        $session = $payload['session_name'] ?? 'default';
        $phone = WhatsAppNotificationService::normalizePhone($payload['phone'] ?? '');
        if ($phone === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        }

        $data = [
            'session_name' => $session,
            'customer_id' => $payload['customer_id'] ?? null,
            'phone' => $phone,
            'message' => $payload['message'] ?? '',
            'message_type' => $payload['message_type'] ?? 'custom',
            'meta_json' => !empty($payload['meta']) ? json_encode($payload['meta']) : null,
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'scheduled_at' => $payload['scheduled_at'] ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return (int) $db->insert(
            "INSERT INTO whatsapp_queue (session_name, customer_id, phone, message, message_type, meta_json, status, attempts, last_error, scheduled_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['session_name'],
                $data['customer_id'],
                $data['phone'],
                $data['message'],
                $data['message_type'],
                $data['meta_json'],
                $data['status'],
                $data['attempts'],
                $data['last_error'],
                $data['scheduled_at'],
                $data['created_at'],
                $data['updated_at'],
            ]
        );
    }

    public static function getQueueSummary(): array
    {
        self::boot();
        global $db;

        $rows = $db->fetchAll(
            "SELECT status, COUNT(*) AS total FROM whatsapp_queue GROUP BY status"
        );

        $summary = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($rows as $row) {
            $key = $row['status'] ?? 'pending';
            if (isset($summary[$key])) {
                $summary[$key] = (int) $row['total'];
            }
        }

        return $summary;
    }

    public static function getSessions(): array
    {
        self::boot();
        global $db;

        $sessions = $db->fetchAll(
            "SELECT * FROM whatsapp_sessions ORDER BY session_name"
        );

        foreach ($sessions as &$session) {
            $session['info'] = !empty($session['info_json']) ? json_decode($session['info_json'], true) : null;
            unset($session['info_json']);
        }

        return $sessions;
    }
}

