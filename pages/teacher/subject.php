<?php
session_start(); // Start the session to access the session variable

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
  // Redirect if not a teacher
  header("Location: ../landing-pages/login-page.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Teacher - Schedule Dashboard</title>
  <link rel="stylesheet" href="./subjects.css">
</head>
<body>
  <!-- Navbar -->
  <nav id="navbar">
    <a class="navbar-brand d-flex align-items-center" href="../../index.php">
                <img src="../../images/SmaRM-Logo.png" class="img-fluid logo-image">
                <div class="d-flex flex-column">
                    <strong class="logo-text">SmaRM</strong><br>
                    <small class="logo-slogan">Smart Room Management</small>
                </div>
      </a>
      <!-- Display logged-in user's name -->
	 <div class="nav-left">
      <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <ul class="nav-middle">
      <li><a href="./teacher.php">Dashboard</a></li>
      <li><a href="#">Subject Management</a></li>
    </ul>
    <div class="nav-right">
      <div class="dropdown">
        <button class="dropdown-btn">Menu</button>
        <div class="dropdown-menu">
          <button id="account-settings">Account Settings</button>
          <button id="logout"><a href="../../php/logout.php">Logout</a></button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="dashboard">
    <!-- Schedule Board -->
    <section id="schedule-board" class="dashboard-panel">
      <h2>Subject Management</h2>
      <div id="professor-pool" class="container">
        <h3>Team</h3>
        <div id="team-info">
          <!-- This will show the current team if the user is assigned -->
        </div>
        <div id="team-selection" style="display: none;">
          <label for="department-select">Choose a Department:</label>
          <select id="department-select">
            <!-- Dynamic teams will be populated here -->
          </select>
          <button id="assign-team-btn">Join Team</button>
        </div>
      </div>
    </section>
  </main>

<!-- Modal for Account Settings -->
      <div id="account-settings-modal" class="modal">
        <div class="modal-content">
          <h2>Update Account Settings</h2>
          <form id="account-settings-form">
            
            <!-- Username field with checkbox to enable/disable -->
            <label for="new-username">
              <input type="checkbox" id="change-username" name="change-username">
              Change Username
            </label>
            <input type="text" id="new-username" name="new-username" disabled required>
            <span id="username-error" style="color: red; display: none;">Username already exists.</span>
            
            <!-- Password field with checkbox to enable/disable -->
            <label for="new-password">
              <input type="checkbox" id="change-password" name="change-password">
              Change Password
            </label>
            <input type="password" id="new-password" name="new-password" disabled required>
            
            <div class="modal-buttons">
              <button type="button" id="close-account-settings">Cancel</button>
              <button type="submit">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
</div>

  <footer>
    <p>&copy 2024 Room Scheduler. All rights reserved.</p>
  </footer>

  <script src="./subject.js"></script>
</body>
</html>