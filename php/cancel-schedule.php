<?php
session_start();
require 'config.php';

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['room_number']) && isset($data['schedule_date']) && 
        isset($data['start_time']) && isset($data['end_time'])) {
        
        $room_number = $data['room_number'];
        $schedule_date = $data['schedule_date'];
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];

        // Delete the schedule from approved_requests
        $stmt = $conn->prepare("
            DELETE FROM approved_requests 
            WHERE room_number = ? AND schedule_date = ? AND start_time = ? AND end_time = ?
        ");
        $stmt->bind_param("ssss", $room_number, $schedule_date, $start_time, $end_time);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Schedule cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel schedule']);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 