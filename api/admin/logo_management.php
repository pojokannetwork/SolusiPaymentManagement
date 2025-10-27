<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/logo_config.php';

header('Content-Type: application/json');

// Check authentication and permissions
$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'upload':
        handleLogoUpload();
        break;
        
    case 'delete':
        handleLogoDelete();
        break;
        
    case 'save_config':
        handleSaveConfig();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleLogoUpload() {
    try {
        // Validate CSRF token
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $logoType = $_POST['logo_type'] ?? '';
        $allowedTypes = ['main', 'white', 'small', 'favicon'];
        
        if (!in_array($logoType, $allowedTypes)) {
            throw new Exception('Invalid logo type');
        }
        
        $file = $_FILES['logo_file'];
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 2MB allowed.');
        }
        
        // Validate file type
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];
        if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and ICO files are allowed.');
        }
        
        // Determine file extension
        $extension = '';
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $extension = '.jpg';
                break;
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/gif':
                $extension = '.gif';
                break;
            case 'image/x-icon':
            case 'image/vnd.microsoft.icon':
                $extension = '.ico';
                break;
        }
        
        // Generate filename based on logo type
        $filename = '';
        switch ($logoType) {
            case 'main':
                $filename = 'logo' . $extension;
                break;
            case 'white':
                $filename = 'logo-white' . $extension;
                break;
            case 'small':
                $filename = 'logo-small' . $extension;
                break;
            case 'favicon':
                $filename = 'favicon.ico';
                break;
        }
        
        $uploadDir = __DIR__ . '/../../assets/images/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        // Remove existing file if exists
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Set proper permissions
        chmod($uploadPath, 0644);
        
        // Update config file if needed
        updateLogoConfig($logoType, $filename);
        
        echo json_encode([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'filename' => $filename
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleLogoDelete() {
    try {
        // Validate CSRF token
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        $logoType = $_POST['logo_type'] ?? '';
        $allowedTypes = ['main', 'white', 'small', 'favicon'];
        
        if (!in_array($logoType, $allowedTypes)) {
            throw new Exception('Invalid logo type');
        }
        
        $config = getLogoConfig();
        $filename = '';
        
        switch ($logoType) {
            case 'main':
                $filename = $config['logo_main'];
                break;
            case 'white':
                $filename = $config['logo_white'];
                break;
            case 'small':
                $filename = $config['logo_small'];
                break;
            case 'favicon':
                $filename = $config['favicon'];
                break;
        }
        
        if (empty($filename)) {
            throw new Exception('No logo file to delete');
        }
        
        $filePath = __DIR__ . '/../../assets/images/logos/' . $filename;
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Logo deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleSaveConfig() {
    try {
        // Validate CSRF token
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        $configFile = __DIR__ . '/../../includes/logo_config.php';
        $config = getLogoConfig();
        
        // Update configuration values
        $config['company_name'] = sanitizeInput($_POST['company_name'] ?? $config['company_name']);
        $config['company_tagline'] = sanitizeInput($_POST['company_tagline'] ?? $config['company_tagline']);
        $config['logo_width'] = sanitizeInput($_POST['logo_width'] ?? $config['logo_width']);
        $config['logo_width_mobile'] = sanitizeInput($_POST['logo_width_mobile'] ?? $config['logo_width_mobile']);
        $config['show_logo'] = isset($_POST['show_logo']);
        $config['show_text'] = isset($_POST['show_text']);
        $config['use_text_only'] = isset($_POST['use_text_only']);
        
        // Generate new config file content
        $configContent = "<?php\n";
        $configContent .= "/**\n * Logo Configuration\n * Edit this file to change logo settings\n */\n\n";
        $configContent .= "// Logo settings - dapat diubah sesuai kebutuhan\n";
        $configContent .= "\$logo_config = " . var_export($config, true) . ";\n\n";
        
        // Add the functions from original file
        $originalContent = file_get_contents($configFile);
        $functionsStart = strpos($originalContent, '/**');
        if ($functionsStart !== false) {
            $functionsStart = strpos($originalContent, '/**', $functionsStart + 1);
            if ($functionsStart !== false) {
                $configContent .= substr($originalContent, $functionsStart);
            }
        }
        
        // Write updated config
        if (!file_put_contents($configFile, $configContent)) {
            throw new Exception('Failed to save configuration');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuration saved successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function updateLogoConfig($logoType, $filename) {
    $configFile = __DIR__ . '/../../includes/logo_config.php';
    $config = getLogoConfig();
    
    switch ($logoType) {
        case 'main':
            $config['logo_main'] = $filename;
            break;
        case 'white':
            $config['logo_white'] = $filename;
            break;
        case 'small':
            $config['logo_small'] = $filename;
            break;
        case 'favicon':
            $config['favicon'] = $filename;
            break;
    }
    
    // Generate new config file content
    $configContent = "<?php\n";
    $configContent .= "/**\n * Logo Configuration\n * Edit this file to change logo settings\n */\n\n";
    $configContent .= "// Logo settings - dapat diubah sesuai kebutuhan\n";
    $configContent .= "\$logo_config = " . var_export($config, true) . ";\n\n";
    
    // Add the functions from original file
    $originalContent = file_get_contents($configFile);
    $functionsStart = strpos($originalContent, '/**');
    if ($functionsStart !== false) {
        $functionsStart = strpos($originalContent, '/**', $functionsStart + 1);
        if ($functionsStart !== false) {
            $configContent .= substr($originalContent, $functionsStart);
        }
    }
    
    file_put_contents($configFile, $configContent);
}

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}
?>