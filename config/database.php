<?php
// Database Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'plant_nursery';

// Simple connection
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
