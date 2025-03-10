<?php
session_start();
require '../../php/config.php';

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to delete a scheduler.";
    exit();
}

if (isset($_POST['scheduler_name'])) {
    $scheduler_name = $_POST['scheduler_name'];
    $username = $_SESSION['username'];

    // Get the user ID from the username
    $user_id_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $user_id_query->bind_param("s", $username);
    $user_id_query->execute();
    $user_id_query->store_result();
    $user_id_query->bind_result($user_id);
    $user_id_query->fetch();
    $user_id_query->close();

    // Delete the scheduler from the database
    $stmt = $conn->prepare("DELETE FROM schedulers WHERE user_id = ? AND scheduler_name = ?");
    $stmt->bind_param("is", $user_id, $scheduler_name);
    $stmt->execute();
    $stmt->close();

    echo "Scheduler deleted successfully!";
} else {
    echo "No scheduler specified.";
}

$conn->close();
?>