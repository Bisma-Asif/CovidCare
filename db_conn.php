<?php
// Get database configuration from environment variables
$servername = getenv('MYSQLHOST') ?: 'localhost';
$username   = getenv('MYSQLUSER') ?: 'root';
$password   = getenv('MYSQLPASSWORD') ?: '';
$dbname     = getenv('MYSQLDATABASE') ?: 'covidcare';
$port       = getenv('MYSQLPORT') ?: 3306;

// Suppress connection warnings during initial load
$db_conn = @new mysqli($servername, $username, $password, $dbname, $port);

// Check connection with proper error handling
if ($db_conn->connect_error) {
    // Log the error but don't die - allow static pages to load
    error_log("Database Connection Error: " . $db_conn->connect_error);
    // For static HTML pages, this is not critical
}
?>