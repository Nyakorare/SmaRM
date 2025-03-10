<?php
require '../../php/config.php'; // Include your database connection.
session_start(); // Start the session to access the session variable

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect if not an admin
    header("Location: ../landing-pages/login-page.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Admin - Dashboard</title>
  <link rel="stylesheet" href="./teamss.css">
</head>
<body>
  <!-- Navbar -->
  <nav id="navbar">
    <div class="nav-left">
      <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <ul class="nav-middle">
      <li><a href="./admin.php">Dashboard</a></li>
      <li><a href="./accounts.php">Accounts</a></li>
      <li><a href="#">Team Management</a></li>
    </ul>
    <div class="nav-right">
      <div class="dropdown">
        <button class="dropdown-btn">Menu</button>
        <div class="dropdown-menu">
          <button id="logout"><a href="../../php/logout.php">Logout</a></button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="dashboard">
    <!-- Sidebar for Teams -->
    <div id="sidebar">
      <h3>Active Teams</h3>
      <ul id="team-list">
        <?php
        // Fetch all active teams from the team_list table
        $stmt = $conn->prepare("SELECT * FROM team_list");
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Loop through teams and display them
        while ($row = $result->fetch_assoc()) {
          echo "<li>
                  {$row['department']}
                  <button onclick=\"deleteTeam('{$row['department']}')\">Delete</button>
                  <button onclick=\"editTeam('{$row['department']}')\">Edit</button>
                </li>";
        }
        $stmt->close();
        ?>
      </ul>
      <input type="text" id="new-team-name" placeholder="Add New Team">
      <button onclick="addTeam()">Add Team</button>
    </div>

    <!-- Teams Section -->
    <section id="teams-section">
      <h2>Teacher Team Management</h2>
      <table id="teams-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Current Team</th>
            <th>Actions</th>
          </tr>
        </thead>
          <tbody>
          <?php
          // Fetch users and their associated teams
          $stmt = $conn->prepare("SELECT u.id AS user_id, u.username, u.email, t.department 
                                  FROM users u 
                                  LEFT JOIN teams t ON u.id = t.user_id 
                                  WHERE u.username != 'admin'");
          $stmt->execute();
          $result = $stmt->get_result();

          // Check if there are any users
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $team = $row['department'] ? $row['department'] : 'Not Assigned';
                  echo "<tr>
                          <td>{$row['username']}</td>
                          <td>{$row['email']}</td>
                          <td>{$team}</td>
                          <td>";

                  // Get active teams from the database, excluding the user's current team
                  $teams_stmt = $conn->prepare("SELECT department FROM team_list WHERE department != ?");
                  $teams_stmt->bind_param("s", $team);
                  $teams_stmt->execute();
                  $teams_result = $teams_stmt->get_result();

                  // Display dropdown to assign/change team
                  echo "<select id='team-select-{$row['user_id']}'>";
                  while ($team_row = $teams_result->fetch_assoc()) {
                      $selected = $team_row['department'] === $team ? 'selected' : '';
                      echo "<option value='{$team_row['department']}' {$selected}>{$team_row['department']}</option>";
                  }
                  echo "</select>";

                  if ($team === 'Not Assigned') {
                      // User not in a team, show "Assign Team" button
                      echo "<button onclick=\"assignTeam({$row['user_id']})\">Assign Team</button>";
                  } else {
                      // User is in a team, show "Change Team" and "Delete from Team"
                      echo "<button onclick=\"changeTeam({$row['user_id']})\">Change Team</button>
                            <button onclick=\"deleteFromTeam({$row['user_id']})\">Delete from Team</button>";
                  }
                  echo "</td>
                        </tr>";
              }
          } else {
              echo "<tr>
                      <td colspan='4'>No accounts found.</td>
                    </tr>";
          }
          $stmt->close();
          ?>
        </tbody>
      </table>
    </section>
  </main>

  <footer>
    <p>&copy 2024 Room Scheduler. All rights reserved.</p>
  </footer>

  <script src="./teams.js"></script>
</body>
</html>