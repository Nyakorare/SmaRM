<?php
require '../../php/config.php'; // Database connection

// Fetch all active teams from the team_list table
$stmt = $conn->prepare("SELECT department FROM team_list");
$stmt->execute();
$result = $stmt->get_result();

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;  // Store the department names in the array
}

echo json_encode($teams);

$stmt->close();
$conn->close();
?>