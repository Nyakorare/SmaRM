<?php
session_start(); // Start the session to access the session variable

// Check if the user is logged in, if not, redirect to the login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
  // Redirect if not a teacher
  header("Location: ../landing-pages/login-page.php");
  exit();
}

require '../../php/config.php'; // Database connection
$username = $_SESSION['username'];

// Query to fetch all available departments from team_list
$departmentsQuery = $conn->prepare("
    SELECT tl.department, COUNT(t.id) as member_count
    FROM team_list tl
    LEFT JOIN teams t ON tl.department = t.department
    GROUP BY tl.department
");
$departmentsQuery->execute();
$departmentsQuery->store_result();
$departmentsQuery->bind_result($department, $member_count);
$departments = [];

while ($departmentsQuery->fetch()) {
    $departments[] = [
        'department' => $department,
        'member_count' => $member_count
    ];
}
$departmentsQuery->close();

// Query to check if the user is already in a team
$userTeamQuery = $conn->prepare("
    SELECT t.department
    FROM teams t
    JOIN users u ON t.user_id = u.id
    WHERE u.username = ?
");
$userTeamQuery->bind_param("s", $username);
$userTeamQuery->execute();
$userTeamQuery->store_result();
$userTeamQuery->bind_result($current_department);
$hasTeam = $userTeamQuery->fetch();
$userTeamQuery->close();

// Query to fetch department members if user is in a team
$departmentMembers = [];
if ($hasTeam) {
    $membersQuery = $conn->prepare("
        SELECT u.username
        FROM teams t
        JOIN users u ON t.user_id = u.id
        WHERE t.department = ?
        ORDER BY u.username
    ");
    $membersQuery->bind_param("s", $current_department);
    $membersQuery->execute();
    $membersQuery->store_result();
    $membersQuery->bind_result($member_username);
    
    while ($membersQuery->fetch()) {
        $departmentMembers[] = $member_username;
    }
    $membersQuery->close();
}

// Handle team join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_team'])) {
    $department = $_POST['department'];
    
    // Get user ID
    $userIdQuery = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $userIdQuery->bind_param("s", $username);
    $userIdQuery->execute();
    $userIdQuery->store_result();
    $userIdQuery->bind_result($user_id);
    $userIdQuery->fetch();
    $userIdQuery->close();
    
    // Add user to team
    $joinTeamStmt = $conn->prepare("INSERT INTO teams (user_id, department) VALUES (?, ?)");
    $joinTeamStmt->bind_param("is", $user_id, $department);
    $joinTeamStmt->execute();
    $joinTeamStmt->close();
    
    // Refresh the page to show updated team information
    header("Location: subject.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmaRM Teacher - Department Information</title>
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
              <a href="./teacher.php" class="flex items-center space-x-2 text-gray-900 dark:text-white hover:text-primary-light dark:hover:text-primary-dark transition-colors">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
              </a>
            </li>
            <li>
              <a href="#" class="flex items-center space-x-2 text-gray-900 dark:text-white hover:text-primary-light dark:hover:text-primary-dark transition-colors">
                <i class="fas fa-building"></i>
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
  <main class="max-w-7xl mx-auto px-4 py-8">
    <section class="bg-white dark:bg-dark-lighter rounded-lg p-6 shadow-lg transition-colors">
      <h2 class="text-2xl font-bold text-primary-light dark:text-primary-dark mb-6 flex items-center">
        <i class="fas fa-building mr-2"></i>Department Information
      </h2>
      
      <?php if ($hasTeam): ?>
        <!-- Current Team Information -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-user-check mr-2"></i>Your Current Department
          </h3>
          <div class="bg-gray-200 dark:bg-gray-700 p-6 rounded-lg shadow-md">
            <div class="grid grid-cols-1 gap-4">
              <div>
                <p class="text-gray-900 dark:text-white">
                  <span class="font-semibold">Department:</span> 
                  <?php echo htmlspecialchars($current_department); ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Department Members -->
        <div>
          <h3 class="text-xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-users mr-2"></i>Department Members
          </h3>
          <div class="bg-gray-200 dark:bg-gray-700 p-6 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($departmentMembers as $member): ?>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                  <div class="flex items-center space-x-3">
                    <i class="fas fa-user-circle text-2xl text-primary-light dark:text-primary-dark"></i>
                    <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($member); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Department Selection -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-primary-light dark:text-primary-dark mb-4 flex items-center">
            <i class="fas fa-user-plus mr-2"></i>Join a Department
          </h3>
          <div class="bg-gray-200 dark:bg-gray-700 p-6 rounded-lg shadow-md">
            <form method="POST" action="" class="space-y-4">
              <div>
                <label for="department-select" class="block text-gray-900 dark:text-white mb-2 flex items-center">
                  <i class="fas fa-building mr-2"></i>Select Department:
                </label>
                <select id="department-select" name="department" class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-2 rounded shadow-sm" required>
                  <option value="">Select a department...</option>
                  <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                      <?php echo htmlspecialchars($dept['department']); ?> 
                      (<?php echo $dept['member_count']; ?> members)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="flex justify-end">
                <button type="submit" name="join_team" class="bg-secondary text-white px-6 py-2 rounded hover:bg-primary-light dark:hover:bg-primary-dark hover:text-white transition-colors shadow-md flex items-center space-x-2">
                  <i class="fas fa-user-plus"></i>
                  <span>Join Department</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="bg-white dark:bg-dark text-gray-500 text-center py-4 fixed bottom-0 w-full transition-colors shadow-lg">
    <p class="flex items-center justify-center">
      <i class="fas fa-copyright mr-2"></i>2024 Room Scheduler. All rights reserved.
    </p>
  </footer>

  <script src="./theme.js"></script>
  <script src="./menu.js"></script>
  <script src="./subject.js"></script>
</body>
</html>