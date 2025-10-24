<?php
// SolusiPaymentManagement Helper Functions

// Format currency
function formatCurrency($amount, $currency = 'IDR') {
    $locale = 'id_ID'; // Indonesian locale
    $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    return $fmt->formatCurrency($amount, $currency);
}

// Format date
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

// Format datetime
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

// Calculate age from date
function calculateAge($birthDate) {
    if (!$birthDate) return 0;
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// Generate random string
function randomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// Slugify string
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Get file size in human readable format
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Get days between dates
function getDaysBetween($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    return $start->diff($end)->days;
}

// Check if date is weekend
function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    return $dayOfWeek >= 6; // 6 = Saturday, 7 = Sunday
}

// Get business days between dates
function getBusinessDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    $businessDays = 0;
    foreach ($period as $date) {
        if (!$date->format('N') >= 6) { // Not Saturday or Sunday
            $businessDays++;
        }
    }
    return $businessDays;
}

// Generate pagination links
function generatePagination($currentPage, $totalPages, $baseUrl = '', $params = []) {
    if ($totalPages <= 1) return '';

    $pagination = '<nav><ul class="pagination">';

    // Previous button
    if ($currentPage > 1) {
        $prevUrl = buildUrl($baseUrl, array_merge($params, ['page' => $currentPage - 1]));
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    }

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $url = buildUrl($baseUrl, array_merge($params, ['page' => 1]));
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '">1</a></li>';
        if ($start > 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $url = buildUrl($baseUrl, array_merge($params, ['page' => $i]));
        $class = $i == $currentPage ? ' active' : '';
        $pagination .= '<li class="page-item' . $class . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $url = buildUrl($baseUrl, array_merge($params, ['page' => $totalPages]));
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '">' . $totalPages . '</a></li>';
    }

    // Next button
    if ($currentPage < $totalPages) {
        $nextUrl = buildUrl($baseUrl, array_merge($params, ['page' => $currentPage + 1]));
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    }

    $pagination .= '</ul></nav>';
    return $pagination;
}

// Build URL with parameters
function buildUrl($baseUrl, $params = []) {
    $url = $baseUrl;
    if (!empty($params)) {
        $query = http_build_query($params);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }
    return $url;
}

// Get client browser info
function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browser = 'Unknown';

    if (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
        $browser = 'Internet Explorer';
    }

    return $browser;
}

// Get client OS info
function getOSInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $os = 'Unknown';

    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($userAgent, 'iOS') !== false) {
        $os = 'iOS';
    }

    return $os;
}

// Send email (basic implementation - extend as needed)
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . APP_NAME . ' <noreply@' . parse_url(APP_URL, PHP_URL_HOST) . '>'
    ];

    $allHeaders = array_merge($defaultHeaders, $headers);
    $headerString = implode("\r\n", $allHeaders);

    return mail($to, $subject, $message, $headerString);
}

// Get setting value
function getSetting($key, $default = null) {
    global $db;
    $result = $db->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $result ? $result['value'] : $default;
}

// Set setting value
function setSetting($key, $value) {
    global $db;
    $db->execute(
        "INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?",
        [$key, $value, $value]
    );
}

// Calculate depreciation (straight line method)
function calculateDepreciation($cost, $salvageValue, $usefulLifeMonths) {
    if ($usefulLifeMonths <= 0) return 0;
    return ($cost - $salvageValue) / $usefulLifeMonths;
}

// Get asset current value after depreciation
function getAssetCurrentValue($asset) {
    $monthsUsed = getDaysBetween($asset['tgl_beli'], date('Y-m-d')) / 30; // Approximate months
    $depreciation = calculateDepreciation(
        $asset['nilai_perolehan'],
        0, // Assuming no salvage value
        $asset['umur_ekonomis_bulan']
    );
    $totalDepreciation = $depreciation * min($monthsUsed, $asset['umur_ekonomis_bulan']);
    return max(0, $asset['nilai_perolehan'] - $totalDepreciation);
}

// Generate QR code data URL (requires QR code library)
function generateQRCode($data, $size = 200) {
    // This is a placeholder - implement with a QR code library like chillerlan/php-qrcode
    return "data:image/png;base64," . base64_encode("QR Code for: {$data}");
}

// Export to CSV
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

// Import from CSV
function importFromCSV($filePath, $hasHeaders = true) {
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        $row = 0;
        while (($line = fgetcsv($handle, 1000, ',')) !== false) {
            if ($hasHeaders && $row === 0) {
                $row++;
                continue;
            }
            $data[] = $line;
            $row++;
        }
        fclose($handle);
    }
    return $data;
}

// Get database backup (basic implementation)
function getDatabaseBackup() {
    global $db;
    $pdo = $db->getConnection();

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $backup = "-- " . APP_NAME . " Database Backup\n";
    $backup .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $backup .= "-- Table: {$table}\n";
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $backup .= $createTable['Create Table'] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $backup .= "INSERT INTO `{$table}` VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    $rowValues[] = $pdo->quote($value);
                }
                $values[] = "(" . implode(", ", $rowValues) . ")";
            }
            $backup .= implode(",\n", $values) . ";\n\n";
        }
    }

    return $backup;
}

// Restore database from backup
function restoreDatabaseBackup($sqlContent) {
    global $db;
    $pdo = $db->getConnection();

    $pdo->beginTransaction();

    try {
        $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Database restore failed: " . $e->getMessage());
        return false;
    }
}

// User Helper Functions
function getCurrentUser() {
    return RouterGuard::getInstance()->getCurrentUser();
}

function hasPermission($permission) {
    return RouterGuard::getInstance()->hasPermission($permission);
}

function isAuthenticated() {
    return RouterGuard::getInstance()->isAuthenticated();
}

// Activity Logger Helper Functions
function logSecurityEvent($event, $details = []) {
    ActivityLogger::getInstance()->logSecurity($event, $details);
}

function logActivity($action, $details = [], $userId = null, $endpoint = null) {
    ActivityLogger::getInstance()->log($action, $details, $userId, $endpoint);
}
