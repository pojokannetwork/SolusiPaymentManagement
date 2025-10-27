<?php

class MikrotikService
{
    private static $apiInstance = null;
    private static $apiRouterId = null;
    private static $cache = [];

    private static function getApi(): MtkApi
    {
        $routerId = self::getDefaultRouterId();

        if (self::$apiInstance === null || self::$apiRouterId !== $routerId) {
            self::$apiInstance = MtkFactory::createFromRouter($routerId);
            self::$apiRouterId = $routerId;
        }

        return self::$apiInstance;
    }

    private static function getDefaultRouterId(): int
    {
        $configured = (int) getSetting('default_mikrotik_router_id', 0);
        if ($configured > 0) {
            return $configured;
        }

        global $db;
        $router = $db->fetchOne("SELECT id FROM mikrotik_routers ORDER BY id ASC LIMIT 1");
        if (!$router) {
            throw new RuntimeException('Belum ada router MikroTik yang dikonfigurasi.');
        }

        return (int) $router['id'];
    }

    private static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $now = time();
        if (isset(self::$cache[$key]) && self::$cache[$key]['expires_at'] > $now) {
            return self::$cache[$key]['value'];
        }

        $value = $callback();
        self::$cache[$key] = [
            'value' => $value,
            'expires_at' => $now + $ttlSeconds,
        ];

