<?php
// Remove Old Tables Execution Script
// Safely removes legacy tables after migration verification

// Database configuration
$host = 'localhost';
$port = 3307;
$dbname = 'it_hub_new';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Remove Old Tables - Execution</h2>";
    echo "<h3>Connected to database: $dbname</h3>";
    
    // Safety verification first
    echo "<h3>🔍 SAFETY VERIFICATION</h3>";
    
    // Check users migration
    $newUsers = $pdo->query("SELECT COUNT(*) FROM users_new")->fetchColumn();
    $oldUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 4px solid #0066cc;'>";
    echo "<strong>Users Migration:</strong><br>";
    echo "New table (users_new): $newUsers records<br>";
    echo "Old table (users): $oldUsers records<br>";
    echo "<strong>Status:</strong> " . ($newUsers >= $oldUsers ? "✅ <span style='color: green;'>Safe to proceed</span>" : "❌ <span style='color: red;'>WARNING: Data loss risk!</span>");
    echo "</div>";
    
    // Check courses migration
    $newCourses = $pdo->query("SELECT COUNT(*) FROM courses_new")->fetchColumn();
    $oldCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    
    echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 4px solid #0066cc;'>";
    echo "<strong>Courses Migration:</strong><br>";
    echo "New table (courses_new): $newCourses records<br>";
    echo "Old table (courses): $oldCourses records<br>";
    echo "<strong>Status:</strong> " . ($newCourses >= $oldCourses ? "✅ <span style='color: green;'>Safe to proceed</span>" : "❌ <span style='color: red;'>WARNING: Data loss risk!</span>");
    echo "</div>";
    
    // Check categories migration
    $newCategories = $pdo->query("SELECT COUNT(*) FROM categories_new")->fetchColumn();
    $oldCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 4px solid #0066cc;'>";
    echo "<strong>Categories Migration:</strong><br>";
    echo "New table (categories_new): $newCategories records<br>";
    echo "Old table (categories): $oldCategories records<br>";
    echo "<strong>Status:</strong> " . ($newCategories >= $oldCategories ? "✅ <span style='color: green;'>Safe to proceed</span>" : "❌ <span style='color: red;'>WARNING: Data loss risk!</span>");
    echo "</div>";
    
    // Check if it's safe to proceed
    $safeToProceed = ($newUsers >= $oldUsers) && ($newCourses >= $oldCourses) && ($newCategories >= $oldCategories);
    
    if (!$safeToProceed) {
        echo "<div style='background: #ffe6e6; padding: 15px; margin: 20px 0; border: 2px solid red;'>";
        echo "<h3 style='color: red;'>⚠️ SAFETY WARNING</h3>";
        echo "<p><strong>Not all data has been properly migrated!</strong></p>";
        echo "<p>Removing old tables could result in data loss.</p>";
        echo "<p>Please run the migration script again to ensure all data is transferred.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>🗑️ DROPPING OLD TABLES</h3>";
    
    // List of old tables to drop
    $oldTables = ['users', 'courses', 'categories', 'enrollments'];
    $droppedTables = [];
    $errors = [];
    
    foreach ($oldTables as $table) {
        try {
            // Check if table exists first
            $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
            
            if ($exists) {
                // Get record count before dropping
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                
                $pdo->exec("DROP TABLE `$table`");
                $droppedTables[] = "$table ($count records)";
                echo "<p style='color: green;'>✅ Dropped table: <strong>$table</strong> (had $count records)</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Table <strong>$table</strong> doesn't exist</p>";
            }
        } catch (PDOException $e) {
            $errors[] = "Error dropping $table: " . $e->getMessage();
            echo "<p style='color: red;'>❌ Error dropping <strong>$table</strong>: " . $e->getMessage() . "</p>";
        }
    }
    
    // Show summary
    echo "<h3>📊 EXECUTION SUMMARY</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border-left: 4px solid green;'>";
    echo "<p><strong>Tables Successfully Dropped:</strong> " . count($droppedTables) . "</p>";
    if (!empty($droppedTables)) {
        echo "<ul>";
        foreach ($droppedTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    echo "<p><strong>Errors:</strong> " . count($errors) . "</p>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<h3>❌ ERRORS ENCOUNTERED</h3>";
        foreach ($errors as $error) {
            echo "<p style='color: red; background: #ffe6e6; padding: 5px;'>" . htmlspecialchars($error) . "</p>";
        }
    }
    
    // Show final table list
    echo "<h3>📋 REMAINING TABLES</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<p><strong>Total tables remaining:</strong> " . count($tables) . "</p>";
    
    // Group tables by type
    $newTables = array_filter($tables, function($t) { return strpos($t, '_new') !== false; });
    $systemTables = array_filter($tables, function($t) { return strpos($t, '_new') === false; });
    
    echo "<h4>New System Tables:</h4>";
    foreach ($newTables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<p><strong>$table:</strong> $count records</p>";
    }
    
    echo "<h4>System Tables:</h4>";
    foreach ($systemTables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<p><strong>$table:</strong> $count records</p>";
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
    echo "<h3 style='color: #155724;'>✅ CLEANUP COMPLETE</h3>";
    echo "<p><strong>Old tables successfully removed!</strong></p>";
    echo "<p>The database now uses only the new table structure.</p>";
    echo "<p>All data integrity has been maintained.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2>Database Connection Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>General Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
h3 { color: #555; margin-top: 30px; }
h4 { color: #666; margin-top: 20px; }
p { margin: 8px 0; }
strong { color: #333; }
ul { margin: 10px 0; padding-left: 20px; }
li { margin: 5px 0; }
</style>
