<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$userName  = $_SESSION['user_name']  ?? 'Resident';
$userRole  = $_SESSION['role_id']    ?? 2;       // 2 = Resident
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Eco-Drainage Monitoring System</title>

    <link rel="stylesheet" href="residentDashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<header class="topbar">
    <h1 class="appTitle">Eco-Drainage Monitoring System</h1>

    <div class="userMenu">
        <!-- File report -->
        <button class="newReportBtn" onclick="window.location.href='createReport.php'">
            File New Report
        </button>

        <!-- Notification Icon -->
        <button class="notifBtn" title="Notifications" onclick="window.location.href='checkNotification.php'">
            🔔
        </button>

        <!-- User text block -->
        <div class="userInfo">
            <span class="welcomeText">Welcome, <?php echo htmlspecialchars($userName); ?>!</span>
            <p class="roleText">Resident</p>
        </div>

        <!-- Logout -->
        <button id="logoutBtn" class="logoutBtn">Logout</button>
    </div>
</header>

<main class="dashboard">

    <div class="reportsHeader">
        <div class="reportsHeader-left">
            <h2>My Reports</h2>
            <p class="subtitle">Track and manage your drainage reports</p>
        </div>

        <div class="reportsHeader-right">
            <button class="dbNewReportBtn" onclick="window.location.href='createReport.php'">
                + File New Report
            </button>
        </div>
    </div>

    <!-- Stats row -->
    <section class="stats">
        <div class="card">
            <h3>Total Reports</h3>
            <p id="totalReports">0</p>
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

    <!-- MAIN TWO-COLUMN LAYOUT -->
    <section class="mainLayout">

        <!-- LEFT: MAP -->
        <div class="mapColumn card">
            <h2 class="sectionTitle">Reports Map - Cebu City</h2>
            <div id="map"></div>
        </div>

        <!-- RIGHT: RECENT REPORTS -->
        <aside class="reportsColumn card">
            <div class="reportsColumnHeader">
                <h2>Recent Reports</h2>
            </div>
            <div id="reportsList" class="reportsList">
                <p>Loading your reports...</p>
            </div>
        </aside>
    </section>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const USER_EMAIL = <?php echo json_encode($userEmail); ?>;
</script>
<script src="residentDashboard.js"></script>
</body>
</html>
