<?php
session_start(); // Start the session to access session variable

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    // Redirect if not a teacher
    header("Location: ../landing-pages/login-page.php");
    exit();
}

require '../../php/config.php'; // Database connection
$username = $_SESSION['username'];

// Query to check if the user is assigned to a team
$stmt = $conn->prepare("SELECT department FROM teams WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

$isAssignedToTeam = false;
$department = '';
if ($stmt->num_rows > 0) {
    $isAssignedToTeam = true;
    $stmt->bind_result($department);
    $stmt->fetch();
}
$stmt->close();

// Handle the add scheduler request (AJAX)
if (isset($_POST['add_scheduler'])) {
    $schedule_name = $_POST['schedule_name'];
    
    // Check if the schedule name already exists
    $checkScheduleStmt = $conn->prepare("SELECT id FROM schedulers WHERE user_id = (SELECT id FROM users WHERE username = ?) AND scheduler_name = ?");
    $checkScheduleStmt->bind_param("ss", $username, $schedule_name);
    $checkScheduleStmt->execute();
    $checkScheduleStmt->store_result();
    
    if ($checkScheduleStmt->num_rows > 0) {
        // Schedule name already exists, return an error message
        echo "Scheduler name already exists. Please choose a different name.";
        exit();
    }

    $checkScheduleStmt->close();

    // Get user ID
    $user_id_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $user_id_query->bind_param("s", $username);
    $user_id_query->execute();
    $user_id_query->store_result();
    $user_id_query->bind_result($user_id);
    $user_id_query->fetch();
    $user_id_query->close();

    // Insert the new scheduler into the database
    $stmt = $conn->prepare("INSERT INTO schedulers (user_id, scheduler_name, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $schedule_name);
    $stmt->execute();
    $stmt->close();
    
    echo "Scheduler added successfully!";
    exit();
}

if (isset($_POST['rename_scheduler'])) {
  $old_scheduler_name = $_POST['old_scheduler_name'];
  $new_scheduler_name = $_POST['new_scheduler_name'];

  // Check if the new scheduler name is the same as the old one
  if ($old_scheduler_name === $new_scheduler_name) {
      echo "The new scheduler name is the same as the old one.";
      exit();
  }

  // Check if the new scheduler name already exists for the same user
  $checkSchedulerStmt = $conn->prepare("SELECT id FROM schedulers WHERE user_id = (SELECT id FROM users WHERE username = ?) AND scheduler_name = ?");
  $checkSchedulerStmt->bind_param("ss", $username, $new_scheduler_name);
  $checkSchedulerStmt->execute();
  $checkSchedulerStmt->store_result();

  // If a scheduler with the new name already exists, return an error
  if ($checkSchedulerStmt->num_rows > 0) {
      echo "Scheduler name already exists. Please choose a different name.";
      exit();
  }
  $checkSchedulerStmt->close();

  // Update the scheduler name in the database
  $updateSchedulerStmt = $conn->prepare("UPDATE schedulers SET scheduler_name = ? WHERE user_id = (SELECT id FROM users WHERE username = ?) AND scheduler_name = ?");
  $updateSchedulerStmt->bind_param("sss", $new_scheduler_name, $username, $old_scheduler_name);
  $updateSchedulerStmt->execute();
  $updateSchedulerStmt->close();

  echo "Scheduler renamed successfully!";
  exit();
}

// Query to fetch all schedulers for the logged-in user
$schedulerQuery = $conn->prepare("SELECT scheduler_name FROM schedulers WHERE user_id = (SELECT id FROM users WHERE username = ?)");
$schedulerQuery->bind_param("s", $username);
$schedulerQuery->execute();
$schedulerQuery->store_result();
$schedulerQuery->bind_result($scheduler_name);
$schedulers = [];

while ($schedulerQuery->fetch()) {
    $schedulers[] = $scheduler_name;
}

$schedulerQuery->close();

// Query to fetch requested schedules for the logged-in user
$requestSchedulesQuery = $conn->prepare("
    SELECT sr.room_number, sr.scheduler_name, sr.schedule_date, sr.start_time, sr.end_time
    FROM scheduler_requests sr
    JOIN users u ON u.id = sr.user_id
    WHERE u.username = ? 
    ORDER BY sr.schedule_date, sr.start_time
");
$requestSchedulesQuery->bind_param("s", $username);
$requestSchedulesQuery->execute();
$requestSchedulesQuery->store_result();
$requestSchedulesQuery->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time);
$requestSchedules = [];

while ($requestSchedulesQuery->fetch()) {
    $requestSchedules[] = [
        'room_number' => $room_number,
        'scheduler_name' => $scheduler_name,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ];
}
$requestSchedulesQuery->close();

date_default_timezone_set('Asia/Manila');
// Automatically delete expired requests
$currentDateTime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("DELETE FROM scheduler_requests WHERE CONCAT(schedule_date, ' ', start_time) < ?");
$stmt->bind_param("s", $currentDateTime);
$stmt->execute();
$stmt->close();

// Automatically delete expired approved requests
$currentDateTime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("DELETE FROM approved_requests WHERE CONCAT(schedule_date, ' ', end_time) < ?");
$stmt->bind_param("s", $currentDateTime);
$stmt->execute();
$stmt->close();

// Query to fetch approved schedules for the logged-in user with department information
$approvedSchedulesQuery = $conn->prepare("
    SELECT ar.room_number, ar.schedule_date, ar.start_time, ar.end_time, t.department
    FROM approved_requests ar
    JOIN users u ON u.id = ar.user_id
    JOIN teams t ON t.user_id = u.id
    WHERE u.username = ?
    ORDER BY ar.schedule_date, ar.start_time
");
$approvedSchedulesQuery->bind_param("s", $username);
$approvedSchedulesQuery->execute();
$approvedSchedulesQuery->store_result();
$approvedSchedulesQuery->bind_result($room_number, $schedule_date, $start_time, $end_time, $department);
$approvedSchedules = [];

while ($approvedSchedulesQuery->fetch()) {
    $approvedSchedules[] = [
        'room_number' => $room_number,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'department' => $department
    ];
}
$approvedSchedulesQuery->close();

// Query to fetch approved schedules with department for each room
$roomSchedulesQuery = $conn->prepare("
    SELECT ar.room_number, ar.schedule_date, ar.start_time, ar.end_time, t.department, u.username
    FROM approved_requests ar
    JOIN users u ON u.id = ar.user_id
    JOIN teams t ON t.user_id = u.id
    ORDER BY ar.room_number, ar.schedule_date, ar.start_time
");
$roomSchedulesQuery->execute();
$roomSchedulesQuery->store_result();
$roomSchedulesQuery->bind_result($room_number, $schedule_date, $start_time, $end_time, $department, $username);
$roomSchedules = [];

while ($roomSchedulesQuery->fetch()) {
    $roomSchedules[] = [
        'room_number' => $room_number,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'department' => $department,
        'username' => $username, // Add username to the schedule array
    ];
}
$roomSchedulesQuery->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Scheduler - Dashboard</title>
  <link rel="stylesheet" href="./teacher.css">
</head>
<body>
  <!-- Navbar -->
  <nav id="navbar">
    <a class="navbar-brand d-flex align-items-center" href="../../index.php">
                <img src="../../images/SmaRM-Logo.png" class="img-fluid logo-image">
                <div class="d-flex flex-column">
                    <strong class="logo-text">SmaRM</strong>
                    <small class="logo-slogan">Smart Room Management</small>
                </div>
      </a>
      <!-- Display logged-in user's name -->
	 <div class="nav-left">
      <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <ul class="nav-middle">
      <li><a href="#">Dashboard</a></li>
      <li><a href="./subject.php">Subject Management</a></li>
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
    <!-- Left Panel with Tabs -->
    <section id="left-panel">
    <script src="https://cdn.logwork.com/widget/digital.js"></script>
    <script src="https://cdn.logwork.com/widget/text.js"></script>
		<a href="https://logwork.com/clock-widget/" class="clock-widget-text" data-timezone="Asia/Manila" data-language="en" data-textcolor="#ffffff" data-background="#9e1b32" data-digitscolor="#ffffff">Philippines</a>
      <div class="tabs">
        <button id="active-tab" class="tab-btn active">Requested Schedules</button>
        <button id="future-tab" class="tab-btn">Approved Schedules</button>
      </div>
  
      <div id="active-schedules" class="tab-content active">
    <h2>Requested Schedules</h2>
    <div id="today-schedules">
        <?php if (count($requestSchedules) > 0): ?>
            <ul>
                <?php foreach ($requestSchedules as $schedule): ?>
                    <li>
                        <strong>Scheduler Name:</strong> <?php echo htmlspecialchars($schedule['scheduler_name']); ?><br>
                        <strong>Room:</strong> Room <?php echo htmlspecialchars($schedule['room_number']); ?><br>
                        <strong>Date:</strong> <?php echo htmlspecialchars($schedule['schedule_date']); ?><br>
                        <strong>Time:</strong> <?php echo htmlspecialchars($schedule['start_time']); ?> - <?php echo htmlspecialchars($schedule['end_time']); ?><br>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no requested schedules.</p>
        <?php endif; ?>
    </div>
</div>
      
<div id="future-schedules" class="tab-content">
    <h2>Approved Schedules</h2>
    <div id="approved-schedules">
        <?php if (count($approvedSchedules) > 0): ?>
            <ul>
                <?php foreach ($approvedSchedules as $schedule): ?>
                    <li>
                        <strong>Room:</strong> Room <?php echo htmlspecialchars($schedule['room_number']); ?><br>
                        <strong>Date:</strong> <?php echo htmlspecialchars($schedule['schedule_date']); ?><br>
                        <strong>Time:</strong> <?php echo htmlspecialchars($schedule['start_time']); ?> - <?php echo htmlspecialchars($schedule['end_time']); ?><br>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no approved schedules.</p>
        <?php endif; ?>
    </div>
</div>
    </section>

    <!-- Schedule Board -->
    <section id="schedule-board" class="dashboard-panel">
      <h2>Schedule Board</h2>
      <!-- Scheduler -->
      <button id="add-scheduler-btn" <?php echo $isAssignedToTeam ? '' : 'disabled'; ?>>Add Scheduler</button>
      <div id="scheduler-pool" class="container">
        <h3>Available Schedulers</h3>
        <?php if ($isAssignedToTeam): ?>
<!-- Display existing schedulers -->
          <?php foreach ($schedulers as $scheduler): ?>
            <div class="scheduler-container">
  <div class="circle" id="scheduler-<?php echo htmlspecialchars($scheduler); ?>" draggable="true">
    <?php echo htmlspecialchars($scheduler); ?>
  </div>
  <button class="delete-scheduler-btn" data-scheduler="<?php echo htmlspecialchars($scheduler); ?>">Delete</button>
  <!-- Add Rename Button -->
  <button class="rename-scheduler-btn" data-scheduler="<?php echo htmlspecialchars($scheduler); ?>">Rename</button>
</div>
<?php endforeach; ?>

        <?php else: ?>
          <p>You must join a team first to add a scheduler.</p>
        <?php endif; ?>
      </div>

      <!-- Rooms Section -->
<div id="rooms" class="container">
    <h3>Rooms</h3>
    <div id="room1" class="room" data-room="1">
        <h3>Room 321</h3>
        <?php if (!empty($approvedSchedules)): ?>
            <button class="view-schedules-btn" data-room="1">View Schedules</button>
        <?php endif; ?>
    </div>
    <div id="room2" class="room" data-room="2">
        <h3>Room 322</h3>
        <?php if (!empty($approvedSchedules)): ?>
            <button class="view-schedules-btn" data-room="2">View Schedules</button>
        <?php endif; ?>
    </div>
    <div id="room3" class="room" data-room="3">
        <h3>Room 323</h3>
        <?php if (!empty($approvedSchedules)): ?>
            <button class="view-schedules-btn" data-room="3">View Schedules</button>
        <?php endif; ?>
    </div>
    <div id="room4" class="room" data-room="4">
        <h3>Room 324</h3>
        <?php if (!empty($approvedSchedules)): ?>
            <button class="view-schedules-btn" data-room="4">View Schedules</button>
        <?php endif; ?>
    </div>
    <div id="room5" class="room" data-room="5">
        <h3>Room 325</h3>
        <?php if (!empty($approvedSchedules)): ?>
            <button class="view-schedules-btn" data-room="5">View Schedules</button>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Viewing Schedules -->
<div id="view-schedules-modal" class="modal">
    <div class="modal-content">
        <h2>Approved Schedules for Room <span id="room-number"></span></h2>
        <ul id="schedule-list"></ul>
        <button type="button" id="close-modal">Close</button>
    </div>
</div>
    </section>

    <!-- Team Assignment Section -->
    <?php if ($isAssignedToTeam): ?>
      <section id="team-assignment" class="dashboard-panel">
        <h3>You are part of the <?php echo htmlspecialchars($department); ?> team.</h3>
      </section>
    <?php else: ?>
      <section id="join-team-reminder" class="dashboard-panel">
        <h3>You are currently not in a team.</h3>
        <p>Would you like to join a team? Go to the <a href="./subject.php">Subject Management</a> tab to join a team.</p>
      </section>
    <?php endif; ?>
    
    <!-- Modal for Account Settings -->
    <div id="account-settings-modal" class="modal">
      <div class="modal-content">
        <h2>Update Account Settings</h2>
        <form id="account-settings-form">
          <label for="new-username">
            <input type="checkbox" id="change-username" name="change-username">
            Change Username
          </label>
          <input type="text" id="new-username" name="new-username" disabled required>
          <span id="username-error" style="color: red; display: none;">Username already exists.</span>
          
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

<!-- Modal for Renaming Scheduler -->
<div id="rename-scheduler-modal" class="modal">
  <div class="modal-content">
    <h2>Rename Scheduler</h2>
    <form id="rename-scheduler-form">
      <label for="new-scheduler-name">New Scheduler Name:</label>
      <input type="text" id="new-scheduler-name" name="new-scheduler-name" required>

      <div class="modal-buttons">
        <button type="button" id="close-rename-scheduler">Cancel</button>
        <button type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal for Delete Confirmation -->
<div id="delete-scheduler-modal" class="modal">
  <div class="modal-content">
    <h2>Are you sure you want to delete this scheduler?</h2>
    <div class="modal-buttons">
      <button type="button" id="cancel-delete-scheduler">Cancel</button>
      <button type="button" id="confirm-delete-scheduler">Delete</button>
    </div>
  </div>
</div>

<!-- Scheduling Modal -->
<div id="schedule-modal" class="modal">
    <div class="modal-content">
        <h2>Schedule Scheduler</h2>
        <p>Scheduler Name: <span id="modal-scheduler-name"></span></p>
        <p>Room Number: <span id="modal-room-number"></span></p>
        <form id="schedule-form">
            <label for="schedule-date">Schedule Date:</label>
            <input type="date" id="schedule-date" name="schedule-date" required>
            
            <label for="start-time">Start Time:</label>
            <input type="time" id="start-time" name="start-time" required>
            
            <label for="duration-hours">Duration (hours):</label>
            <input type="number" id="duration-hours" name="duration-hours" min="1" max="12" required>
            
            <div class="modal-buttons">
                <button type="button" id="cancel-modal">Cancel</button>
                <button type="submit">Confirm</button>
            </div>
        </form>
    </div>
</div>
  </main>

  <footer>
    <p>&copy 2024 Room Scheduler. All rights reserved.</p>
  </footer>
  <script src="./teacher.js"></script>
</body>
</html>