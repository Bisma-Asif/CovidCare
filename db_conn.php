<?php
// Get database configuration from environment variables
$servername = getenv('MYSQLHOST') ?: false;
$username   = getenv('MYSQLUSER') ?: false;
$password   = getenv('MYSQLPASSWORD') ?: false;
$dbname     = getenv('MYSQLDATABASE') ?: false;
$port       = getenv('MYSQLPORT') ?: 3306;

$db_conn = null;

// Only attempt connection if environment variables are set
if ($servername && $username !== false) {
    try {
        $db_conn = new mysqli($servername, $username, $password, $dbname, $port);
        
        if ($db_conn->connect_error) {
            error_log("Database Connection Error: " . $db_conn->connect_error);
            $db_conn = null;
        }
    } catch (Exception $e) {
        error_log("Database Exception: " . $e->getMessage());
        $db_conn = null;
    }
} else {
    error_log("Database credentials not configured in environment variables");
}
?>