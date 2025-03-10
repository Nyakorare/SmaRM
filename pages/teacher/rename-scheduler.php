<?php
session_start();
require '../../php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_scheduler_name = $_POST['old_scheduler_name'];
    $new_scheduler_name = $_POST['new_scheduler_name'];
    $username = $_SESSION['username'];

    // Check if the new scheduler name already exists for the same user
    $checkSchedulerStmt = $conn->prepare("SELECT id FROM schedulers WHERE user_id = (SELECT id FROM users WHERE username = ?) AND scheduler_name = ?");
    $checkSchedulerStmt->bind_param("ss", $username, $new_scheduler_name);
    $checkSchedulerStmt->execute();
    $checkSchedulerStmt->store_result();

    if ($checkSchedulerStmt->num_rows > 0 && $new_scheduler_name !== $old_scheduler_name) {
        // Scheduler name already exists and it's not the same as the old name
        echo "Scheduler name already exists. Please choose a different name.";
        exit();
    }
    $checkSchedulerStmt->close();

    // Proceed with renaming the scheduler
    $updateSchedulerStmt = $conn->prepare("UPDATE schedulers SET scheduler_name = ? WHERE user_id = (SELECT id FROM users WHERE username = ?) AND scheduler_name = ?");
    $updateSchedulerStmt->bind_param("sss", $new_scheduler_name, $username, $old_scheduler_name);
    $updateSchedulerStmt->execute();
    $updateSchedulerStmt->close();

    echo "Scheduler renamed successfully!";
}
?>