<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.html");
    exit();
}

$userName = $_SESSION['user_name'] ?? 'Staff Member';
$userEmail = $_SESSION['user_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Eco-Drainage System</title>

    <link rel="stylesheet" href="residentDashboard.css">
    <link rel="stylesheet" href="staffDashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<header class="topbar">
    <h1 class="appTitle">Eco-Drainage (Staff Portal)</h1>

    <div class="userMenu">
        <button class="notifBtn" title="Notifications">
            🔔
        </button>

        <div class="userInfo">
            <span class="welcomeText">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
            <p class="roleText">Maintenance Staff</p>
        </div>

        <button id="logoutBtn" class="logoutBtn">Logout</button>
    </div>
</header>

<main class="dashboard">

    <div class="reportsHeader">
        <div class="reportsHeader-left">
            <h2>Assigned Work Orders</h2>
            <p class="subtitle">Manage reports assigned to you</p>
        </div>
        </div>

    <section class="stats">
        <div class="card">
            <h3>Assigned to Me</h3>
            <p id="totalAssigned">0</p>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <p id="pendingReports">0</p>
        </div>
        <div class="card">
            <h3>Ongoing</h3>
            <p id="ongoingReports">0</p>
        </div>
        <div class="card">
            <h3>Completed</h3>
            <p id="completedReports">0</p>
        </div>
    </section>

    <h2>Assigned Area Map</h2>
    <section class="map card">
        <div id="map" style="height: 400px; width: 100%; border-radius: 12px;"></div>
    </section>

    <section class="reports">
        <h2>My Task List</h2>
        <div id="reportsList" class="reportsList">
            <p>Loading assigned tasks...</p>
        </div>
    </section>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const STAFF_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
<script src="staffDashboard.js"></script>
</body>
</html>