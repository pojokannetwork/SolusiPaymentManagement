<?php
// OLT Monitoring utility functions and schema management

class OltMonitoring
{
    /** @var PDO|null */
    private static $pdo = null;

    /** @var bool */
    private static $schemaEnsured = false;

    /** @var array|null */
    private static $thresholdCache = null;

    /**
     * Initialise the module and ensure the database schema exists.
     */
    public static function boot(): void
    {
        if (self::$pdo === null) {
            global $db;
            if (!isset($db) || !method_exists($db, 'getConnection')) {
                throw new RuntimeException('Database connection not available for OLT Monitoring module.');
            }
            self::$pdo = $db->getConnection();
        }

        if (!self::$schemaEnsured) {
            self::ensureSchema();
            self::$schemaEnsured = true;
        }
    }

    /**
     * Return the underlying PDO connection.
     */
    private static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::boot();
        }

        return self::$pdo;
    }

    /**
     * Ensure all required tables and default data exist.
     */
    private static function ensureSchema(): void
    {
        $pdo = self::pdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');

        $pdo->exec(self::schemaSqlDevices($isSqlite));
        $pdo->exec(self::schemaSqlOnts($isSqlite));
        $pdo->exec(self::schemaSqlEvents($isSqlite));
        $pdo->exec(self::schemaSqlThresholds($isSqlite));
        $pdo->exec(self::schemaSqlSettings($isSqlite));

        self::seedDefaultThresholds($pdo);
        self::seedDefaultSettings($pdo);
    }

    private static function schemaSqlDevices(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS olt_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    location TEXT,
    ip_address TEXT NOT NULL UNIQUE,
    port INTEGER DEFAULT 22,
    username TEXT,
    password TEXT,
    device_type TEXT NOT NULL DEFAULT 'epon',
    status TEXT NOT NULL DEFAULT 'offline',
    polling_method TEXT NOT NULL DEFAULT 'ssh',
    snmp_community TEXT DEFAULT 'public',
    snmp_version TEXT DEFAULT 'v2c',
    total_ports INTEGER DEFAULT 4,
    last_poll DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS olt_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NULL,
    ip_address VARCHAR(100) NOT NULL UNIQUE,
    port INT UNSIGNED DEFAULT 22,
    username VARCHAR(100) NULL,
    password VARCHAR(255) NULL,
    device_type VARCHAR(30) NOT NULL DEFAULT 'epon',
    status VARCHAR(20) NOT NULL DEFAULT 'offline',
    polling_method VARCHAR(20) NOT NULL DEFAULT 'ssh',
    snmp_community VARCHAR(100) DEFAULT 'public',
    snmp_version VARCHAR(10) DEFAULT 'v2c',
    total_ports INT UNSIGNED DEFAULT 4,
    last_poll DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
SQL;
    }

    private static function schemaSqlOnts(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS olt_onts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    olt_id INTEGER NOT NULL,
    pon_port TEXT NOT NULL,
    ont_number TEXT NOT NULL,
    serial_number TEXT,
    mac_address TEXT,
    customer_name TEXT,
    status TEXT NOT NULL DEFAULT 'offline',
    rx_power REAL,
    tx_power REAL,
    distance REAL,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(olt_id, pon_port, ont_number),
    FOREIGN KEY (olt_id) REFERENCES olt_devices(id) ON DELETE CASCADE
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS olt_onts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id INT UNSIGNED NOT NULL,
    pon_port VARCHAR(50) NOT NULL,
    ont_number VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) NULL,
    mac_address VARCHAR(50) NULL,
    customer_name VARCHAR(150) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'offline',
    rx_power DECIMAL(6,2) NULL,
    tx_power DECIMAL(6,2) NULL,
    distance DECIMAL(8,2) NULL,
    last_seen DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ont (olt_id, pon_port, ont_number),
    KEY idx_olt_status (olt_id, status),
    CONSTRAINT fk_olt_device FOREIGN KEY (olt_id) REFERENCES olt_devices(id) ON DELETE CASCADE
)
SQL;
    }

    private static function schemaSqlEvents(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS olt_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    olt_id INTEGER NOT NULL,
    ont_id INTEGER,
    event_type TEXT NOT NULL,
    severity TEXT NOT NULL DEFAULT 'info',
    title TEXT NOT NULL,
    description TEXT,
    metadata TEXT,
    acknowledged INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olt_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (ont_id) REFERENCES olt_onts(id) ON DELETE SET NULL
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS olt_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id INT UNSIGNED NOT NULL,
    ont_id INT UNSIGNED NULL,
    event_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    metadata JSON NULL,
    acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_olt_events (olt_id, created_at),
    KEY idx_event_type (event_type),
    KEY idx_severity (severity),
    CONSTRAINT fk_event_olt FOREIGN KEY (olt_id) REFERENCES olt_devices(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_ont FOREIGN KEY (ont_id) REFERENCES olt_onts(id) ON DELETE SET NULL
)
SQL;
    }

    private static function schemaSqlThresholds(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS olt_thresholds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    metric TEXT NOT NULL,
    level TEXT NOT NULL,
    min_value REAL,
    max_value REAL,
    unit TEXT,
    severity TEXT NOT NULL DEFAULT 'info',
    enabled INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(metric, level)
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS olt_thresholds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric VARCHAR(30) NOT NULL,
    level VARCHAR(20) NOT NULL,
    min_value DECIMAL(8,2) NULL,
    max_value DECIMAL(8,2) NULL,
    unit VARCHAR(10) NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric_level (metric, level)
)
SQL;
    }

    private static function schemaSqlSettings(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS olt_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    category TEXT
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS olt_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    description TEXT NULL,
    category VARCHAR(50) NULL
)
SQL;
    }

    private static function seedDefaultThresholds(PDO $pdo): void
    {
        $defaults = [
            ['rx_power', 'safe', -25.0, -8.0, 'dBm', 'info'],
            ['rx_power', 'warning', -27.0, -25.0, 'dBm', 'warning'],
            ['rx_power', 'danger', null, -27.0, 'dBm', 'critical'],
            ['tx_power', 'safe', -2.0, 5.0, 'dBm', 'info'],
            ['tx_power', 'warning', -4.0, -2.0, 'dBm', 'warning'],
            ['tx_power', 'danger', null, -4.0, 'dBm', 'critical'],
            ['distance', 'safe', 0.0, 20.0, 'km', 'info'],
            ['distance', 'warning', 20.0, 25.0, 'km', 'warning'],
            ['distance', 'danger', 25.0, null, 'km', 'critical'],
        ];

        $select = $pdo->prepare('SELECT COUNT(*) FROM olt_thresholds WHERE metric = ? AND level = ?');
        $insert = $pdo->prepare('INSERT INTO olt_thresholds (metric, level, min_value, max_value, unit, severity) VALUES (?, ?, ?, ?, ?, ?)');

        foreach ($defaults as $row) {
            $select->execute([$row[0], $row[1]]);
            $exists = (int) $select->fetchColumn();
            if ($exists === 0) {
                $insert->execute($row);
            }
        }
    }

    private static function seedDefaultSettings(PDO $pdo): void
    {
        $defaults = [
            ['polling_interval', '300', 'Polling interval in seconds', 'polling'],
            ['polling_enabled', '1', 'Enable or disable automatic polling', 'polling'],
            ['notifications_enabled', '0', 'Enable Telegram notifications', 'notifications'],
            ['telegram_bot_token', '', 'Telegram bot token', 'notifications'],
            ['telegram_chat_id', '', 'Default Telegram chat ID', 'notifications'],
            ['system_name', 'OLT Monitoring System', 'Display name for the monitoring module', 'general'],
        ];

        $select = $pdo->prepare('SELECT COUNT(*) FROM olt_settings WHERE setting_key = ?');
        $insert = $pdo->prepare('INSERT INTO olt_settings (setting_key, setting_value, description, category) VALUES (?, ?, ?, ?)');

        foreach ($defaults as $row) {
            $select->execute([$row[0]]);
            $exists = (int) $select->fetchColumn();
            if ($exists === 0) {
                $insert->execute($row);
            }
        }
    }

    /**
     * Fetch aggregated statistics for the dashboard.
     */
    public static function getSummary(): array
    {
        self::boot();
        $pdo = self::pdo();

        $summary = [
            'olts' => ['total' => 0, 'online' => 0, 'offline' => 0],
            'onts' => ['total' => 0, 'online' => 0, 'offline' => 0, 'los' => 0],
            'warnings' => ['power' => 0, 'distance' => 0],
            'recent_events' => []
        ];

        $summary['olts']['total'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_devices")->fetchColumn();
        $summary['olts']['online'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_devices WHERE status = 'online'")->fetchColumn();
        $summary['olts']['offline'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_devices WHERE status != 'online'")->fetchColumn();

        $summary['onts']['total'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_onts")->fetchColumn();
        $summary['onts']['online'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_onts WHERE status = 'online'")->fetchColumn();
        $summary['onts']['offline'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_onts WHERE status = 'offline'")->fetchColumn();
        $summary['onts']['los'] = (int) $pdo->query("SELECT COUNT(*) FROM olt_onts WHERE status = 'los'")->fetchColumn();

        $ranges = self::getThresholdMatrix();

        // Power warnings: outside safe range
        if (isset($ranges['rx_power']['safe'])) {
            $safe = $ranges['rx_power']['safe'];
            $danger = $ranges['rx_power']['danger'] ?? null;

            $conditions = [];
            $params = [];

            if ($safe['min'] !== null) {
                $conditions[] = 'rx_power < ?';
                $params[] = $safe['min'];
            }
            if ($safe['max'] !== null) {
                $conditions[] = 'rx_power > ?';
                $params[] = $safe['max'];
            }

            if ($danger && $danger['max'] !== null) {
                $conditions[] = 'rx_power < ?';
                $params[] = $danger['max'];
            }

            if (!empty($conditions)) {
                $sql = 'SELECT COUNT(*) FROM olt_onts WHERE rx_power IS NOT NULL AND (' . implode(' OR ', $conditions) . ')';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $summary['warnings']['power'] = (int) $stmt->fetchColumn();
            }
        }

        // Distance warnings: above warning max
        if (isset($ranges['distance']['warning'])) {
            $warning = $ranges['distance']['warning'];
            $params = [];
            $conditions = [];

            if ($warning['max'] !== null) {
                $conditions[] = 'distance > ?';
                $params[] = $warning['max'];
            } elseif ($warning['min'] !== null) {
                $conditions[] = 'distance > ?';
                $params[] = $warning['min'];
            }

            if (!empty($conditions)) {
                $sql = 'SELECT COUNT(*) FROM olt_onts WHERE distance IS NOT NULL AND (' . implode(' OR ', $conditions) . ')';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $summary['warnings']['distance'] = (int) $stmt->fetchColumn();
            }
        }

        $stmt = $pdo->prepare("
            SELECT e.*, d.name AS olt_name, o.pon_port, o.ont_number
            FROM olt_events e
            JOIN olt_devices d ON e.olt_id = d.id
            LEFT JOIN olt_onts o ON e.ont_id = o.id
            ORDER BY e.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $summary['recent_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $summary;
    }

    /**
     * Return the available devices.
     */
    public static function listDevices(): array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->query("
            SELECT d.*,
                (SELECT COUNT(*) FROM olt_onts o WHERE o.olt_id = d.id) AS total_onts,
                (SELECT COUNT(*) FROM olt_onts o WHERE o.olt_id = d.id AND o.status = 'online') AS online_onts,
                (SELECT COUNT(*) FROM olt_onts o WHERE o.olt_id = d.id AND o.status = 'los') AS los_onts
            FROM olt_devices d
            ORDER BY d.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get device by ID.
     */
    public static function getDevice(int $id): ?array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("SELECT * FROM olt_devices WHERE id = ?");
        $stmt->execute([$id]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        return $device ?: null;
    }

    /**
     * Create a device entry.
     * @return int New device ID
     */
    public static function createDevice(array $data): int
    {
        self::boot();
        $pdo = self::pdo();

        $now = self::now();
        $stmt = $pdo->prepare("
            INSERT INTO olt_devices
            (name, location, ip_address, port, username, password, device_type, status, polling_method, snmp_community, snmp_version, total_ports, created_at, updated_at)
            VALUES (:name, :location, :ip_address, :port, :username, :password, :device_type, :status, :polling_method, :snmp_community, :snmp_version, :total_ports, :created_at, :updated_at)
        ");

        $stmt->execute([
            ':name' => $data['name'],
            ':location' => $data['location'] ?? null,
            ':ip_address' => $data['ip_address'],
            ':port' => (int) ($data['port'] ?? 22),
            ':username' => $data['username'] ?? null,
            ':password' => $data['password'] ?? null,
            ':device_type' => $data['device_type'] ?? 'epon',
            ':status' => $data['status'] ?? 'offline',
            ':polling_method' => $data['polling_method'] ?? 'ssh',
            ':snmp_community' => $data['snmp_community'] ?? 'public',
            ':snmp_version' => $data['snmp_version'] ?? 'v2c',
            ':total_ports' => (int) ($data['total_ports'] ?? 4),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update a device record.
     */
    public static function updateDevice(int $id, array $data): void
    {
        self::boot();
        $pdo = self::pdo();

        $fields = [];
        $params = [];

        $mapping = [
            'name' => 'name',
            'location' => 'location',
            'ip_address' => 'ip_address',
            'port' => 'port',
            'username' => 'username',
            'password' => 'password',
            'device_type' => 'device_type',
            'status' => 'status',
            'polling_method' => 'polling_method',
            'snmp_community' => 'snmp_community',
            'snmp_version' => 'snmp_version',
            'total_ports' => 'total_ports',
            'last_poll' => 'last_poll',
        ];

        foreach ($mapping as $inputKey => $column) {
            if (array_key_exists($inputKey, $data)) {
                $fields[] = "{$column} = :{$column}";
                if ($column === 'port' || $column === 'total_ports') {
                    $params[":{$column}"] = (int) $data[$inputKey];
                } elseif ($column === 'last_poll') {
                    $params[":{$column}"] = $data[$inputKey] ?: self::now();
                } else {
                    $params[":{$column}"] = $data[$inputKey];
                }
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = self::now();
        $params[':id'] = $id;

        $sql = "UPDATE olt_devices SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Delete a device and its related ONTs/events.
     */
    public static function deleteDevice(int $id): void
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("DELETE FROM olt_devices WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * List ONTs with optional filtering.
     */
    public static function listOnts(array $filters = []): array
    {
        self::boot();
        $pdo = self::pdo();

        $conditions = [];
        $params = [];

        if (!empty($filters['olt_id'])) {
            $conditions[] = 'o.olt_id = :olt_id';
            $params[':olt_id'] = (int) $filters['olt_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'o.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(o.customer_name LIKE :search OR o.pon_port LIKE :search OR o.ont_number LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "
            SELECT o.*, d.name AS olt_name, d.ip_address AS olt_ip
            FROM olt_onts o
            JOIN olt_devices d ON o.olt_id = d.id
            {$where}
            ORDER BY o.updated_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the most recent events.
     */
    public static function listEvents(int $limit = 50, array $filters = []): array
    {
        self::boot();
        $pdo = self::pdo();

        $conditions = [];
        $params = [];

        if (!empty($filters['severity'])) {
            $conditions[] = 'e.severity = :severity';
            $params[':severity'] = $filters['severity'];
        }

        if (!empty($filters['olt_id'])) {
            $conditions[] = 'e.olt_id = :olt_id';
            $params[':olt_id'] = (int) $filters['olt_id'];
        }

        if (!empty($filters['since'])) {
            $conditions[] = 'e.created_at >= :since';
            $params[':since'] = $filters['since'];
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "
            SELECT e.*, d.name AS olt_name, o.pon_port, o.ont_number
            FROM olt_events e
            JOIN olt_devices d ON e.olt_id = d.id
            LEFT JOIN olt_onts o ON e.ont_id = o.id
            {$where}
            ORDER BY e.created_at DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate mock/sample data to keep the dashboard populated.
     */
    public static function generateSampleData(?int $deviceId = null): array
    {
        self::boot();
        $pdo = self::pdo();

        $devices = [];
        if ($deviceId !== null) {
            $device = self::getDevice($deviceId);
            if ($device) {
                $devices[] = $device;
            }
        } else {
            $devices = self::listDevices();
        }

        if (empty($devices)) {
            return ['processed' => 0, 'created_onts' => 0, 'updated_onts' => 0];
        }

        $created = 0;
        $updated = 0;

        foreach ($devices as $device) {
            $ports = self::buildPortList((int) $device['total_ports']);
            foreach ($ports as $portName) {
                $ontCount = random_int(2, 6);
                for ($i = 1; $i <= $ontCount; $i++) {
                    $ontNumber = (string) $i;
                    $existing = self::findOnt($device['id'], $portName, $ontNumber);
                    $payload = self::generateOntSample($device, $portName, $ontNumber);

                    if ($existing) {
                        self::updateOnt($existing['id'], $payload, $existing);
                        $updated++;
                    } else {
                        self::createOnt($device['id'], $payload);
                        $created++;
                    }
                }
            }

            self::updateDevice($device['id'], [
                'status' => 'online',
                'last_poll' => self::now()
            ]);
        }

        return ['processed' => count($devices), 'created_onts' => $created, 'updated_onts' => $updated];
    }

    /**
     * Retrieve configuration settings.
     */
    public static function getSettings(): array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->query('SELECT setting_key, setting_value, description, category FROM olt_settings');
        $settings = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = $row;
        }
        return $settings;
    }

    /**
     * Save settings key/value pairs.
     */
    public static function saveSettings(array $settings): void
    {
        self::boot();
        $pdo = self::pdo();

        $update = $pdo->prepare('UPDATE olt_settings SET setting_value = :value WHERE setting_key = :key');
        $insert = $pdo->prepare('INSERT INTO olt_settings (setting_key, setting_value) VALUES (:key, :value)');

        foreach ($settings as $key => $value) {
            $update->execute([
                ':key' => $key,
                ':value' => $value,
            ]);

            if ($update->rowCount() === 0) {
                $insert->execute([
                    ':key' => $key,
                    ':value' => $value,
                ]);
            }
        }
    }

    /**
     * Return thresholds keyed by metric/level.
     */
    public static function getThresholds(): array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->query('SELECT * FROM olt_thresholds ORDER BY metric, level');
        $thresholds = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $metric = $row['metric'];
            $level = $row['level'];
            unset($row['metric'], $row['level']);
            $thresholds[$metric][$level] = $row;
        }

        return $thresholds;
    }

    /**
     * Persist threshold definitions.
     */
    public static function saveThresholds(array $thresholds): void
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare('UPDATE olt_thresholds SET min_value = :min_value, max_value = :max_value, severity = :severity, enabled = :enabled, unit = :unit, updated_at = :updated_at WHERE metric = :metric AND level = :level');

        foreach ($thresholds as $metric => $levels) {
            foreach ($levels as $level => $values) {
                $stmt->execute([
                    ':min_value' => $values['min_value'] ?? null,
                    ':max_value' => $values['max_value'] ?? null,
                    ':severity' => $values['severity'] ?? 'info',
                    ':enabled' => isset($values['enabled']) ? (int) (bool) $values['enabled'] : 1,
                    ':unit' => $values['unit'] ?? null,
                    ':updated_at' => self::now(),
                    ':metric' => $metric,
                    ':level' => $level,
                ]);
            }
        }

        self::$thresholdCache = null;
    }

    /**
     * Build threshold matrix for quick access.
     */
    private static function getThresholdMatrix(): array
    {
        if (self::$thresholdCache !== null) {
            return self::$thresholdCache;
        }

        $matrix = [];
        $thresholds = self::getThresholds();
        foreach ($thresholds as $metric => $levels) {
            foreach ($levels as $level => $row) {
                $matrix[$metric][$level] = [
                    'min' => $row['min_value'] !== null ? (float) $row['min_value'] : null,
                    'max' => $row['max_value'] !== null ? (float) $row['max_value'] : null,
                    'severity' => $row['severity'] ?? 'info',
                ];
            }
        }

        self::$thresholdCache = $matrix;
        return self::$thresholdCache;
    }

    /**
     * Generate a list of logical port labels based on the configured port count.
     */
    private static function buildPortList(int $totalPorts): array
    {
        $totalPorts = max(1, min($totalPorts, 16));
        $ports = [];
        for ($i = 1; $i <= $totalPorts; $i++) {
            $ports[] = 'PON-' . $i;
        }
        return $ports;
    }

    /**
     * Locate an ONT record.
     */
    private static function findOnt(int $oltId, string $port, string $ontNumber): ?array
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM olt_onts WHERE olt_id = ? AND pon_port = ? AND ont_number = ?");
        $stmt->execute([$oltId, $port, $ontNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create an ONT record.
     */
    private static function createOnt(int $oltId, array $data): void
    {
        $pdo = self::pdo();
        $now = self::now();
        $stmt = $pdo->prepare("
            INSERT INTO olt_onts
            (olt_id, pon_port, ont_number, serial_number, mac_address, customer_name, status, rx_power, tx_power, distance, last_seen, created_at, updated_at)
            VALUES (:olt_id, :pon_port, :ont_number, :serial_number, :mac_address, :customer_name, :status, :rx_power, :tx_power, :distance, :last_seen, :created_at, :updated_at)
        ");

        $stmt->execute([
            ':olt_id' => $oltId,
            ':pon_port' => $data['pon_port'],
            ':ont_number' => $data['ont_number'],
            ':serial_number' => $data['serial_number'],
            ':mac_address' => $data['mac_address'],
            ':customer_name' => $data['customer_name'],
            ':status' => $data['status'],
            ':rx_power' => $data['rx_power'],
            ':tx_power' => $data['tx_power'],
            ':distance' => $data['distance'],
            ':last_seen' => $data['last_seen'],
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $ontId = (int) self::pdo()->lastInsertId();
        self::recordStatusEvent($oltId, $ontId, null, $data['status'], $data['pon_port'], $data['ont_number']);
    }

    /**
     * Update an existing ONT record.
     */
    private static function updateOnt(int $ontId, array $data, array $previous): void
    {
        $pdo = self::pdo();
        $now = self::now();
        $stmt = $pdo->prepare("
            UPDATE olt_onts
            SET serial_number = :serial_number,
                mac_address = :mac_address,
                customer_name = :customer_name,
                status = :status,
                rx_power = :rx_power,
                tx_power = :tx_power,
                distance = :distance,
                last_seen = :last_seen,
                updated_at = :updated_at
            WHERE id = :id
        ");

        $stmt->execute([
            ':serial_number' => $data['serial_number'],
            ':mac_address' => $data['mac_address'],
            ':customer_name' => $data['customer_name'],
            ':status' => $data['status'],
            ':rx_power' => $data['rx_power'],
            ':tx_power' => $data['tx_power'],
            ':distance' => $data['distance'],
            ':last_seen' => $data['last_seen'],
            ':updated_at' => $now,
            ':id' => $ontId,
        ]);

        if ($previous['status'] !== $data['status']) {
            self::recordStatusEvent(
                (int) $previous['olt_id'],
                $ontId,
                $previous['status'],
                $data['status'],
                $previous['pon_port'],
                $previous['ont_number']
            );
        }

        self::recordThresholdEvents((int) $previous['olt_id'], $ontId, $data, $previous);
    }

    /**
     * Generate a randomised ONT payload for demonstration purposes.
     */
    private static function generateOntSample(array $device, string $port, string $ontNumber): array
    {
        $statusRoll = random_int(1, 100);
        if ($statusRoll <= 80) {
            $status = 'online';
        } elseif ($statusRoll <= 90) {
            $status = 'offline';
        } else {
            $status = 'los';
        }

        $rxPower = null;
        $txPower = null;
        $distance = null;
        $lastSeen = null;

        if ($status === 'online') {
            $rxPower = round(-15 + (mt_rand(-700, 700) / 100), 2);
            $txPower = round(2 + (mt_rand(-300, 300) / 100), 2);
            $distance = round(mt_rand(200, 2500) / 100, 2);
            $lastSeen = self::now();
        }

        return [
            'pon_port' => $port,
            'ont_number' => $ontNumber,
            'serial_number' => self::generateSerial($device['device_type'], $port, $ontNumber),
            'mac_address' => self::generateMac(),
            'customer_name' => 'ONT ' . $port . '-' . $ontNumber,
            'status' => $status,
            'rx_power' => $rxPower,
            'tx_power' => $txPower,
            'distance' => $distance,
            'last_seen' => $lastSeen,
        ];
    }

    /**
     * Record status change events.
     */
    private static function recordStatusEvent(int $oltId, int $ontId, ?string $oldStatus, string $newStatus, string $port, string $ontNumber): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $events = [
            'online' => ['ont_online', 'info', 'ONT Online'],
            'offline' => ['ont_offline', 'warning', 'ONT Offline'],
            'los' => ['ont_los', 'critical', 'Loss of Signal detected'],
        ];

        $event = $events[$newStatus] ?? null;
        if (!$event) {
            return;
        }

        self::storeEvent($oltId, $ontId, $event[0], $event[1], $event[2], sprintf('ONT %s/%s status: %s', $port, $ontNumber, strtoupper($newStatus)));
    }

    /**
     * Record threshold related events (power/distance).
     */
    private static function recordThresholdEvents(int $oltId, int $ontId, array $current, array $previous): void
    {
        $matrix = self::getThresholdMatrix();

        if ($current['status'] !== 'online') {
            return;
        }

        if ($current['rx_power'] !== null && isset($matrix['rx_power']['danger'])) {
            $danger = $matrix['rx_power']['danger'];
            if ($danger['max'] !== null && $current['rx_power'] < $danger['max']) {
                self::storeEvent(
                    $oltId,
                    $ontId,
                    'power_warning',
                    'critical',
                    'RX Power Critical',
                    sprintf('RX power %.2f dBm below danger threshold %.2f dBm', $current['rx_power'], $danger['max'])
                );
            }
        }

        if ($current['distance'] !== null && isset($matrix['distance']['danger'])) {
            $danger = $matrix['distance']['danger'];
            $limit = $danger['min'] ?? $danger['max'] ?? null;
            if ($limit !== null && $current['distance'] > $limit) {
                self::storeEvent(
                    $oltId,
                    $ontId,
                    'distance_warning',
                    'warning',
                    'Distance Warning',
                    sprintf('Distance %.2f km above threshold %.2f km', $current['distance'], $limit)
                );
            }
        }
    }

    /**
     * Persist an event row.
     */
    private static function storeEvent(int $oltId, ?int $ontId, string $type, string $severity, string $title, string $description): void
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO olt_events (olt_id, ont_id, event_type, severity, title, description, created_at)
            VALUES (:olt_id, :ont_id, :event_type, :severity, :title, :description, :created_at)
        ");
        $stmt->execute([
            ':olt_id' => $oltId,
            ':ont_id' => $ontId,
            ':event_type' => $type,
            ':severity' => $severity,
            ':title' => $title,
            ':description' => $description,
            ':created_at' => self::now(),
        ]);
    }

    /**
     * Generate a pseudo MAC address.
     */
    private static function generateMac(): string
    {
        $octets = [];
        for ($i = 0; $i < 6; $i++) {
            $octets[] = strtoupper(str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT));
        }
        return implode(':', $octets);
    }

    /**
     * Generate a readable serial per device type.
     */
    private static function generateSerial(string $deviceType, string $port, string $ontNumber): string
    {
        $prefix = strtoupper(substr($deviceType, 0, 3));
        $portNum = preg_replace('/\D/', '', $port);
        return sprintf('%s-%02d-%04d-%03d', $prefix, (int) $portNum, random_int(1000, 9999), (int) $ontNumber);
    }

    /**
     * Simple timestamp helper.
     */
    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
