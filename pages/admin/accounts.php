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
  <title>SmaRM Admin - Accounts</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#9e1b32',
            secondary: '#c53030',
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'slide-in': 'slideIn 0.5s ease-in-out',
            'bounce-in': 'bounceIn 0.5s ease-in-out',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0' },
              '100%': { opacity: '1' },
            },
            slideIn: {
              '0%': { transform: 'translateY(10px)', opacity: '0' },
              '100%': { transform: 'translateY(0)', opacity: '1' },
            },
            bounceIn: {
              '0%': { transform: 'scale(0.95)', opacity: '0' },
              '50%': { transform: 'scale(1.05)' },
              '100%': { transform: 'scale(1)', opacity: '1' },
            },
          },
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
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex flex-col transition-colors duration-200">
  <!-- Navbar -->
  <nav class="bg-white dark:bg-gray-800 shadow-lg transition-colors duration-200 animate-fade-in">
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
      <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200 animate-slide-in">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center space-x-2">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
          </svg>
          <span>User Statistics</span>
        </h3>
        <div class="space-y-4">
          <?php
          // Fetch total users count
          $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE username != 'admin'");
          $stmt->execute();
          $result = $stmt->get_result();
          $total_users = $result->fetch_assoc()['total_users'];
          $stmt->close();

          // Fetch total schedulers count
          $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as total_schedulers FROM schedulers");
          $stmt->execute();
          $result = $stmt->get_result();
          $total_schedulers = $result->fetch_assoc()['total_schedulers'];
          $stmt->close();

          // Fetch total ongoing schedules
          $stmt = $conn->prepare("SELECT COUNT(*) as total_schedules FROM approved_requests WHERE CONCAT(schedule_date, ' ', end_time) > NOW()");
          $stmt->execute();
          $result = $stmt->get_result();
          $total_schedules = $result->fetch_assoc()['total_schedules'];
          $stmt->close();
          ?>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Total Users</span>
              </div>
              <span class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $total_users; ?></span>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Total Schedulers</span>
              </div>
              <span class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $total_schedulers; ?></span>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Active Schedules</span>
              </div>
              <span class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $total_schedules; ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- User Accounts Section -->
      <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200 animate-slide-in">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6 flex items-center space-x-2">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
          </svg>
          <span>User Accounts</span>
        </h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Username</span>
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Email</span>
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>Schedulers Count</span>
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Actions</span>
                  </div>
                </th>
          </tr>
        </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <?php
            // Fetch users from the database, excluding the admin
            $stmt = $conn->prepare("SELECT u.id, u.username, u.email, 
                                  (SELECT COUNT(*) FROM schedulers WHERE user_id = u.id) as schedulers_count,
                                  (SELECT COUNT(*) FROM approved_requests WHERE user_id = u.id AND CONCAT(schedule_date, ' ', end_time) > NOW()) as ongoing_schedules_count 
                                  FROM users u 
                                  WHERE u.username != 'admin'");
          $stmt->execute();
          $result = $stmt->get_result();

            // Check if there are any users
          if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                      echo "<tr class='animate-fade-in hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200'>
                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>{$row['username']}</td>
                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>{$row['email']}</td>
                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>
                                  <div class='flex items-center space-x-4'>
                                      <div class='flex items-center space-x-1'>
                                          <svg class='w-4 h-4 text-blue-600 dark:text-blue-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                              <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'></path>
                                          </svg>
                                          <span>Schedulers: {$row['schedulers_count']}</span>
                                      </div>
                                      <div class='flex items-center space-x-1'>
                                          <svg class='w-4 h-4 text-green-600 dark:text-green-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                              <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                          </svg>
                                          <span>Ongoing: {$row['ongoing_schedules_count']}</span>
                                      </div>
                                  </div>
                            </td>
                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>
                                  <button onclick=\"deleteUser({$row['id']}, {$row['ongoing_schedules_count']})\" class='bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition-colors duration-200 animate-bounce-in shadow-md hover:shadow-lg flex items-center space-x-2'>
                                      <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                          <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path>
                                      </svg>
                                      <span>Delete</span>
                                  </button>
                          </td>
                        </tr>";
              }
          } else {
                echo "<tr class='animate-fade-in'>
                        <td colspan='4' class='px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center'>No accounts found.</td>
                    </tr>";
          }
          $stmt->close();
          ?>
        </tbody>
      </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Delete Confirmation Modal -->
  <div id="delete-confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3 text-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200 flex items-center justify-center space-x-2">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <span>Confirm Delete</span>
        </h3>
        <div class="mt-2 px-7 py-3">
          <p class="text-sm text-gray-500 dark:text-gray-400" id="delete-confirmation-message">
            Are you sure you want to delete this account? This action cannot be undone.
          </p>
        </div>
        <div class="items-center px-4 py-3">
          <button id="confirm-delete" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-md hover:shadow-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            <span>Delete</span>
          </button>
          <button id="cancel-delete" class="mt-3 px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-md hover:shadow-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>Cancel</span>
          </button>
        </div>
      </div>
    </div>
  </div>

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

    // Delete account functionality
    let userIdToDelete = null;
    let approvedSchedulesCount = 0;
    const deleteModal = document.getElementById('delete-confirmation-modal');
    const deleteMessage = document.getElementById('delete-confirmation-message');

    function deleteUser(userId, approvedSchedules) {
      userIdToDelete = userId;
      approvedSchedulesCount = approvedSchedules;
      
      if (approvedSchedules > 0) {
        deleteMessage.textContent = `This user currently has ${approvedSchedules} approved schedule(s). Are you sure you want to delete this account? This action cannot be undone.`;
      } else {
        deleteMessage.textContent = 'Are you sure you want to delete this account? This action cannot be undone.';
      }
      
      deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
      deleteModal.classList.add('hidden');
      userIdToDelete = null;
      approvedSchedulesCount = 0;
    }

    // Add event listener for cancel button
    document.getElementById('cancel-delete').addEventListener('click', closeDeleteModal);

    // Add event listener for confirm button
    document.getElementById('confirm-delete').addEventListener('click', confirmDelete);

    function confirmDelete() {
      if (!userIdToDelete) return;

      fetch('../../php/delete-user.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userIdToDelete
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
        alert('An error occurred while deleting the account');
      });

      closeDeleteModal();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target === deleteModal) {
        closeDeleteModal();
      }
    }
  </script>
</body>
</html>