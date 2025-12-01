<?php
$host = "localhost";       // same as shown in your screenshot
$user = "root";            // default username for MySQL
$pass = "root";                // leave blank if you didn’t set a password
$dbname = "drainage_system"; // name of your database in MySQL

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
