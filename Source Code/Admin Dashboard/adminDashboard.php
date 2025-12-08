<?php
session_start();
require_once "db_config.php";

// Only allow Admin (roleID = 1)
if (!isset($_SESSION['roleID']) || $_SESSION['roleID'] != 1) {
    header("Location: login.html");
    exit();
}

// ---------- DASHBOARD STATS ----------
$totalUsers = 0;
$totalReports = 0;
$activeReports = 0;
$completedReports = 0;

// Total users
$res = $conn->query("SELECT COUNT(*) AS totalUsers FROM users");
if ($res && $row = $res->fetch_assoc()) {
    $totalUsers = (int)$row['totalUsers'];
}

// Total reports
$res = $conn->query("SELECT COUNT(*) AS totalReports FROM REPORTS");
if ($res && $row = $res->fetch_assoc()) {
    $totalReports = (int)$row['totalReports'];
}

// Active reports (anything not Completed)
$res = $conn->query("SELECT COUNT(*) AS activeReports FROM REPORTS WHERE status <> 'Completed'");
if ($res && $row = $res->fetch_assoc()) {
    $activeReports = (int)$row['activeReports'];
}

// Completed reports
$res = $conn->query("SELECT COUNT(*) AS completedReports FROM REPORTS WHERE status = 'Completed'");
if ($res && $row = $res->fetch_assoc()) {
    $completedReports = (int)$row['completedReports'];
}

// ---------- USER MANAGEMENT DATA ----------
$userRows = [];
$sqlUsers = "
    SELECT 
        u.userID,
        u.firstName,
        u.lastName,
        u.email,
        u.contactNumber,
        u.dateRegistered,
        u.roleID,
        r.roleName
    FROM users u
    INNER JOIN roles r ON u.roleID = r.roleID
    ORDER BY u.dateRegistered DESC, u.userID DESC
";

if ($resUsers = $conn->query($sqlUsers)) {
    while ($row = $resUsers->fetch_assoc()) {
        $userRows[] = $row;
    }
}

// ---------- REPORT LOGS DATA ----------
$reportRows = [];
$sqlReports = "
    SELECT 
        id,
        reportType,
        location,
        severity,
        status,
        description,
        dateFiled
    FROM REPORTS
    ORDER BY dateFiled DESC, id DESC
";

if ($resReports = $conn->query($sqlReports)) {
    while ($row = $resReports->fetch_assoc()) {
        $reportRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eco-Drainage Monitoring System - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS (OpenStreetMap) -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    >

    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="adminDashboard.css">
</head>
<body>
    <header class="top-bar">
        <div class="top-bar-left">
            <span class="system-title">Eco-Drainage Monitoring System</span>
        </div>
        <div class="top-bar-right">
            <span class="welcome-text">Welcome, Administrator!</span>
            <form action="logout.php" method="post" style="display:inline;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
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
                <p class="stat-value"><?php echo $totalUsers; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Total Reports</p>
                <p class="stat-value"><?php echo $totalReports; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Active Reports</p>
                <p class="stat-value"><?php echo $activeReports; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Completed</p>
                <p class="stat-value"><?php echo $completedReports; ?></p>
            </div>
        </section>

        <section class="content-row">
            <div class="map-panel">
                <div id="map"></div>
                <div id="street-view"></div>
            </div>

            <aside class="side-panel">
                <button class="file-report-btn">VIEW REPORT</button>
            </aside>
        </section>

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
                            <?php if (!empty($userRows)): ?>
                                <?php foreach ($userRows as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['firstName'] . ' ' . $u['lastName']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <?php
                                                $roleName = $u['roleName'];
                                                $badgeClass = 'badge-resident';
                                                if ($roleName === 'Admin') {
                                                    $badgeClass = 'badge-admin';
                                                } elseif ($roleName === 'Maintenance Staff') {
                                                    $badgeClass = 'badge-engineer';
                                                } elseif ($roleName === 'Resident') {
                                                    $badgeClass = 'badge-resident';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($roleName); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['contactNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($u['dateRegistered']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No users found.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- REPORT LOGS TABLE -->
                    <div id="report-logs" class="logs-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Date Filed</th>
                                    <th>Report Info</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($reportRows)): ?>
                                <?php foreach ($reportRows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['id']); ?></td>
                                        <td><?php echo htmlspecialchars($r['reportType']); ?></td>
                                        <td><?php echo htmlspecialchars($r['location']); ?></td>
                                        <td>
                                            <?php
                                                $sev = $r['severity'];
                                                $sevClass = 'badge-low';
                                                if (strcasecmp($sev, 'High') === 0) {
                                                    $sevClass = 'badge-high';
                                                } elseif (strcasecmp($sev, 'Medium') === 0) {
                                                    $sevClass = 'badge-medium';
                                                } elseif (strcasecmp($sev, 'Low') === 0) {
                                                    $sevClass = 'badge-low';
                                                }
                                            ?>
                                            <span class="badge <?php echo $sevClass; ?>">
                                                <?php echo htmlspecialchars($sev); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                                $st = $r['status'];
                                                $stClass = 'badge-pending';
                                                if (strcasecmp($st, 'Ongoing') === 0) {
                                                    $stClass = 'badge-ongoing';
                                                } elseif (strcasecmp($st, 'Completed') === 0) {
                                                    $stClass = 'badge-completed';
                                                } elseif (strcasecmp($st, 'Pending') === 0) {
                                                    $stClass = 'badge-pending';
                                                }
                                            ?>
                                            <span class="badge <?php echo $stClass; ?>">
                                                <?php echo htmlspecialchars($st); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['dateFiled']); ?></td>
                                        <td><?php echo htmlspecialchars($r['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No reports found.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ACTIVITY LOGS (EMPTY FOR NOW) -->
                    <div id="activity-logs" class="logs-table">
                        <!-- Future activity logs here -->
                    </div>

                </div>
            </div>
        </section>

    </main>

    <!-- Leaflet JS -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>

    <!-- Dashboard JS -->
    <script src="adminDashboard.js"></script>
</body>
</html>
