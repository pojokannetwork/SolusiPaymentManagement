<?php
// Fiber Optic Management utility helpers

class FiberManagement
{
    /** @var PDO|null */
    private static $pdo = null;

    /** @var bool */
    private static $schemaEnsured = false;

    public static function boot(): void
    {
        if (self::$pdo === null) {
            global $db;
            if (!isset($db) || !method_exists($db, 'getConnection')) {
                throw new RuntimeException('Database connection not available for Fiber Management module.');
            }
            self::$pdo = $db->getConnection();
        }

        if (!self::$schemaEnsured) {
            self::ensureSchema();
            self::$schemaEnsured = true;
        }
    }

    public static function closureNameExists(string $name, ?int $excludeId = null): bool
    {
        self::boot();
        $pdo = self::pdo();

        $sql = "SELECT COUNT(*) FROM fiber_joint_closures WHERE LOWER(name) = LOWER(:name)";
        $params = [':name' => $name];
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    private static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::boot();
        }
        return self::$pdo;
    }

    private static function ensureSchema(): void
    {
        $pdo = self::pdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = ($driver === 'sqlite');

        $pdo->exec(self::schemaClosures($isSqlite));
        $pdo->exec(self::schemaConnections($isSqlite));

        if ($isSqlite) {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_fiber_closures_name ON fiber_joint_closures (name)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_fiber_connections_closure ON fiber_core_connections (closure_id)");
        } else {
            self::ensureIndex($pdo, "CREATE INDEX idx_fiber_closures_name ON fiber_joint_closures (name(100))");
            self::ensureIndex($pdo, "CREATE INDEX idx_fiber_connections_closure ON fiber_core_connections (closure_id)");
        }
    }

    private static function schemaClosures(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS fiber_joint_closures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    address TEXT,
    latitude REAL,
    longitude REAL,
    altitude REAL,
    description TEXT,
    photo_path TEXT,
    labels TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS fiber_joint_closures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    latitude DECIMAL(10,6) NULL,
    longitude DECIMAL(10,6) NULL,
    altitude DECIMAL(8,2) NULL,
    description TEXT NULL,
    photo_path VARCHAR(255) NULL,
    labels JSON NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_fiber_closures_latlng (latitude, longitude)
)
SQL;
    }

    private static function ensureIndex(PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            $duplicateCodes = ['1061', '42000'];
            foreach ($duplicateCodes as $code) {
                if (strpos($e->getMessage(), $code) !== false) {
                    return;
                }
            }
            throw $e;
        }
    }

    private static function schemaConnections(bool $isSqlite): string
    {
        if ($isSqlite) {
            return <<<SQL
CREATE TABLE IF NOT EXISTS fiber_core_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    closure_id INTEGER NOT NULL,
    tube_source TEXT,
    core_source TEXT,
    tube_dest TEXT,
    core_dest TEXT,
    network_name TEXT,
    attenuation_before REAL,
    attenuation_after REAL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (closure_id) REFERENCES fiber_joint_closures(id) ON DELETE CASCADE
)
SQL;
        }

        return <<<SQL
