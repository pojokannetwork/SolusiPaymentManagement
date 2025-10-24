<?php
// SolusiPaymentManagement OpenStreetMap Integration

class OpenStreetMap {
    private static $instance = null;
    private $nominatimUrl;
    private $nominatimReverseUrl;
    private $userAgent;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->nominatimUrl = NOMINATIM_URL;
        $this->nominatimReverseUrl = NOMINATIM_REVERSE_URL;
        $this->userAgent = NOMINATIM_USER_AGENT;
    }

    // Make HTTP request with rate limiting
    private function makeRequest($url, $params = []) {
        // Simple rate limiting (max 1 request per second)
        static $lastRequest = 0;
        $now = microtime(true);
        if ($now - $lastRequest < 1) {
            usleep(1000000 - (($now - $lastRequest) * 1000000));
        }
        $lastRequest = microtime(true);

        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("OpenStreetMap API request failed: {$error}");
            return ['error' => $error];
        }

        if ($httpCode !== 200) {
            error_log("OpenStreetMap API returned HTTP {$httpCode}: {$response}");
            return ['error' => "HTTP {$httpCode}", 'response' => $response];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode OpenStreetMap response: " . json_last_error_msg());
            return ['error' => 'Invalid JSON response'];
        }

        return $result;
    }

    // Geocode address to coordinates
    public function geocode($address, $country = 'Indonesia') {
        $params = [
            'q' => $address,
            'country' => $country,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1
        ];

        $result = $this->makeRequest($this->nominatimUrl, $params);

        if (isset($result['error']) || empty($result)) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'No results found'
            ];
        }

        $place = $result[0];

        return [
            'success' => true,
            'lat' => (float) $place['lat'],
            'lon' => (float) $place['lon'],
            'display_name' => $place['display_name'],
            'address' => $place['address'] ?? [],
            'importance' => (float) ($place['importance'] ?? 0)
        ];
    }

    // Reverse geocode coordinates to address
    public function reverseGeocode($lat, $lon) {
        $params = [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'addressdetails' => 1,
            'zoom' => 18
        ];

        $result = $this->makeRequest($this->nominatimReverseUrl, $params);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['error']
            ];
        }

        return [
            'success' => true,
            'display_name' => $result['display_name'],
            'address' => $result['address'] ?? [],
            'lat' => (float) $result['lat'],
            'lon' => (float) $result['lon']
        ];
    }

    // Search places with bounding box
    public function searchInBounds($query, $bounds) {
        $params = [
            'q' => $query,
            'format' => 'json',
            'limit' => 50,
            'bounded' => 1,
            'viewbox' => implode(',', $bounds), // min_lon,min_lat,max_lon,max_lat
            'addressdetails' => 1
        ];

        $result = $this->makeRequest($this->nominatimUrl, $params);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['error']
            ];
        }

        $places = [];
        foreach ($result as $place) {
            $places[] = [
                'lat' => (float) $place['lat'],
                'lon' => (float) $place['lon'],
                'display_name' => $place['display_name'],
                'type' => $place['type'] ?? '',
                'importance' => (float) ($place['importance'] ?? 0)
            ];
        }

        return [
            'success' => true,
            'places' => $places
        ];
    }

    // Get customer locations for map display
    public function getCustomerLocations($filters = []) {
        global $db;

        $where = ["status != 'terminated'"];
        $params = [];

        if (!empty($filters['paket'])) {
            $where[] = "paket = ?";
            $params[] = $filters['paket'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        $customers = $db->fetchAll(
            "SELECT id, kode_pelanggan, nama, lat, lon, alamat, paket, status
             FROM pelanggan
             WHERE {$whereClause} AND lat IS NOT NULL AND lon IS NOT NULL
             ORDER BY nama",
            $params
        );

        $locations = [];
        foreach ($customers as $customer) {
            $locations[] = [
                'id' => $customer['id'],
                'code' => $customer['kode_pelanggan'],
                'name' => $customer['nama'],
                'lat' => (float) $customer['lat'],
                'lon' => (float) $customer['lon'],
                'address' => $customer['alamat'],
                'package' => $customer['paket'],
                'status' => $customer['status']
            ];
        }

        return $locations;
    }

    // Calculate distance between two points (Haversine formula)
    public function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
        $earthRadius = ($unit === 'km') ? 6371 : 3959; // km or miles

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Find customers within radius
    public function findCustomersInRadius($centerLat, $centerLon, $radiusKm) {
        global $db;

        $customers = $db->fetchAll(
            "SELECT id, kode_pelanggan, nama, lat, lon, alamat, paket, status
             FROM pelanggan
             WHERE status != 'terminated' AND lat IS NOT NULL AND lon IS NOT NULL"
        );

        $nearbyCustomers = [];
        foreach ($customers as $customer) {
            $distance = $this->calculateDistance(
                $centerLat, $centerLon,
                $customer['lat'], $customer['lon']
            );

            if ($distance <= $radiusKm) {
                $customer['distance'] = round($distance, 2);
                $nearbyCustomers[] = $customer;
            }
        }

        // Sort by distance
        usort($nearbyCustomers, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $nearbyCustomers;
    }

    // Update customer coordinates from address
    public function updateCustomerCoordinates($customerId) {
        global $db;

        $customer = $db->fetchOne(
            "SELECT id, alamat FROM pelanggan WHERE id = ?",
            [$customerId]
        );

        if (!$customer || empty($customer['alamat'])) {
            return ['success' => false, 'error' => 'Customer not found or no address'];
        }

        $geocodeResult = $this->geocode($customer['alamat']);

        if (!$geocodeResult['success']) {
            return $geocodeResult;
        }

        $db->execute(
            "UPDATE pelanggan SET lat = ?, lon = ?, updated_at = NOW() WHERE id = ?",
            [$geocodeResult['lat'], $geocodeResult['lon'], $customerId]
        );

        return [
            'success' => true,
            'lat' => $geocodeResult['lat'],
            'lon' => $geocodeResult['lon']
        ];
    }

    // Batch update coordinates for customers without them
    public function batchUpdateCoordinates() {
        global $db;

        $customers = $db->fetchAll(
            "SELECT id, alamat FROM pelanggan
             WHERE (lat IS NULL OR lon IS NULL) AND alamat IS NOT NULL AND alamat != ''
             LIMIT 50" // Process in batches to avoid rate limits
        );

        $updated = 0;
        $errors = [];

        foreach ($customers as $customer) {
            $result = $this->updateCustomerCoordinates($customer['id']);
            if ($result['success']) {
                $updated++;
            } else {
                $errors[] = [
                    'id' => $customer['id'],
                    'error' => $result['error']
                ];
            }

            // Small delay between requests
            usleep(200000); // 0.2 seconds
        }

        return [
            'success' => true,
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    // Generate map markers data for Leaflet
    public function generateMapMarkers($customers = null) {
        if ($customers === null) {
            $customers = $this->getCustomerLocations();
        }

        $markers = [];
        foreach ($customers as $customer) {
            $statusColor = $this->getStatusColor($customer['status']);

            $markers[] = [
                'id' => $customer['id'],
                'lat' => $customer['lat'],
                'lon' => $customer['lon'],
                'popup' => $this->generatePopupContent($customer),
                'color' => $statusColor,
                'status' => $customer['status']
            ];
        }

        return $markers;
    }

    // Get color for customer status
    private function getStatusColor($status) {
        $colors = [
            'active' => 'green',
            'isolir' => 'red',
            'suspended' => 'orange',
            'terminated' => 'gray'
        ];

        return $colors[$status] ?? 'blue';
    }

    // Generate popup content for map marker
    private function generatePopupContent($customer) {
        $statusBadge = $this->getStatusBadge($customer['status']);

        return "
            <div class='map-popup'>
                <h6>{$customer['name']}</h6>
                <p><strong>Code:</strong> {$customer['code']}</p>
                <p><strong>Package:</strong> {$customer['package']}</p>
                <p><strong>Status:</strong> {$statusBadge}</p>
                <p><strong>Address:</strong> " . truncateText($customer['address'], 100) . "</p>
                <div class='popup-actions'>
                    <a href='/admin/customers/{$customer['id']}' class='btn btn-sm btn-primary'>View</a>
                    <button class='btn btn-sm btn-warning' onclick='isolirCustomer({$customer['id']})'>Isolir</button>
                    <button class='btn btn-sm btn-success' onclick='activateCustomer({$customer['id']})'>Activate</button>
                </div>
            </div>
        ";
    }

    // Get status badge HTML
    private function getStatusBadge($status) {
        $badges = [
            'active' => '<span class="badge bg-success">Active</span>',
            'isolir' => '<span class="badge bg-danger">Isolir</span>',
            'suspended' => '<span class="badge bg-warning">Suspended</span>',
            'terminated' => '<span class="badge bg-secondary">Terminated</span>'
        ];

        return $badges[$status] ?? '<span class="badge bg-info">' . ucfirst($status) . '</span>';
    }
}

// Global OpenStreetMap instance
$osm = OpenStreetMap::getInstance();
