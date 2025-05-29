<?php
session_start();

// Disable error reporting to prevent HTML errors from being output
error_reporting(0);
ini_set('display_errors', 0);

// Set proper JSON header
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

require '../../php/config.php';

// Get room number from query parameter
$room_number = isset($_GET['room']) ? intval($_GET['room']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : null;

// Validate room number (should be 1-5)
if ($room_number < 1 || $room_number > 5) {
    echo json_encode(['error' => 'Invalid room number']);
    exit();
}

try {
    // Prepare the query to get approved schedules for the room
    $query = "
        SELECT 
            ar.schedule_date,
            ar.start_time,
            ar.end_time,
            t.department,
            u.username,
            ar.scheduler_name
        FROM approved_requests ar
        JOIN users u ON u.id = ar.user_id
        LEFT JOIN teams t ON t.user_id = u.id
        WHERE ar.room_number = ?
    ";

    $params = [$room_number];
    $types = "i";

    // Add date filter if provided
    if ($date) {
        $query .= " AND ar.schedule_date = ?";
        $params[] = $date;
        $types .= "s";
    }

    $query .= " ORDER BY ar.schedule_date, ar.start_time";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    if (!$stmt->bind_param($types, ...$params)) {
        throw new Exception("Parameter binding failed: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = [
            'schedule_date' => $row['schedule_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'department' => $row['department'] ?? 'No Department',
            'username' => $row['username'],
            'scheduler_name' => $row['scheduler_name']
        ];
    }

    $stmt->close();
    $conn->close();

    // Return the schedules as JSON
    echo json_encode($schedules);
    
} catch (Exception $e) {
    error_log("Error in getRoomSchedules.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch schedules: ' . $e->getMessage()]);
}
?>