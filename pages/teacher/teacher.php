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
<html lang="en" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Scheduler - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: {
              light: '#2E7D32', // Darker green for light mode
              dark: '#76FF03'   // Original lime green for dark mode
            },
            secondary: '#9e1b32',
            dark: '#121212',
            'dark-lighter': '#1E1E1E',
          }
        }
      }
    }
  </script>
  <style>
    .clock {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #2E7D32 0%, #76FF03 100%);
      border-radius: 0.5rem;
      padding: 1rem;
      color: white;
      text-align: center;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .clock-time {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    .clock-date {
      font-size: 1rem;
      opacity: 0.9;
    }
    .clock-timezone {
      font-size: 0.875rem;
      opacity: 0.8;
      margin-top: 0.25rem;
    }
  </style>
</head>
<body class="bg-white dark:bg-dark text-gray-900 dark:text-white min-h-screen transition-colors duration-200">
  <!-- Navbar -->
  <nav class="bg-white dark:bg-dark-lighter shadow-lg transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <a href="../../index.php" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
          <img src="../../images/SmaRM-Logo.png" class="h-12 w-auto">
          <div class="flex flex-col">
            <strong class="text-4xl text-primary-light dark:text-primary-dark">SmaRM</strong>
            <small class="text-secondary">Smart Room Management</small>
                </div>
      </a>
        
        <!-- Navigation Links -->
        <div class="flex-1 flex justify-center">
          <ul class="flex space-x-8">
            <li>
              <a href="#" class="flex items-center space-x-2 text-gray-900 dark:text-white hover:text-primary-light dark:hover:text-primary-dark transition-colors">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
              </a>
            </li>
            <li>
              <a href="./subject.php" class="flex items-center space-x-2 text-gray-900 dark:text-white hover:text-primary-light dark:hover:text-primary-dark transition-colors">
                <i class="fas fa-users"></i>
                <span>Department Information</span>
              </a>
            </li>
    </ul>
        </div>
        
        <!-- User Menu -->
        <div class="flex items-center space-x-4">
          <span class="text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-user-circle mr-2"></i>
            Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>
          </span>
          
          <!-- Theme Toggle -->
          <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors shadow-sm">
            <i id="theme-icon" class="fas fa-sun text-gray-900 dark:text-white"></i>
          </button>
          
          <div class="relative">
            <button id="menu-button" class="bg-secondary text-white px-4 py-2 rounded-lg hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors flex items-center space-x-2 shadow-md">
              <i class="fas fa-bars"></i>
              <span>Menu</span>
            </button>
            <div id="menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-dark-lighter rounded-lg shadow-xl hidden transition-colors">
              <a href="../../php/logout.php" class="block w-full text-left px-4 py-2 text-primary-light dark:text-primary-dark hover:bg-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
              </a>
            </div>
    </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-full mx-auto px-4 py-8">
    <div class="flex gap-8">
    <!-- Left Panel with Tabs -->
      <section class="w-1/3 bg-white dark:bg-dark-lighter rounded-lg p-6 shadow-lg transition-colors">
        <div class="mb-6">
          <div class="clock">
            <div id="clock-time" class="clock-time">00:00:00</div>
            <div id="clock-date" class="clock-date">Loading...</div>
            <div class="clock-timezone">Philippines (GMT+8)</div>
          </div>
      </div>
  
        <div class="flex flex-col space-y-2 mb-6">
          <button id="requested-tab" class="bg-primary-light dark:bg-primary-dark text-white p-3 rounded-lg hover:bg-secondary hover:text-white transition-colors active flex items-center justify-center space-x-2 shadow-md">
            <i class="fas fa-clock"></i>
            <span>Requested Schedules</span>
          </button>
          <button id="approved-tab" class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-3 rounded-lg hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors flex items-center justify-center space-x-2 shadow-md">
            <i class="fas fa-check-circle"></i>
            <span>Approved Schedules</span>
          </button>
        </div>

        <!-- Requested Schedules Tab -->
        <div id="requested-schedules" class="tab-content active">
          <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-clock mr-2"></i>Requested Schedules
          </h2>
          <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
        <?php if (count($requestSchedules) > 0): ?>
                <?php 
                // Group schedules by date
                $schedulesByDate = [];
                foreach ($requestSchedules as $schedule) {
                  $date = $schedule['schedule_date'];
                  if (!isset($schedulesByDate[$date])) {
                    $schedulesByDate[$date] = [];
                  }
                  $schedulesByDate[$date][] = $schedule;
                }
                ksort($schedulesByDate); // Sort dates
                
                // Get the first date and its schedules
                $firstDate = array_key_first($schedulesByDate);
                $firstDateSchedules = $schedulesByDate[$firstDate];
                $firstSchedule = $firstDateSchedules[0];
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-800 overflow-hidden">
                  <div class="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2 border-b border-yellow-200 dark:border-yellow-800">
                    <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center">
                      <i class="fas fa-calendar-day mr-2"></i>
                      <?php 
                        $dateObj = new DateTime($firstDate);
                        echo $dateObj->format('F j, Y (l)');
                      ?>
                    </h3>
                  </div>
                  <div class="divide-y divide-yellow-100 dark:divide-yellow-800">
                    <div class="p-4 hover:bg-yellow-50 dark:hover:bg-yellow-900/10 transition-colors">
                      <div class="flex justify-between items-start">
                        <div class="flex-1">
                          <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                              <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                <span class="text-red-600 dark:text-red-400 font-semibold"><?php echo intval($firstSchedule['room_number']) + 320; ?></span>
                              </div>
                            </div>
                            <div>
                              <p class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($firstSchedule['scheduler_name']); ?></p>
                              <p class="text-sm text-gray-600 dark:text-gray-400">
                                <?php 
                                  $start = new DateTime($firstSchedule['start_time']);
                                  $end = new DateTime($firstSchedule['end_time']);
                                  echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); 
                                ?>
                              </p>
                            </div>
                          </div>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                          <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-medium">
                            Pending
                          </span>
                          <button class="cancel-request-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors flex items-center space-x-2"
                                  data-scheduler="<?php echo htmlspecialchars($firstSchedule['scheduler_name']); ?>"
                                  data-room="<?php echo htmlspecialchars($firstSchedule['room_number']); ?>"
                                  data-date="<?php echo htmlspecialchars($firstSchedule['schedule_date']); ?>"
                                  data-start="<?php echo htmlspecialchars($firstSchedule['start_time']); ?>"
                                  data-end="<?php echo htmlspecialchars($firstSchedule['end_time']); ?>">
                            <i class="fas fa-times"></i>
                            <span>Cancel Request</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php if (count($requestSchedules) > 1): ?>
                  <div class="text-center">
                    <button id="view-all-requested" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white transition-colors shadow-sm">
                      <i class="fas fa-list mr-2"></i>See More (<?php echo count($requestSchedules) - 1; ?> more)
                    </button>
                  </div>
                <?php endif; ?>
        <?php else: ?>
                <div class="text-center py-8">
                  <i class="fas fa-calendar-times text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                  <p class="text-gray-500 dark:text-gray-400">You have no requested schedules.</p>
                </div>
        <?php endif; ?>
            </div>
    </div>
</div>
      
        <!-- Approved Schedules Tab -->
        <div id="approved-schedules" class="tab-content hidden">
          <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>Approved Schedules
          </h2>
          <div class="space-y-4">
        <?php if (count($approvedSchedules) > 0): ?>
    <ul class="space-y-4">
      <?php 
      // Show only the first schedule
      $firstSchedule = $approvedSchedules[0];
      ?>
      <li class="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg transition-colors shadow-md">
        <div class="flex items-center space-x-2 mb-2">
          <i class="fas fa-door-open text-primary-light dark:text-primary-dark"></i>
          <strong class="text-primary-light dark:text-primary-dark">Room:</strong>
          <span>Room <?php echo intval($firstSchedule['room_number']) + 320; ?></span>
        </div>
        <div class="flex items-center space-x-2 mb-2">
          <i class="fas fa-calendar text-primary-light dark:text-primary-dark"></i>
          <strong class="text-primary-light dark:text-primary-dark">Date:</strong>
          <span><?php echo htmlspecialchars($firstSchedule['schedule_date']); ?></span>
        </div>
        <div class="flex items-center space-x-2">
          <i class="fas fa-clock text-primary-light dark:text-primary-dark"></i>
          <strong class="text-primary-light dark:text-primary-dark">Time:</strong>
          <span><?php 
            $start = new DateTime($firstSchedule['start_time']);
            $end = new DateTime($firstSchedule['end_time']);
            echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); 
          ?></span>
        </div>
                    </li>
      <?php if (count($approvedSchedules) > 1): ?>
        <div class="text-center">
          <button id="view-all-approved" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white transition-colors shadow-sm">
            <i class="fas fa-list mr-2"></i>See More (<?php echo count($approvedSchedules) - 1; ?> more)
          </button>
        </div>
      <?php endif; ?>
            </ul>
        <?php else: ?>
    <p class="flex items-center justify-center text-gray-500">
      <i class="fas fa-info-circle mr-2"></i>You have no approved schedules.
    </p>
        <?php endif; ?>
    </div>
