<?php
// SolusiPaymentManagement Database Setup Script

require_once 'config/database.php';

echo "Setting up SolusiPaymentManagement database...\n";

try {
    // Read schema file
    $schema = file_get_contents('database/schema.sql');

    // Convert MySQL schema to SQLite compatible
    $schema = preg_replace('/^USE solusipaymentmanagement;$/m', '', $schema);
    $schema = preg_replace('/ENGINE=InnoDB[^;]*/', '', $schema);
    $schema = preg_replace('/DEFAULT CHARSET=utf8mb4[^;]*/', '', $schema);
    $schema = preg_replace('/COLLATE utf8mb4_unicode_ci[^;]*/', '', $schema);
    $schema = preg_replace('/AUTO_INCREMENT/', 'AUTOINCREMENT', $schema);
    $schema = preg_replace('/TINYINT\(1\)/', 'INTEGER', $schema);
    $schema = preg_replace('/YEAR/', 'INTEGER', $schema);
    $schema = preg_replace('/ENUM\([^)]+\)/', "'default'", $schema); // Simplify ENUMs for SQLite
    $schema = preg_replace('/COMMENT \'[^\']*\'/', '', $schema);

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    $executed = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }

        try {
            $db->query($statement);
            $executed++;
            echo "✓ Executed statement " . $executed . "\n";
        } catch (Exception $e) {
            $errors++;
            echo "✗ Error in statement " . ($executed + $errors) . ": " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }

    echo "\nDatabase setup completed!\n";
    echo "Executed: $executed statements\n";
    echo "Errors: $errors\n";

    // Verify tables were created
    $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
    echo "\nCreated tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- " . $table['name'] . "\n";
    }

    // Check if admin user exists
    try {
        $admin = $db->fetchOne("SELECT * FROM pengguna WHERE email = ?", ['admin@solusipayment.local']);
        if ($admin) {
            echo "\n✓ Default admin user exists: admin@solusipayment.local\n";
        } else {
            echo "\n✗ Default admin user not found!\n";
        }
    } catch (Exception $e) {
        echo "\n⚠ Could not check admin user: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nSetup complete! You can now login with:\n";
echo "Email: admin@solusipayment.local\n";
echo "Password: Admin123!\n";
echo "Role: admin\n";
