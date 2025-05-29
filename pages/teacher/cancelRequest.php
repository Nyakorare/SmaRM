<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo "Unauthorized access.";
    exit();
}

require '../../php/config.php';

// Debug information
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $scheduler_name = $_POST['scheduler_name'];
    $room_number = $_POST['room_number'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $username = $_SESSION['username'];

    // Debug information
    error_log("Processing cancel request for user: " . $username);
    error_log("Scheduler: " . $scheduler_name);
    error_log("Room: " . $room_number);
    error_log("Date: " . $schedule_date);
    error_log("Time: " . $start_time . " - " . $end_time);

    // Get user ID
    $user_id_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $user_id_query->bind_param("s", $username);
    $user_id_query->execute();
    $user_id_query->store_result();
    $user_id_query->bind_result($user_id);
    $user_id_query->fetch();
    $user_id_query->close();

    // Debug information
    error_log("User ID: " . $user_id);

    // Delete the schedule request
    $delete_stmt = $conn->prepare("DELETE FROM scheduler_requests WHERE user_id = ? AND scheduler_name = ? AND room_number = ? AND schedule_date = ? AND start_time = ? AND end_time = ?");
    $delete_stmt->bind_param("isssss", $user_id, $scheduler_name, $room_number, $schedule_date, $start_time, $end_time);
    
    if ($delete_stmt->execute()) {
        if ($delete_stmt->affected_rows > 0) {
            echo "Schedule request cancelled successfully!";
            error_log("Schedule request cancelled successfully");
        } else {
            echo "No matching schedule request found.";
            error_log("No matching schedule request found");
        }
    } else {
        echo "Error cancelling schedule request: " . $conn->error;
        error_log("Error cancelling schedule request: " . $conn->error);
    }
    
    $delete_stmt->close();
} else {
    echo "Invalid request. Required parameters missing.";
    error_log("Invalid request - Missing cancel_request parameter or wrong method");
}

$conn->close();
?> 