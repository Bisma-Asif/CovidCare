<?php
// Health check endpoint
header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'environment' => [
        'PORT' => getenv('PORT') ?: '8080',
        'MYSQLHOST' => getenv('MYSQLHOST') ?: 'not-set',
    ]
];

// Check database connection
$servername = getenv('MYSQLHOST');
$username   = getenv('MYSQLUSER');
$password   = getenv('MYSQLPASSWORD');
$dbname     = getenv('MYSQLDATABASE');
$port       = getenv('MYSQLPORT') ?: 3306;

if ($servername) {
    $db_conn = @new mysqli($servername, $username, $password, $dbname, $port);
    $health['database'] = $db_conn->connect_error ? 'ERROR: ' . $db_conn->connect_error : 'Connected';
} else {
    $health['database'] = 'Not configured';
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
