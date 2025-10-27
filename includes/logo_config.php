<?php
/**
 * Logo Configuration
 * Edit this file to change logo settings
 */

// Logo settings - dapat diubah sesuai kebutuhan
$logo_config = [
    'company_name' => 'SolusiPaymentManagement',
    'company_tagline' => 'Smart Payment Solution',
    
    // Logo files (relative to /assets/images/logos/)
    'logo_main' => 'logo.png',           // Main logo (light background)
    'logo_white' => 'logo-white.png',    // White logo (dark background) 
    'logo_small' => 'logo-small.png',    // Small logo (mobile)
    'favicon' => 'favicon.ico',          // Browser favicon
    
    // Logo display settings
    'show_logo' => true,                 // Show logo image
    'show_text' => true,                 // Show company name text
    'logo_width' => '180px',             // Desktop logo width
    'logo_width_mobile' => '120px',      // Mobile logo width
    'logo_height' => 'auto',             // Logo height
    
    // Fallback if no logo image
    'use_text_only' => true,             // Show text if no image
    'text_icon' => 'fas fa-credit-card', // Icon for text-only mode
];

/**
 * Get logo configuration
 */
function getLogoConfig() {
    global $logo_config;
    return $logo_config;
}

/**
 * Check if logo file exists
 */
function logoExists($logoFile) {
    $logoPath = __DIR__ . '/../assets/images/logos/' . $logoFile;
    if (file_exists($logoPath)) {
        return true;
    }
    
    // Check for default logo
    $defaultLogo = str_replace('.png', '-default.svg', $logoFile);
    $defaultLogo = str_replace('.jpg', '-default.svg', $defaultLogo);
    $defaultPath = __DIR__ . '/../assets/images/logos/' . $defaultLogo;
    
    return file_exists($defaultPath);
}

/**
 * Get logo URL
 */
function getLogoUrl($logoFile) {
    $logoPath = __DIR__ . '/../assets/images/logos/' . $logoFile;
    if (file_exists($logoPath)) {
        return '/assets/images/logos/' . $logoFile;
    }
    
    // Use default logo if main logo doesn't exist
    $defaultLogo = str_replace('.png', '-default.svg', $logoFile);
    $defaultLogo = str_replace('.jpg', '-default.svg', $defaultLogo);
    $defaultPath = __DIR__ . '/../assets/images/logos/' . $defaultLogo;
    
    if (file_exists($defaultPath)) {
        return '/assets/images/logos/' . $defaultLogo;
    }
    
    return '/assets/images/logos/' . $logoFile; // Return original even if not exists
}

/**
 * Render responsive logo HTML
 */
function renderLogo($theme = 'light', $size = 'normal') {
    $config = getLogoConfig();
    
    if (!$config['show_logo'] && !$config['show_text']) {
        return '';
    }
    
    $logoFile = ($theme === 'dark') ? $config['logo_white'] : $config['logo_main'];
    $logoFile = ($size === 'small') ? $config['logo_small'] : $logoFile;
    
    $width = ($size === 'small') ? $config['logo_width_mobile'] : $config['logo_width'];
    
    $html = '<div class="logo-container d-flex align-items-center">';
    
    // Logo image
    if ($config['show_logo'] && logoExists($logoFile)) {
        $html .= '<img src="' . getLogoUrl($logoFile) . '" 
                       alt="' . htmlspecialchars($config['company_name']) . '"
                       class="logo-image me-3" 
                       style="width: ' . $width . '; height: ' . $config['logo_height'] . ';">';
    } else if ($config['use_text_only']) {
        // Fallback icon
        $html .= '<div class="logo-icon me-3">
                    <i class="' . $config['text_icon'] . ' fa-2x text-primary"></i>
                  </div>';
    }
    
    // Company name text
    if ($config['show_text']) {
        $html .= '<div class="logo-text">
                    <h5 class="mb-0 fw-bold">' . htmlspecialchars($config['company_name']) . '</h5>';
        
        if (!empty($config['company_tagline']) && $size !== 'small') {
            $html .= '<small class="text-muted">' . htmlspecialchars($config['company_tagline']) . '</small>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render favicon HTML
 */
function renderFavicon() {
    $config = getLogoConfig();
    
    if (logoExists($config['favicon'])) {
        return '<link rel="icon" type="image/x-icon" href="' . getLogoUrl($config['favicon']) . '">';
    }
    
    return '<link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💳</text></svg>">';
}
?>