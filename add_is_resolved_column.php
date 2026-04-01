<?php
require_once 'config/config.php';

echo "<h2>Add is_resolved Column to discussions Table</h2>\n";

$conn = connectDB();

// Check if column already exists
echo "<h3>Checking if is_resolved column exists...</h3>\n";
$checkColumn = $conn->query("SHOW COLUMNS FROM discussions LIKE 'is_resolved'");

if ($checkColumn && $checkColumn->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Column 'is_resolved' already exists!</p>\n";
} else {
    echo "<p style='color: blue;'>📋 Column 'is_resolved' does not exist. Adding it...</p>\n";
    
    // Add the column
    $addColumn = $conn->query("ALTER TABLE discussions ADD COLUMN is_resolved TINYINT(1) DEFAULT 0 AFTER pinned");
    
    if ($addColumn) {
        echo "<p style='color: green;'>✅ Successfully added 'is_resolved' column!</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Failed to add column: " . $conn->error . "</p>\n";
    }
}

// Show updated table structure
echo "<h3>Updated discussions Table Structure:</h3>\n";
$structure = $conn->query("DESCRIBE discussions");

if ($structure) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    while ($row = $structure->fetch_assoc()) {
        $row_style = ($row['Field'] == 'is_resolved') ? "background-color: #e8f5e8;" : "";
        echo "<tr style='$row_style'>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

$conn->close();

echo "<hr>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>1. Refresh the discussions page to see if the resolve functionality works</li>\n";
echo "<li>2. The undefined array key errors should be resolved</li>\n";
echo "<li>3. Test the pin/resolve buttons functionality</li>\n";
echo "</ul>\n";

?>
