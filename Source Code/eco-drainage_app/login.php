<?php
session_start();
require_once "db_config.php"; // $conn = new mysqli(...)

header("Content-Type: application/json");

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required"]);
    exit;
}

// Query user + role
$sql = "
    SELECT users.userID, users.firstName, users.lastName, users.password, users.roleID, roles.roleName
    FROM users
    INNER JOIN roles ON users.roleID = roles.roleID
    WHERE users.email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No account found"]);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["success" => false, "message" => "Incorrect password"]);
    exit;
}

// SUCCESS — Store session
$_SESSION['user_id'] = $user['userID'];
$_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
$_SESSION['role_id'] = $user['roleID'];
$_SESSION['role_name'] = $user['roleName'];
$_SESSION['user_email'] = $email;

echo json_encode([
    "success" => true,
    "role_id" => (int)$user['roleID'],   // <-- important for redirect
    "roleName" => $user['roleName']
]);
exit;

?>
