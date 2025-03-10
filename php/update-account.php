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

$newUsername = $data['username'];
$newPassword = $data['password'];

// Sanitize the inputs to prevent SQL injection
if ($newUsername) {
  $newUsername = mysqli_real_escape_string($conn, $newUsername);
}

if ($newPassword) {
  $newPassword = mysqli_real_escape_string($conn, $newPassword);
  $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
}

// Update the username and/or password in the database if provided
$query = "UPDATE users SET 
          " . ($newUsername ? "username = '$newUsername'" : "") . "
          " . ($newPassword ? ", password = '$hashedPassword'" : "") . "
          WHERE username = '{$_SESSION['username']}'";

if (mysqli_query($conn, $query)) {
  // Update session username if username was changed
  if ($newUsername) {
    $_SESSION['username'] = $newUsername;
  }
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Error updating account.']);
}
?>