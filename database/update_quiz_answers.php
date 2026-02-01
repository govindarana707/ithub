<?php
require_once dirname(__DIR__) . '/config/config.php';

echo "Updating quiz_answers table structure...\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully.\n";
    
    // Read and execute the update SQL
    $sqlFile = dirname(__FILE__) . '/update_quiz_answers_table.sql';
    if (!file_exists($sqlFile)) {
        die("Update file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 60) . "...\n";
            if (!$conn->query($statement)) {
                echo "Error: " . $conn->error . "\n";
                echo "Statement: $statement\n";
            } else {
                echo "Success!\n";
            }
        }
    }
    
    echo "\nUpdate completed!\n";
    
    // Verify the updated table structure
    echo "\nUpdated quiz_answers table structure:\n";
    $result = $conn->query("DESCRIBE quiz_answers");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . " " . $row['Null'] . " " . $row['Key'] . ")\n";
        }
    } else {
        echo "Error describing table: " . $conn->error . "\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Update failed: " . $e->getMessage() . "\n";
}
?>
