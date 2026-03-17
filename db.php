<?php
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$db   = getenv('DB_NAME') ?: "wedding";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed");
}
$conn->set_charset('utf8mb4');
?>