</div>
    </section>

    <!-- Schedule Board -->
      <section class="w-2/3 bg-white dark:bg-dark-lighter rounded-lg p-6 shadow-lg transition-colors mr-4">
        <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-6 text-center flex items-center justify-center">
          <i class="fas fa-calendar-alt mr-2"></i>Schedule Board
        </h2>
        
        <div class="flex justify-between items-center mb-6">
          <button id="add-scheduler-btn" class="bg-secondary text-white p-4 rounded-lg hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors shadow-md flex items-center justify-center space-x-2" <?php echo $isAssignedToTeam ? '' : 'disabled'; ?>>
            <i class="fas fa-plus"></i>
            <span>Add Scheduler</span>
          </button>
        </div>

        <div id="scheduler-pool" class="mb-8">
          <h3 class="text-xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-list mr-2"></i>Available Schedulers
          </h3>
        <?php if ($isAssignedToTeam): ?>
            <div class="grid grid-cols-2 gap-4">
          <?php foreach ($schedulers as $scheduler): ?>
                <div class="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg transition-colors shadow-md">
                  <div class="bg-primary-light dark:bg-primary-dark text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-2 cursor-move shadow-lg draggable-scheduler" 
                       id="scheduler-<?php echo htmlspecialchars($scheduler); ?>" 
                       draggable="true"
                       data-scheduler="<?php echo htmlspecialchars($scheduler); ?>">
                    <i class="fas fa-calendar-alt text-2xl"></i>
                    <span class="text-sm mt-1"><?php echo htmlspecialchars($scheduler); ?></span>
  </div>
                  <div class="flex justify-center space-x-2">
                    <button class="bg-secondary text-white px-3 py-1 rounded hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors shadow-sm delete-scheduler-btn" data-scheduler="<?php echo htmlspecialchars($scheduler); ?>">
                      <i class="fas fa-trash-alt mr-1"></i>Delete
                    </button>
                    <button class="bg-secondary text-white px-3 py-1 rounded hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors shadow-sm rename-scheduler-btn" data-scheduler="<?php echo htmlspecialchars($scheduler); ?>">
                      <i class="fas fa-edit mr-1"></i>Rename
                    </button>
                  </div>
</div>
<?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="flex items-center justify-center text-gray-500">
              <i class="fas fa-exclamation-circle mr-2"></i>You must join a team first to add a scheduler.
            </p>
        <?php endif; ?>
      </div>

      <!-- Rooms Section -->
        <div id="rooms" class="grid grid-cols-3 gap-4">
          <?php for($i = 1; $i <= 5; $i++): ?>
            <div id="room<?php echo $i; ?>" 
                 class="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg text-center cursor-pointer hover:scale-105 transition-all shadow-md drop-zone" 
                 data-room="<?php echo $i; ?>"
                 ondrop="drop(event)"
                 ondragover="allowDrop(event)"
                 ondragenter="dragEnter(event)"
                 ondragleave="dragLeave(event)">
              <h3 class="text-lg font-bold mb-2 flex items-center justify-center">
                <i class="fas fa-door-open mr-2"></i>Room <?php echo 320 + $i; ?>
              </h3>
              <div class="scheduler-drop-area min-h-[100px] flex items-center justify-center border-2 border-dashed border-gray-400 dark:border-gray-600 rounded-lg p-2 mb-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Drop scheduler here</p>
    </div>
    </div>
          <?php endfor; ?>
    </div>
      </section>
    </div>
  </main>

  <!-- Modals -->
  <!-- Rename Scheduler Modal -->
  <div id="rename-scheduler-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-edit mr-2"></i>Rename Scheduler
      </h2>
      <form id="rename-scheduler-form" class="space-y-4">
        <div>
          <label class="block text-primary-light dark:text-primary-dark mb-2">New Scheduler Name:</label>
          <input type="text" id="new-scheduler-name" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
        </div>
        <div class="flex justify-end space-x-2">
          <button type="button" id="close-rename-scheduler" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 shadow-sm">
            <i class="fas fa-times mr-1"></i>Cancel
          </button>
          <button type="submit" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white shadow-sm">
            <i class="fas fa-save mr-1"></i>Save
          </button>
          </div>
        </form>
      </div>
    </div>

  <!-- Delete Confirmation Modal -->
  <div id="delete-scheduler-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-trash-alt mr-2"></i>Delete Scheduler
      </h2>
      <p class="mb-4 text-gray-900 dark:text-white">Are you sure you want to delete this scheduler?</p>
      <div class="flex justify-end space-x-2">
        <button type="button" id="cancel-delete-scheduler" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 shadow-sm">
          <i class="fas fa-times mr-1"></i>Cancel
        </button>
        <button type="button" id="confirm-delete-scheduler" class="bg-secondary text-white px-4 py-2 rounded hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white shadow-sm">
          <i class="fas fa-trash-alt mr-1"></i>Delete
        </button>
      </div>
    </div>
  </div>

  <!-- Schedule Modal -->
  <div id="schedule-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-calendar-plus mr-2"></i>Schedule Scheduler
      </h2>
      <p class="mb-2 text-gray-900 dark:text-white">Scheduler Name: <span id="modal-scheduler-name" class="text-primary-light dark:text-primary-dark"></span></p>
      <p class="mb-4 text-gray-900 dark:text-white">Room Number: <span id="modal-room-number" class="text-primary-light dark:text-primary-dark"></span></p>
      <form id="schedule-form" class="space-y-4">
        <div>
          <label class="block text-primary-light dark:text-primary-dark mb-2">Schedule Date:</label>
          <input type="date" id="schedule-date" name="schedule_date" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
        </div>
        <div>
          <label class="block text-primary-light dark:text-primary-dark mb-2">Start Time:</label>
          <input type="time" id="start-time" name="start_time" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
        </div>
        <div>
          <label class="block text-primary-light dark:text-primary-dark mb-2">Duration (hours):</label>
          <input type="number" id="duration-hours" name="duration_hours" min="1" max="12" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Maximum duration is 12 hours</p>
        </div>
        <div class="flex justify-end space-x-2">
          <button type="button" id="cancel-modal" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 shadow-sm">
            <i class="fas fa-times mr-1"></i>Cancel
          </button>
          <button type="submit" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white shadow-sm">
            <i class="fas fa-check mr-1"></i>Confirm
          </button>
          </div>
        </form>
      </div>
    </div>

  <!-- View Schedules Modal -->
  <div id="view-schedules-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-calendar-alt mr-2"></i>Approved Schedules for Room <span id="room-number"></span>
      </h2>
      <ul id="schedule-list" class="space-y-4"></ul>
      <div class="flex justify-end mt-4">
        <button type="button" id="close-modal" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white shadow-sm">
          <i class="fas fa-times mr-1"></i>Close
        </button>
    </div>
  </div>
