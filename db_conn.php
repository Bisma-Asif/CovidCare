<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'chk_db';

$db_conn = new mysqli( $servername, $username, $password, $dbname );
if ( $db_conn->connect_error ) {
    die( 'Connection failed: ' . $conn->connect_error );
}
?>
