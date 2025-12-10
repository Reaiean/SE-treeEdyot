<?php
session_start();

require_once "db_config.php"; // uses your existing DB connector

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.html");
    exit();
}

$totalUsers = 0;
$totalReports = 0;
$activeReports = 0;
$completedReports = 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM users");
$totalUsers = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS");
$totalReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS WHERE status='Ongoing'");
$activeReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM REPORTS WHERE status='Completed'");
$completedReports = ($res) ? (int)$res->fetch_assoc()['c'] : 0;

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

$activityRows = [];
$activityQuery = "
    SELECT al.*, 
           u.firstName, u.lastName,
           r.reportType
    FROM activity_logs al
    JOIN users u ON al.userID = u.userID
    JOIN reports r ON al.reportID = r.id
    ORDER BY al.timestamp DESC
";

if ($result = $conn->query($activityQuery)) {
    while ($row = $result->fetch_assoc()) {
        $activityRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Eco Drainage</title>

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

    <section class="content-row">
        <div class="map-panel">
            <div id="map"></div>
            <div id="street-view"></div>
        </div>

    </section>

    <section class="logs-section">
        <div class="logs-card">

            <div class="logs-tabs">
                <button class="logs-tab active-tab" data-target="user-management">User Management</button>
                <button class="logs-tab" data-target="report-logs">Report Logs</button>
                <button class="logs-tab" data-target="activity-logs">Activity Logs</button>
            </div>

            <div class="logs-content">

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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $r): ?>
                                <tr>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['reportType']) ?></td>
                                    <td><?= htmlspecialchars($r['location']) ?></td>
                                    <td>
                                        <span class="badge 
                                          <?php
                                         $sev = strtolower($r['severity']);
                                            if ($sev === 'severe') echo 'badge-severe';
                                         elseif ($sev === 'moderate') echo 'badge-moderate';
                                         elseif ($sev === 'minor') echo 'badge-minor';
                                         else echo 'badge-minor'; // default
                                            ?>">
                                        <?= htmlspecialchars($r['severity']) ?>
                                        </span>
                                    </td>

                                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                                    <td><?= htmlspecialchars($r['dateFiled']) ?></td>
                                    <td>
                                    <button class="action-view-btn" onclick="window.location.href='viewReport.php?id=<?= $r['id'] ?>'">
                                    👁
                                    </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
<div id="activity-logs" class="logs-table">
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Report</th>
                <th>Action</th>
                <th>Description</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($activityRows as $a): ?>
                <tr>
                    <td><?= date("F d, Y h:i A", strtotime($a['timestamp'])) ?></td>
                    <td><?= htmlspecialchars($a['firstName'] . " " . $a['lastName']) ?></td>
                    <td>RPT<?= str_pad($a['reportID'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($a['actionType']) ?></td>
                    <td><?= htmlspecialchars($a['actionDescription']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


            </div>
        </div>
    </section>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="">
</script>

<script src="adminDashboard.js"></script>

</body>
</html>
