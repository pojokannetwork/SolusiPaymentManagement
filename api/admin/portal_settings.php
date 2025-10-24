<?php
// SolusiPaymentManagement Admin API - Portal Settings

require_once __DIR__ . '/../../includes/bootstrap.php';

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('Method not allowed', 405);
}

// Handle remove logo action
if (isset($_GET['action']) && $_GET['action'] === 'remove_logo') {
    global $db;
    
    // Get current settings
    $settings = $db->fetchOne("SELECT * FROM settings WHERE setting_key = 'portal_config'");
    if ($settings) {
        $config = json_decode($settings['setting_value'], true);
        
        // Delete logo file if exists
        if (!empty($config['logo'])) {
            $logoPath = __DIR__ . '/../../assets/uploads/' . $config['logo'];
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
        }
        
        // Update config
        $config['logo'] = null;
        $db->execute(
            "UPDATE settings SET setting_value = ?, updated_at = datetime('now') WHERE setting_key = 'portal_config'",
            [json_encode($config)]
        );
        
        logSecurityEvent('portal_settings_updated', ['action' => 'remove_logo']);
        successResponse(['message' => 'Logo removed successfully']);
    }
    
    errorResponse('Settings not found', 404);
}

// Handle portal settings update
global $db;

try {
    // Get current settings
    $settings = $db->fetchOne("SELECT * FROM settings WHERE setting_key = 'portal_config'");
    $config = $settings ? json_decode($settings['setting_value'], true) : [];
    
    // Update text fields
    $config['title'] = sanitizeInput($_POST['title'] ?? 'SolusiPaymentManagement');
    $config['tagline'] = sanitizeInput($_POST['tagline'] ?? 'Sistem Manajemen Pembayaran & ISP Terdepan');
    $config['description'] = sanitizeInput($_POST['description'] ?? 'Platform terintegrasi untuk manajemen pelanggan, pembayaran, dan operasional ISP.');
    $config['primary_color'] = sanitizeInput($_POST['primary_color'] ?? '#2563eb');
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../assets/uploads/';
        
        // Create upload directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['logo']['type'];
        $fileSize = $_FILES['logo']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            errorResponse('Invalid file type. Only JPG, PNG, and GIF are allowed.', 400);
        }
        
        if ($fileSize > 2 * 1024 * 1024) { // 2MB
            errorResponse('File size too large. Maximum 2MB allowed.', 400);
        }
        
        // Delete old logo if exists
        if (!empty($config['logo'])) {
            $oldLogoPath = $uploadDir . $config['logo'];
            if (file_exists($oldLogoPath)) {
                unlink($oldLogoPath);
            }
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $config['logo'] = $filename;
        } else {
            errorResponse('Failed to upload logo', 500);
        }
    }
    
    // Handle slide images (optional)
    for ($i = 2; $i <= 3; $i++) {
        $fieldName = 'slide' . $i;
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../assets/uploads/';
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES[$fieldName]['type'];
            $fileSize = $_FILES[$fieldName]['size'];
            
            if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
                // Delete old slide image if exists
                if (!empty($config[$fieldName])) {
                    $oldImagePath = $uploadDir . $config[$fieldName];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                // Generate unique filename
                $extension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
                $filename = $fieldName . '_' . time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
                    $config[$fieldName] = $filename;
                }
            }
        }
    }
    
    // Save settings to database
    if ($settings) {
        // Update existing settings
        $db->execute(
            "UPDATE settings SET setting_value = ?, updated_at = datetime('now') WHERE setting_key = 'portal_config'",
            [json_encode($config)]
        );
    } else {
        // Insert new settings
        $db->execute(
            "INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, datetime('now'), datetime('now'))",
            ['portal_config', json_encode($config)]
        );
    }
    
    // Log activity
    logSecurityEvent('portal_settings_updated', ['config' => $config]);
    
    successResponse([
        'message' => 'Portal settings saved successfully',
        'config' => $config
    ]);
    
} catch (Exception $e) {
    error_log('Portal settings error: ' . $e->getMessage());
    errorResponse('Failed to save portal settings: ' . $e->getMessage(), 500);
}
