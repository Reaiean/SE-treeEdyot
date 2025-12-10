<?php
session_start();
require_once "db_config.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) != 3) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$staffID = $_SESSION['user_id'];

// Fetch assigned reports
$sql = "
SELECT id, reportType, location, severity, status, dateFiled, description, latitude, longitude, photoPath AS image
FROM REPORTS
WHERE assignedTo = ?
ORDER BY dateFiled DESC, id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staffID);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
$stats = [
    "total" => 0,
    "pending" => 0,
    "ongoing" => 0,
    "completed" => 0
];

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;

    $stats['total']++;
    $statusLower = strtolower($row['status']);
    if ($statusLower === "pending") $stats['pending']++;
    elseif ($statusLower === "ongoing") $stats['ongoing']++;
    elseif ($statusLower === "completed") $stats['completed']++;
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    "reports" => $reports,
    "stats" => $stats
]);
