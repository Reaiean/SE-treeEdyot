<?php
ob_start();
session_start();
ini_set('display_errors', 0);


include 'db_config.php';

// --- Handle POST (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $reportType = $_POST['reportType'] ?? '';
    $description = $_POST['description'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $location = $_POST['location'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $photoPath = null;

    // Handle optional file upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= 5_000_000) {
            $targetDir = "uploads/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES['photo']['name']);
            $targetFilePath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                $photoPath = $targetFilePath;
            }
        }
    }

    if (empty($reportType) || empty($description) || empty($severity) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $sql = "INSERT INTO reports 
            (userID, reportType, description, severity, location, dateFiled, status, latitude, longitude, photoPath)
            VALUES (?, ?, ?, ?, ?, CURDATE(), 'Pending', ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "isssssss",
        $userId,
        $reportType,
        $description,
        $severity,
        $location,
        $latitude,
        $longitude,
        $photoPath
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

ob_end_flush();

// --- Page view ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File a New Report</title>
<link rel="stylesheet" href="createReport.css">
</head>
<body>

<div class="container">
    <a href="residentDashboard.php" class="back-link">← Back to Dashboard</a>
    <h2>File a New Report</h2>

    <form id="reportForm" enctype="multipart/form-data">
        <label>Report Type</label>
        <select name="reportType" required>
            <option disabled selected value="">Select Report Type</option>
            <option value="Drainage Issue">Drainage Issue</option>
            <option value="Garbage Blocking Drainage">Garbage Blocking Drainage</option>
            <option value="Flooding">Flooding</option>
            <option value="Others">Others</option>
        </select>

        <label>Severity</label>
        <select name="severity" required>
            <option disabled selected value="">Select Severity</option>
            <option value="Minor">Minor</option>
            <option value="Moderate">Moderate</option>
            <option value="Severe">Severe</option>
        </select>

        <label>Description</label>
        <textarea name="description" rows="4" required></textarea>

        <label>Location</label>
        <input type="text" name="location" placeholder="Ex: Cebu City - Mabolo" required>

        <label>Latitude</label>
        <input type="text" name="latitude" id="latitude" readonly>
        <label>Longitude</label>
        <input type="text" name="longitude" id="longitude" readonly>
        <button type="button" id="getLocationBtn">Get Current Location</button>

        <label>Upload Photo</label>
        <input type="file" name="photo" accept="image/*" required>

        <button type="submit" id="submitBtn">Submit Report</button>
    </form>

    <p id="message"></p>
</div>

<script src="createReport.js"></script>
</body>
</html>
