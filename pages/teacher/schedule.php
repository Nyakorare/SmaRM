<?php
session_start();

// Disable error reporting to prevent HTML errors from being output
error_reporting(0);
ini_set('display_errors', 0);

// Set proper JSON header
header('Content-Type: application/json');

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require '../../php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log the raw input and POST data
    error_log("Raw input: " . file_get_contents('php://input'));
    error_log("POST data: " . print_r($_POST, true));
    error_log("Content-Type: " . $_SERVER['CONTENT_TYPE']);

    // Get form data
    $scheduler_name = $_POST['scheduler_name'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $schedule_date = $_POST['schedule_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $duration_hours = $_POST['duration_hours'] ?? '';
    $username = $_SESSION['username'];

    // Convert room number to 1-5 range
    $room_number = intval($room_number);
    if ($room_number >= 321 && $room_number <= 325) {
        $room_number = $room_number - 320;
    }

    // Validate required fields
    if (empty($scheduler_name) || empty($room_number) || empty($schedule_date) || 
        empty($start_time) || empty($duration_hours)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields must not be empty',
            'debug' => [
                'scheduler_name' => $scheduler_name,
                'room_number' => $room_number,
                'schedule_date' => $schedule_date,
                'start_time' => $start_time,
                'duration_hours' => $duration_hours
            ]
        ]);
        exit();
    }

    // Validate duration
    if ($duration_hours < 1 || $duration_hours > 12) {
        echo json_encode(['success' => false, 'message' => 'Duration must be between 1 and 12 hours']);
        exit();
    }

    try {
        // Get user ID
        $user_id_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $user_id_query->bind_param("s", $username);
        $user_id_query->execute();
        $user_id_query->store_result();
        $user_id_query->bind_result($user_id);
        $user_id_query->fetch();
        $user_id_query->close();

        if (!$user_id) {
            throw new Exception("User not found");
        }

        // Calculate end time
        $start_datetime = new DateTime($start_time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . intval($duration_hours) . 'H'));
        $end_time = $end_datetime->format('H:i:s');

        // Check for approved schedule conflicts
        $approved_conflict_check = $conn->prepare("
            SELECT COUNT(*) as conflict_count 
            FROM approved_requests 
            WHERE room_number = ? 
            AND schedule_date = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $approved_conflict_check->bind_param("isssssss", 
            $room_number, 
            $schedule_date, 
            $start_time, $start_time,
            $end_time, $end_time,
            $start_time, $end_time
        );
        $approved_conflict_check->execute();
        $approved_conflict_result = $approved_conflict_check->get_result();
        $approved_conflict_row = $approved_conflict_result->fetch_assoc();
        $approved_conflict_check->close();

        if ($approved_conflict_row['conflict_count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'This time slot is already approved for another schedule']);
            exit();
        }

        // Check for user's own pending requests for the same time slot
        $checkUserStmt = $conn->prepare("
            SELECT * FROM scheduler_requests 
            WHERE user_id = ? 
            AND room_number = ? 
            AND schedule_date = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
        ");
        
        $checkUserStmt->bind_param("issssssss", 
            $user_id,
            $room_number, 
            $schedule_date, 
            $start_time, $start_time,
            $end_time, $end_time,
            $start_time, $end_time
        );
        
        $checkUserStmt->execute();
        $result = $checkUserStmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You already have a pending request for this time slot']);
            exit();
        }
        $checkUserStmt->close();

        // Insert the schedule request
        $insert_stmt = $conn->prepare("
            INSERT INTO scheduler_requests 
            (user_id, scheduler_name, room_number, schedule_date, start_time, end_time) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("isssss", 
            $user_id, 
            $scheduler_name, 
            $room_number, 
            $schedule_date, 
            $start_time, 
            $end_time
        );

        if ($insert_stmt->execute()) {
            // Delete the scheduler after successful schedule request
            $delete_scheduler = $conn->prepare("DELETE FROM schedulers WHERE user_id = ? AND scheduler_name = ?");
            $delete_scheduler->bind_param("is", $user_id, $scheduler_name);
            $delete_scheduler->execute();
            $delete_scheduler->close();

            echo json_encode([
                'success' => true, 
                'message' => 'Schedule request submitted successfully!'
            ]);
        } else {
            throw new Exception("Failed to submit schedule request");
        }

        $insert_stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?> 