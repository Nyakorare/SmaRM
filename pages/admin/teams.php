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
  <title>SmaRM Admin - Team Management</title>
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
    <!-- Sidebar for Teams -->
      <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200 animate-slide-in">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Active Teams</h3>
        <ul class="space-y-3 mb-6">
        <?php
        // Fetch all active teams from the team_list table
        $stmt = $conn->prepare("SELECT * FROM team_list");
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Loop through teams and display them
        while ($row = $result->fetch_assoc()) {
            echo "<li class='flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-md transition-colors duration-200 animate-fade-in'>
                    <span class='text-gray-700 dark:text-gray-300 text-sm truncate max-w-[150px]'>{$row['department']}</span>
                    <div class='flex space-x-2'>
                        <button onclick='editTeam(\"{$row['department']}\")' class='p-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                            </svg>
                        </button>
                        <button onclick='deleteTeam(\"{$row['department']}\")' class='p-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 focus:outline-none'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path>
                            </svg>
                        </button>
                    </div>
                </li>";
        }
        $stmt->close();
        ?>
      </ul>
        <div class="space-y-3">
          <input type="text" id="new-team-name" placeholder="Add New Team" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-200 transition-colors duration-200">
          <button onclick="addTeam()" class="w-full bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200 animate-bounce-in">Add Team</button>
        </div>
    </div>

    <!-- Teams Section -->
      <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-colors duration-200 animate-slide-in">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6 flex items-center space-x-2">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <span>Teacher Team Management</span>
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>Current Team</span>
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
                      echo "<tr class='animate-fade-in hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200' data-user-id='{$row['user_id']}'>
                              <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>{$row['username']}</td>
                              <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>{$row['email']}</td>
                              <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>
                                <div class='flex items-center space-x-2'>
                                  <svg class='w-4 h-4 text-blue-600 dark:text-blue-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'></path>
                                  </svg>
                                  <span>{$team}</span>
                                </div>
                              </td>
                              <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200'>";

                  if ($team === 'Not Assigned') {
                      // User not in a team, show "Assign Team" button
                          echo "<button onclick=\"changeTeam({$row['user_id']}, '{$team}')\" class='w-40 bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 transition-colors duration-200 animate-bounce-in shadow-md hover:shadow-lg flex items-center justify-center gap-2'>
                                  <svg class='w-5 h-5 flex-shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6v6m0 0v6m0-6h6m-6 0H6'></path>
                                  </svg>
                                  <span>Assign Team</span>
                                </button>";
                  } else {
                      // User is in a team, show "Change Team" and "Delete from Team"
                          echo "<div class='flex items-center space-x-3'>
                                <button onclick=\"changeTeam({$row['user_id']}, '{$team}')\" class='w-40 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200 animate-bounce-in shadow-md hover:shadow-lg flex items-center justify-center gap-2'>
                                  <svg class='w-5 h-5 flex-shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                                  </svg>
                                  <span>Change Team</span>
                                </button>
                                <button onclick=\"deleteFromTeam({$row['user_id']})\" class='w-40 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition-colors duration-200 animate-bounce-in shadow-md hover:shadow-lg flex items-center justify-center gap-2'>
                                  <svg class='w-5 h-5 flex-shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path>
                                  </svg>
                                  <span>Delete from Team</span>
                                </button>
                              </div>";
                  }
                  echo "</td>
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

  <!-- Edit Team Modal -->
  <div id="edit-team-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200 mb-4 flex items-center space-x-2">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
          <span>Edit Team</span>
        </h3>
        <div class="mt-2 px-7 py-3">
          <input type="text" id="edit-team-input" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-gray-100">
        </div>
        <div class="items-center px-4 py-3">
          <button onclick="updateTeam()" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-md hover:shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>Save Changes</span>
          </button>
          <button onclick="closeEditModal()" class="mt-3 px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-md hover:shadow-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>Cancel</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Team Modal -->
  <div id="delete-team-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">Delete Team</h3>
        <div class="mt-2 px-7 py-3">
          <p class="text-gray-600 dark:text-gray-400">Are you sure you want to delete this team? This action cannot be undone.</p>
        </div>
        <div class="flex justify-end px-4 py-3 space-x-2">
          <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">Cancel</button>
          <button onclick="confirmDeleteTeam()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Change Team Modal -->
  <div id="change-team-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">Team Management</h3>
        <div class="mt-2 px-7 py-3">
          <select id="change-team-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-200">
            <!-- Options will be populated dynamically -->
          </select>
        </div>
        <div class="flex justify-end px-4 py-3 space-x-2">
          <button onclick="closeChangeTeamModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">Cancel</button>
          <button id="confirm-team-button" onclick="confirmChangeTeam()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors duration-200">Change Team</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete from Team Modal -->
  <div id="delete-from-team-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">Remove from Team</h3>
        <div class="mt-2 px-7 py-3">
          <p class="text-gray-600 dark:text-gray-400">Are you sure you want to remove this user from their current team?</p>
        </div>
        <div class="flex justify-end px-4 py-3 space-x-2">
          <button onclick="closeDeleteFromTeamModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">Cancel</button>
          <button onclick="confirmDeleteFromTeam()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">Remove</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Team Actions Modal -->
  <div id="team-actions-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">Team Actions</h3>
        <div class="mt-2 px-7 py-3">
          <div class="space-y-3">
            <button onclick="editTeam(currentTeam)" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-200">
              Edit Team
            </button>
            <button onclick="deleteTeam(currentTeam)" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-200">
              Delete Team
            </button>
          </div>
        </div>
        <div class="flex justify-end px-4 py-3">
          <button onclick="closeTeamActionsModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200">Close</button>
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

    // Team management variables
    let currentTeam = '';
    let currentUserId = null;
    const editTeamModal = document.getElementById('edit-team-modal');
    const deleteTeamModal = document.getElementById('delete-team-modal');
    const changeTeamModal = document.getElementById('change-team-modal');
    const deleteFromTeamModal = document.getElementById('delete-from-team-modal');
    const teamActionsModal = document.getElementById('team-actions-modal');
    const editTeamInput = document.getElementById('edit-team-input');
    const changeTeamSelect = document.getElementById('change-team-select');

    // Team actions modal functions
    function showTeamActions(team) {
      currentTeam = team;
      teamActionsModal.classList.remove('hidden');
    }

    function closeTeamActionsModal() {
      teamActionsModal.classList.add('hidden');
      currentTeam = '';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
      if (event.target === editTeamModal) {
        closeEditModal();
      }
      if (event.target === deleteTeamModal) {
        closeDeleteModal();
      }
      if (event.target === changeTeamModal) {
        closeChangeTeamModal();
      }
      if (event.target === deleteFromTeamModal) {
        closeDeleteFromTeamModal();
      }
      if (event.target === teamActionsModal) {
        closeTeamActionsModal();
      }
    }

    // Edit team functions
    function editTeam(team) {
      currentTeam = team;
      editTeamInput.value = team;
      editTeamModal.classList.remove('hidden');
    }

    function closeEditModal() {
      editTeamModal.classList.add('hidden');
      currentTeam = '';
      editTeamInput.value = '';
    }

    function updateTeam() {
      const newTeamName = editTeamInput.value.trim();
      if (!newTeamName) {
        alert('Please enter a team name');
        return;
      }

      if (newTeamName === currentTeam) {
        alert('Please enter a different team name');
        return;
      }

      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update_team',
          old_team: currentTeam,
          new_team: newTeamName
        })
      })
      .then(response => response.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          window.location.reload();
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the team');
      });

      closeEditModal();
    }

    // Delete team functions
    function deleteTeam(team) {
      if (confirm(`Are you sure you want to delete the team "${team}"?`)) {
        fetch('../../php/manage-teams.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'delete_team',
            team: team
          })
        })
        .then(response => response.json())
        .then(data => {
          alert(data.message);
          if (data.success) {
            window.location.reload();
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting the team');
        });
      }
      closeTeamActionsModal();
    }

    function closeDeleteModal() {
      deleteTeamModal.classList.add('hidden');
      currentTeam = '';
    }

    // Change team functions
    function changeTeam(userId, currentTeam) {
      currentUserId = userId;
      const isAssigning = currentTeam === 'Not Assigned';
      
      // Update modal title and button text
      document.querySelector('#change-team-modal h3').textContent = isAssigning ? 'Assign Team' : 'Change Team';
      document.querySelector('#confirm-team-button').textContent = isAssigning ? 'Assign Team' : 'Change Team';
      
      // Clear and populate the select options
      changeTeamSelect.innerHTML = '';
      
      // Fetch all teams from the team_list table
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'get_teams'
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          data.teams.forEach(team => {
            if (team !== currentTeam) {
              const option = document.createElement('option');
              option.value = team;
              option.textContent = team;
              changeTeamSelect.appendChild(option);
            }
          });
          changeTeamModal.classList.remove('hidden');
        } else {
          alert('Error fetching teams');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching teams');
      });
    }

    function closeChangeTeamModal() {
      changeTeamModal.classList.add('hidden');
      currentUserId = null;
    }

    function confirmChangeTeam() {
      const newTeam = changeTeamSelect.value;
      if (!newTeam) {
        alert('Please select a team');
        return;
      }

      // Determine if this is an assign or change action based on the button text
      const isAssigning = document.querySelector('#confirm-team-button').textContent === 'Assign Team';
      const action = isAssigning ? 'assign' : 'change';

      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: action,
          user_id: currentUserId,
          team: newTeam
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
        alert('An error occurred while updating the team');
      });

      closeChangeTeamModal();
    }

    // Delete from team functions
    function deleteFromTeam(userId) {
      currentUserId = userId;
      deleteFromTeamModal.classList.remove('hidden');
    }

    function closeDeleteFromTeamModal() {
      deleteFromTeamModal.classList.add('hidden');
      currentUserId = null;
    }

    function confirmDeleteFromTeam() {
      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete',
          user_id: currentUserId
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
        alert('An error occurred while removing from team');
      });

      closeDeleteFromTeamModal();
    }

    // Add team function
    function addTeam() {
      const newTeamInput = document.getElementById('new-team-name');
      const newTeamName = newTeamInput.value.trim();

      if (!newTeamName) {
        alert('Please enter a team name');
        return;
      }

      fetch('../../php/manage-teams.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'add_team',
          team: newTeamName
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
        alert('An error occurred while adding the team');
      });

      // Clear the input field
      newTeamInput.value = '';
    }

    // Add event listener for Enter key in the new team input
    document.getElementById('new-team-name').addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        addTeam();
      }
    });
  </script>
</body>
</html>