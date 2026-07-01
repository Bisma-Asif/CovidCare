<?php
/**
 * Database Initialization Script
 * Imports schema from chk_db.sql if database is empty
 */

// Get database configuration from environment variables
$servername = getenv('MYSQLHOST') ?: false;
$username   = getenv('MYSQLUSER') ?: false;
$password   = getenv('MYSQLPASSWORD') ?: false;
$dbname     = getenv('MYSQLDATABASE') ?: false;
$port       = getenv('MYSQLPORT') ?: 3306;

if (!$servername || !$username || !$dbname) {
    echo "[INIT-DB] ⚠️  Database credentials not configured. Skipping schema import.\n";
    exit(0);
}

try {
    // Connect to MySQL
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        echo "[INIT-DB] ❌ Connection failed: " . $conn->connect_error . "\n";
        exit(1);
    }
    
    echo "[INIT-DB] ✅ Connected to database: $dbname\n";
    
    // Check if tables already exist
    $result = $conn->query("SHOW TABLES");
    $table_count = $result ? $result->num_rows : 0;
    
    if ($table_count > 0) {
        echo "[INIT-DB] ℹ️  Database already initialized ($table_count tables found). Skipping import.\n";
        $conn->close();
        exit(0);
    }
    
    echo "[INIT-DB] 📥 Database is empty. Importing schema from chk_db.sql...\n";
    
    // Read and execute SQL file
    $sql_file = '/app/chk_db.sql';
    if (!file_exists($sql_file)) {
        echo "[INIT-DB] ❌ Schema file not found: $sql_file\n";
        $conn->close();
        exit(1);
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split SQL statements and execute them
    $statements = array_filter(
        array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );
    
    $imported = 0;
    foreach ($statements as $statement) {
        if ($conn->query($statement) === true) {
            $imported++;
        } else {
            echo "[INIT-DB] ⚠️  Error executing statement: " . $conn->error . "\n";
        }
    }
    
    echo "[INIT-DB] ✅ Schema imported successfully! ($imported statements executed)\n";
    
    // Verify tables
    $result = $conn->query("SHOW TABLES");
    $final_count = $result ? $result->num_rows : 0;
    echo "[INIT-DB] ✅ Database now contains $final_count tables\n";
    
    $conn->close();
    exit(0);
    
} catch (Exception $e) {
    echo "[INIT-DB] ❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}
?>

