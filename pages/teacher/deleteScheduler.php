<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo "Unauthorized access.";
    exit();
}

require '../../php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scheduler'])) {
    $scheduler_name = $_POST['scheduler_name'];
    $username = $_SESSION['username'];

    // Get user ID
    $user_id_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $user_id_query->bind_param("s", $username);
    $user_id_query->execute();
    $user_id_query->store_result();
    $user_id_query->bind_result($user_id);
    $user_id_query->fetch();
    $user_id_query->close();

    // Delete the scheduler
    $delete_stmt = $conn->prepare("DELETE FROM schedulers WHERE user_id = ? AND scheduler_name = ?");
    $delete_stmt->bind_param("is", $user_id, $scheduler_name);
    
    if ($delete_stmt->execute()) {
        if ($delete_stmt->affected_rows > 0) {
            echo "Scheduler deleted successfully!";
        } else {
            echo "No matching scheduler found.";
        }
    } else {
        echo "Error deleting scheduler: " . $conn->error;
    }
    
    $delete_stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?> 