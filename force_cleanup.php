<?php
// Force Cleanup Script - Remove old tables with constraint handling
$pdo = new PDO('mysql:host=localhost;port=3307;dbname=it_hub_new', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== FINAL CLEANUP ===\n";

// Disable foreign key checks temporarily
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
echo "Disabled foreign key checks\n";

// Drop remaining old tables
$tables = ['users', 'courses'];
foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $pdo->exec("DROP TABLE `$table`");
        echo "✅ Dropped $table ($count records)\n";
    } catch (Exception $e) {
        echo "❌ Table $table doesn't exist or error: " . $e->getMessage() . "\n";
    }
}

// Re-enable foreign key checks
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Re-enabled foreign key checks\n";

// Show final status
echo "\n=== FINAL STATUS ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Remaining tables: " . count($tables) . "\n";

$newTables = array_filter($tables, function($t) { return strpos($t, '_new') !== false; });
echo "New system tables: " . count($newTables) . "\n";

foreach ($newTables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    echo "  $table: $count records\n";
}

echo "\n🎉 Database cleanup completed successfully!\n";
echo "System now uses only new table structure.\n";
?>
