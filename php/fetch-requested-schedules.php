<?php
session_start();
require './config.php';

$username = $_SESSION['username'];

// Fetch requested schedules for the logged-in user
$requestSchedulesQuery = $conn->prepare("
    SELECT sr.room_number, sr.scheduler_name, sr.schedule_date, sr.start_time, sr.end_time
    FROM scheduler_requests sr
    JOIN users u ON u.id = sr.user_id
    WHERE u.username = ? 
    ORDER BY sr.schedule_date, sr.start_time
");
$requestSchedulesQuery->bind_param("s", $username);
$requestSchedulesQuery->execute();
$requestSchedulesQuery->store_result();
$requestSchedulesQuery->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time);
$requestSchedules = [];

while ($requestSchedulesQuery->fetch()) {
    $requestSchedules[] = [
        'room_number' => $room_number,
        'scheduler_name' => $scheduler_name,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ];
}

$requestSchedulesQuery->close();

// Return schedules as HTML
if (count($requestSchedules) > 0) {
    echo "<ul>";
    foreach ($requestSchedules as $schedule) {
        echo "<li>";
        echo "<strong>Scheduler Name:</strong> " . htmlspecialchars($schedule['scheduler_name']) . "<br>";
        echo "<strong>Room:</strong> Room " . htmlspecialchars($schedule['room_number']) . "<br>";
        echo "<strong>Date:</strong> " . htmlspecialchars($schedule['schedule_date']) . "<br>";
        echo "<strong>Time:</strong> " . htmlspecialchars($schedule['start_time']) . " - " . htmlspecialchars($schedule['end_time']) . "<br>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>You have no requested schedules.</p>";
}
?>