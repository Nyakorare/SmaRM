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
    SELECT sr.room_number, sr.scheduler_name, sr.schedule_date, sr.start_time, sr.end_time, sr.user_id, u.username, t.department
    FROM scheduler_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN teams t ON u.id = t.user_id
    WHERE sr.schedule_date >= ?
    ORDER BY sr.schedule_date, sr.start_time
");
$stmt->bind_param("s", $todayDate);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time, $user_id, $username, $department);

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
        'username' => $username,
        'department' => $department ? $department : 'Not Assigned'
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

// Fetch approved requests for today and future dates
$stmt = $conn->prepare("
    SELECT ar.room_number, ar.scheduler_name, ar.schedule_date, ar.start_time, ar.end_time, ar.user_id, u.username, t.department
    FROM approved_requests ar
    JOIN users u ON ar.user_id = u.id
    LEFT JOIN teams t ON u.id = t.user_id
    WHERE ar.schedule_date >= ?
    ORDER BY ar.schedule_date, ar.start_time
");
$stmt->bind_param("s", $todayDate);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($room_number, $scheduler_name, $schedule_date, $start_time, $end_time, $user_id, $username, $department);

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
        'username' => $username,
        'department' => $department ? $department : 'Not Assigned'
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
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#9e1b32',
            secondary: '#c53030',
          }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <script>
    // Initialize dark mode
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  </script>
  <style>
    .clock {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #9e1b32 0%, #c53030 100%);
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
    .dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 10;
        display: none;
        min-width: 160px;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        background-color: white;
        border-radius: 0.375rem;
        box-shadow: 0 2px 5px 0 rgba(0,0,0,0.1);
    }
    .dark .dropdown-menu {
        background-color: #1f2937;
    }
    .dropdown-menu.show {
        display: block;
    }
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.5rem 1rem;
        clear: both;
        font-weight: 400;
        color: #374151;
        text-align: inherit;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
        cursor: pointer;
    }
    .dark .dropdown-item {
        color: #e5e7eb;
    }
    .dropdown-item:hover {
        background-color: #f3f4f6;
    }
    .dark .dropdown-item:hover {
        background-color: #374151;
    }
    .dropdown-item.danger {
        color: #dc2626;
    }
    .dark .dropdown-item.danger {
        color: #ef4444;
    }
    .dropdown-item.danger:hover {
        background-color: #fee2e2;
    }
    .dark .dropdown-item.danger:hover {
        background-color: #7f1d1d;
    }
  </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex flex-col transition-colors duration-200">
  <!-- Navbar -->
  <nav class="bg-white dark:bg-gray-800 shadow-lg transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center h-16">
        <!-- Logo and Brand -->
        <div class="flex items-center">
          <a href="../../index.php" class="flex items-center">
            <img src="../../images/SmaRM-Logo.png" class="h-12 w-auto">
            <div class="ml-3">
              <span class="text-2xl font-bold text-green-600 dark:text-green-400">SmaRM</span>
              <p class="text-sm text-red-700 dark:text-red-400">Smart Room Management</p>
                </div>
    </a>
        </div>

        <!-- Navigation Links -->
        <div class="hidden md:flex items-center space-x-8">
          <a href="./admin.php" class="text-red-700 dark:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Dashboard</a>
          <a href="./accounts.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Accounts</a>
          <a href="./teams.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Team Management</a>
          <a href="./room.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Room Management</a>
        </div>

        <!-- User Menu -->
        <div class="flex items-center space-x-4">
          <button id="theme-toggle" class="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
            </svg>
          </button>
          <span class="text-gray-700 dark:text-gray-300 transition-colors duration-200">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
          <div class="relative">
            <button id="menu-button" class="bg-red-700 dark:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-800 dark:hover:bg-red-700 transition-colors duration-200">Menu</button>
            <div id="menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg hidden transition-colors duration-200">
              <a href="../../php/logout.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-gray-700 transition-colors duration-200">Logout</a>
            </div>
    </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 py-6 flex-grow">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Left Panel -->
      <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200">
        <div class="mb-6">
          <div class="clock">
            <div id="clock-time" class="clock-time">00:00:00</div>
            <div id="clock-date" class="clock-date">Loading...</div>
            <div class="clock-timezone">Philippines (GMT+8)</div>
          </div>
    </div>

        <!-- Tabs -->
        <div class="space-y-2">
          <button id="active-tab" class="w-full bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200 flex items-center justify-center space-x-2 shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Active Schedules</span>
          </button>
          <button id="future-tab" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 flex items-center justify-center space-x-2 shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span>Future Schedules</span>
          </button>
        </div>

        <!-- Schedules Container with Fixed Height -->
        <div class="mt-6 h-[400px] relative">
        <!-- Active Schedules Content -->
          <div id="active-schedules" class="absolute inset-0 transition-opacity duration-200">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 transition-colors duration-200 flex items-center space-x-2">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Today Schedules</span>
            </h2>
            <div class="space-y-4 overflow-y-auto" style="max-height: calc(400px - 4rem);">
            <?php if (count($todayApprovedRequests) > 0): ?>
                <?php 
                // Show first schedule
                $firstSchedule = $todayApprovedRequests[0];
                $roomNumber = intval($firstSchedule['room_number']) + 320; // Convert to 321-325 range
                ?>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 shadow-md hover:shadow-lg">
                  <div class="flex items-center justify-between mb-2">
                    <p class="font-medium text-gray-800 dark:text-gray-200 transition-colors duration-200"><?php echo htmlspecialchars($firstSchedule['scheduler_name']); ?></p>
                    <div class="relative">
                        <button onclick="toggleDropdown(this)" class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <button onclick="cancelSchedule('<?php echo htmlspecialchars($firstSchedule['room_number']); ?>', '<?php echo htmlspecialchars($firstSchedule['schedule_date']); ?>', '<?php echo htmlspecialchars($firstSchedule['start_time']); ?>', '<?php echo htmlspecialchars($firstSchedule['end_time']); ?>')" class="dropdown-item danger">Cancel Schedule</button>
                        </div>
                    </div>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Room <?php echo $roomNumber; ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: <?php echo htmlspecialchars($firstSchedule['username']); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Department: <?php echo htmlspecialchars($firstSchedule['department']); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo date('l, F j, Y', strtotime($firstSchedule['schedule_date'])); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo date('h:i A', strtotime($firstSchedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($firstSchedule['end_time'])); ?></p>
                </div>
                <?php if (count($todayApprovedRequests) > 1): ?>
                  <button onclick="showMoreSchedules('active')" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center space-x-2 shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    <span>See More (<?php echo count($todayApprovedRequests) - 1; ?> more)</span>
                  </button>
                <?php endif; ?>
            <?php else: ?>
              <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No approved schedules for today.</p>
            <?php endif; ?>
        </div>
    </div>

        <!-- Future Schedules Content -->
          <div id="future-schedules" class="absolute inset-0 opacity-0 pointer-events-none transition-opacity duration-200">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 transition-colors duration-200 flex items-center space-x-2">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span>Future Schedules</span>
            </h2>
            <div class="space-y-4 overflow-y-auto" style="max-height: calc(400px - 4rem);">
            <?php if (count($futureApprovedRequests) > 0): ?>
                <?php 
                // Show first schedule
                $firstSchedule = $futureApprovedRequests[0];
                $roomNumber = intval($firstSchedule['room_number']) + 320; // Convert to 321-325 range
                ?>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 shadow-md hover:shadow-lg">
                  <div class="flex items-center justify-between mb-2">
                    <p class="font-medium text-gray-800 dark:text-gray-200 transition-colors duration-200"><?php echo htmlspecialchars($firstSchedule['scheduler_name']); ?></p>
                    <div class="relative">
                        <button onclick="toggleDropdown(this)" class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <button onclick="cancelSchedule('<?php echo htmlspecialchars($firstSchedule['room_number']); ?>', '<?php echo htmlspecialchars($firstSchedule['schedule_date']); ?>', '<?php echo htmlspecialchars($firstSchedule['start_time']); ?>', '<?php echo htmlspecialchars($firstSchedule['end_time']); ?>')" class="dropdown-item danger">Cancel Schedule</button>
                        </div>
                    </div>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Room <?php echo $roomNumber; ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: <?php echo htmlspecialchars($firstSchedule['username']); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Department: <?php echo htmlspecialchars($firstSchedule['department']); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo date('l, F j, Y', strtotime($firstSchedule['schedule_date'])); ?></p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-200"><?php echo date('h:i A', strtotime($firstSchedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($firstSchedule['end_time'])); ?></p>
                </div>
                <?php if (count($futureApprovedRequests) > 1): ?>
                  <button onclick="showMoreSchedules('future')" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center space-x-2 shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    <span>See More (<?php echo count($futureApprovedRequests) - 1; ?> more)</span>
                  </button>
                <?php endif; ?>
            <?php else: ?>
              <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No approved schedules for future dates.</p>
            <?php endif; ?>
            </div>
          </div>
        </div>
    </div>

    <!-- Schedule Request Box -->
      <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-6 transition-colors duration-200">Schedule Requests</h2>

        <!-- Tabs -->
        <div class="space-y-2 mb-6">
          <button id="today-requests-tab" class="w-full bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200">Today's Requests</button>
          <button id="future-requests-tab" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">Future Requests</button>
        </div>

        <!-- Today's Requests Content -->
        <div id="today-requests-content">
          <div class="space-y-4">
      <?php if (count($todayRequests) > 0): ?>
          <?php foreach ($todayRequests as $request): ?>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-4">
                      <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                          <span class="text-red-600 dark:text-red-400 font-semibold"><?php echo intval($request['room_number']) + 320; ?></span>
                        </div>
                      </div>
                      <div>
                        <p class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($request['scheduler_name']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: <?php echo htmlspecialchars($request['username']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Department: <?php echo htmlspecialchars($request['department']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo date('l, F j, Y', strtotime($request['schedule_date'])); ?> | <?php echo date('h:i A', strtotime($request['start_time'])); ?> - <?php echo date('h:i A', strtotime($request['end_time'])); ?></p>
                      </div>
                    </div>
                  </div>
                  <form method="POST" action="handle_schedule_request.php" class="flex-shrink-0 space-x-2">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($request['room_number']); ?>">
                <input type="hidden" name="schedule_date" value="<?php echo htmlspecialchars($request['schedule_date']); ?>">
                <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($request['start_time']); ?>">
                <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($request['end_time']); ?>">
                    <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200">Approve</button>
                    <button type="submit" name="action" value="decline" class="bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-red-700 transition-colors duration-200">Decline</button>
              </form>
                </div>
          <?php endforeach; ?>
      <?php else: ?>
              <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No schedule requests for today.</p>
      <?php endif; ?>
    </div>
  </div>

        <!-- Future Requests Content -->
        <div id="future-requests-content" class="hidden">
          <div class="space-y-4">
      <?php if (count($futureRequests) > 0): ?>
          <?php foreach ($futureRequests as $request): ?>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-4">
                      <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                          <span class="text-red-600 dark:text-red-400 font-semibold"><?php echo intval($request['room_number']) + 320; ?></span>
                        </div>
                      </div>
                      <div>
                        <p class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($request['scheduler_name']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: <?php echo htmlspecialchars($request['username']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Department: <?php echo htmlspecialchars($request['department']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo date('l, F j, Y', strtotime($request['schedule_date'])); ?> | <?php echo date('h:i A', strtotime($request['start_time'])); ?> - <?php echo date('h:i A', strtotime($request['end_time'])); ?></p>
                      </div>
                    </div>
                  </div>
                  <form method="POST" action="handle_schedule_request.php" class="flex-shrink-0 space-x-2">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($request['room_number']); ?>">
                <input type="hidden" name="schedule_date" value="<?php echo htmlspecialchars($request['schedule_date']); ?>">
                <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($request['start_time']); ?>">
                <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($request['end_time']); ?>">
                    <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200">Approve</button>
                    <button type="submit" name="action" value="decline" class="bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-red-700 transition-colors duration-200">Decline</button>
              </form>
                </div>
          <?php endforeach; ?>
      <?php else: ?>
              <p class="text-gray-600 dark:text-gray-400 transition-colors duration-200">No future schedule requests.</p>
      <?php endif; ?>
    </div>
  </div>
      </div>
    </div>
  </main>

  <footer class="bg-white dark:bg-gray-800 shadow-lg mt-auto transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 py-4">
      <p class="text-center text-gray-600 dark:text-gray-400 text-sm transition-colors duration-200">&copy 2024 Room Scheduler. All rights reserved.</p>
    </div>
  </footer>

  <!-- More Schedules Modal -->
  <div id="more-schedules-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200 mb-4 flex items-center space-x-2" id="modal-title">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span>More Schedules</span>
        </h3>
        
        <!-- Room Filter -->
        <div class="mb-4">
          <label for="room-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Room</label>
          <select id="room-filter" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-gray-100">
            <option value="all">All Rooms</option>
            <!-- Room options will be populated dynamically -->
          </select>
        </div>

        <div id="modal-content" class="space-y-4 max-h-96 overflow-y-auto">
          <!-- Content will be populated dynamically -->
        </div>
        <div class="mt-4 flex justify-end">
          <button onclick="closeMoreSchedulesModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 flex items-center space-x-2 shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>Close</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Theme toggle functionality
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      themeToggleLightIcon.classList.remove('hidden');
    } else {
      themeToggleDarkIcon.classList.remove('hidden');
    }

    const themeToggleBtn = document.getElementById('theme-toggle');

    themeToggleBtn.addEventListener('click', function() {
      // Toggle icons
      themeToggleDarkIcon.classList.toggle('hidden');
      themeToggleLightIcon.classList.toggle('hidden');

      // If is set in local storage
      if (localStorage.getItem('theme')) {
        if (localStorage.getItem('theme') === 'light') {
          document.documentElement.classList.add('dark');
          localStorage.setItem('theme', 'dark');
        } else {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('theme', 'light');
        }
      } else {
        if (document.documentElement.classList.contains('dark')) {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('theme', 'light');
        } else {
          document.documentElement.classList.add('dark');
          localStorage.setItem('theme', 'dark');
        }
      }
    });

    // Menu dropdown functionality
    const menuButton = document.getElementById('menu-button');
    const menuDropdown = document.getElementById('menu-dropdown');

    menuButton.addEventListener('click', function() {
      menuDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!menuButton.contains(event.target) && !menuDropdown.contains(event.target)) {
        menuDropdown.classList.add('hidden');
      }
    });

    // Tab functionality
    const activeTab = document.getElementById('active-tab');
    const futureTab = document.getElementById('future-tab');
    const activeContent = document.getElementById('active-schedules');
    const futureContent = document.getElementById('future-schedules');

    activeTab.addEventListener('click', () => {
      activeTab.classList.add('bg-green-600', 'text-white');
      activeTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      futureTab.classList.remove('bg-green-600', 'text-white');
      futureTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      activeContent.classList.remove('opacity-0', 'pointer-events-none');
      futureContent.classList.add('opacity-0', 'pointer-events-none');
    });

    futureTab.addEventListener('click', () => {
      futureTab.classList.add('bg-green-600', 'text-white');
      futureTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      activeTab.classList.remove('bg-green-600', 'text-white');
      activeTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      futureContent.classList.remove('opacity-0', 'pointer-events-none');
      activeContent.classList.add('opacity-0', 'pointer-events-none');
    });

    // Schedule Requests Tab functionality
    const todayRequestsTab = document.getElementById('today-requests-tab');
    const futureRequestsTab = document.getElementById('future-requests-tab');
    const todayRequestsContent = document.getElementById('today-requests-content');
    const futureRequestsContent = document.getElementById('future-requests-content');

    todayRequestsTab.addEventListener('click', () => {
      todayRequestsTab.classList.add('bg-green-600', 'text-white');
      todayRequestsTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      futureRequestsTab.classList.remove('bg-green-600', 'text-white');
      futureRequestsTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      todayRequestsContent.classList.remove('hidden');
      futureRequestsContent.classList.add('hidden');
    });

    futureRequestsTab.addEventListener('click', () => {
      futureRequestsTab.classList.add('bg-green-600', 'text-white');
      futureRequestsTab.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      todayRequestsTab.classList.remove('bg-green-600', 'text-white');
      todayRequestsTab.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      futureRequestsContent.classList.remove('hidden');
      todayRequestsContent.classList.add('hidden');
    });

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

    // More Schedules Modal functionality
    const moreSchedulesModal = document.getElementById('more-schedules-modal');
    const modalContent = document.getElementById('modal-content');
    const modalTitle = document.getElementById('modal-title');
    const roomFilter = document.getElementById('room-filter');
    let currentSchedules = [];

    function showMoreSchedules(type) {
      currentSchedules = type === 'active' ? <?php echo json_encode(array_slice($todayApprovedRequests, 1)); ?> : <?php echo json_encode(array_slice($futureApprovedRequests, 1)); ?>;
      
      modalTitle.textContent = type === 'active' ? 'More Today Schedules' : 'More Future Schedules';
      
      // Populate room filter options with all rooms (321-325)
      roomFilter.innerHTML = '<option value="all">All Rooms</option>' + 
        Array.from({length: 5}, (_, i) => i + 321)
          .map(room => `<option value="${room - 320}">Room ${room}</option>`)
          .join('');
      
      // Display all schedules initially
      displayFilteredSchedules('all');
      
      moreSchedulesModal.classList.remove('hidden');
    }

    function displayFilteredSchedules(selectedRoom) {
      modalContent.innerHTML = '';
      
      const filteredSchedules = selectedRoom === 'all' 
        ? currentSchedules 
        : currentSchedules.filter(schedule => schedule.room_number === selectedRoom);

      if (filteredSchedules.length === 0) {
        modalContent.innerHTML = '<p class="text-gray-600 dark:text-gray-400 text-center py-4">No schedules found for the selected room.</p>';
        return;
      }

      filteredSchedules.forEach(schedule => {
        const scheduleElement = document.createElement('div');
        scheduleElement.className = 'bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 shadow-md hover:shadow-lg';
        const roomNumber = parseInt(schedule.room_number) + 320; // Convert to 321-325 range
        scheduleElement.innerHTML = `
          <div class="flex items-center justify-between mb-2">
            <p class="font-medium text-gray-800 dark:text-gray-200">${schedule.scheduler_name}</p>
            <div class="relative">
                <button onclick="toggleDropdown(this)" class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <button onclick="cancelSchedule('${schedule.room_number}', '${schedule.schedule_date}', '${schedule.start_time}', '${schedule.end_time}')" class="dropdown-item danger">Cancel Schedule</button>
                </div>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400">Room ${roomNumber}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: ${schedule.username}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">Department: ${schedule.department}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">${new Date(schedule.schedule_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">${new Date('1970-01-01T' + schedule.start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })} - ${new Date('1970-01-01T' + schedule.end_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</p>
        `;
        modalContent.appendChild(scheduleElement);
      });
    }

    // Add event listener for room filter
    roomFilter.addEventListener('change', (e) => {
      displayFilteredSchedules(e.target.value);
    });

    function closeMoreSchedulesModal() {
      moreSchedulesModal.classList.add('hidden');
      roomFilter.value = 'all'; // Reset filter when closing
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target === moreSchedulesModal) {
        closeMoreSchedulesModal();
      }
    }

    // Add this JavaScript function
    function toggleDropdown(button) {
        const dropdown = button.nextElementSibling;
        const isOpen = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if (menu !== dropdown) {
                menu.classList.remove('show');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
        
        // Close dropdown when clicking outside
        if (!isOpen) {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
    }

    function cancelSchedule(roomNumber, scheduleDate, startTime, endTime) {
        if (confirm('Are you sure you want to cancel this schedule?')) {
            fetch('../../php/cancel-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_number: roomNumber,
                    schedule_date: scheduleDate,
                    start_time: startTime,
                    end_time: endTime
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while canceling the schedule');
            });
        }
    }
  </script>
</body>
</html>