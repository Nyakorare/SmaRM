<?php
require 'config.php'; // Include your database connection.

if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Delete the user from the database.
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);

    if ($stmt->execute()) {
        echo "<script>
                alert('User deleted successfully.');
                window.location.href = 'accounts.php';
              </script>";
    } else {
        echo "<script>
                alert('Error deleting user.');
                window.history.back();
              </script>";
    }

    $stmt->close();
}
$conn->close();
?>