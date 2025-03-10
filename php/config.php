<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smarmdb"; // Use the database name you created in phpMyAdmin.

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>