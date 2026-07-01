<?php
/**
 * init-db.php
 * Imports chk_db.sql into the Railway MySQL database on first startup.
 * Idempotent: skips import if tables already exist.
 */

echo "[INIT-DB] Starting database initialisation...\n";

// Read connection details from environment
$host     = getenv('MYSQLHOST')     ?: null;
$user     = getenv('MYSQLUSER')     ?: null;
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname   = getenv('MYSQLDATABASE') ?: null;
$port     = (int)(getenv('MYSQLPORT') ?: 3306);

if (!$host || !$user || !$dbname) {
    echo "[INIT-DB] ERROR: Required environment variables (MYSQLHOST, MYSQLUSER, MYSQLDATABASE) are not set. Skipping import.\n";
    exit(0);
}

echo "[INIT-DB] Connecting to MySQL at {$host}:{$port} (database: {$dbname})...\n";

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    echo "[INIT-DB] ERROR: Could not connect to MySQL: " . $conn->connect_error . "\n";
    exit(1);
}

echo "[INIT-DB] Connected successfully.\n";

// Check whether tables already exist in this database
$result = $conn->query(
    "SELECT COUNT(*) AS table_count
     FROM information_schema.tables
     WHERE table_schema = '{$conn->real_escape_string($dbname)}'"
);

if (!$result) {
    echo "[INIT-DB] ERROR: Could not query information_schema: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

$row         = $result->fetch_assoc();
$table_count = (int)$row['table_count'];

if ($table_count > 0) {
    echo "[INIT-DB] Database already contains {$table_count} table(s). Skipping import.\n";
    $conn->close();
    exit(0);
}

echo "[INIT-DB] Database is empty. Importing schema from chk_db.sql...\n";

$sql_file = __DIR__ . '/chk_db.sql';

if (!file_exists($sql_file)) {
    echo "[INIT-DB] ERROR: chk_db.sql not found at {$sql_file}. Skipping import.\n";
    $conn->close();
    exit(1);
}

$sql = file_get_contents($sql_file);

if ($sql === false) {
    echo "[INIT-DB] ERROR: Could not read chk_db.sql.\n";
    $conn->close();
    exit(1);
}

// Enable multi-query so the full dump can be executed in one pass
if ($conn->multi_query($sql)) {
    $statements = 0;
    do {
        $statements++;
        // Consume all result sets to avoid "Commands out of sync" errors
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

if ($conn->error) {
    echo "[INIT-DB] ERROR: SQL import failed: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

echo "[INIT-DB] Schema imported successfully ({$statements} statement(s) executed).\n";

// Verify the tables were created
$verify = $conn->query(
    "SELECT COUNT(*) AS table_count
     FROM information_schema.tables
     WHERE table_schema = '{$conn->real_escape_string($dbname)}'"
);

if ($verify) {
    $vrow = $verify->fetch_assoc();
    echo "[INIT-DB] Verification: " . $vrow['table_count'] . " table(s) now exist in '{$dbname}'.\n";
}

$conn->close();
echo "[INIT-DB] Done.\n";
exit(0);
