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
  <link rel="stylesheet" href="./accounts.css">
</head>
<body>
  <!-- Navbar -->
  <nav id="navbar">
    <div class="nav-left">
      <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <ul class="nav-middle">
      <li><a href="./admin.php">Dashboard</a></li>
      <li><a href="#">Accounts</a></li>
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
    <!-- Accounts Section -->
    <section id="accounts-section">
      <h2>Accounts</h2>
      <table id="accounts-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Schedulers Count not Used</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Fetch accounts from the database, excluding the admin.
          $stmt = $conn->prepare("SELECT username, email, 
                                     (SELECT COUNT(*) FROM schedulers WHERE user_id = u.id) AS schedulers_count 
                                  FROM users u WHERE username != 'admin'");
          $stmt->execute();
          $result = $stmt->get_result();

          // Check if there are any users.
          if ($result->num_rows > 0) {
              // Loop through each user and display in the table.
              while ($row = $result->fetch_assoc()) {
                  echo "<tr>
                          <td>{$row['username']}</td>
                          <td>{$row['email']}</td>
                          <td>{$row['schedulers_count']}</td>
                          <td>
                            <button onclick=\"deleteUser('{$row['username']}')\">Delete</button>
                          </td>
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

  <script src="./accounts.js"></script>
</body>
</html>