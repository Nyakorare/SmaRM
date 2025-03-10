<?php
session_start();
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$scheduler_name = $data['scheduler_name'];
$room_number = $data['room_number'];
$schedule_date = $data['schedule_date'];
$start_time = $data['start_time'];
$duration_hours = $data['duration_hours'];

// Get current user's ID
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// Calculate end time
$end_time = date("H:i:s", strtotime("$start_time + $duration_hours hours"));

// Check for schedule conflicts limited to the logged-in user's account
$conflictQuery = $conn->prepare("
    SELECT * FROM scheduler_requests 
    WHERE user_id = ? AND room_number = ? AND schedule_date = ? 
    AND (
        (start_time BETWEEN ? AND ?)
        OR (end_time BETWEEN ? AND ?)
        OR (? BETWEEN start_time AND end_time)
    )
");
$conflictQuery->bind_param("iissssss", $user_id, $room_number, $schedule_date, $start_time, $end_time, $start_time, $end_time, $start_time);
$conflictQuery->execute();
$conflictQuery->store_result();

if ($conflictQuery->num_rows > 0) {
    echo "Conflict detected. Please choose another time.";
    $conflictQuery->close();
    exit();
}
$conflictQuery->close();

// Insert new schedule into scheduler_requests
$insertStmt = $conn->prepare("
    INSERT INTO scheduler_requests (user_id, room_number, scheduler_name, schedule_date, start_time, end_time)
    VALUES (?, ?, ?, ?, ?, ?)
");
$insertStmt->bind_param("iissss", $user_id, $room_number, $scheduler_name, $schedule_date, $start_time, $end_time);
if ($insertStmt->execute()) {
    echo "Schedule created successfully!";
} else {
    echo "Failed to create schedule.";
    exit();
}
$insertStmt->close();

// Now delete from schedulers table
$deleteStmt = $conn->prepare("DELETE FROM schedulers WHERE user_id = ? AND scheduler_name = ?");
$deleteStmt->bind_param("is", $user_id, $scheduler_name);
if ($deleteStmt->execute()) {
    echo "Scheduler removed from schedulers table.";
} else {
    echo "Failed to remove scheduler from schedulers table.";
}
$deleteStmt->close();

$conn->close();
?>