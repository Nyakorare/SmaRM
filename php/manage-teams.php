<?php
require './config.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'];

if ($action === 'assign') {
    $user_id = $data['user_id'];
    $team = $data['team'];
    
    // Check if the user already has a team
    $check_stmt = $conn->prepare("SELECT * FROM teams WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "User is already in a team. Use Change Team instead."]);
    } else {
        $stmt = $conn->prepare("INSERT INTO teams (user_id, department) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $team);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Team assigned successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error assigning team."]);
        }
        $stmt->close();
    }
    $check_stmt->close();
} elseif ($action === 'change') {
    $user_id = $data['user_id'];
    $team = $data['team'];
    
    // Update the team for the user
    $stmt = $conn->prepare("UPDATE teams SET department = ? WHERE user_id = ?");
    $stmt->bind_param("si", $team, $user_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Team updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating team."]);
    }
    $stmt->close();
} elseif ($action === 'delete') {
    $user_id = $data['user_id'];
    
    // Remove the team for the user
    $stmt = $conn->prepare("DELETE FROM teams WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User removed from the team."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error removing user from team."]);
    }
    $stmt->close();
} elseif ($action === 'add_team') {
    $team = $data['team'];
    
    // Insert a new team into the team_list table
    $stmt = $conn->prepare("INSERT INTO team_list (department) VALUES (?)");
    $stmt->bind_param("s", $team);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "New team added successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error adding new team."]);
    }
    $stmt->close();
} elseif ($action === 'delete_team') {
    $team = $data['team'];
    
    // Remove the team from the team_list table
    $stmt = $conn->prepare("DELETE FROM team_list WHERE department = ?");
    $stmt->bind_param("s", $team);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Team deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting team."]);
    }
    $stmt->close();
} elseif ($action === 'update_team') {
    $old_team = $data['old_team'];
    $new_team = $data['new_team'];
    
    // Check if the new team name already exists
    $check_stmt = $conn->prepare("SELECT * FROM team_list WHERE department = ?");
    $check_stmt->bind_param("s", $new_team);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "A team with this name already exists. Please choose a different name."]);
    } else {
        // Update the department name in the team_list table
        $stmt = $conn->prepare("UPDATE team_list SET department = ? WHERE department = ?");
        $stmt->bind_param("ss", $new_team, $old_team);
        if ($stmt->execute()) {
            // Now update the `teams` table to reflect the new department name for all users in the team
            $update_stmt = $conn->prepare("UPDATE teams SET department = ? WHERE department = ?");
            $update_stmt->bind_param("ss", $new_team, $old_team);
            if ($update_stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Team name and all associated users' teams updated successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error updating users' teams."]);
            }
            $update_stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error updating team name."]);
        }
        $stmt->close();
    }

    $check_stmt->close();
}

$conn->close();