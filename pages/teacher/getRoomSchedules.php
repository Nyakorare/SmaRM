<?php
require '../../php/config.php'; // Database connection

if (isset($_GET['room'])) {
    $roomNumber = $_GET['room'];

    // Query to fetch approved schedules for the specific room
    $stmt = $conn->prepare("
        SELECT ar.room_number, ar.schedule_date, ar.start_time, ar.end_time, u.username AS scheduler_name
        FROM approved_requests ar
        JOIN users u ON u.id = ar.user_id
        WHERE ar.room_number = ?
        ORDER BY ar.schedule_date, ar.start_time
    ");
    $stmt->bind_param("i", $roomNumber);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($room_number, $schedule_date, $start_time, $end_time, $scheduler_name);

    $roomSchedules = [];
    while ($stmt->fetch()) {
        $roomSchedules[] = [
            'room_number' => $room_number,
            'schedule_date' => $schedule_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'scheduler_name' => $scheduler_name,
        ];
    }
    $stmt->close();

    // Return the room schedules as JSON
    echo json_encode($roomSchedules);
} else {
    echo json_encode([]);
}

$conn->close();
?>