<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect if not an admin
    header("Location: ../landing-pages/login-page.php");
    exit();
}

require '../../php/config.php'; // Database connection
$username = $_SESSION['username'];

// Query to check if the admin is assigned to a team (this can be skipped if not needed)
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

// Get today's date in 'Y-m-d' format
$todayDate = date('Y-m-d');

// Query to fetch schedule requests for today and future dates
$stmt = $conn->prepare("
    SELECT sr.room_number, sr.scheduler_name, sr.schedule_date, sr.start_time, sr.end_time
    FROM scheduler_requests sr
    WHERE sr.schedule_date >= ?
    ORDER BY sr.schedule_date, sr.start_time
");
$stmt->bind_param("s", $todayDate);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time);

// Arrays to hold today's and future requests
$todayRequests = [];
$futureRequests = [];

while ($stmt->fetch()) {
    $schedule = [
        'room_number' => $room_number,
        'scheduler_name' => $scheduler_name,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ];

    // Separate requests into today's and future requests
    if ($schedule_date == $todayDate) {
        $todayRequests[] = $schedule;
    } else {
        $futureRequests[] = $schedule;
    }
}
$stmt->close();

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


$stmt = $conn->prepare("
    SELECT sr.room_number, sr.scheduler_name, sr.schedule_date, sr.start_time, sr.end_time, sr.user_id
    FROM scheduler_requests sr
    WHERE sr.schedule_date >= ?
    ORDER BY sr.schedule_date, sr.start_time
");
$stmt->bind_param("s", $todayDate);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time, $user_id);

// Arrays to hold today's and future requests
$todayRequests = [];
$futureRequests = [];

while ($stmt->fetch()) {
    $schedule = [
        'room_number' => $room_number,
        'scheduler_name' => $scheduler_name,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'user_id' => $user_id,
    ];

    // Separate requests into today's and future requests
    if ($schedule_date == $todayDate) {
        $todayRequests[] = $schedule;
    } else {
        $futureRequests[] = $schedule;
    }
}

$stmt->close();

// Fetch approved requests for today and future dates
$stmt = $conn->prepare("
    SELECT ar.room_number, ar.scheduler_name, ar.schedule_date, ar.start_time, ar.end_time, ar.user_id
    FROM approved_requests ar
    WHERE ar.schedule_date >= ?
    ORDER BY ar.schedule_date, ar.start_time
");
$stmt->bind_param("s", $todayDate);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time, $user_id);

// Arrays to hold today's and future approved requests
$todayApprovedRequests = [];
$futureApprovedRequests = [];

