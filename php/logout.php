<?php
session_start();
session_destroy(); // Destroy the session
header("Location: ../pages/landing-pages/login-page.php"); // Redirect to the login page
exit();
?>