<?php
session_start();
include_once './config.php'; // Include your database connection file

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Get the data from the request
$data = json_decode(file_get_contents('php://input'), true);
$newPassword = $data['password'];

// Sanitize the input to prevent SQL injection
$newPassword = mysqli_real_escape_string($conn, $newPassword);
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update the password in the database
$query = "UPDATE users SET password = '$hashedPassword' WHERE username = '{$_SESSION['username']}'";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating password.']);
}
?>