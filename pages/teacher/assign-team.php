<?php
session_start();
require '../../php/config.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

$username = $_SESSION['username'];
$data = json_decode(file_get_contents('php://input'), true);
$department = $data['department'];

// Get user_id from the username
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit();
}

// Insert or update the user's team assignment
$stmt = $conn->prepare("INSERT INTO teams (user_id, department) VALUES (?, ?) ON DUPLICATE KEY UPDATE department = ?");
$stmt->bind_param("iss", $user_id, $department, $department);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$stmt->close();
$conn->close();
?>