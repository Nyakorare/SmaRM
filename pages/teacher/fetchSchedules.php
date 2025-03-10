<?php
require '../../php/config.php'; // Database connection

if (isset($_GET['room'])) {
    $roomNumber = $_GET['room'];

    // Fetch approved schedules for the room
    $stmt = $conn->prepare("
        SELECT ar.schedule_date, ar.start_time, ar.end_time, t.department
        FROM approved_requests ar
        JOIN users u ON u.id = ar.user_id
        JOIN teams t ON t.user_id = u.id
        WHERE ar.room_number = ? 
        ORDER BY ar.schedule_date, ar.start_time
    ");
    $stmt->bind_param("i", $roomNumber);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($schedule_date, $start_time, $end_time, $department);

    $schedules = [];
    while ($stmt->fetch()) {
        $schedules[] = [
            'schedule_date' => $schedule_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'department' => $department
        ];
    }
    $stmt->close();

    echo json_encode($schedules);
}
?>