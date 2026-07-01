<?php
$servername = getenv('MYSQLHOST');
$username   = getenv('MYSQLUSER');
$password   = getenv('MYSQLPASSWORD');
$dbname     = getenv('MYSQLDATABASE');
$port       = getenv('MYSQLPORT');

$db_conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($db_conn->connect_error) {
    die("Connection failed: " . $db_conn->connect_error);
}
?>