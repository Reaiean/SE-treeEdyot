<?php
session_start();

require_once "db_config.php"; // uses your existing DB connector

// ----------- ADMIN ACCESS CHECK -----------
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.html");
    exit();
}


// ----------- DASHBOARD STATS -----------
$totalUsers = 0;
$totalReports = 0;
$activeReports = 0;
$completedReports = 0;

// Total users
$res = $conn->query("SELECT COUNT(*) AS c FROM users");
$totalUsers = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

// Total reports
$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS");
$totalReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

// Active reports (Ongoing)
$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS WHERE status='Ongoing'");
$activeReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

// Completed reports
$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS WHERE status='Completed'");
$completedReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;


// ----------- USER MANAGEMENT DATA -----------
$userRows = [];
$userQuery = "
    SELECT 
        u.firstName,
        u.lastName,
        u.email,
        u.contactNumber,
        u.dateRegistered,
        r.roleName
    FROM users u
    JOIN roles r ON u.roleID = r.roleID
    ORDER BY u.dateRegistered DESC
";

if ($result = $conn->query($userQuery)) {
    while ($row = $result->fetch_assoc()) {
        $userRows[] = $row;
    }
}


// ----------- REPORT LOGS DATA -----------
$reportRows = [];
$reportQuery = "
    SELECT id, reportType, location, severity, status, dateFiled, description, latitude, longitude
    FROM REPORTS
    ORDER BY dateFiled DESC, id DESC
";
if ($result = $conn->query($reportQuery)) {
    while ($row = $result->fetch_assoc()) {
        $reportRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Eco Drainage</title>

    <!-- Leaflet OpenStreetMap CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <link rel="stylesheet" href="adminDashboard.css">
</head>
<body>

<header class="top-bar">
    <div class="top-bar-left">
        <span class="system-title">Eco-Drainage Monitoring System</span>
    </div>
    <div class="top-bar-right">
        <span class="welcome-text">
          Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?>!
        </span>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>
</header>

<main class="dashboard-wrapper">

    <section class="admin-header">
        <h1>ADMIN DASHBOARD</h1>
        <p>System Overview and Management</p>
    </section>

    <!-- ----------- STATS CARDS ----------- -->
    <section class="stats-row">
        <div class="stat-card">
            <p class="stat-label">Total Users</p>
            <p class="stat-value"><?= $totalUsers ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Total Reports</p>
            <p class="stat-value"><?= $totalReports ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active Reports</p>
            <p class="stat-value"><?= $activeReports ?></p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Completed Reports</p>
            <p class="stat-value"><?= $completedReports ?></p>
        </div>
    </section>

    <!-- ----------- MAP PANEL ----------- -->
    <section class="content-row">
        <div class="map-panel">
            <div id="map"></div>
            <div id="street-view"></div>
        </div>

        <aside class="side-panel">
            <button class="file-report-btn">VIEW REPORT</button>
        </aside>
    </section>

    <!-- ----------- LOGS SECTION ----------- -->
    <section class="logs-section">
        <div class="logs-card">

            <div class="logs-tabs">
                <button class="logs-tab active-tab" data-target="user-management">User Management</button>
                <button class="logs-tab" data-target="report-logs">Report Logs</button>
                <button class="logs-tab" data-target="activity-logs">Activity Logs</button>
            </div>

            <div class="logs-content">

                <!-- USER MANAGEMENT TABLE -->
                <div id="user-management" class="logs-table active-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userRows as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['firstName'] . " " . $u['lastName']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                                if ($u['roleName'] === 'Admin') echo 'badge-admin';
                                                elseif ($u['roleName'] === 'Resident') echo 'badge-resident';
                                                elseif ($u['roleName'] === 'Maintenance Staff') echo 'badge-engineer';
                                            ?>">
                                            <?= htmlspecialchars($u['roleName']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($u['contactNumber']) ?></td>
                                    <td><?= htmlspecialchars($u['dateRegistered']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- REPORT LOGS TABLE -->
                <div id="report-logs" class="logs-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $r): ?>
                                <tr>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['reportType']) ?></td>
                                    <td><?= htmlspecialchars($r['location']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($r['severity']) ?>"><?= $r['severity'] ?></span></td>
                                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                                    <td><?= htmlspecialchars($r['dateFiled']) ?></td>
                                    <td><?= htmlspecialchars($r['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY ACTIVITY LOG -->
                <div id="activity-logs" class="logs-table"></div>

            </div>
        </div>
    </section>

</main>

<!-- Leaflet JS (OpenStreetMap) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="">
</script>

<!-- Your Dashboard JS (tabs + OpenStreetMap logic) -->
<script src="adminDashboard.js"></script>

</body>
</html>
