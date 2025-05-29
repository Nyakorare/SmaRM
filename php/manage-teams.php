<?php
require './config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Error handling
function returnJsonResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['action'])) {
        returnJsonResponse(false, 'Invalid request data');
    }
    
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
            returnJsonResponse(false, "User is already in a team. Use Change Team instead.");
        } else {
            // Insert the user into the team
            $stmt = $conn->prepare("INSERT INTO teams (user_id, department) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $team);
            if ($stmt->execute()) {
                returnJsonResponse(true, "Team assigned successfully.");
            } else {
                returnJsonResponse(false, "Error assigning team.");
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
            returnJsonResponse(true, "Team updated successfully.");
        } else {
            returnJsonResponse(false, "Error updating team.");
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $user_id = $data['user_id'];
        
        // Remove the team for the user
        $stmt = $conn->prepare("DELETE FROM teams WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            returnJsonResponse(true, "User removed from the team.");
        } else {
            returnJsonResponse(false, "Error removing user from team.");
        }
        $stmt->close();
    } elseif ($action === 'add_team') {
        $team = $data['team'];
        
        // Check if team already exists
        $check_stmt = $conn->prepare("SELECT * FROM team_list WHERE department = ?");
        $check_stmt->bind_param("s", $team);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            returnJsonResponse(false, "A team with this name already exists.");
        } else {
            // Insert a new team into the team_list table
            $stmt = $conn->prepare("INSERT INTO team_list (department) VALUES (?)");
            $stmt->bind_param("s", $team);
            if ($stmt->execute()) {
                returnJsonResponse(true, "New team added successfully.");
            } else {
                returnJsonResponse(false, "Error adding new team.");
            }
            $stmt->close();
        }
        $check_stmt->close();
    } elseif ($action === 'delete_team') {
        $team = $data['team'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First check how many users are in this team
            $check_stmt = $conn->prepare("SELECT COUNT(*) as member_count FROM teams WHERE department = ?");
            $check_stmt->bind_param("s", $team);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            $member_count = $row['member_count'];
            $check_stmt->close();
            
            if ($member_count > 0) {
                throw new Exception("Cannot delete team. There are {$member_count} member(s) currently in this team.");
            }
            
            // Delete from team_list (this will cascade to teams table due to foreign key)
            $stmt = $conn->prepare("DELETE FROM team_list WHERE department = ?");
            $stmt->bind_param("s", $team);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting team.");
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Team not found.");
            }
            
            $stmt->close();
            
            // If we got here, commit the transaction
            $conn->commit();
            returnJsonResponse(true, "Team deleted successfully.");
            
        } catch (Exception $e) {
            // If there was an error, rollback the transaction
            $conn->rollback();
            returnJsonResponse(false, $e->getMessage());
        }
    } elseif ($action === 'update_team') {
        $old_team = $data['old_team'];
        $new_team = $data['new_team'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if the new team name already exists
            $check_stmt = $conn->prepare("SELECT * FROM team_list WHERE department = ? AND department != ?");
            $check_stmt->bind_param("ss", $new_team, $old_team);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                throw new Exception("A team with this name already exists. Please choose a different name.");
            }
            $check_stmt->close();
            
            // First check if the old team exists
            $check_old = $conn->prepare("SELECT * FROM team_list WHERE department = ?");
            $check_old->bind_param("s", $old_team);
            $check_old->execute();
            $old_result = $check_old->get_result();
            
            if ($old_result->num_rows === 0) {
                throw new Exception("Team not found.");
            }
            $check_old->close();

            // First update the team_list table (parent table)
            $update_list = $conn->prepare("UPDATE team_list SET department = ? WHERE department = ?");
            $update_list->bind_param("ss", $new_team, $old_team);
            if (!$update_list->execute()) {
                throw new Exception("Error updating team name.");
            }
            $update_list->close();
            
            // Then update the teams table (child table)
            $update_teams = $conn->prepare("UPDATE teams SET department = ? WHERE department = ?");
            $update_teams->bind_param("ss", $new_team, $old_team);
            if (!$update_teams->execute()) {
                throw new Exception("Error updating users' teams.");
            }
            $update_teams->close();
            
            // If we got here, commit the transaction
            $conn->commit();
            returnJsonResponse(true, "Team and all associated users' teams updated successfully.");
            
        } catch (Exception $e) {
            // If there was an error, rollback the transaction
            $conn->rollback();
            returnJsonResponse(false, $e->getMessage());
        }
    } elseif ($action === 'get_teams') {
        // Fetch all teams from the team_list table
        $stmt = $conn->prepare("SELECT department FROM team_list ORDER BY department");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row['department'];
        }
        
        echo json_encode([
            "success" => true,
            "teams" => $teams
        ]);
        
        $stmt->close();
    } else {
        returnJsonResponse(false, "Invalid action specified.");
    }
} catch (Exception $e) {
    returnJsonResponse(false, "An error occurred: " . $e->getMessage());
}

$conn->close();