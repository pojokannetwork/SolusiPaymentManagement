<?php
// SolusiPaymentManagement Activity Logger

class ActivityLogger {
    private static $instance = null;
    private $db;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $db;
        $this->db = $db;
    }

    // Log activity
    public function log($action, $details = [], $userId = null, $endpoint = null) {
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($endpoint === null) {
            $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        }

        $ip = getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $payload = json_encode($details);

        // Truncate payload if too long
        if (strlen($payload) > 1000) {
            $payload = substr($payload, 0, 997) . '...';
        }

        try {
            $this->db->insert(
                "INSERT INTO activity_logs (user_id, aksi, endpoint, ip, user_agent, payload_singkat, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, datetime('now'))",
                [$userId, $action, $endpoint, $ip, $userAgent, $payload]
            );
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    // Log user login
    public function logLogin($userId, $success = true, $details = []) {
        $action = $success ? 'login_success' : 'login_failed';
        $this->log($action, $details, $userId);
    }

    // Log user logout
    public function logLogout($userId) {
        $this->log('logout', [], $userId);
    }

    // Log data changes
    public function logDataChange($table, $action, $recordId, $oldData = null, $newData = null) {
        $details = [
            'table' => $table,
            'action' => $action,
            'record_id' => $recordId
        ];

        if ($oldData !== null) {
            $details['old_data'] = $oldData;
        }

        if ($newData !== null) {
            $details['new_data'] = $newData;
        }

        $this->log("data_{$action}", $details);
    }

    // Log security events
    public function logSecurity($event, $details = []) {
        $this->log("security_{$event}", $details);
    }

    // Log payment events
    public function logPayment($action, $invoiceId, $amount, $gateway, $details = []) {
        $details = array_merge($details, [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'gateway' => $gateway
        ]);

        $this->log("payment_{$action}", $details);
    }

    // Log ISP events
    public function logISP($action, $customerId, $details = []) {
        $details = array_merge($details, [
            'customer_id' => $customerId
        ]);

        $this->log("isp_{$action}", $details);
    }

    // Get activity logs with filtering
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "aksi LIKE ?";
            $params[] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['ip'])) {
            $where[] = "ip = ?";
            $params[] = $filters['ip'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT al.*, p.nama as user_name, p.email as user_email
                FROM activity_logs al
                LEFT JOIN pengguna p ON al.user_id = p.id
                {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    // Get activity statistics
    public function getStats($period = '30 days') {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $stats = [];

        // Total activities
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM activity_logs WHERE created_at >= ?",
            [$dateFrom]
        );
        $stats['total_activities'] = $result['total'];

        // Activities by action type
        $results = $this->db->fetchAll(
            "SELECT aksi, COUNT(*) as count
             FROM activity_logs
             WHERE created_at >= ?
             GROUP BY aksi
             ORDER BY count DESC
             LIMIT 10",
            [$dateFrom]
        );
        $stats['activities_by_type'] = $results;

        // Activities by user
        $results = $this->db->fetchAll(
            "SELECT p.nama, COUNT(*) as count
             FROM activity_logs al
             LEFT JOIN pengguna p ON al.user_id = p.id
             WHERE al.created_at >= ?
             GROUP BY al.user_id
             ORDER BY count DESC
             LIMIT 10",
            [$dateFrom]
        );
        $stats['activities_by_user'] = $results;

        // Recent security events
        $results = $this->db->fetchAll(
            "SELECT * FROM activity_logs
             WHERE aksi LIKE 'security_%' AND created_at >= ?
             ORDER BY created_at DESC
             LIMIT 20",
            [$dateFrom]
        );
        $stats['security_events'] = $results;

        return $stats;
    }

    // Clean old logs (keep last N days)
    public function cleanOldLogs($daysToKeep = 365) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        $deleted = $this->db->execute(
            "DELETE FROM activity_logs WHERE created_at < ?",
            [$cutoffDate]
        );

        $this->log('system_cleanup', ['action' => 'clean_old_logs', 'deleted' => $deleted]);

        return $deleted;
    }

    // Export logs to CSV
    public function exportLogs($filters = [], $filename = null) {
        if ($filename === null) {
            $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
        }

        $logs = $this->getLogs($filters, 10000, 0); // Export up to 10k records

        $headers = ['ID', 'User', 'Action', 'Endpoint', 'IP', 'User Agent', 'Details', 'Created At'];

        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                $log['id'],
                $log['user_name'] ?? 'System',
                $log['aksi'],
                $log['endpoint'],
                $log['ip'],
                truncateText($log['user_agent'], 100),
                $log['payload_singkat'],
                $log['created_at']
            ];
        }

        exportToCSV($data, $filename, $headers);
    }
}

// Global activity logger instance
$logger = ActivityLogger::getInstance();
