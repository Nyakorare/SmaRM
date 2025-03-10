<?php
session_start();
require '../../php/config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $room_number = $_POST['room_number'];
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $action = $_POST['action']; // Approve or Decline

    if ($action == 'approve') {
        // Check for schedule conflicts in the approved_requests table
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM approved_requests 
            WHERE room_number = ? AND schedule_date = ? 
            AND (
                (start_time < ? AND end_time > ?) OR 
                (start_time >= ? AND start_time < ?) OR 
                (end_time > ? AND end_time <= ?)
            )
        ");
        $stmt->bind_param(
            "ssssssss",
            $room_number,
            $schedule_date,
            $end_time, $start_time,  // First condition: Overlap
            $start_time, $end_time,  // Second condition: Starts within
            $start_time, $end_time   // Third condition: Ends within
        );
        $stmt->execute();
        $stmt->bind_result($conflictCount);
        $stmt->fetch();
        $stmt->close();

        // If there's a conflict, deny approval and show an alert
        if ($conflictCount > 0) {
            echo "<script>
                alert('Cannot approve schedule due to a conflict with an existing approved schedule.');
                window.location.href = './admin.php';
            </script>";
            exit();
        }

        // If no conflict, approve the request
        $stmt = $conn->prepare("
            INSERT INTO approved_requests (room_number, scheduler_name, schedule_date, start_time, end_time, user_id)
            SELECT room_number, scheduler_name, schedule_date, start_time, end_time, user_id
            FROM scheduler_requests
            WHERE room_number = ? AND schedule_date = ? AND start_time = ? AND end_time = ?
        ");
        $stmt->bind_param("ssss", $room_number, $schedule_date, $start_time, $end_time);
        $stmt->execute();
        $stmt->close();

        // Delete the request from the scheduler_requests table
        $stmt = $conn->prepare("
            DELETE FROM scheduler_requests 
            WHERE room_number = ? AND schedule_date = ? AND start_time = ? AND end_time = ?
        ");
        $stmt->bind_param("ssss", $room_number, $schedule_date, $start_time, $end_time);
        $stmt->execute();
        $stmt->close();

        // Success alert
        echo "<script>
            alert('Schedule approved successfully.');
            window.location.href = './admin.php';
        </script>";
        exit();
    } elseif ($action == 'decline') {
        // Decline the request
        $stmt = $conn->prepare("
            DELETE FROM scheduler_requests 
            WHERE room_number = ? AND schedule_date = ? AND start_time = ? AND end_time = ?
        ");
        $stmt->bind_param("ssss", $room_number, $schedule_date, $start_time, $end_time);
        $stmt->execute();
        $stmt->close();

        // Success alert for decline
        echo "<script>
            alert('Schedule declined successfully.');
            window.location.href = './admin.php';
        </script>";
        exit();
    }
}
?>