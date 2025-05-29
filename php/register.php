<?php
require './config.php'; // Include database connection.

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = 'teacher'; // Default role for new registrations

    // Check for duplicate username.
    $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
                alert('Username is already taken.');
                window.history.back();
              </script>";
    } else {
        // Check for duplicate email.
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>
                    alert('Email is already taken.');
                    window.history.back();
                  </script>";
        } else {
            // Insert new user with role.
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registration successful!');
                        window.location.href = '../pages/landing-pages/login-page.php';
                      </script>";
            } else {
                echo "<script>
                        alert('Error: Unable to register. Please try again later.');
                        window.history.back();
                      </script>";
            }
        }
    }

    $stmt->close();
}
$conn->close();
?>