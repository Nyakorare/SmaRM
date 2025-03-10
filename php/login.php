<?php
session_start();  // Start a session
// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../pages/admin/admin.php");
    } else {
        header("Location: ../pages/teacher/teacher.php");
    }
    exit; // Prevent further execution
}

require 'config.php'; // Include database connection.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username; // Store the username in session
        
            // Determine user role
            if ($username === 'admin') {
                $_SESSION['role'] = 'admin'; // Set role to admin
                echo "<script>
                        alert('Welcome, Admin! Redirecting to Admin Dashboard...');
                        window.location.href = '../pages/admin/admin.php';
                      </script>";
            } else {
                $_SESSION['role'] = 'teacher'; // Set role to teacher
                echo "<script>
                        alert('Login successful! Redirecting to Teacher Dashboard...');
                        window.location.href = '../pages/teacher/teacher.php';
                      </script>";
            }
        } else {
            echo "<script>
                    alert('Invalid password/username.');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('Username does not exist.');
                window.history.back();
              </script>";
    }

    $stmt->close();
}
$conn->close();
?>