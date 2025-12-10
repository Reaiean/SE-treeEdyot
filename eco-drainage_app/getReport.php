<?php
session_start(); // Start the session to access user_id
header('Content-Type: application/json; charset=utf-8');
require 'db_config.php';

function json_error($msg = 'Error', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Check if user is logged in via Session
if (!isset($_SESSION['user_id'])) {
    json_error('User not logged in', 401);
}

$userId = $_SESSION['user_id'];

// Fetch user's reports
$sql = "SELECT id, reportType, description, status, severity, location, latitude, longitude, dateFiled, dateResolved, photoPath
        FROM reports
        WHERE userId = ?
        ORDER BY dateFiled DESC";

if (!($stmt = $conn->prepare($sql))) {
    json_error('Database error (prepare reports)', 500);
}

$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    json_error('Database error (execute reports)', 500);
}

$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
$reports[] = [
    'id' => isset($row['id']) ? (int)$row['id'] : null,
    'reportType' => $row['reportType'] ?? null,
    'description' => $row['description'] ?? null,
    'status' => $row['status'] ?? null,
    'severity' => $row['severity'] ?? null,
    'location' => $row['location'] ?? null,
    'latitude' => is_null($row['latitude']) ? null : floatval($row['latitude']),
    'longitude' => is_null($row['longitude']) ? null : floatval($row['longitude']),
    'dateFiled' => $row['dateFiled'] ?? null,
    'dateResolved' => $row['dateResolved'] ?? null,
    'image' => $row['photoPath'] ?? null,
];

}
$stmt->close();

// Compute stats
$stats = [
    'total' => count($reports),
    'pending' => 0,
    'ongoing' => 0,
    'completed' => 0
];

foreach ($reports as $r) {
    $st = strtolower((string)($r['status'] ?? ''));
    if ($st === 'pending') $stats['pending']++;
    else if ($st === 'ongoing' || $st === 'in progress') $stats['ongoing']++;
    else if ($st === 'completed' || $st === 'done' || $st === 'resolved') $stats['completed']++;
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'reports' => $reports
]);

$conn->close();
exit;
?>
