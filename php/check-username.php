<?php
session_start();
include_once './config.php'; // Include your database connection file

// Get the data from the request
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];

// Sanitize the input to prevent SQL injection
$username = mysqli_real_escape_string($conn, $username);

// Query to check if the username already exists
$query = "SELECT * FROM users WHERE username = '$username' AND username != '{$_SESSION['username']}'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
  echo json_encode(['available' => false]); // Username exists
} else {
  echo json_encode(['available' => true]); // Username is available
}
?>