        return $value;
    }

    private static function forget(string $key): void
    {
        unset(self::$cache[$key]);
    }

    public static function getPppUsers(bool $forceFresh = false): array
    {
        $loader = function () {
            $api = self::getApi();

            $secretsResponse = $api->getSecrets();
            if (!$secretsResponse['success']) {
                throw new RuntimeException($secretsResponse['error'] ?? 'Gagal mengambil secret PPPoE');
            }

            $activeResponse = $api->getActiveConnections();
            $activeNames = [];
            $activeDetails = [];
            if ($activeResponse['success']) {
                foreach ($activeResponse['connections'] ?? [] as $active) {
                    if (!empty($active['name'])) {
                        $activeNames[] = $active['name'];
                        $activeDetails[$active['name']] = [
                            'address' => $active['address'] ?? null,
                            'uptime' => $active['uptime'] ?? null,
                            'service' => $active['service'] ?? null,
                            'caller_id' => $active['caller-id'] ?? null,
                            'encoding' => $active['encoding'] ?? null,
                            'session_id' => $active['.id'] ?? null,
                        ];
                    }
                }
            }

            $users = [];
            foreach ($secretsResponse['secrets'] ?? [] as $secret) {
                $name = $secret['name'] ?? '';
                $users[] = [
                    'id' => $secret['.id'] ?? null,
                    'name' => $name,
                    'profile' => $secret['profile'] ?? '-',
                    'service' => $secret['service'] ?? 'pppoe',
                    'comment' => $secret['comment'] ?? '',
                    'disabled' => ($secret['disabled'] ?? 'no') === 'yes',
                    'active' => in_array($name, $activeNames, true),
                    'active_details' => $activeDetails[$name] ?? null,
                    'last_logged_out' => $secret['last-logged-out'] ?? null,
                ];
            }

            return $users;
        };

        if ($forceFresh) {
            return $loader();
        }

        return self::remember('mikrotik_ppp_users', 30, $loader);
    }

    public static function createPppUser(array $payload): array
    {
        $api = self::getApi();
        $required = ['name', 'password'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new InvalidArgumentException("Field {$field} wajib diisi");
            }
        }

        $profile = $payload['profile'] ?? 'default';
        $service = $payload['service'] ?? 'pppoe';

        $result = $api->createSecret($payload['name'], $payload['password'], $profile, $service);
        if (!$result['success']) {
            throw new RuntimeException($result['error'] ?? 'Gagal membuat user PPPoE');
        }

        return $result;
    }

    public static function updatePppUser(string $identifier, array $updates): array
    {
        $api = self::getApi();
        $result = $api->updateSecret($identifier, $updates);
        if (!$result['success']) {
            throw new RuntimeException($result['error'] ?? 'Gagal memperbarui user PPPoE');
        }

        self::forget('mikrotik_ppp_users');

        return $result;
    }

    public static function deletePppUser(string $identifier): array
    {
        $api = self::getApi();
        $result = $api->removeSecret($identifier);
        if (!$result['success']) {
            throw new RuntimeException($result['error'] ?? 'Gagal menghapus user PPPoE');
        }

        self::forget('mikrotik_ppp_users');

        return $result;
    }

    public static function disconnectPppSession(string $username): array
    {
        $api = self::getApi();
        $result = $api->disconnectActive($username);
        if (!$result['success']) {
            throw new RuntimeException($result['error'] ?? 'Gagal memutus koneksi PPPoE');
        }

        return $result;
    }

    public static function getPppProfiles(bool $forceFresh = false): array
    {
        $loader = function () {
            $api = self::getApi();
            $response = $api->getProfiles('ppp');
            if (!$response['success']) {
                throw new RuntimeException($response['error'] ?? 'Gagal mengambil profil PPPoE');
            }
            return $response['profiles'] ?? [];
        };

        if ($forceFresh) {
            return $loader();
        }

        return self::remember('mikrotik_ppp_profiles', 60, $loader);
    }

    public static function savePppProfile(array $payload, ?string $identifier = null): array
    {
        $api = self::getApi();
        $response = $identifier
            ? $api->updateProfile($identifier, $payload, 'ppp')
            : $api->addProfile($payload, 'ppp');

        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menyimpan profil PPPoE');
        }

        self::forget('mikrotik_ppp_profiles');

        return $response;
    }

    public static function deletePppProfile(string $identifier): array
    {
        $api = self::getApi();
        $response = $api->removeProfile($identifier, 'ppp');
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menghapus profil PPPoE');
        }

        self::forget('mikrotik_ppp_profiles');

        return $response;
    }

    public static function getRouterSummary(): array
    {
        return self::remember('mikrotik_router_summary', 30, function () {
            $api = self::getApi();

            $resources = $api->getSystemResources();
            if (!$resources['success']) {
                throw new RuntimeException($resources['error'] ?? 'Gagal mengambil resource router');
            }

            $interface = getSetting('mikrotik_main_interface', 'ether1');
            $traffic = $api->getInterfaceTraffic($interface);
            $trafficData = $traffic['success'] ? ($traffic['traffic'] ?? []) : [];

            $formatBytes = function ($value) {
                if ($value === null) {
                    return 0;
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
                $filtered = preg_replace('/[^0-9.]/', '', (string) $value);
                return (int) floatval($filtered);
            };

            $res = $resources['resources'];
            return [
                'identity' => $res['identity'] ?? ($res['name'] ?? ''),
                'model' => $res['model'] ?? ($res['board-name'] ?? 'unknown'),
                'version' => $res['version'] ?? 'unknown',
                'cpu_load' => isset($res['cpu-load']) ? (int) $res['cpu-load'] : 0,
                'memory_total' => $formatBytes($res['total-memory'] ?? null),
                'memory_free' => $formatBytes($res['free-memory'] ?? null),
                'disk_total' => $formatBytes($res['total-hdd-space'] ?? null),
                'disk_free' => $formatBytes($res['free-hdd-space'] ?? null),
                'traffic_rx' => isset($trafficData['rx-bits-per-second']) ? (int) $trafficData['rx-bits-per-second'] : 0,
                'traffic_tx' => isset($trafficData['tx-bits-per-second']) ? (int) $trafficData['tx-bits-per-second'] : 0,
            ];
        });
    }

    public static function getHotspotUsers(bool $forceFresh = false): array
    {
        $loader = function () {
            $api = self::getApi();
            $response = $api->getHotspotUsers();
            if (!$response['success']) {
                throw new RuntimeException($response['error'] ?? 'Gagal mengambil user hotspot');
            }
            return $response['users'] ?? [];
        };

        if ($forceFresh) {
            return $loader();
        }

        return self::remember('mikrotik_hotspot_users', 30, $loader);
    }

    public static function addHotspotUser(array $payload): array
    {
        $api = self::getApi();
        $response = $api->addHotspotUser($payload);
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menambah user hotspot');
        }
        return $response;
    }

    public static function updateHotspotUser(string $identifier, array $payload): array
    {
        $api = self::getApi();
        $response = $api->updateHotspotUser($identifier, $payload);
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal memperbarui user hotspot');
        }
        return $response;
    }

    public static function deleteHotspotUser(string $identifier): array
    {
        $api = self::getApi();
        $response = $api->removeHotspotUser($identifier);
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menghapus user hotspot');
        }
        return $response;
    }

    public static function disconnectHotspotUser(string $identifier): array
    {
        $api = self::getApi();
        $response = $api->disconnectHotspotUser($identifier);
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal memutus user hotspot');
        }
        return $response;
    }

    public static function getHotspotProfiles(): array
    {
        return self::remember('mikrotik_hotspot_profiles', 60, function () {
            $api = self::getApi();
            $response = $api->getHotspotProfiles();
            if (!$response['success']) {
                throw new RuntimeException($response['error'] ?? 'Gagal mengambil profil hotspot');
            }
            return $response['profiles'] ?? [];
        });
    }

    public static function saveHotspotProfile(array $payload, ?string $identifier = null): array
    {
        $api = self::getApi();
        $response = $identifier
            ? $api->updateHotspotProfile($identifier, $payload)
            : $api->addHotspotProfile($payload);

        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menyimpan profil hotspot');
        }

        self::forget('mikrotik_hotspot_profiles');

        return $response;
    }

    public static function deleteHotspotProfile(string $identifier): array
    {
        $api = self::getApi();
        $response = $api->removeHotspotProfile($identifier);
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal menghapus profil hotspot');
        }

        self::forget('mikrotik_hotspot_profiles');

        return $response;
    }

    public static function getHotspotServers(): array
    {
        $api = self::getApi();
        $response = $api->getHotspotServers();
        if (!$response['success']) {
            throw new RuntimeException($response['error'] ?? 'Gagal mengambil server hotspot');
        }
        return $response['servers'] ?? [];
    }

    public static function generateHotspotVouchers(int $count, array $options = []): array
    {
        if ($count <= 0 || $count > 500) {
            throw new InvalidArgumentException('Jumlah voucher harus antara 1-500');
        }

        $prefix = $options['prefix'] ?? 'VCHR';
        $length = $options['length'] ?? 6;
        $profile = $options['profile'] ?? 'default';
        $server = $options['server'] ?? null;
        $comment = $options['comment'] ?? '';

        $api = self::getApi();
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $username = $prefix . strtoupper(bin2hex(random_bytes((int) ceil($length / 2))));
            $username = substr($username, 0, strlen($prefix) + $length);
            $password = strtoupper(bin2hex(random_bytes(2)));

            $payload = [
                'name' => $username,
                'password' => $password,
                'profile' => $profile,
            ];

            if ($server) {
                $payload['server'] = $server;
            }

            if ($comment) {
                $payload['comment'] = $comment;
            }

            $response = $api->addHotspotUser($payload);
            if (!$response['success']) {
                $results[] = [
                    'success' => false,
                    'username' => $username,
                    'error' => $response['error'] ?? 'gagal'
                ];
            } else {
                $results[] = [
                    'success' => true,
                    'username' => $username,
                    'password' => $password
                ];
            }
        }

        return $results;
    }
}