</div>

  <!-- Room Schedules Modal -->
  <div id="room-schedules-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-[800px] max-h-[80vh] overflow-y-auto transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark flex items-center">
          <i class="fas fa-calendar-alt mr-2"></i>
          <span id="modal-room-title">Room Schedules</span>
        </h2>
        <button id="close-room-schedules" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <div class="flex gap-6">
        <!-- Left side - Calendar -->
        <div class="w-64 bg-white dark:bg-dark-lighter rounded-lg p-4 shadow-md">
          <div class="flex justify-between items-center mb-4">
            <button id="room-prev-month" class="text-gray-600 dark:text-gray-400 hover:text-primary-light dark:hover:text-primary-dark">
              <i class="fas fa-chevron-left"></i>
            </button>
            <h3 id="room-current-month" class="font-semibold text-primary-light dark:text-primary-dark"></h3>
            <button id="room-next-month" class="text-gray-600 dark:text-gray-400 hover:text-primary-light dark:hover:text-primary-dark">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="grid grid-cols-7 gap-1 text-center mb-2">
            <?php
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($days as $day) {
              echo "<div class='text-xs text-gray-500 dark:text-gray-400'>$day</div>";
            }
            ?>
          </div>
          <div id="room-calendar-days" class="grid grid-cols-7 gap-1 text-center">
            <!-- Calendar days will be populated by JavaScript -->
  </div>
</div>

        <!-- Right side - Schedules -->
        <div class="flex-1">
          <!-- Date and Time Filters -->
          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Date:</label>
              <input type="date" id="schedule-filter-date" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
    </div>
            <div class="flex-1">
              <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Time:</label>
              <input type="time" id="schedule-filter-time" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm">
  </div>
</div>

          <div id="room-schedules-content" class="space-y-4">
            <!-- Schedules will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cancel Request Confirmation Modal -->
  <div id="cancel-request-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-times-circle mr-2"></i>Cancel Schedule Request
      </h2>
      <p class="mb-4 text-gray-900 dark:text-white">Are you sure you want to cancel this schedule request?</p>
      <div class="flex justify-end space-x-2">
        <button type="button" id="cancel-request-no" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 shadow-sm">
          <i class="fas fa-times mr-1"></i>No, Keep It
        </button>
        <button type="button" id="cancel-request-yes" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 shadow-sm">
          <i class="fas fa-check mr-1"></i>Yes, Cancel It
        </button>
    </div>
  </div>
</div>

  <!-- All Requested Schedules Modal -->
  <div id="all-requested-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-[600px] max-h-[80vh] overflow-y-auto transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark flex items-center">
          <i class="fas fa-clock mr-2"></i>
          All Requested Schedules
        </h2>
        <button id="close-all-requested" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <!-- Date Filter -->
      <div class="mb-4">
        <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Date:</label>
        <input type="date" id="requested-filter-date" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
      </div>
      
      <div id="all-requested-content" class="space-y-4">
        <?php 
        if (count($requestSchedules) > 0):
          foreach ($schedulesByDate as $date => $schedules): 
        ?>
          <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2 border-b border-yellow-200 dark:border-yellow-800 mb-4">
              <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center">
                <i class="fas fa-calendar-day mr-2"></i>
                <?php 
                  $dateObj = new DateTime($date);
                  echo $dateObj->format('F j, Y (l)');
                ?>
              </h3>
            </div>
            <div class="space-y-4">
              <?php foreach ($schedules as $schedule): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-800 p-4">
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                          <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                            <span class="text-red-600 dark:text-red-400 font-semibold"><?php echo intval($schedule['room_number']) + 320; ?></span>
                          </div>
                        </div>
                        <div>
                          <p class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($schedule['scheduler_name']); ?></p>
                          <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?php 
                              $start = new DateTime($schedule['start_time']);
                              $end = new DateTime($schedule['end_time']);
                              echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); 
                            ?>
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="flex flex-col items-end space-y-2">
                      <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-medium">
                        Pending
                      </span>
                      <button class="cancel-request-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors flex items-center space-x-2"
                              data-scheduler="<?php echo htmlspecialchars($schedule['scheduler_name']); ?>"
                              data-room="<?php echo htmlspecialchars($schedule['room_number']); ?>"
                              data-date="<?php echo htmlspecialchars($schedule['schedule_date']); ?>"
                              data-start="<?php echo htmlspecialchars($schedule['start_time']); ?>"
                              data-end="<?php echo htmlspecialchars($schedule['end_time']); ?>">
                        <i class="fas fa-times"></i>
                        <span>Cancel Request</span>
                      </button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php 
          endforeach;
        endif;
        ?>
      </div>
    </div>
  </div>

  <!-- Add Scheduler Modal -->
  <div id="add-scheduler-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-96 transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i>Add New Scheduler
      </h2>
      <form id="add-scheduler-form" class="space-y-4">
        <div>
          <label class="block text-primary-light dark:text-primary-dark mb-2">Scheduler Name:</label>
          <input type="text" name="schedule_name" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
        </div>
        <div class="flex justify-end space-x-2">
          <button type="button" id="close-add-scheduler" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500 shadow-sm">
            <i class="fas fa-times mr-1"></i>Cancel
          </button>
          <button type="submit" class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded hover:bg-secondary hover:text-white shadow-sm">
            <i class="fas fa-plus mr-1"></i>Add Scheduler
          </button>
            </div>
        </form>
    </div>
