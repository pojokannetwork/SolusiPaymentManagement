<?php
// SolusiPaymentManagement MikroTik RouterOS API Integration

use PEAR2\Net\RouterOS\Client as RouterClient;
use PEAR2\Net\RouterOS\Request as RouterRequest;
use PEAR2\Net\RouterOS\Response as RouterResponse;
use PEAR2\Net\Transmitter\NetworkStream as RouterNetworkStream;

class MtkApi {
    private $host;
    private $port;
    private $username;
    private $password;
    private $useTls;
    private $timeout;
    private $connection;
    private $lastPing;
    private $lastError;

    public function __construct($config = []) {
        $this->host = $config['host'] ?? '192.168.1.1';
        $this->port = $config['port'] ?? MIKROTIK_DEFAULT_PORT;
        $this->username = $config['username'] ?? 'admin';
        $this->password = $config['password'] ?? '';
        $this->useTls = $config['use_tls'] ?? false;
        $this->timeout = $config['timeout'] ?? MIKROTIK_TIMEOUT;
        $this->connection = null;
        $this->lastPing = 0;
        $this->lastError = null;
    }

    // Connect to MikroTik router
    private function connect() {
        if ($this->connection) {
            return true;
        }

        if (!class_exists(RouterClient::class)) {
            $this->lastError = "Pustaka PEAR2\\Net\\RouterOS tidak tersedia. Pastikan dependensi sudah terpasang.";
            error_log($this->lastError);
            return false;
        }

        try {
            $context = null;
            if ($this->useTls) {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ]);
            }

            $crypto = $this->useTls
                ? RouterNetworkStream::CRYPTO_TLS
                : RouterNetworkStream::CRYPTO_OFF;

            $this->connection = new RouterClient(
                $this->host,
                $this->username,
                $this->password,
                $this->port,
                false,
                $this->timeout,
                $crypto,
                $context
            );
            $this->lastPing = time();
            $this->lastError = null;
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("MikroTik connection failed: " . $e->getMessage());
            return false;
        }
    }

    // Execute command
    private function exec($command, $attributes = []) {
        if (!$this->connect()) {
            $message = $this->lastError ?: 'Failed to connect to MikroTik router';
            throw new Exception($message);
        }

        try {
            $attributes = $this->normalizeAttributes($attributes);

            $request = new RouterRequest($command);
            foreach ($attributes as $key => $value) {
                if ($value === null) {
                    continue;
                }
                if ($value === '') {
                    $request->setArgument($key, '');
                } else {
                    $request->setArgument($key, (string) $value);
                }
            }

            $responseCollection = $this->connection->sendSync($request);
            $rows = [];
            $errorMessage = null;

            foreach ($responseCollection as $response) {
                $type = $response->getType();
                if ($type === RouterResponse::TYPE_DATA) {
                    $rows[] = $this->responseToArray($response);
                } elseif ($type === RouterResponse::TYPE_ERROR
                    || $type === RouterResponse::TYPE_FATAL) {
                    $errorMessage = $response->getProperty('message')
                        ?? 'RouterOS error';
                }
            }

            $this->lastPing = time();

            global $logger;
            if (isset($logger)) {
                $logger->log('mikrotik_command', [
                    'command' => $command,
                    'attributes' => $attributes,
                    'host' => $this->host
                ]);
            }

            if ($errorMessage !== null) {
                throw new RuntimeException($errorMessage);
            }

            return $rows;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("MikroTik command failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function normalizeAttributes(array $attributes): array {
        $normalized = [];
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '') {
                $normalized[$key] = $value;
                continue;
            }

            if (is_bool($value)) {
                $normalized[$key] = $value ? 'yes' : 'no';
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = implode(',', $value);
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function responseToArray(RouterResponse $response): array
    {
        $result = [];
        foreach ($response as $key => $value) {
            if (is_resource($value)) {
                $result[$key] = stream_get_contents($value);
                if (is_resource($value) && stream_get_meta_data($value)['seekable']) {
                    rewind($value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    // Test connection
    public function test() {
        try {
            $response = $this->exec('/system/identity/print');
            return [
                'success' => true,
                'identity' => $response[0]['name'] ?? 'Unknown'
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function disconnectActive($username) {
        try {
            $this->exec('/ppp/active/remove', [
                'numbers' => $username
            ]);

            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getProfiles($type = 'ppp') {
        try {
            $command = $type === 'hotspot'
                ? '/ip/hotspot/user-profile/print'
                : '/ppp/profile/print';
            $response = $this->exec($command);
            return [
                'success' => true,
                'profiles' => $response
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function addProfile(array $profile, $type = 'ppp') {
        try {
            $command = $type === 'hotspot'
                ? '/ip/hotspot/user-profile/add'
                : '/ppp/profile/add';
            $this->exec($command, $profile);

            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateProfile($identifier, array $profile, $type = 'ppp') {
        try {
            $command = $type === 'hotspot'
                ? '/ip/hotspot/user-profile/set'
                : '/ppp/profile/set';

            $this->exec($command, array_merge([
                'numbers' => $identifier
            ], $profile));

            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function removeProfile($identifier, $type = 'ppp') {
        try {
            $command = $type === 'hotspot'
                ? '/ip/hotspot/user-profile/remove'
                : '/ppp/profile/remove';

            $this->exec($command, [
                'numbers' => $identifier
            ]);

            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getHotspotUsers() {
        try {
            $response = $this->exec('/ip/hotspot/active/print');
            return [
                'success' => true,
                'users' => $response
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function addHotspotUser(array $data) {
        try {
            $this->exec('/ip/hotspot/user/add', $data);
            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function updateHotspotUser($identifier, array $data) {
        try {
            $this->exec('/ip/hotspot/user/set', array_merge([
                'numbers' => $identifier
            ], $data));
            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function removeHotspotUser($identifier) {
        try {
            $this->exec('/ip/hotspot/user/remove', [
                'numbers' => $identifier
            ]);
            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function disconnectHotspotUser($identifier) {
        try {
            $this->exec('/ip/hotspot/active/remove', [
                'numbers' => $identifier
            ]);
            return ['success' => true];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getHotspotProfiles() {
        return $this->getProfiles('hotspot');
    }

    public function addHotspotProfile(array $profile) {
        return $this->addProfile($profile, 'hotspot');
    }

    public function updateHotspotProfile($identifier, array $profile) {
        return $this->updateProfile($identifier, $profile, 'hotspot');
    }

    public function removeHotspotProfile($identifier) {
        return $this->removeProfile($identifier, 'hotspot');
    }

    public function getHotspotServers() {
        try {
            $response = $this->exec('/ip/hotspot/server/print');
            return [
                'success' => true,
                'servers' => $response
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateHotspotVoucher($username, $password, array $options = []) {
        $payload = array_merge([
            'name' => $username,
            'password' => $password,
        ], $options);
        return $this->addHotspotUser($payload);
    }

    public function getInterfaceTraffic($interface, $once = true) {
        try {
            $attributes = [
                'name' => $interface
            ];
            if ($once) {
                $attributes['once'] = '';
            }
            $response = $this->exec('/interface/monitor-traffic', $attributes);
            return [
                'success' => true,
                'traffic' => $response[0] ?? []
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function runRawCommand($command, array $attributes = []) {
        try {
            $response = $this->exec($command, $attributes);
            return [
                'success' => true,
                'response' => $response
            ];
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
