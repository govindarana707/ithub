<?php
// Complete Migration Script
$pdo = new PDO('mysql:host=localhost;port=3307;dbname=it_hub_new', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== COMPLETING MIGRATION ===\n";

// Complete users migration
$stmt = $pdo->exec('INSERT IGNORE INTO users_new (id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at) SELECT id, username, email, password, full_name, role, profile_image, bio, phone, status, created_at, updated_at FROM users WHERE id NOT IN (SELECT id FROM users_new)');
echo "Migrated $stmt users\n";

// Complete categories migration  
$stmt = $pdo->exec('INSERT IGNORE INTO categories_new (id, name, description, created_at, updated_at) SELECT id, name, description, created_at, created_at FROM categories WHERE id NOT IN (SELECT id FROM categories_new)');
echo "Migrated $stmt categories\n";

// Check results
$newUsers = $pdo->query('SELECT COUNT(*) FROM users_new')->fetchColumn();
$oldUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$newCategories = $pdo->query('SELECT COUNT(*) FROM categories_new')->fetchColumn();
$oldCategories = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

echo "\n=== MIGRATION STATUS ===\n";
echo "Users: $newUsers/$oldUsers " . ($newUsers >= $oldUsers ? "✓" : "✗") . "\n";
echo "Categories: $newCategories/$oldCategories " . ($newCategories >= $oldCategories ? "✓" : "✗") . "\n";

if ($newUsers >= $oldUsers && $newCategories >= $oldCategories) {
    echo "\n✅ Migration complete! Safe to remove old tables.\n";
    
    // Now drop old tables
    echo "\n=== DROPPING OLD TABLES ===\n";
    
    $tables = ['users', 'categories', 'courses', 'enrollments'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $pdo->exec("DROP TABLE `$table`");
            echo "✅ Dropped $table ($count records)\n";
        } catch (Exception $e) {
            echo "❌ Error dropping $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Cleanup complete!\n";
} else {
    echo "\n❌ Migration incomplete. Do not drop old tables.\n";
}
?>
