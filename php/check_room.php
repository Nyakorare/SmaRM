<?php
// Include your database connection here
include('./config.php');

// Get the room number and time from the POST request
$room = $_POST['room'];
$time = $_POST['time'];

// Prepare and execute the query
$stmt = $conn->prepare("
    SELECT ar.*, u.username, t.department 
    FROM approved_requests ar
    JOIN users u ON ar.user_id = u.id
    LEFT JOIN teams t ON u.id = t.user_id
    WHERE ar.room_number = ? 
    AND ar.schedule_date = CURDATE()
    AND (
        (TIME(ar.start_time) <= ? AND TIME(ar.end_time) > ?) OR
        (TIME(ar.start_time) < ? AND TIME(ar.end_time) >= ?) OR
        (TIME(ar.start_time) >= ? AND TIME(ar.start_time) < ?)
    )
");
$stmt->bind_param("sssssss", $room, $time, $time, $time, $time, $time, $time);
$stmt->execute();
$result = $stmt->get_result();

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
} else {
    $response = [
        'available' => true,
    ];
}

// Return the response as JSON
echo json_encode($response);
?>