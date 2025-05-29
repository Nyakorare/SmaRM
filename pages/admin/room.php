<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect if not an admin
    header("Location: ../landing-pages/login-page.php");
    exit();
}

require '../../php/config.php'; // Database connection
$username = $_SESSION['username'];

// Get today's date in 'Y-m-d' format
$todayDate = date('Y-m-d');

// Query to fetch approved requests for today and future dates
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
  <title>SmaRM Admin - Room Management</title>
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
          <a href="./admin.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Dashboard</a>
          <a href="./accounts.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Accounts</a>
          <a href="./teams.php" class="text-gray-700 dark:text-gray-300 hover:text-red-700 dark:hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Team Management</a>
          <a href="./room.php" class="text-red-700 dark:text-red-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Room Management</a>
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

        <!-- Room Selection -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Select Room</h3>
          <div class="grid grid-cols-2 gap-2">
            <?php for($i = 1; $i <= 5; $i++): ?>
              <button onclick="showRoomSchedules(<?php echo $i; ?>)" class="room-btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">
                Room <?php echo $i + 320; ?>
              </button>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Calendar -->
        <div class="calendar-container">
          <div class="flex justify-between items-center mb-4">
            <button onclick="previousMonth()" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
              <i class="fas fa-chevron-left"></i>
            </button>
            <h3 id="current-month" class="text-lg font-semibold text-gray-800 dark:text-gray-200"></h3>
            <button onclick="nextMonth()" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="grid grid-cols-7 gap-1 text-center text-sm mb-2">
            <div class="text-gray-600 dark:text-gray-400">Sun</div>
            <div class="text-gray-600 dark:text-gray-400">Mon</div>
            <div class="text-gray-600 dark:text-gray-400">Tue</div>
            <div class="text-gray-600 dark:text-gray-400">Wed</div>
            <div class="text-gray-600 dark:text-gray-400">Thu</div>
            <div class="text-gray-600 dark:text-gray-400">Fri</div>
            <div class="text-gray-600 dark:text-gray-400">Sat</div>
          </div>
          <div id="calendar-days" class="grid grid-cols-7 gap-1"></div>
        </div>
      </div>

      <!-- Right Panel -->
      <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Room Schedules</h2>
        
        <!-- Filters -->
        <div class="flex space-x-4 mb-6">
          <div class="flex-1">
            <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Date:</label>
            <input type="date" id="schedule-filter-date" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm">
          </div>
          <div class="flex-1">
            <label class="block text-primary-light dark:text-primary-dark mb-2">Filter by Time:</label>
            <input type="time" id="schedule-filter-time" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-2 rounded shadow-sm">
          </div>
        </div>

        <!-- Schedules List -->
        <div id="schedules-list" class="space-y-4">
          <!-- Schedules will be populated here -->
        </div>
      </div>
    </div>
  </main>

  <footer class="bg-white dark:bg-gray-800 shadow-lg mt-auto transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 py-4">
      <p class="text-center text-gray-600 dark:text-gray-400 text-sm transition-colors duration-200">&copy 2024 Room Scheduler. All rights reserved.</p>
    </div>
  </footer>

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

    // Calendar functionality
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    let selectedRoom = null;
    let roomSchedules = [];

    function generateCalendar() {
      const firstDay = new Date(currentYear, currentMonth, 1);
      const lastDay = new Date(currentYear, currentMonth + 1, 0);
      const startingDay = firstDay.getDay();
      const totalDays = lastDay.getDate();
      
      document.getElementById('current-month').textContent = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      const calendarDays = document.getElementById('calendar-days');
      calendarDays.innerHTML = '';
      
      // Add empty cells for days before the first day of the month
      for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'h-8';
        calendarDays.appendChild(emptyDay);
      }
      
      // Add days of the month
      for (let day = 1; day <= totalDays; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'relative h-8 flex items-center justify-center text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 rounded';
        
        const dateString = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        // Check if this date has any schedules
        const hasSchedule = Array.isArray(roomSchedules) && roomSchedules.some(schedule => {
          const scheduleDate = new Date(schedule.schedule_date);
          const currentDate = new Date(dateString);
          return scheduleDate.toDateString() === currentDate.toDateString();
        });
        
        if (hasSchedule) {
          dayElement.classList.add('bg-green-100', 'dark:bg-green-900');
          const checkMark = document.createElement('div');
          checkMark.className = 'absolute -top-1 -right-1';
          checkMark.innerHTML = '<i class="fas fa-check-circle text-green-500 dark:text-green-400 text-xs"></i>';
          dayElement.appendChild(checkMark);
        }
        
        dayElement.textContent = day;
        calendarDays.appendChild(dayElement);
      }
    }

    function previousMonth() {
      currentMonth--;
      if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
      }
      generateCalendar();
    }

    function nextMonth() {
      currentMonth++;
      if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
      }
      generateCalendar();
    }

    // Room schedules functionality
    function showRoomSchedules(roomNumber) {
      selectedRoom = roomNumber;
      document.querySelectorAll('.room-btn').forEach(btn => {
        btn.classList.remove('bg-red-600', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      });
      event.target.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
      event.target.classList.add('bg-red-600', 'text-white');
      fetchSchedules();
    }

    function fetchSchedules() {
      if (!selectedRoom) return;

      const filterDate = document.getElementById('schedule-filter-date').value;
      const filterTime = document.getElementById('schedule-filter-time').value;

      fetch(`../../php/get-room-schedules.php?room=${selectedRoom}&date=${filterDate}&time=${filterTime}`)
        .then(response => response.json())
        .then(data => {
          roomSchedules = data;
          updateSchedulesDisplay();
          generateCalendar();
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('schedules-list').innerHTML = '<p class="text-red-600 dark:text-red-400">Error loading schedules</p>';
        });
    }

    function updateSchedulesDisplay() {
      const schedulesList = document.getElementById('schedules-list');
      schedulesList.innerHTML = '';

      if (!Array.isArray(roomSchedules) || roomSchedules.length === 0) {
        schedulesList.innerHTML = '<p class="text-gray-600 dark:text-gray-400">No schedules found for this room.</p>';
        return;
      }

      roomSchedules.forEach(schedule => {
        const scheduleElement = document.createElement('div');
        scheduleElement.className = 'bg-gray-50 dark:bg-gray-700 p-4 rounded-md transition-colors duration-200 shadow-md hover:shadow-lg';
        scheduleElement.innerHTML = `
          <div class="flex items-center justify-between mb-2">
            <p class="font-medium text-gray-800 dark:text-gray-200">${schedule.scheduler_name}</p>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400">Scheduled by: ${schedule.username}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">Department: ${schedule.department}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">${new Date(schedule.schedule_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">${new Date('1970-01-01T' + schedule.start_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })} - ${new Date('1970-01-01T' + schedule.end_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</p>
        `;
        schedulesList.appendChild(scheduleElement);
      });
    }

    // Add event listeners for filters
    document.getElementById('schedule-filter-date').addEventListener('change', fetchSchedules);
    document.getElementById('schedule-filter-time').addEventListener('change', fetchSchedules);

    // Initialize calendar
    generateCalendar();
  </script>
</body>
</html> 