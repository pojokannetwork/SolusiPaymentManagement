<?php
// SolusiPaymentManagement Admin API - Server Control

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

$guard = RouterGuard::getInstance();
$guard->requirePermission('admin.settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'restart_apache':
        handleRestartApache();
        break;
    default:
        errorResponse('Invalid action', 400);
}

function handleRestartApache() {
    if (!function_exists('exec')) {
        errorResponse('Command execution is disabled on this server.', 500);
    }

    $commands = [
        'systemctl restart apache2',
        'sudo -n systemctl restart apache2',
        'sudo -n service apache2 restart',
        'sudo -n /etc/init.d/apache2 restart'
    ];

    $attempts = [];

    foreach ($commands as $command) {
        $output = [];
        $status = 0;

        exec($command . ' 2>&1', $output, $status);

        $attempts[] = [
            'command' => $command,
            'status' => $status,
            'output' => implode("\n", $output)
        ];

        if ($status === 0) {
            logSecurityEvent('apache_restart_requested', [
                'command' => $command,
                'output' => shortenOutput($output)
            ]);

            successResponse([
                'command' => $command,
                'output' => implode("\n", $output)
            ], 'Apache restart command executed');
        }
    }

    logSecurityEvent('apache_restart_failed', [
        'attempts' => array_map(function ($attempt) {
            return [
                'command' => $attempt['command'],
                'status' => $attempt['status'],
                'output' => shortenOutput($attempt['output'])
            ];
        }, $attempts)
    ]);

    errorResponse(
        'Failed to restart Apache automatically. Please restart manually or adjust sudo permissions.',
        500,
        ['attempts' => $attempts]
    );
}

function shortenOutput($output) {
    $text = is_array($output) ? implode("\n", $output) : (string) $output;
    $text = preg_replace('/[^[:print:]\r\n\t]/', '', $text);
    if (strlen($text) > 500) {
        $text = substr($text, 0, 497) . '...';
    }
    return $text;
}
