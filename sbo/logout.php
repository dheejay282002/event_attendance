<?php
session_start();

// Clear all SBO session variables
unset($_SESSION['sbo_id']);
unset($_SESSION['sbo_email']);
unset($_SESSION['sbo_name']);
unset($_SESSION['sbo_position']);

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