CREATE TABLE IF NOT EXISTS fiber_core_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    closure_id INT UNSIGNED NOT NULL,
    tube_source VARCHAR(50) NULL,
    core_source VARCHAR(50) NULL,
    tube_dest VARCHAR(50) NULL,
    core_dest VARCHAR(50) NULL,
    network_name VARCHAR(150) NULL,
    attenuation_before DECIMAL(6,2) NULL,
    attenuation_after DECIMAL(6,2) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fiber_connection_closure FOREIGN KEY (closure_id) REFERENCES fiber_joint_closures(id) ON DELETE CASCADE
)
SQL;
    }

    public static function getUploadDirectory(): string
    {
        $dir = __DIR__ . '/../assets/uploads/fiber';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $real = realpath($dir);
        return $real !== false ? $real : $dir;
    }

    public static function listClosures(array $filters = []): array
    {
        self::boot();
        $pdo = self::pdo();

        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE :search OR address LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['label'])) {
            $conditions[] = '(labels LIKE :label)';
            $params[':label'] = '%"'. $filters['label'] .'"%';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT c.*, 
                (SELECT COUNT(*) FROM fiber_core_connections fc WHERE fc.closure_id = c.id) AS connection_count,
                (SELECT AVG(attenuation_after) FROM fiber_core_connections fc WHERE fc.closure_id = c.id AND attenuation_after IS NOT NULL) AS avg_attenuation
            FROM fiber_joint_closures c
            {$where}
            ORDER BY c.updated_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item['photo_url'] = self::resolvePhotoUrl($item['photo_path'] ?? null);
            $item['labels'] = self::decodeLabels($item['labels'] ?? null);
        }

        return $items;
    }

    public static function getClosure(int $id): ?array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("
            SELECT c.*,
                COALESCE((SELECT COUNT(*) FROM fiber_core_connections fc WHERE fc.closure_id = c.id), 0) AS connection_count
            FROM fiber_joint_closures c
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $closure = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$closure) {
            return null;
        }

        $closure['photo_url'] = self::resolvePhotoUrl($closure['photo_path'] ?? null);
        $closure['labels'] = self::decodeLabels($closure['labels'] ?? null);

        return $closure;
    }

    public static function createClosure(array $data): int
    {
        self::boot();
        $pdo = self::pdo();

        $sql = "
            INSERT INTO fiber_joint_closures
            (name, address, latitude, longitude, altitude, description, photo_path, labels, created_by, created_at, updated_at)
            VALUES (:name, :address, :latitude, :longitude, :altitude, :description, :photo_path, :labels, :created_by, :created_at, :updated_at)
        ";

        $now = self::now();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':address' => $data['address'] ?? null,
            ':latitude' => self::normalizeFloat($data['latitude'] ?? null),
            ':longitude' => self::normalizeFloat($data['longitude'] ?? null),
            ':altitude' => self::normalizeFloat($data['altitude'] ?? null),
            ':description' => $data['description'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':labels' => self::encodeLabels($data['labels'] ?? []),
            ':created_by' => $data['created_by'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateClosure(int $id, array $data): void
    {
        self::boot();
        $pdo = self::pdo();

        $fields = [];
        $params = [':id' => $id];

        $mapping = [
            'name' => 'name',
            'address' => 'address',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'altitude' => 'altitude',
            'description' => 'description',
            'photo_path' => 'photo_path',
        ];

        foreach ($mapping as $key => $column) {
            if (array_key_exists($key, $data)) {
                $fields[] = "{$column} = :{$column}";
                if (in_array($column, ['latitude', 'longitude', 'altitude'], true)) {
                    $params[":{$column}"] = self::normalizeFloat($data[$key]);
                } else {
                    $params[":{$column}"] = $data[$key];
                }
            }
        }

        if (array_key_exists('labels', $data)) {
            $fields[] = "labels = :labels";
            $params[':labels'] = self::encodeLabels($data['labels']);
        }

        if (!$fields) {
            return;
        }

        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = self::now();

        $sql = "UPDATE fiber_joint_closures SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public static function deleteClosure(int $id): void
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("DELETE FROM fiber_joint_closures WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public static function listConnections(array $filters = []): array
    {
        self::boot();
        $pdo = self::pdo();

        $conditions = [];
        $params = [];

        if (!empty($filters['closure_id'])) {
            $conditions[] = 'fc.closure_id = :closure_id';
            $params[':closure_id'] = (int) $filters['closure_id'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(fc.network_name LIKE :search OR fc.tube_source LIKE :search OR fc.core_source LIKE :search OR fc.tube_dest LIKE :search OR fc.core_dest LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT fc.*, c.name AS closure_name, c.latitude, c.longitude
            FROM fiber_core_connections fc
            JOIN fiber_joint_closures c ON c.id = fc.closure_id
            {$where}
            ORDER BY fc.updated_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getConnection(int $id): ?array
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("
            SELECT fc.*, c.name AS closure_name
            FROM fiber_core_connections fc
            JOIN fiber_joint_closures c ON c.id = fc.closure_id
            WHERE fc.id = :id
        ");
        $stmt->execute([':id' => $id]);

        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    public static function createConnection(array $data): int
    {
        self::boot();
        $pdo = self::pdo();

        $sql = "
            INSERT INTO fiber_core_connections
            (closure_id, tube_source, core_source, tube_dest, core_dest, network_name, attenuation_before, attenuation_after, notes, created_at, updated_at)
            VALUES (:closure_id, :tube_source, :core_source, :tube_dest, :core_dest, :network_name, :attenuation_before, :attenuation_after, :notes, :created_at, :updated_at)
        ";

        $now = self::now();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':closure_id' => (int) $data['closure_id'],
            ':tube_source' => $data['tube_source'] ?? null,
            ':core_source' => $data['core_source'] ?? null,
            ':tube_dest' => $data['tube_dest'] ?? null,
            ':core_dest' => $data['core_dest'] ?? null,
            ':network_name' => $data['network_name'] ?? null,
            ':attenuation_before' => self::normalizeFloat($data['attenuation_before'] ?? null),
            ':attenuation_after' => self::normalizeFloat($data['attenuation_after'] ?? null),
            ':notes' => $data['notes'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateConnection(int $id, array $data): void
    {
        self::boot();
        $pdo = self::pdo();

        $fields = [];
        $params = [':id' => $id];

        $mapping = [
            'closure_id' => 'closure_id',
            'tube_source' => 'tube_source',
            'core_source' => 'core_source',
            'tube_dest' => 'tube_dest',
            'core_dest' => 'core_dest',
            'network_name' => 'network_name',
            'attenuation_before' => 'attenuation_before',
            'attenuation_after' => 'attenuation_after',
            'notes' => 'notes',
        ];

        foreach ($mapping as $key => $column) {
            if (array_key_exists($key, $data)) {
                $fields[] = "{$column} = :{$column}";
                if (in_array($column, ['attenuation_before', 'attenuation_after'], true)) {
                    $params[":{$column}"] = self::normalizeFloat($data[$key]);
                } elseif ($column === 'closure_id') {
                    $params[":{$column}"] = (int) $data[$key];
                } else {
                    $params[":{$column}"] = $data[$key];
                }
            }
        }

        if (!$fields) {
            return;
        }

        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = self::now();

        $sql = "UPDATE fiber_core_connections SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public static function deleteConnection(int $id): void
    {
        self::boot();
        $pdo = self::pdo();

        $stmt = $pdo->prepare("DELETE FROM fiber_core_connections WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public static function getSummary(): array
    {
        self::boot();
        $pdo = self::pdo();

        $summary = [
            'closures' => (int) $pdo->query("SELECT COUNT(*) FROM fiber_joint_closures")->fetchColumn(),
            'connections' => (int) $pdo->query("SELECT COUNT(*) FROM fiber_core_connections")->fetchColumn(),
            'avg_attenuation' => null,
            'active_networks' => 0,
        ];

        $avg = $pdo->query("SELECT AVG(attenuation_after) FROM fiber_core_connections WHERE attenuation_after IS NOT NULL")->fetchColumn();
        $summary['avg_attenuation'] = $avg !== false ? round((float) $avg, 2) : null;

        $networks = $pdo->query("SELECT COUNT(DISTINCT network_name) FROM fiber_core_connections WHERE network_name IS NOT NULL AND network_name != ''")->fetchColumn();
        $summary['active_networks'] = (int) $networks;

        return $summary;
    }

    public static function storeUploadedPhoto(array $file): ?string
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new InvalidArgumentException('Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
        }

        $targetDir = self::getUploadDirectory();
        $filename = 'closure_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Gagal menyimpan file upload.');
        }

        return $filename;
    }

    private static function resolvePhotoUrl(?string $photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }
        return '/assets/uploads/fiber/' . ltrim($photoPath, '/');
    }

    private static function decodeLabels(?string $labels): array
    {
        if (!$labels) {
            return [];
        }
        $decoded = json_decode($labels, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function encodeLabels($labels): ?string
    {
        if (empty($labels)) {
            return null;
        }
        if (is_string($labels)) {
            $parts = array_filter(array_map('trim', explode(',', $labels)));
            return $parts ? json_encode(array_values(array_unique($parts))) : null;
        }
        if (is_array($labels)) {
            $labels = array_values(array_unique(array_map('trim', $labels)));
            return $labels ? json_encode($labels) : null;
        }
        return null;
    }

    private static function normalizeFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }

    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
