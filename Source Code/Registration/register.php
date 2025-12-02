<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    sendResponse(false, 'Invalid JSON received.');
}

$roleID = isset($data['roleID']) ? intval($data['roleID']) : 0;
$firstName = isset($data['firstName']) ? trim($data['firstName']) : '';
$lastName = isset($data['lastName']) ? trim($data['lastName']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$contactNumber = isset($data['contactNumber']) ? trim($data['contactNumber']) : '';
$address = isset($data['address']) ? trim($data['address']) : '';

if (!$roleID || !$firstName || !$lastName || !$email || !$password || !$contactNumber || !$address) {
    sendResponse(false, 'Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Invalid email format.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$servername = "localhost";
$username = "root";
$dbpassword = "root";
$dbname = "drainage_system";

$conn = new mysqli($servername, $username, $dbpassword, $dbname);
if ($conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . $conn->connect_error);
}

$roleCheck = $conn->prepare("SELECT roleID FROM roles WHERE roleID = ?");
$roleCheck->bind_param("i", $roleID);
$roleCheck->execute();
$roleCheck->store_result();
if ($roleCheck->num_rows === 0) {
    $roleCheck->close();
    $conn->close();
    sendResponse(false, 'Selected role does not exist.');
}
$roleCheck->close();

$emailCheck = $conn->prepare("SELECT userID FROM users WHERE email = ?");
$emailCheck->bind_param("s", $email);
$emailCheck->execute();
$emailCheck->store_result();
if ($emailCheck->num_rows > 0) {
    $emailCheck->close();
    $conn->close();
    sendResponse(false, 'Email already registered.');
}
$emailCheck->close();

$dateRegistered = date('Y-m-d');
$stmt = $conn->prepare("INSERT INTO users (roleID, firstName, lastName, email, password, contactNumber, address, dateRegistered) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssss", $roleID, $firstName, $lastName, $email, $hashedPassword, $contactNumber, $address, $dateRegistered);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, 'Registration successful.');
} else {
    $stmt->close();
    $conn->close();
    sendResponse(false, 'Registration failed: ' . $stmt->error);
}
