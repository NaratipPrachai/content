<?php
$host = "localhost";
$db = "media_system";
$user = "root";
$pass = "adminbhic";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
