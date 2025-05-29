<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require '../php/config.php';

$room = isset($_GET['room']) ? intval($_GET['room']) : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$time = isset($_GET['time']) ? $_GET['time'] : null;

if (!$room) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Room number is required']);
    exit();
}

// Base query
$query = "
    SELECT ar.room_number, ar.scheduler_name, ar.schedule_date, ar.start_time, ar.end_time, ar.user_id, u.username, t.department
    FROM approved_requests ar
    JOIN users u ON ar.user_id = u.id
    LEFT JOIN teams t ON u.id = t.user_id
    WHERE ar.room_number = ?
";

$params = [$room];
$types = "i";

// Add date filter if provided
if ($date) {
    $query .= " AND ar.schedule_date = ?";
    $params[] = $date;
    $types .= "s";
}

// Add time filter if provided
if ($time) {
    $query .= " AND ? BETWEEN ar.start_time AND ar.end_time";
    $params[] = $time;
    $types .= "s";
}

$query .= " ORDER BY ar.schedule_date, ar.start_time";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = [
        'room_number' => $row['room_number'],
        'scheduler_name' => $row['scheduler_name'],
        'schedule_date' => $row['schedule_date'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'user_id' => $row['user_id'],
        'username' => $row['username'],
        'department' => $row['department'] ? $row['department'] : 'Not Assigned'
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($schedules); 