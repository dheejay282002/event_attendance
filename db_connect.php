<?php
// Local XAMPP Database Configuration
$host = "localhost";
$user = "root";
$password = ""; // Default XAMPP MySQL password is empty
$dbname = "adlor_db";

// Create database if it doesn't exist
$conn_temp = mysqli_connect($host, $user, $password);
if (!$conn_temp) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// Create database
$create_db_query = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!mysqli_query($conn_temp, $create_db_query)) {
    die("❌ Failed to create database: " . mysqli_error($conn_temp));
}
mysqli_close($conn_temp);

// Connect to the specific database
$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}