while ($stmt->fetch()) {
    $schedule = [
        'room_number' => $room_number,
        'scheduler_name' => $scheduler_name,
        'schedule_date' => $schedule_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'user_id' => $user_id,
    ];

    // Separate requests into today's and future requests
    if ($schedule_date == $todayDate) {
        $todayApprovedRequests[] = $schedule;
    } else {
        $futureApprovedRequests[] = $schedule;
    }
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Admin - Dashboard</title>
  <link rel="stylesheet" href="./adminss.css">
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
    <div class="nav-left">
      <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <ul class="nav-middle">
      <li><a href="#">Dashboard</a></li>
      <li><a href="./accounts.php">Accounts</a></li>
      <li><a href="./teams.php">Team Management</a></li>
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
      <!-- Left Panel with Tabs -->
<section id="left-panel">
<script src="https://cdn.logwork.com/widget/text.js"></script>
    <a href="https://logwork.com/clock-widget/" class="clock-widget-text" data-timezone="Asia/Manila" data-language="en" data-textcolor="#ffffff" data-background="#9e1b32" data-digitscolor="#ffffff">Philippines</a>
    <div class="tabs">
        <button id="active-tab" class="tab-btn active">Active Schedules</button>
        <button id="future-tab" class="tab-btn">Future Schedules</button>
    </div>

    <div id="active-schedules" class="tab-content active">
        <h2>Active Schedules (Today)</h2>
        <div id="today-schedules">
            <!-- Approved today schedule items will be here -->
            <?php if (count($todayApprovedRequests) > 0): ?>
                <ul>
                    <?php foreach ($todayApprovedRequests as $request): ?>
                        <li>
                            <strong>Scheduler Name:</strong > <?php echo htmlspecialchars($request['scheduler_name']); ?><br>
                            <strong>Room:</strong> Room <?php echo htmlspecialchars($request['room_number']); ?><br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($request['schedule_date']); ?><br>
                            <strong>Time:</strong> <?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?><br>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No approved schedules for today.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="future-schedules" class="tab-content">
        <h2>Approved Future Schedules</h2>
        <div id="future-schedules-list">
            <!-- Approved future schedule items will be here -->
            <?php if (count($futureApprovedRequests) > 0): ?>
                <ul>
                    <?php foreach ($futureApprovedRequests as $request): ?>
                        <li>
                            <strong>Scheduler Name:</strong> <?php echo htmlspecialchars($request['scheduler_name']); ?><br>
                            <strong>Room:</strong> Room <?php echo htmlspecialchars($request['room_number']); ?><br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($request['schedule_date']); ?><br>
                            <strong>Time:</strong> <?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?><br>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No approved schedules for future dates.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

    <!-- Schedule Request Box -->
<section id="schedule-request-box" class="dashboard-panel">
  <h2>Schedule Requests</h2>

  <!-- Todays Schedule Requests -->
  <div class="request-section">
    <h3>Today</h3>
    <div id="today-requests">
      <?php if (count($todayRequests) > 0): ?>
        <ul>
          <?php foreach ($todayRequests as $request): ?>
            <li>
              <strong>Scheduler Name:</strong> <?php echo htmlspecialchars($request['scheduler_name']); ?><br>
              <strong>Room:</strong> Room <?php echo htmlspecialchars($request['room_number']); ?><br>
              <strong>Date:</strong> <?php echo htmlspecialchars($request['schedule_date']); ?><br>
              <strong>Time:</strong> <?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?><br>
              <!-- Approve and Decline Buttons -->
              <form method="POST" action="handle_schedule_request.php">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($request['room_number']); ?>">
                <input type="hidden" name="schedule_date" value="<?php echo htmlspecialchars($request['schedule_date']); ?>">
                <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($request['start_time']); ?>">
                <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($request['end_time']); ?>">
                <button type="submit" name="action" value="approve">Approve</button>
                <button type="submit" name="action" value="decline">Decline</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No schedule requests for today.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Future Schedule Requests -->
  <hr />
  <div class="request-section">
    <h3>Future</h3>
    <div id="future-requests">
      <?php if (count($futureRequests) > 0): ?>
        <ul>
          <?php foreach ($futureRequests as $request): ?>
            <li>
              <strong>Scheduler Name:</strong> <?php echo htmlspecialchars($request['scheduler_name']); ?><br>
              <strong>Room:</strong> Room <?php echo htmlspecialchars($request['room_number']); ?><br>
              <strong>Date:</strong> <?php echo htmlspecialchars($request['schedule_date']); ?><br>
              <strong>Time:</strong> <?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?><br>
              <!-- Approve and Decline Buttons for Future Requests -->
              <form method="POST" action="handle_schedule_request.php">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($request['room_number']); ?>">
                <input type="hidden" name="schedule_date" value="<?php echo htmlspecialchars($request['schedule_date']); ?>">
                <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($request['start_time']); ?>">
                <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($request['end_time']); ?>">
                <button type="submit" name="action" value="approve">Approve</button>
                <button type="submit" name="action" value="decline">Decline</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No future schedule requests.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
  </main>

  <footer>
    <p>&copy 2024 Room Scheduler. All rights reserved.</p>
  </footer>
  <script src="./admin.js"></script>
</body>
</html>