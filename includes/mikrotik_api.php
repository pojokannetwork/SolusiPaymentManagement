<?php
// SolusiPaymentManagement MikroTik RouterOS API Integration

class MtkApi {
    private $host;
    private $port;
    private $username;
    private $password;
    private $useTls;
    private $timeout;
    private $connection;

    public function __construct($config = []) {
        $this->host = $config['host'] ?? '192.168.1.1';
        $this->port = $config['port'] ?? MIKROTIK_DEFAULT_PORT;
        $this->username = $config['username'] ?? 'admin';
        $this->password = $config['password'] ?? '';
        $this->useTls = $config['use_tls'] ?? false;
        $this->timeout = $config['timeout'] ?? MIKROTIK_TIMEOUT;
        $this->connection = null;
    }

    // Connect to MikroTik router
    private function connect() {
        if ($this->connection) {
            return true;
        }

        try {
            $this->connection = RouterOS::connect([
                'host' => $this->host,
                'port' => $this->port,
                'user' => $this->username,
                'pass' => $this->password,
                'ssl' => $this->useTls
            ]);

            return true;
        } catch (Exception $e) {
            error_log("MikroTik connection failed: " . $e->getMessage());
            return false;
        }
    }

    // Execute command
    private function exec($command, $attributes = []) {
        if (!$this->connect()) {
            throw new Exception("Failed to connect to MikroTik router");
        }

        try {
            $query = new RouterOS\Query($command);
            foreach ($attributes as $key => $value) {
                $query->equal($key, $value);
            }

            $response = $this->connection->query($query)->read();

            // Log activity
            global $logger;
            $logger->log('mikrotik_command', [
                'command' => $command,
                'attributes' => $attributes,
                'host' => $this->host
            ]);

            return $response;
        } catch (Exception $e) {
            error_log("MikroTik command failed: " . $e->getMessage());
            throw $e;
        }
    }

    // Test connection
    public function test() {
        try {
            $response = $this->exec('/system/identity/print');
            return [
                'success' => true,
                'identity' => $response[0]['name'] ?? 'Unknown'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Set PPP secret profile
    public function setPppSecretProfile($username, $profile) {
        try {
            $this->exec('/ppp/secret/set', [
                'numbers' => $username,
                'profile' => $profile
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Enable PPP secret
    public function enable($username) {
        try {
            $this->exec('/ppp/secret/enable', [
                'numbers' => $username
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Disable PPP secret
    public function disable($username) {
        try {
            $this->exec('/ppp/secret/disable', [
                'numbers' => $username
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Add simple queue
    public function addQueue($name, $target, $maxLimit, $comment = '') {
        try {
            $this->exec('/queue/simple/add', [
                'name' => $name,
                'target' => $target,
                'max-limit' => $maxLimit,
                'comment' => $comment
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Remove simple queue
    public function removeQueue($name) {
        try {
            $this->exec('/queue/simple/remove', [
                'numbers' => $name
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get PPP active connections
    public function getActiveConnections() {
        try {
            $response = $this->exec('/ppp/active/print');
            return [
                'success' => true,
                'connections' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get PPP secrets
    public function getSecrets($username = null) {
        try {
            $attributes = [];
            if ($username) {
                $attributes['name'] = $username;
            }

            $response = $this->exec('/ppp/secret/print', $attributes);
            return [
                'success' => true,
                'secrets' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Create PPP secret
    public function createSecret($username, $password, $profile = 'default', $service = 'pppoe') {
        try {
            $this->exec('/ppp/secret/add', [
                'name' => $username,
                'password' => $password,
                'profile' => $profile,
                'service' => $service
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Update PPP secret
    public function updateSecret($username, $updates) {
        try {
            $this->exec('/ppp/secret/set', array_merge([
                'numbers' => $username
            ], $updates));

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Remove PPP secret
    public function removeSecret($username) {
        try {
            $this->exec('/ppp/secret/remove', [
                'numbers' => $username
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get interface list
    public function getInterfaces() {
        try {
            $response = $this->exec('/interface/print');
            return [
                'success' => true,
                'interfaces' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get system resources
    public function getSystemResources() {
        try {
            $response = $this->exec('/system/resource/print');
            return [
                'success' => true,
                'resources' => $response[0] ?? []
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Execute provisioning for customer
    public function provisionCustomer($customerData) {
        $results = [];

        // Set profile
        if (isset($customerData['profile'])) {
            $result = $this->setPppSecretProfile($customerData['pppoe_user'], $customerData['profile']);
            $results['set_profile'] = $result;
        }

        // Enable/disable
        if (isset($customerData['enabled'])) {
            if ($customerData['enabled']) {
                $result = $this->enable($customerData['pppoe_user']);
            } else {
                $result = $this->disable($customerData['pppoe_user']);
            }
            $results['set_status'] = $result;
        }

        // Update queue if provided
        if (isset($customerData['queue'])) {
            $queueData = $customerData['queue'];
            if (isset($queueData['action'])) {
                if ($queueData['action'] === 'add') {
                    $result = $this->addQueue(
                        $queueData['name'],
                        $queueData['target'],
                        $queueData['max_limit'],
                        $queueData['comment'] ?? ''
                    );
                    $results['add_queue'] = $result;
                } elseif ($queueData['action'] === 'remove') {
                    $result = $this->removeQueue($queueData['name']);
                    $results['remove_queue'] = $result;
                }
            }
        }

        return $results;
    }

    // Close connection
    public function close() {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    // Destructor
    public function __destruct() {
        $this->close();
    }
}

// MikroTik API Factory
class MtkFactory {
    public static function createFromRouter($routerId) {
        global $db;

        $router = $db->fetchOne(
            "SELECT * FROM mikrotik_routers WHERE id = ?",
            [$routerId]
        );

        if (!$router) {
            throw new Exception("Router not found");
        }

        return new MtkApi([
            'host' => $router['host'],
            'port' => $router['port'],
            'username' => $router['username'],
            'password' => decryptData($router['password_enc']),
            'use_tls' => (bool) $router['use_tls']
        ]);
    }

    public static function testRouter($routerId) {
        try {
            $api = self::createFromRouter($routerId);
            return $api->test();
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
