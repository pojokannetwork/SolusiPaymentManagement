<?php
// SolusiPaymentManagement Database Configuration

// Main application database - Using SQLite for demo
define('DB_HOST', 'sqlite:' . __DIR__ . '/../solusipaymentmanagement.db');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', '');

// RADIUS database (separate from main app DB)
define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'radius_user'); // Replace with actual username
define('RADIUS_DB_PASS', 'radius_password'); // Replace with actual password
define('RADIUS_DB_CHARSET', 'utf8mb4');

// PDO options
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Database connection class
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            if (strpos(DB_HOST, 'sqlite:') === 0) {
                // SQLite connection
                $this->pdo = new PDO(DB_HOST, null, null, PDO_OPTIONS);
            } else {
                // MySQL connection
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
            }
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Get RADIUS database connection
    public function getRadiusConnection() {
        static $radiusPdo = null;
        if ($radiusPdo === null) {
            try {
                $dsn = "mysql:host=" . RADIUS_DB_HOST . ";dbname=" . RADIUS_DB_NAME . ";charset=" . RADIUS_DB_CHARSET;
                $radiusPdo = new PDO($dsn, RADIUS_DB_USER, RADIUS_DB_PASS, PDO_OPTIONS);
            } catch (PDOException $e) {
                error_log("RADIUS database connection failed: " . $e->getMessage());
                return null;
            }
        }
        return $radiusPdo;
    }

    // Execute query with parameters
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    // Get single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Get multiple rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Insert and return last insert ID
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    // Update/Delete
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->pdo->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->pdo->rollBack();
    }
}

// Global database instance
$db = Database::getInstance();
