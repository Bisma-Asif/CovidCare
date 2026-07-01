<?php
// Health check endpoint
header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
];

// Check for environment variables
$required_env_vars = ['MYSQLHOST', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE'];
$health['environment'] = [];

foreach ($required_env_vars as $var) {
    $value = getenv($var);
    $health['environment'][$var] = $value ? '✓ Set' : '✗ Not set';
}

// Check database connection
$servername = getenv('MYSQLHOST');
$username   = getenv('MYSQLUSER');
$password   = getenv('MYSQLPASSWORD');
$dbname     = getenv('MYSQLDATABASE');
$port       = getenv('MYSQLPORT') ?: 3306;

if ($servername && $username !== false) {
    try {
        $db_conn = @new mysqli($servername, $username, $password, $dbname, $port);
        if ($db_conn->connect_error) {
            $health['database'] = 'ERROR: ' . $db_conn->connect_error;
            $health['database_status'] = 'failed';
        } else {
            $health['database'] = 'Connected ✓';
            $health['database_status'] = 'connected';
            $db_conn->close();
        }
    } catch (Exception $e) {
        $health['database'] = 'ERROR: ' . $e->getMessage();
        $health['database_status'] = 'error';
    }
} else {
    $health['database'] = 'Environment variables not configured';
    $health['database_status'] = 'not_configured';
    $health['setup_instructions'] = [
        'step1' => 'Go to Railway Dashboard',
        'step2' => 'Click your deployment',
        'step3' => 'Go to Variables tab',
        'step4' => 'Add MySQL variables: MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT',
        'option' => 'Or click "Add" → "Database" → "MySQL" to create a new database'
    ];
}

$health['api_endpoints'] = [
    'health' => '/health.php',
    'login' => '/login_register.php',
    'admin' => '/Admin/dashboard.php (requires login)'
];

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

