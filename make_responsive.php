<?php
/**
 * Script untuk membuat semua halaman admin menjadi responsif
 * Run: php make_responsive.php
 */

// Daftar file admin yang akan diupdate
$adminFiles = [
    'dashboard.php',
    'customers.php', 
    'payment_gateways.php',
    'agents.php',
    'packages.php',
    'vouchers.php',
    'mikrotik.php',
    'fiber_management.php',
    'customers_map.php', 
    'olt_monitoring.php',
    'employees.php',
    'payroll.php',
    'taxes.php',
    'portal_settings.php',
    'whatsapp_notifications.php',
    'settings.php',
    'transactions.php',
    'assets.php'
];

$responsiveCSS = '
    <!-- Custom Responsive Styles -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/responsive.css" rel="stylesheet">
';

$responsiveJS = '
<script>
// Mobile Sidebar Toggle Functionality
function toggleSidebar() {
    const sidebar = document.querySelector(\'.sidebar, .offcanvas\');
    if (!sidebar) return;
    
    if (sidebar.classList.contains(\'offcanvas\')) {
        // Bootstrap offcanvas
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebar);
        bsOffcanvas.toggle();
    } else {
        // Custom sidebar
        sidebar.classList.toggle(\'show\');
        
        if (window.innerWidth <= 991) {
            let overlay = document.querySelector(\'.sidebar-overlay\');
            if (sidebar.classList.contains(\'show\')) {
                if (!overlay) {
                    overlay = document.createElement(\'div\');
                    overlay.className = \'sidebar-overlay\';
                    overlay.style.cssText = `
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                        background: rgba(0, 0, 0, 0.5); z-index: 1040; display: block;
                    `;
                    overlay.onclick = toggleSidebar;
                    document.body.appendChild(overlay);
                }
            } else if (overlay) {
                overlay.remove();
            }
        }
    }
}

// Add responsive classes to tables
document.addEventListener(\'DOMContentLoaded\', function() {
    // Make tables responsive
    document.querySelectorAll(\'table\').forEach(table => {
        if (!table.closest(\'.table-responsive\')) {
            const wrapper = document.createElement(\'div\');
            wrapper.className = \'table-responsive\';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        table.classList.add(\'table-mobile\');
    });
    
    // Add data-label attributes to table cells
    document.querySelectorAll(\'table.table-mobile\').forEach(table => {
        const headers = Array.from(table.querySelectorAll(\'thead th\')).map(th => th.textContent.trim());
        table.querySelectorAll(\'tbody tr\').forEach(row => {
            row.querySelectorAll(\'td\').forEach((cell, index) => {
                if (headers[index] && !cell.hasAttribute(\'data-label\')) {
                    cell.setAttribute(\'data-label\', headers[index]);
                }
            });
        });
    });
    
    // Add responsive classes to buttons
    document.querySelectorAll(\'.btn\').forEach(btn => {
        if (!btn.classList.contains(\'btn-responsive\')) {
            btn.classList.add(\'btn-responsive\');
        }
    });
    
    // Add responsive classes to cards
    document.querySelectorAll(\'.card\').forEach(card => {
        if (!card.classList.contains(\'card-responsive\')) {
            card.classList.add(\'card-responsive\');
        }
    });
    
    // Handle window resize
    window.addEventListener(\'resize\', function() {
        const sidebar = document.querySelector(\'.sidebar\');
        const overlay = document.querySelector(\'.sidebar-overlay\');
        
        if (window.innerWidth > 991 && sidebar) {
            sidebar.classList.remove(\'show\');
            if (overlay) overlay.remove();
        }
    });
});
</script>
';

foreach ($adminFiles as $file) {
    $filePath = __DIR__ . '/admin/' . $file;
    
    if (!file_exists($filePath)) {
        echo "❌ File tidak ditemukan: $file\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Skip jika sudah ada responsive CSS
    if (strpos($content, 'responsive.css') !== false) {
        echo "✅ $file sudah responsif\n";
        continue;
    }
    
    // Tambahkan responsive CSS setelah Bootstrap
    $content = preg_replace(
        '/(<link[^>]*bootstrap[^>]*>)/i',
        '$1' . $responsiveCSS,
        $content,
        1
    );
    
    // Tambahkan responsive JS sebelum </body>
    $content = str_replace('</body>', $responsiveJS . '</body>', $content);
    
    // Backup file asli
    copy($filePath, $filePath . '.backup');
    
    // Tulis file yang sudah diupdate
    if (file_put_contents($filePath, $content)) {
        echo "✅ $file berhasil dibuat responsif\n";
    } else {
        echo "❌ Gagal update $file\n";
    }
}

echo "\n🎉 Update responsif selesai!\n";
echo "📱 Test di: http://localhost:8888/admin/[filename].php\n";
echo "🔧 Demo responsif: http://localhost:8888/admin/invoice_responsive_demo.php\n";
?>