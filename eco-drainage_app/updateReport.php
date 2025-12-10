<?php
session_start();
require_once "db_config.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) != 3) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$reportId = (int)($input['reportId'] ?? 0);
$status = trim($input['status'] ?? '');
$remarks = trim($input['remarks'] ?? '');

if (!$reportId || !$status || !$remarks) {
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// Ensure the report belongs to this staff
$sqlCheck = "SELECT id FROM REPORTS WHERE id=? AND assignedTo=?";
$stmt = $conn->prepare($sqlCheck);
$stmt->bind_param("ii", $reportId, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["error" => "Report not found or not assigned to you"]);
    exit;
}

// Update report
$sql = "UPDATE REPORTS SET status=?, remarks=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $status, $remarks, $reportId);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => true]);
