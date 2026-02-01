<?php
require_once dirname(__DIR__) . '/config/config.php';

echo "Starting quiz tables migration...\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully.\n";
    
    // Read and execute the migration SQL
    $sqlFile = dirname(__FILE__) . '/create_quiz_tables.sql';
    if (!file_exists($sqlFile)) {
        die("Migration file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            if (!$conn->query($statement)) {
                echo "Error: " . $conn->error . "\n";
                echo "Statement: $statement\n";
            } else {
                echo "Success!\n";
            }
        }
    }
    
    echo "\nMigration completed!\n";
    
    // Verify tables were created
    $tables = ['quizzes', 'quiz_questions', 'quiz_options', 'quiz_attempts', 'quiz_answers'];
    echo "\nVerifying created tables:\n";
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table missing\n";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
