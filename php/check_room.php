<?php
// Include your database connection here
include('./config.php');

// Get the room number and time from the POST request
$room = $_POST['room'];
$time = $_POST['time'];
$date = $_POST['date'];

// Set timezone to match your server's timezone
date_default_timezone_set('Asia/Manila');

// Use the provided date instead of current date
$schedule_date = date('Y-m-d', strtotime($date));

// Debug information
error_log("Checking room: " . $room);
error_log("Time: " . $time);
error_log("Schedule date: " . $schedule_date);

// Prepare and execute the query
$stmt = $conn->prepare("
    SELECT ar.*, u.username, t.department 
    FROM approved_requests ar
    JOIN users u ON ar.user_id = u.id
    LEFT JOIN teams t ON u.id = t.user_id
    WHERE ar.room_number = ? 
    AND ar.schedule_date = ?
    AND ? BETWEEN TIME(ar.start_time) AND TIME(ar.end_time)
    ORDER BY ar.start_time ASC
");
$stmt->bind_param("sss", $room, $schedule_date, $time);
$stmt->execute();
$result = $stmt->get_result();

// Debug information
error_log("Number of results found: " . $result->num_rows);

// Check if a room is scheduled at this time
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response = [
        'available' => false,
        'scheduler_name' => $row['scheduler_name'],
        'username' => $row['username'],
        'department' => $row['department'] ? $row['department'] : 'No Department',
        'start_time' => date('h:i A', strtotime($row['start_time'])),
        'end_time' => date('h:i A', strtotime($row['end_time'])),
    ];
    error_log("Room is booked. Details: " . json_encode($response));
} else {
    $response = [
        'available' => true,
    ];
    error_log("Room is available");
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>