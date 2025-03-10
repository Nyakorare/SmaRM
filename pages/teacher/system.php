<?php
session_start();
require '../../php/config.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../pages/landing-pages/login.html");
    exit();
}

$username = $_SESSION['username'];

// Query to check if the user is assigned to a team
$stmt = $conn->prepare("SELECT department FROM teams WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($department);
    $stmt->fetch();
    // The user is already assigned to a team, so return the department
    echo json_encode(["teamAssigned" => true, "department" => $department]);
} else {
    // The user is not assigned to any team, allow them to choose a department
    echo json_encode(["teamAssigned" => false]);
}

$stmt->close();
$conn->close();
?>