<?php
require_once 'config/config.php';

$conn = connectDB();

// List all tables in database
$result = $conn->query("SHOW TABLES");

echo "<h1>All Tables in Database</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Table Name</th><th>Has 'enroll' in name?</th></tr>";

$enrollTables = [];
while ($row = $result->fetch_array()) {
    $tableName = $row[0];
    $isEnroll = stripos($tableName, 'enroll') !== false;
    if ($isEnroll) {
        $enrollTables[] = $tableName;
    }
    echo "<tr><td>$tableName</td><td>" . ($isEnroll ? "✅ YES" : "") . "</td></tr>";
}
echo "</table>";

// Show structure of enrollment-related tables
foreach ($enrollTables as $table) {
    echo "<h2>Structure of: $table</h2>";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Show sample data
        $result = $conn->query("SELECT * FROM $table LIMIT 5");
        if ($result && $result->num_rows > 0) {
            echo "<h4>Sample Data:</h4>";
            echo "<table border='1' cellpadding='5'>";
            $first = true;
            while ($row = $result->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach ($row as $key => $val) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

$conn->close();
?>
