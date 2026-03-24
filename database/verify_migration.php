<?php
/**
 * Database Migration Verification Script
 * 
 * This script helps verify that the database migration was successful
 * and all data is properly migrated.
 */

require_once '../config/config.php';

echo "<h1>🗄️ Database Migration Verification</h1>\n";

try {
    $conn = connectDB();
    
    echo "<h2>📊 Migration Status</h2>\n";
    
    // Check migration log
    $result = $conn->query("SELECT * FROM migration_log ORDER BY created_at DESC");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Step</th><th>Status</th><th>Message</th><th>Records Affected</th><th>Time</th></tr>";
    
    $completed_steps = 0;
    $failed_steps = 0;
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['migration_step']) . "</td>";
        echo "<td style='color: " . ($row['status'] == 'completed' ? 'green' : 'red') . "'>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
        echo "<td>" . $row['records_affected'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
        
        if ($row['status'] == 'completed') $completed_steps++;
        else $failed_steps++;
    }
    echo "</table>";
    
    echo "<h3>📈 Migration Summary</h3>\n";
    echo "<p><strong>Completed Steps:</strong> $completed_steps</p>";
    echo "<p><strong>Failed Steps:</strong> $failed_steps</p>";
    
    if ($failed_steps == 0) {
        echo "<p style='color: green; font-size: 18px;'>✅ Migration Completed Successfully!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Migration Failed - Check failed steps above</p>";
    }
    
    echo "<h2>🗃️ Table Structure Verification</h2>\n";
    
    // Check table structure
    $tables = $conn->query("SHOW TABLES");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Table Name</th><th>Rows</th><th>Size (MB)</th><th>Engine</th><th>Status</th></tr>";
    
    $total_tables = 0;
    $total_rows = 0;
    $total_size = 0;
    
    while ($table = $tables->fetch_row()) {
        $tableName = $table[0];
        
        // Skip backup tables
        if (strpos($tableName, 'backup_') === 0 || strpos($tableName, '_old') !== false || strpos($tableName, 'temp_') === 0) {
            continue;
        }
        
        // Get table info
        $info = $conn->query("SHOW TABLE STATUS LIKE '$tableName'")->fetch_assoc();
        $rows = $info['Rows'];
        $size = round(($info['Data_length'] + $info['Index_length']) / 1024 / 1024, 2);
        $engine = $info['Engine'];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($tableName) . "</td>";
        echo "<td>" . number_format($rows) . "</td>";
        echo "<td>" . $size . "</td>";
        echo "<td>" . $engine . "</td>";
        echo "<td style='color: green;'>✅ Active</td>";
        echo "</tr>";
        
        $total_tables++;
        $total_rows += $rows;
        $total_size += $size;
    }
    echo "</table>";
    
    echo "<h3>📊 Database Statistics</h3>\n";
    echo "<p><strong>Total Tables:</strong> $total_tables</p>";
    echo "<p><strong>Total Rows:</strong> " . number_format($total_rows) . "</p>";
    echo "<p><strong>Total Size:</strong> " . round($total_size, 2) . " MB</p>";
    
    echo "<h2>🔍 Data Integrity Verification</h2>\n";
    
    // Check critical tables
    $checks = [
        'users' => 'username, email, password_hash',
        'courses' => 'title, instructor_id, category_id',
        'enrollments' => 'student_id, course_id, status',
        'lessons' => 'title, course_id, lesson_order',
        'categories' => 'name, slug'
    ];
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Table</th><th>Total Records</th><th>NULL Values</th><th>Status</th></tr>";
    
    foreach ($checks as $table => $required_fields) {
        $result = $conn->query("SELECT COUNT(*) as total FROM $table");
        $total = $result->fetch_assoc()['total'];
        
        // Check for NULL values in required fields
        $fields = explode(', ', $required_fields);
        $nullCount = 0;
        
        foreach ($fields as $field) {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table WHERE $field IS NULL");
            $nullCount += $result->fetch_assoc()['count'];
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($table) . "</td>";
        echo "<td>" . number_format($total) . "</td>";
        echo "<td>" . $nullCount . "</td>";
        echo "<td style='color: " . ($nullCount == 0 ? 'green' : 'orange') . "'>" . ($nullCount == 0 ? '✅ Good' : '⚠️ Issues') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🚀 Performance Verification</h2>\n";
    
    // Check if views exist
    $views = $conn->query("SHOW FULL TABLES IN it_hub_new WHERE TABLE_TYPE = 'VIEW'");
    echo "<h3>📊 Views Available:</h3>\n";
    echo "<ul>";
    while ($view = $views->fetch_row()) {
        echo "<li>" . htmlspecialchars($view[0]) . "</li>";
    }
    echo "</ul>";
    
    // Check if stored procedures exist
    $procedures = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'it_hub_new'");
    echo "<h3>🔧 Stored Procedures Available:</h3>\n";
    echo "<ul>";
    while ($proc = $procedures->fetch_row()) {
        echo "<li>" . htmlspecialchars($proc[1]) . "</li>";
    }
    echo "</ul>";
    
    // Test a key view
    echo "<h2>🧪 Functionality Test</h2>\n";
    
    try {
        $result = $conn->query("SELECT * FROM v_course_statistics LIMIT 5");
        echo "<p>✅ v_course_statistics view working - " . $result->num_rows . " records returned</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ v_course_statistics view failed: " . $e->getMessage() . "</p>";
    }
    
    try {
        $conn->query("CALL sp_migration_health_check()");
        echo "<p>✅ sp_migration_health_check procedure working</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ sp_migration_health_check procedure failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Recommendations</h2>\n";
    
    if ($failed_steps > 0) {
        echo "<div style='background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
        echo "<h3 style='color: #d32f2f;'>⚠️ Action Required</h3>";
        echo "<p>Migration failed. Please check the failed steps in the migration log and consider running the rollback procedure.</p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50;'>";
        echo "<h3 style='color: #2e7d32;'>✅ Migration Successful!</h3>";
        echo "<p>Your database has been successfully migrated to the optimized schema. You can now:</p>";
        echo "<ul>";
        echo "<li>✅ Remove backup tables after 1 week</li>";
        echo "<li>✅ Monitor performance using the new views</li>";
        echo "<li>✅ Update application if needed</li>";
        echo "<li>✅ Train team on new structure</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h2>📞 Next Steps</h2>\n";
    echo "<ol>";
    echo "<li><strong>Test your application thoroughly</strong></li>";
    echo "<li><strong>Monitor performance for the first week</strong></li>";
    echo "<li><strong>Keep backup tables for at least 1 week</strong></li>";
    echo "<li><strong>Run performance monitoring queries regularly</strong></li>";
    echo "<li><strong>Document any issues found</strong></li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<p><small>Verification completed at: " . date('Y-m-d H:i:s') . "</small></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>An error occurred during verification: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
