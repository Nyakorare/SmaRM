<?php
require 'config.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$userId = $data['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // First delete related records from teams table
    $stmt = $conn->prepare("DELETE FROM teams WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Then delete related records from approved_requests table
    $stmt = $conn->prepare("DELETE FROM approved_requests WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Then delete related records from schedulers table
    $stmt = $conn->prepare("DELETE FROM schedulers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Finally delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        throw new Exception('User not found or cannot be deleted');
    }
    
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>