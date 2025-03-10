<?php
// Include your database connection here
include('./config.php');

// Get the room number and time from the POST request
$room = $_POST['room'];
$time = $_POST['time'];

// Query to check if the room is booked at the chosen time
$query = "
    SELECT * FROM approved_requests 
    WHERE room_number = ? 
    AND TIME(start_time) <= ? AND TIME(end_time) > ?;
";

$stmt = $conn->prepare($query);
$stmt->bind_param('sss', $room, $time, $time);
$stmt->execute();
$result = $stmt->get_result();

// Check if a room is scheduled at this time
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response = [
        'available' => false,
        'scheduler_name' => $row['scheduler_name'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
    ];
} else {
    $response = [
        'available' => true,
    ];
}

// Return the response as JSON
echo json_encode($response);
?>