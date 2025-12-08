<?php
session_start();
require_once "db_config.php"; // must return $conn = new mysqli(...)

header("Content-Type: application/json");

// Read JSON body
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required"]);
    exit;
}

// Query user + role
$sql = "
    SELECT users.userID, users.password, users.roleID, roles.roleName
    FROM users
    INNER JOIN roles ON users.roleID = roles.roleID
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
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
$_SESSION['userID'] = $user['userID'];
$_SESSION['roleID'] = $user['roleID'];
$_SESSION['roleName'] = $user['roleName'];

echo json_encode([
    "success" => true,
    "roleID" => (int)$user['roleID'],  // VERY IMPORTANT
    "roleName" => $user['roleName']
]);
exit;

?>