</div>

  <!-- All Approved Schedules Modal -->
  <div id="all-approved-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-dark-lighter p-6 rounded-lg w-[800px] max-h-[80vh] overflow-y-auto transition-colors shadow-xl transform -translate-y-1/2 top-1/2 left-1/2 -translate-x-1/2 absolute">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark flex items-center">
          <i class="fas fa-check-circle mr-2"></i>
          All Approved Schedules
        </h2>
        <button id="close-all-approved" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <div class="flex gap-6">
        <!-- Left side - Calendar -->
        <div class="w-64 bg-white dark:bg-dark-lighter rounded-lg p-4 shadow-md">
          <div class="flex justify-between items-center mb-4">
            <button id="prev-month" class="text-gray-600 dark:text-gray-400 hover:text-primary-light dark:hover:text-primary-dark">
              <i class="fas fa-chevron-left"></i>
            </button>
            <h3 id="current-month" class="font-semibold text-primary-light dark:text-primary-dark"></h3>
            <button id="next-month" class="text-gray-600 dark:text-gray-400 hover:text-primary-light dark:hover:text-primary-dark">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="grid grid-cols-7 gap-1 text-center mb-2">
            <?php
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($days as $day) {
              echo "<div class='text-xs text-gray-500 dark:text-gray-400'>$day</div>";
            }
            ?>
          </div>
          <div id="calendar-days" class="grid grid-cols-7 gap-1 text-center">
            <!-- Calendar days will be populated by JavaScript -->
          </div>
        </div>

        <!-- Right side - Schedules -->
        <div class="flex-1">
          <!-- Date and Time Filters -->
          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Date:</label>
              <input type="date" id="approved-filter-date" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="flex-1">
              <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Time:</label>
              <input type="time" id="approved-filter-time" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm">
            </div>
          </div>
          
          <div id="all-approved-content" class="space-y-4">
            <!-- Schedules will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="bg-white dark:bg-dark text-gray-500 text-center py-4 fixed bottom-0 w-full transition-colors shadow-lg">
    <p class="flex items-center justify-center">
      <i class="fas fa-copyright mr-2"></i>2024 Room Scheduler. All rights reserved.
    </p>
  </footer>

  <script src="./theme.js"></script>
  <script src="./menu.js"></script>
  <script>
    // Global drag and drop functions
    function allowDrop(ev) {
      ev.preventDefault();
    }

    function dragEnter(ev) {
      ev.preventDefault();
      const dropZone = ev.target.closest('.drop-zone');
      if (dropZone) {
        dropZone.classList.add('bg-gray-300', 'dark:bg-gray-600');
      }
    }

    function dragLeave(ev) {
      const dropZone = ev.target.closest('.drop-zone');
      if (dropZone) {
        dropZone.classList.remove('bg-gray-300', 'dark:bg-gray-600');
      }
    }

    function drag(ev) {
      const scheduler = ev.target.closest('.draggable-scheduler');
      if (scheduler) {
        ev.dataTransfer.setData("text", scheduler.dataset.scheduler);
        scheduler.classList.add('opacity-50');
      }
    }

    function drop(ev) {
      ev.preventDefault();
      const dropZone = ev.target.closest('.drop-zone');
      if (dropZone) {
        dropZone.classList.remove('bg-gray-300', 'dark:bg-gray-600');
        const schedulerName = ev.dataTransfer.getData("text");
        const roomNumber = parseInt(dropZone.closest('[data-room]').dataset.room);
        
        // Show the schedule modal
        const modal = document.getElementById('schedule-modal');
        const schedulerNameSpan = document.getElementById('modal-scheduler-name');
        const roomNumberSpan = document.getElementById('modal-room-number');
        
        schedulerNameSpan.textContent = schedulerName;
        roomNumberSpan.textContent = roomNumber + 320; // Display as 321-325
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }
    }

    // Clock functionality
    function updateClock() {
      const now = new Date();
      const timeString = now.toLocaleTimeString('en-US', { 
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      const dateString = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      
      document.getElementById('clock-time').textContent = timeString;
      document.getElementById('clock-date').textContent = dateString;
    }

    // Update clock immediately and then every second
    updateClock();
    setInterval(updateClock, 1000);

    document.addEventListener('DOMContentLoaded', function() {
      // Add drag event listeners to all schedulers
      const schedulers = document.querySelectorAll('.draggable-scheduler');
      schedulers.forEach(scheduler => {
        scheduler.addEventListener('dragstart', drag);
        scheduler.addEventListener('dragend', function(ev) {
          ev.target.classList.remove('opacity-50');
        });
      });

      // Add Scheduler Modal functionality
      const addSchedulerBtn = document.getElementById('add-scheduler-btn');
      const addSchedulerModal = document.getElementById('add-scheduler-modal');
      const closeAddScheduler = document.getElementById('close-add-scheduler');
      const addSchedulerForm = document.getElementById('add-scheduler-form');

      if (addSchedulerBtn) {
        addSchedulerBtn.addEventListener('click', () => {
          addSchedulerModal.classList.remove('hidden');
          addSchedulerModal.classList.add('flex');
        });
      }

      if (closeAddScheduler) {
        closeAddScheduler.addEventListener('click', () => {
          addSchedulerModal.classList.add('hidden');
          addSchedulerModal.classList.remove('flex');
        });
      }

      if (addSchedulerForm) {
        addSchedulerForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          formData.append('add_scheduler', 'true');

          fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(result => {
            if (result.includes('successfully')) {
              alert(result);
              location.reload();
            } else {
              alert(result);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the scheduler.');
          });

          addSchedulerModal.classList.add('hidden');
          addSchedulerModal.classList.remove('flex');
        });
      }

      // Tab switching functionality
      const requestedTab = document.getElementById('requested-tab');
      const approvedTab = document.getElementById('approved-tab');
      const requestedSchedules = document.getElementById('requested-schedules');
      const approvedSchedules = document.getElementById('approved-schedules');

      function switchTab(selectedTab, selectedContent, otherTab, otherContent) {
        // Update tab styles
        selectedTab.classList.add('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
        selectedTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
        
        otherTab.classList.remove('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
        otherTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');

        // Show/hide content
        selectedContent.classList.remove('hidden');
        selectedContent.classList.add('active');
        otherContent.classList.add('hidden');
        otherContent.classList.remove('active');
      }

      // Add click event listeners to tabs
      if (requestedTab && approvedTab) {
        requestedTab.addEventListener('click', () => {
          switchTab(requestedTab, requestedSchedules, approvedTab, approvedSchedules);
        });

        approvedTab.addEventListener('click', () => {
          switchTab(approvedTab, approvedSchedules, requestedTab, requestedSchedules);
        });

        // Initialize with requested tab selected
        switchTab(requestedTab, requestedSchedules, approvedTab, approvedSchedules);
      }

      // Schedule Modal functionality
      const scheduleDate = document.getElementById('schedule-date');
      const startTime = document.getElementById('start-time');
      const durationHours = document.getElementById('duration-hours');
      const scheduleForm = document.getElementById('schedule-form');
      const cancelModal = document.getElementById('cancel-modal');

      if (scheduleDate) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        scheduleDate.min = today;

        // Update time validation when date changes
        scheduleDate.addEventListener('change', function() {
          const selectedDate = new Date(this.value);
          const now = new Date();
          
          // If selected date is today, set minimum time to current time
          if (selectedDate.toDateString() === now.toDateString()) {
            const currentHour = now.getHours().toString().padStart(2, '0');
            const currentMinute = now.getMinutes().toString().padStart(2, '0');
            startTime.min = `${currentHour}:${currentMinute}`;
          } else {
            // If selected date is in the future, allow any time
            startTime.min = '00:00';
          }
        });
      }

      if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const selectedDate = new Date(scheduleDate.value);
          const selectedTime = startTime.value;
          const duration = parseInt(durationHours.value);
          
          // Validate duration
          if (duration < 1 || duration > 12) {
            alert('Duration must be between 1 and 12 hours.');
            return false;
          }
          
          // Create Date objects for start and end times
          const [startHour, startMinute] = selectedTime.split(':');
          const startDateTime = new Date(selectedDate);
          startDateTime.setHours(parseInt(startHour), parseInt(startMinute));
          
          const endDateTime = new Date(startDateTime);
          endDateTime.setHours(endDateTime.getHours() + duration);
          
          const now = new Date();
          
          // Check if the selected time has passed
          if (startDateTime < now) {
            alert('Cannot schedule for a time that has already passed.');
            return false;
          }
          
          // Check if the schedule extends beyond midnight
          if (endDateTime.getDate() !== startDateTime.getDate()) {
            alert('Schedule cannot extend beyond midnight.');
            return false;
          }
          
          // If all validations pass, submit the form
          const formData = new FormData(this);
          // Get the room number from the modal and convert it to 1-5 range
          const displayRoomNumber = document.getElementById('modal-room-number').textContent;
          const dbRoomNumber = parseInt(displayRoomNumber) - 320;
          formData.append('room_number', dbRoomNumber.toString());
          formData.append('scheduler_name', document.getElementById('modal-scheduler-name').textContent);
          
          fetch('./schedule.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Close the modal first
              const modal = document.getElementById('schedule-modal');
              modal.classList.add('hidden');
              modal.classList.remove('flex');
              
              // Remove the scheduler from the pool
              const schedulerName = document.getElementById('modal-scheduler-name').textContent;
              const schedulerElement = document.querySelector(`.draggable-scheduler[data-scheduler="${schedulerName}"]`);
              if (schedulerElement) {
                const schedulerContainer = schedulerElement.closest('.bg-gray-200');
                if (schedulerContainer) {
                  schedulerContainer.remove();
                  
                  // Check if there are any schedulers left
                  const remainingSchedulers = document.querySelectorAll('.draggable-scheduler');
                  if (remainingSchedulers.length === 0) {
                    const schedulerPool = document.getElementById('scheduler-pool');
                    if (schedulerPool) {
                      schedulerPool.innerHTML = `
                        <h3 class="text-xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
                          <i class="fas fa-list mr-2"></i>Available Schedulers
                        </h3>
                        <p class="flex items-center justify-center text-gray-500">
                          <i class="fas fa-exclamation-circle mr-2"></i>You must join a team first to add a scheduler.
                        </p>
                      `;
                    }
                  }
                }
              }
              
              // Show success message
              alert(data.message || 'Schedule request submitted successfully!');
              
              // Reload the page after a short delay
              setTimeout(() => {
                window.location.reload();
              }, 500);
            } else {
              alert(data.message || 'Failed to submit schedule request.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Failed to submit schedule request. Please try again.');
          });
        });
      }

      if (cancelModal) {
        cancelModal.addEventListener('click', () => {
          const modal = document.getElementById('schedule-modal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
        });
      }

      // Cancel Request functionality
      const cancelRequestBtns = document.querySelectorAll('.cancel-request-btn');
      const cancelRequestModal = document.getElementById('cancel-request-modal');
      const cancelRequestYes = document.getElementById('cancel-request-yes');
      const cancelRequestNo = document.getElementById('cancel-request-no');

      cancelRequestBtns.forEach(button => {
        button.addEventListener('click', function() {
          const requestData = {
            scheduler: this.dataset.scheduler,
            room: this.dataset.room,
            date: this.dataset.date,
            start: this.dataset.start,
            end: this.dataset.end
          };
          
          // Store the current request data
          cancelRequestYes.dataset.request = JSON.stringify(requestData);
          
          cancelRequestModal.classList.remove('hidden');
          cancelRequestModal.classList.add('flex');
        });
      });

      if (cancelRequestYes) {
        cancelRequestYes.addEventListener('click', () => {
          const requestData = JSON.parse(cancelRequestYes.dataset.request);
          
          // Create form data
          const formData = new FormData();
          formData.append('scheduler_name', requestData.scheduler);
          formData.append('room_number', requestData.room);
          formData.append('schedule_date', requestData.date);
          formData.append('start_time', requestData.start);
          formData.append('end_time', requestData.end);
          formData.append('cancel_request', 'true');

          // Send the request
          fetch('./cancelRequest.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(result => {
            if (result.includes('successfully')) {
              alert('Schedule request cancelled successfully!');
              location.reload();
            } else {
              alert(result || 'Failed to cancel schedule request.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the schedule request.');
          });

          // Close the modal
          cancelRequestModal.classList.add('hidden');
          cancelRequestModal.classList.remove('flex');
        });
      }

      if (cancelRequestNo) {
        cancelRequestNo.addEventListener('click', () => {
          cancelRequestModal.classList.add('hidden');
          cancelRequestModal.classList.remove('flex');
        });
      }

      // Close cancel request modal when clicking outside
      if (cancelRequestModal) {
        cancelRequestModal.addEventListener('click', (e) => {
          if (e.target === cancelRequestModal) {
            cancelRequestModal.classList.add('hidden');
            cancelRequestModal.classList.remove('flex');
          }
        });
      }

      // Delete Scheduler functionality
      const deleteSchedulerBtns = document.querySelectorAll('.delete-scheduler-btn');
      const deleteSchedulerModal = document.getElementById('delete-scheduler-modal');
      const confirmDeleteScheduler = document.getElementById('confirm-delete-scheduler');
      const cancelDeleteScheduler = document.getElementById('cancel-delete-scheduler');

      deleteSchedulerBtns.forEach(button => {
        button.addEventListener('click', function() {
          const schedulerName = this.dataset.scheduler;
          confirmDeleteScheduler.dataset.scheduler = schedulerName;
          deleteSchedulerModal.classList.remove('hidden');
          deleteSchedulerModal.classList.add('flex');
        });
      });

      if (confirmDeleteScheduler) {
        confirmDeleteScheduler.addEventListener('click', () => {
          const schedulerName = confirmDeleteScheduler.dataset.scheduler;
          
          const formData = new FormData();
          formData.append('scheduler_name', schedulerName);
          formData.append('delete_scheduler', 'true');

          fetch('./deleteScheduler.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(result => {
            if (result.includes('successfully')) {
              alert('Scheduler deleted successfully!');
              location.reload();
            } else {
              alert(result || 'Failed to delete scheduler.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the scheduler.');
          });

          deleteSchedulerModal.classList.add('hidden');
          deleteSchedulerModal.classList.remove('flex');
        });
      }

      if (cancelDeleteScheduler) {
        cancelDeleteScheduler.addEventListener('click', () => {
          deleteSchedulerModal.classList.add('hidden');
          deleteSchedulerModal.classList.remove('flex');
        });
      }

      // Close delete scheduler modal when clicking outside
      if (deleteSchedulerModal) {
        deleteSchedulerModal.addEventListener('click', (e) => {
          if (e.target === deleteSchedulerModal) {
            deleteSchedulerModal.classList.add('hidden');
            deleteSchedulerModal.classList.remove('flex');
          }
        });
      }

      // Function to show room schedules
      function showRoomSchedules(roomNumber) {
        const modal = document.getElementById('room-schedules-modal');
        const modalTitle = document.getElementById('modal-room-title');
        const content = document.getElementById('room-schedules-content');
        const filterDate = document.getElementById('schedule-filter-date');
        const filterTime = document.getElementById('schedule-filter-time');
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        filterDate.min = today;
        
        // Update modal title
        modalTitle.textContent = `Room ${roomNumber} Schedules`;
        
        // Show loading state
        content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-primary-light dark:text-primary-dark"></i></div>';
        
        // Show modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Function to fetch and display schedules
        function fetchSchedules() {
          const selectedDate = filterDate.value;
          const selectedTime = filterTime.value;
          const url = `./getRoomSchedules.php?room=${roomNumber}`; // Always fetch all schedules
          
          fetch(url)
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.error) {
                content.innerHTML = `
                  <div class="text-center py-8">
                    <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                    <p class="text-red-500">${data.error}</p>
                  </div>`;
                return;
              }

              // Store all schedules for calendar
              roomSchedules = data;

              // Filter schedules for display based on selected date and time
              let filteredData = data;
              if (selectedDate) {
                filteredData = filteredData.filter(schedule => schedule.schedule_date === selectedDate);
              }
              if (selectedTime) {
                filteredData = filteredData.filter(schedule => {
                  const scheduleStart = new Date('1970-01-01T' + schedule.start_time);
                  const scheduleEnd = new Date('1970-01-01T' + schedule.end_time);
                  const filterTimeDate = new Date('1970-01-01T' + selectedTime);
                  return filterTimeDate >= scheduleStart && filterTimeDate <= scheduleEnd;
                });
              }

              if (filteredData.length === 0) {
                content.innerHTML = `
                  <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">No schedules found for this room${selectedDate ? ' on the selected date' : ''}.</p>
                  </div>`;
                // Update calendar with all schedules
                generateRoomCalendar();
                return;
              }
              
              // Group filtered schedules by date for display
              const schedulesByDate = filteredData.reduce((acc, schedule) => {
                const date = schedule.schedule_date;
                if (!acc[date]) {
                  acc[date] = [];
                }
                acc[date].push(schedule);
                return acc;
              }, {});
              
              // Create HTML for schedules
              content.innerHTML = Object.entries(schedulesByDate)
                .map(([date, dateSchedules]) => `
                  <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                    <div class="bg-primary-light dark:bg-primary-dark text-white px-4 py-2 rounded-t-lg mb-4">
                      <h3 class="font-semibold flex items-center">
                        <i class="fas fa-calendar-day mr-2"></i>
                        ${new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                      </h3>
                    </div>
                    <div class="space-y-3">
                      ${dateSchedules.map(schedule => {
                        const startTime = new Date('1970-01-01T' + schedule.start_time);
                        const endTime = new Date('1970-01-01T' + schedule.end_time);
                        return `
                          <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start">
                              <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                  <div class="bg-primary-light dark:bg-primary-dark text-white px-3 py-1 rounded-full text-sm">
                                    ${schedule.scheduler_name}
                                  </div>
                                  <span class="text-sm text-gray-500 dark:text-gray-400">
                                    ${startTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - 
                                    ${endTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}
                                  </span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                  <i class="fas fa-user"></i>
                                  <span>${schedule.username}</span>
                                  <span class="mx-2">•</span>
                                  <i class="fas fa-building"></i>
                                  <span>${schedule.department}</span>
                                </div>
                              </div>
                            </div>
                          </div>
                        `;
                      }).join('')}
                    </div>
                  </div>
                `).join('');

              // Update calendar with check marks for all dates with schedules
              generateRoomCalendar();
            })
            .catch(error => {
              console.error('Error:', error);
              content.innerHTML = `
                <div class="text-center py-8">
                  <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                  <p class="text-red-500">Error loading schedules. Please try again.</p>
                </div>`;
              // Clear calendar check marks on error
              generateRoomCalendar();
            });
        }

        // Initial fetch
        fetchSchedules();

        // Add event listeners for filters
        filterDate.addEventListener('change', fetchSchedules);
        filterTime.addEventListener('change', fetchSchedules);

        // Add event listeners for calendar navigation
        const roomPrevMonthBtn = document.getElementById('room-prev-month');
        const roomNextMonthBtn = document.getElementById('room-next-month');

        if (roomPrevMonthBtn) {
          roomPrevMonthBtn.addEventListener('click', () => {
            roomCurrentMonth--;
            if (roomCurrentMonth < 0) {
              roomCurrentMonth = 11;
              roomCurrentYear--;
            }
            generateRoomCalendar();
            // Update filter date to first day of the new month
            const newDate = new Date(roomCurrentYear, roomCurrentMonth, 1);
            filterDate.value = newDate.toISOString().split('T')[0];
            fetchSchedules();
          });
        }

        if (roomNextMonthBtn) {
          roomNextMonthBtn.addEventListener('click', () => {
            roomCurrentMonth++;
            if (roomCurrentMonth > 11) {
              roomCurrentMonth = 0;
              roomCurrentYear++;
            }
            generateRoomCalendar();
            // Update filter date to first day of the new month
            const newDate = new Date(roomCurrentYear, roomCurrentMonth, 1);
            filterDate.value = newDate.toISOString().split('T')[0];
            fetchSchedules();
          });
        }
      }

      // Add click event listeners to room cards
      document.querySelectorAll('.drop-zone').forEach(room => {
        room.addEventListener('click', (e) => {
          // Prevent the click event from triggering when dropping a scheduler
          if (e.target.closest('.draggable-scheduler')) {
            return;
          }
          const roomNumber = room.closest('[data-room]').dataset.room;
          showRoomSchedules(roomNumber);
        });
      });

      // Close room schedules modal when clicking the close button
      const closeRoomSchedules = document.getElementById('close-room-schedules');
      if (closeRoomSchedules) {
        closeRoomSchedules.addEventListener('click', () => {
          const modal = document.getElementById('room-schedules-modal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
        });
      }

      // Close room schedules modal when clicking outside
      const roomSchedulesModal = document.getElementById('room-schedules-modal');
      if (roomSchedulesModal) {
        roomSchedulesModal.addEventListener('click', (e) => {
          if (e.target === roomSchedulesModal) {
            roomSchedulesModal.classList.add('hidden');
            roomSchedulesModal.classList.remove('flex');
          }
        });
      }

      // Rename Scheduler functionality
      const renameSchedulerBtns = document.querySelectorAll('.rename-scheduler-btn');
      const renameSchedulerModal = document.getElementById('rename-scheduler-modal');
      const renameSchedulerForm = document.getElementById('rename-scheduler-form');
      const closeRenameScheduler = document.getElementById('close-rename-scheduler');
      const newSchedulerName = document.getElementById('new-scheduler-name');

      renameSchedulerBtns.forEach(button => {
        button.addEventListener('click', function() {
          const oldSchedulerName = this.dataset.scheduler;
          newSchedulerName.value = oldSchedulerName;
          renameSchedulerForm.dataset.oldName = oldSchedulerName;
          renameSchedulerModal.classList.remove('hidden');
          renameSchedulerModal.classList.add('flex');
        });
      });

      if (renameSchedulerForm) {
        renameSchedulerForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const oldName = this.dataset.oldName;
          const newName = newSchedulerName.value;

          const formData = new FormData();
          formData.append('old_scheduler_name', oldName);
          formData.append('new_scheduler_name', newName);
          formData.append('rename_scheduler', 'true');

          fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(result => {
            if (result.includes('successfully')) {
              alert('Scheduler renamed successfully!');
              location.reload();
            } else {
              alert(result || 'Failed to rename scheduler.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while renaming the scheduler.');
          });

          renameSchedulerModal.classList.add('hidden');
          renameSchedulerModal.classList.remove('flex');
        });
      }

      if (closeRenameScheduler) {
        closeRenameScheduler.addEventListener('click', () => {
          renameSchedulerModal.classList.add('hidden');
          renameSchedulerModal.classList.remove('flex');
        });
      }

      // Close rename scheduler modal when clicking outside
      if (renameSchedulerModal) {
        renameSchedulerModal.addEventListener('click', (e) => {
          if (e.target === renameSchedulerModal) {
            renameSchedulerModal.classList.add('hidden');
            renameSchedulerModal.classList.remove('flex');
          }
        });
      }

      // Function to filter schedules by date
      function filterSchedulesByDate(schedules, date) {
        if (!date) return schedules;
        return schedules.filter(schedule => schedule.schedule_date === date);
      }

      // Function to update approved schedules display
      function updateApprovedSchedulesDisplay(date) {
        const content = document.getElementById('all-approved-content');
        const schedules = <?php echo json_encode($approvedSchedules); ?>;
        const filteredSchedules = filterSchedulesByDate(schedules, date);
        
        if (filteredSchedules.length === 0) {
          content.innerHTML = `
            <div class="text-center py-8">
              <i class="fas fa-calendar-times text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
              <p class="text-gray-600 dark:text-gray-400">No schedules found for the selected date.</p>
            </div>`;
          return;
        }

        content.innerHTML = filteredSchedules.map(schedule => `
          <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex items-center space-x-2 mb-2">
              <i class="fas fa-door-open text-primary-light dark:text-primary-dark"></i>
              <strong class="text-primary-light dark:text-primary-dark">Room:</strong>
              <span>Room ${parseInt(schedule.room_number) + 320}</span>
            </div>
            <div class="flex items-center space-x-2 mb-2">
              <i class="fas fa-calendar text-primary-light dark:text-primary-dark"></i>
              <strong class="text-primary-light dark:text-primary-dark">Date:</strong>
              <span>${new Date(schedule.schedule_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
            </div>
            <div class="flex items-center space-x-2">
              <i class="fas fa-clock text-primary-light dark:text-primary-dark"></i>
              <strong class="text-primary-light dark:text-primary-dark">Time:</strong>
              <span>${new Date('1970-01-01T' + schedule.start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - 
                    ${new Date('1970-01-01T' + schedule.end_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</span>
            </div>
          </div>
        `).join('');
      }

      // Function to update requested schedules display
      function updateRequestedSchedulesDisplay(date) {
        const content = document.getElementById('all-requested-content');
        const schedules = <?php echo json_encode($requestSchedules); ?>;
        const filteredSchedules = filterSchedulesByDate(schedules, date);
        
        if (filteredSchedules.length === 0) {
          content.innerHTML = `
            <div class="text-center py-8">
              <i class="fas fa-calendar-times text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
              <p class="text-gray-600 dark:text-gray-400">No schedules found for the selected date.</p>
            </div>`;
          return;
        }

        // Group schedules by date
        const schedulesByDate = filteredSchedules.reduce((acc, schedule) => {
          const date = schedule.schedule_date;
          if (!acc[date]) {
            acc[date] = [];
          }
          acc[date].push(schedule);
          return acc;
        }, {});

        content.innerHTML = Object.entries(schedulesByDate).map(([date, dateSchedules]) => `
          <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="bg-yellow-100 dark:bg-yellow-900/30 px-4 py-2 border-b border-yellow-200 dark:border-yellow-800 mb-4">
              <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center">
                <i class="fas fa-calendar-day mr-2"></i>
                ${new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
              </h3>
            </div>
            <div class="space-y-4">
              ${dateSchedules.map(schedule => `
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-yellow-200 dark:border-yellow-800 p-4">
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                          <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                            <span class="text-red-600 dark:text-red-400 font-semibold">${parseInt(schedule.room_number) + 320}</span>
                          </div>
                        </div>
                        <div>
                          <p class="font-medium text-gray-800 dark:text-gray-200">${schedule.scheduler_name}</p>
                          <p class="text-sm text-gray-600 dark:text-gray-400">
                            ${new Date('1970-01-01T' + schedule.start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - 
                            ${new Date('1970-01-01T' + schedule.end_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="flex flex-col items-end space-y-2">
                      <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-medium">
                        Pending
                      </span>
                      <button class="cancel-request-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors flex items-center space-x-2"
                              data-scheduler="${schedule.scheduler_name}"
                              data-room="${schedule.room_number}"
                              data-date="${schedule.schedule_date}"
                              data-start="${schedule.start_time}"
                              data-end="${schedule.end_time}">
                        <i class="fas fa-times"></i>
                        <span>Cancel Request</span>
                      </button>
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        `).join('');

        // Reattach event listeners to cancel buttons
        document.querySelectorAll('.cancel-request-btn').forEach(button => {
          button.addEventListener('click', function() {
            const requestData = {
              scheduler: this.dataset.scheduler,
              room: this.dataset.room,
              date: this.dataset.date,
              start: this.dataset.start,
              end: this.dataset.end
            };
            
            cancelRequestYes.dataset.request = JSON.stringify(requestData);
            cancelRequestModal.classList.remove('hidden');
            cancelRequestModal.classList.add('flex');
          });
        });
      }

      // Add event listeners for date filters
      const approvedFilterDate = document.getElementById('approved-filter-date');
      const requestedFilterDate = document.getElementById('requested-filter-date');

      if (approvedFilterDate) {
        approvedFilterDate.addEventListener('change', (e) => {
          updateApprovedSchedulesDisplay(e.target.value);
        });
      }

      if (requestedFilterDate) {
        requestedFilterDate.addEventListener('change', (e) => {
          updateRequestedSchedulesDisplay(e.target.value);
        });
      }

      // Update displays when modals are opened
      const viewAllApprovedBtn = document.getElementById('view-all-approved');
      const viewAllRequestedBtn = document.getElementById('view-all-requested');
      const allApprovedModal = document.getElementById('all-approved-modal');
      const allRequestedModal = document.getElementById('all-requested-modal');

      if (viewAllApprovedBtn) {
        viewAllApprovedBtn.addEventListener('click', () => {
          allApprovedModal.classList.remove('hidden');
          allApprovedModal.classList.add('flex');
          updateApprovedSchedulesDisplay(approvedFilterDate.value);
        });
      }

      if (viewAllRequestedBtn) {
        viewAllRequestedBtn.addEventListener('click', () => {
          allRequestedModal.classList.remove('hidden');
          allRequestedModal.classList.add('flex');
          updateRequestedSchedulesDisplay(requestedFilterDate.value);
        });
      }

      // Add close button functionality for the modals
      const closeAllApproved = document.getElementById('close-all-approved');
      const closeAllRequested = document.getElementById('close-all-requested');

      if (closeAllApproved) {
        closeAllApproved.addEventListener('click', () => {
          allApprovedModal.classList.add('hidden');
          allApprovedModal.classList.remove('flex');
        });
      }

      if (closeAllRequested) {
        closeAllRequested.addEventListener('click', () => {
          allRequestedModal.classList.add('hidden');
          allRequestedModal.classList.remove('flex');
        });
      }

      // Close modals when clicking outside
      if (allApprovedModal) {
        allApprovedModal.addEventListener('click', (e) => {
          if (e.target === allApprovedModal) {
            allApprovedModal.classList.add('hidden');
            allApprovedModal.classList.remove('flex');
          }
        });
      }

      if (allRequestedModal) {
        allRequestedModal.addEventListener('click', (e) => {
          if (e.target === allRequestedModal) {
            allRequestedModal.classList.add('hidden');
            allRequestedModal.classList.remove('flex');
          }
        });
      }
    });

    // Calendar functionality
    const calendarDays = document.getElementById('calendar-days');
    const currentMonthElement = document.getElementById('current-month');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    // Get all approved schedule dates
    const approvedDates = <?php echo json_encode(array_map(function($schedule) {
      return $schedule['schedule_date'];
    }, $approvedSchedules)); ?>;

    function generateCalendar() {
      const firstDay = new Date(currentYear, currentMonth, 1);
      const lastDay = new Date(currentYear, currentMonth + 1, 0);
      const startingDay = firstDay.getDay();
      const totalDays = lastDay.getDate();
      
      // Update month and year display
      currentMonthElement.textContent = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      // Clear previous calendar
      calendarDays.innerHTML = '';
      
      // Add empty cells for days before the first day of the month
      for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-1 text-gray-400 dark:text-gray-600';
        calendarDays.appendChild(emptyDay);
      }
      
      // Add days of the month
      for (let day = 1; day <= totalDays; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'p-1 rounded relative';
        
        // Check if this date has an approved schedule
        const dateString = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const hasSchedule = approvedDates.includes(dateString);
        
        if (hasSchedule) {
          dayElement.classList.add('bg-green-50', 'dark:bg-green-900/20');
          dayElement.innerHTML = `
            ${day}
            <div class="absolute -top-1 -right-1">
              <i class="fas fa-check-circle text-green-500 dark:text-green-400 text-xs"></i>
            </div>
          `;
        } else {
          dayElement.textContent = day;
        }
        
        calendarDays.appendChild(dayElement);
      }
    }

    // Initialize calendar
    generateCalendar();

    // Add event listeners for month navigation
    if (prevMonthBtn) {
      prevMonthBtn.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
          currentMonth = 11;
          currentYear--;
        }
        generateCalendar();
      });
    }

    if (nextMonthBtn) {
      nextMonthBtn.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
          currentMonth = 0;
          currentYear++;
        }
        generateCalendar();
      });
    }

    // Add input validation for duration hours
    const durationHours = document.getElementById('duration-hours');
    if (durationHours) {
      durationHours.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (value > 12) {
          this.value = 12;
        } else if (value < 1) {
          this.value = 1;
        }
      });
    }

    // Room Calendar functionality
    const roomCalendarDays = document.getElementById('room-calendar-days');
    const roomCurrentMonthElement = document.getElementById('room-current-month');
    const roomPrevMonthBtn = document.getElementById('room-prev-month');
    const roomNextMonthBtn = document.getElementById('room-next-month');

    let roomCurrentDate = new Date();
    let roomCurrentMonth = roomCurrentDate.getMonth();
    let roomCurrentYear = roomCurrentDate.getFullYear();
    let roomSchedules = []; // Initialize roomSchedules as empty array

    function generateRoomCalendar() {
      const firstDay = new Date(roomCurrentYear, roomCurrentMonth, 1);
      const lastDay = new Date(roomCurrentYear, roomCurrentMonth + 1, 0);
      const startingDay = firstDay.getDay();
      const totalDays = lastDay.getDate();
      
      // Update month and year display
      roomCurrentMonthElement.textContent = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      // Clear previous calendar
      roomCalendarDays.innerHTML = '';
      
      // Add empty cells for days before the first day of the month
      for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-1 text-gray-400 dark:text-gray-600';
        roomCalendarDays.appendChild(emptyDay);
      }
      
      // Add days of the month
      for (let day = 1; day <= totalDays; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'p-1 rounded relative';
        
        // Check if this date has a schedule
        const dateString = `${roomCurrentYear}-${String(roomCurrentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const hasSchedule = Array.isArray(roomSchedules) && roomSchedules.some(schedule => {
          const scheduleDate = new Date(schedule.schedule_date);
          const currentDate = new Date(dateString);
          return scheduleDate.toDateString() === currentDate.toDateString();
        });
        
        if (hasSchedule) {
          dayElement.classList.add('bg-green-50', 'dark:bg-green-900/20');
          dayElement.innerHTML = `
            ${day}
            <div class="absolute -top-1 -right-1">
              <i class="fas fa-check-circle text-green-500 dark:text-green-400 text-xs"></i>
            </div>
          `;
        } else {
          dayElement.textContent = day;
        }
        
        roomCalendarDays.appendChild(dayElement);
      }
    }

    // Initialize room calendar
    generateRoomCalendar();

    // Add event listeners for room calendar month navigation
    if (roomPrevMonthBtn) {
      roomPrevMonthBtn.addEventListener('click', () => {
        roomCurrentMonth--;
        if (roomCurrentMonth < 0) {
          roomCurrentMonth = 11;
          roomCurrentYear--;
        }
        generateRoomCalendar();
      });
    }

    if (roomNextMonthBtn) {
      roomNextMonthBtn.addEventListener('click', () => {
        roomCurrentMonth++;
        if (roomCurrentMonth > 11) {
          roomCurrentMonth = 0;
          roomCurrentYear++;
        }
        generateRoomCalendar();
      });
    }

    // Function to filter schedules by date and time
    function filterSchedulesByDateAndTime(schedules, date, time) {
      let filteredSchedules = schedules;
      
      if (date) {
        filteredSchedules = filteredSchedules.filter(schedule => schedule.schedule_date === date);
      }
      
      if (time) {
        filteredSchedules = filteredSchedules.filter(schedule => {
          const scheduleStart = new Date('1970-01-01T' + schedule.start_time);
          const scheduleEnd = new Date('1970-01-01T' + schedule.end_time);
          const filterTimeDate = new Date('1970-01-01T' + time);
          return filterTimeDate >= scheduleStart && filterTimeDate <= scheduleEnd;
        });
      }
      
      return filteredSchedules;
    }

    // Function to update approved schedules display
    function updateApprovedSchedulesDisplay(date, time) {
      const content = document.getElementById('all-approved-content');
      const schedules = <?php echo json_encode($approvedSchedules); ?>;
      const filteredSchedules = filterSchedulesByDateAndTime(schedules, date, time);
      
      if (filteredSchedules.length === 0) {
        content.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-calendar-times text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">No schedules found${date ? ' for the selected date' : ''}${time ? ' at the selected time' : ''}.</p>
          </div>`;
        return;
      }

      content.innerHTML = filteredSchedules.map(schedule => `
        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
          <div class="flex items-center space-x-2 mb-2">
            <i class="fas fa-door-open text-primary-light dark:text-primary-dark"></i>
            <strong class="text-primary-light dark:text-primary-dark">Room:</strong>
            <span>Room ${parseInt(schedule.room_number) + 320}</span>
          </div>
          <div class="flex items-center space-x-2 mb-2">
            <i class="fas fa-calendar text-primary-light dark:text-primary-dark"></i>
            <strong class="text-primary-light dark:text-primary-dark">Date:</strong>
            <span>${new Date(schedule.schedule_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
          </div>
          <div class="flex items-center space-x-2">
            <i class="fas fa-clock text-primary-light dark:text-primary-dark"></i>
            <strong class="text-primary-light dark:text-primary-dark">Time:</strong>
            <span>${new Date('1970-01-01T' + schedule.start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - 
                  ${new Date('1970-01-01T' + schedule.end_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</span>
          </div>
        </div>
      `).join('');

      // Update calendar with check marks for all dates with schedules
      generateCalendar();
    }

    // Add event listeners for date and time filters
    const approvedFilterDate = document.getElementById('approved-filter-date');
    const approvedFilterTime = document.getElementById('approved-filter-time');

    if (approvedFilterDate) {
      approvedFilterDate.addEventListener('change', (e) => {
        updateApprovedSchedulesDisplay(e.target.value, approvedFilterTime.value);
      });
    }

    if (approvedFilterTime) {
      approvedFilterTime.addEventListener('change', (e) => {
        updateApprovedSchedulesDisplay(approvedFilterDate.value, e.target.value);
      });
    }

    // Update displays when modals are opened
    const viewAllApprovedBtn = document.getElementById('view-all-approved');
    if (viewAllApprovedBtn) {
      viewAllApprovedBtn.addEventListener('click', () => {
        allApprovedModal.classList.remove('hidden');
        allApprovedModal.classList.add('flex');
        updateApprovedSchedulesDisplay(approvedFilterDate.value, approvedFilterTime.value);
      });
    }
  </script>
</body>
</html>