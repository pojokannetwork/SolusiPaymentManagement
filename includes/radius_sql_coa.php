<?php
// SolusiPaymentManagement FreeRADIUS MySQL + CoA Integration

class RadiusSqlCoa {
    private $db;
    private $nasIp;
    private $nasSecret;
    private $coaPort;

    public function __construct($config = []) {
        $this->db = Database::getInstance()->getRadiusConnection();
        $this->nasIp = $config['nas_ip'] ?? getSetting('nas_ip');
        $this->nasSecret = $config['nas_secret'] ?? getSetting('nas_secret');
        $this->coaPort = $config['coa_port'] ?? RADIUS_COA_PORT;
    }

    // Test RADIUS database connection
    public function test() {
        try {
            $stmt = $this->db->query("SELECT 1");
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Upsert user in radcheck table
    public function upsertUser($username, $password = null, $attributes = []) {
        try {
            // Remove existing entries for this user
            $this->db->exec("DELETE FROM radcheck WHERE username = ?", [$username]);
            $this->db->exec("DELETE FROM radreply WHERE username = ?", [$username]);
            $this->db->exec("DELETE FROM radusergroup WHERE username = ?", [$username]);

            // Add password if provided
            if ($password) {
                $this->db->exec(
                    "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)",
                    [$username, $password]
                );
            }

            // Add additional attributes
            foreach ($attributes as $attr) {
                if ($attr['type'] === 'check') {
                    $this->db->exec(
                        "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)",
                        [$username, $attr['attribute'], $attr['op'], $attr['value']]
                    );
                } elseif ($attr['type'] === 'reply') {
                    $this->db->exec(
                        "INSERT INTO radreply (username, attribute, op, value) VALUES (?, ?, ?, ?)",
                        [$username, $attr['attribute'], $attr['op'], $attr['value']]
                    );
                }
            }

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Set user group
    public function setGroup($username, $groupName) {
        try {
            // Remove existing group assignment
            $this->db->exec("DELETE FROM radusergroup WHERE username = ?", [$username]);

            // Add new group assignment
            $this->db->exec(
                "INSERT INTO radusergroup (username, groupname, priority) VALUES (?, ?, 1)",
                [$username, $groupName]
            );

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Set rate limit for user
    public function setRateLimit($username, $rateLimit) {
        try {
            // Remove existing rate limit
            $this->db->exec(
                "DELETE FROM radreply WHERE username = ? AND attribute = 'Mikrotik-Rate-Limit'",
                [$username]
            );

            // Add new rate limit
            $this->db->exec(
                "INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Mikrotik-Rate-Limit', ':=', ?)",
                [$username, $rateLimit]
            );

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Send CoA (Change of Authorization) request
    public function sendCoA($username, $attributes = []) {
        if (!$this->nasIp || !$this->nasSecret) {
            return [
                'success' => false,
                'error' => 'NAS IP or secret not configured'
            ];
        }

        try {
            // Create CoA packet
            $packet = $this->createCoAPacket($username, $attributes);

            // Send UDP packet to NAS
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => RADIUS_TIMEOUT, 'usec' => 0]);

            $sent = socket_sendto($socket, $packet, strlen($packet), 0, $this->nasIp, $this->coaPort);

            if ($sent === false) {
                throw new Exception('Failed to send CoA packet');
            }

            // Wait for response (optional)
            $response = '';
            $from = '';
            $port = 0;
            socket_recvfrom($socket, $response, 4096, 0, $from, $port);
            socket_close($socket);

            // Log CoA attempt
            global $logger;
            $logger->log('radius_coa', [
                'username' => $username,
                'nas_ip' => $this->nasIp,
                'attributes' => $attributes,
                'response_received' => !empty($response)
            ]);

            return [
                'success' => true,
                'response_received' => !empty($response)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Create CoA packet
    private function createCoAPacket($username, $attributes = []) {
        // RADIUS CoA packet structure (simplified)
        $code = 43; // CoA-Request
        $identifier = rand(0, 255);
        $length = 20; // Minimum length
        $authenticator = random_bytes(16);

        // Add attributes
        $attributesData = '';

        // User-Name attribute
        $attributesData .= $this->createAttribute(1, $username);

        // Additional attributes
        foreach ($attributes as $attr) {
            $attributesData .= $this->createAttribute($attr['id'], $attr['value']);
        }

        $length += strlen($attributesData);
        $packet = pack('CCn', $code, $identifier, $length) . $authenticator . $attributesData;

        // Calculate response authenticator (simplified)
        $responseAuth = md5($packet . $this->nasSecret, true);
        $packet = substr_replace($packet, $responseAuth, 4, 16);

        return $packet;
    }

    // Create RADIUS attribute
    private function createAttribute($type, $value) {
        $length = strlen($value) + 2;
        return pack('CC', $type, $length) . $value;
    }

    // Get user info from RADIUS database
    public function getUserInfo($username) {
        try {
            $checkAttrs = $this->db->query(
                "SELECT attribute, value FROM radcheck WHERE username = ?",
                [$username]
            )->fetchAll(PDO::FETCH_ASSOC);

            $replyAttrs = $this->db->query(
                "SELECT attribute, value FROM radreply WHERE username = ?",
                [$username]
            )->fetchAll(PDO::FETCH_ASSOC);

            $groups = $this->db->query(
                "SELECT groupname FROM radusergroup WHERE username = ?",
                [$username]
            )->fetchAll(PDO::FETCH_COLUMN);

            return [
                'success' => true,
                'check_attributes' => $checkAttrs,
                'reply_attributes' => $replyAttrs,
                'groups' => $groups
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Provision customer (create/update user and send CoA)
    public function provisionCustomer($customerData) {
        $results = [];

        $username = $customerData['pppoe_user'];
        $password = $customerData['pppoe_pass'];
        $profile = $customerData['profile'];

        // Upsert user
        $attributes = [];
        if (isset($customerData['rate_limit'])) {
            $attributes[] = [
                'type' => 'reply',
                'attribute' => 'Mikrotik-Rate-Limit',
                'op' => ':=',
                'value' => $customerData['rate_limit']
            ];
        }

        $result = $this->upsertUser($username, $password, $attributes);
        $results['upsert_user'] = $result;

        if (!$result['success']) {
            return $results;
        }

        // Set group (profile)
        $result = $this->setGroup($username, $profile);
        $results['set_group'] = $result;

        // Send CoA
        $coaAttributes = [
            ['id' => 1, 'value' => $username] // User-Name
        ];

        $result = $this->sendCoA($username, $coaAttributes);
        $results['send_coa'] = $result;

        return $results;
    }

    // Get online users
    public function getOnlineUsers() {
        try {
            $users = $this->db->query(
                "SELECT username, nasipaddress, framedipaddress, callingstationid, acctstarttime
                 FROM radacct WHERE acctstoptime IS NULL"
            )->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Disconnect user (force logout)
    public function disconnectUser($username) {
        // Send Disconnect-Message (DM) instead of CoA
        $code = 40; // Disconnect-Request

        try {
            $packet = $this->createCoAPacket($username); // Reuse CoA packet creation
            $packet = chr($code) . substr($packet, 1); // Change code

            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_sendto($socket, $packet, strlen($packet), 0, $this->nasIp, $this->coaPort);
            socket_close($socket);

            global $logger;
            $logger->log('radius_disconnect', [
                'username' => $username,
                'nas_ip' => $this->nasIp
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get user accounting data
    public function getUserAccounting($username, $limit = 50) {
        try {
            $accounting = $this->db->query(
                "SELECT acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, nasipaddress
                 FROM radacct WHERE username = ? ORDER BY acctstarttime DESC LIMIT ?",
                [$username, $limit]
            )->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'accounting' => $accounting
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Clean up old accounting records
    public function cleanupOldAccounting($daysToKeep = 90) {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

            $deleted = $this->db->exec(
                "DELETE FROM radacct WHERE acctstoptime < ? AND acctstoptime IS NOT NULL",
                [$cutoffDate]
            );

            global $logger;
            $logger->log('radius_cleanup', [
                'action' => 'cleanup_old_accounting',
                'deleted_records' => $deleted,
                'days_kept' => $daysToKeep
            ]);

            return [
                'success' => true,
                'deleted' => $deleted
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
