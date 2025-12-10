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

function logActivity($conn, $userID, $reportID, $type, $desc = null) {
    $sql = "INSERT INTO activity_logs (userID, reportID, actionType, actionDescription)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $userID, $reportID, $type, $desc);
    $stmt->execute();
}

?>

