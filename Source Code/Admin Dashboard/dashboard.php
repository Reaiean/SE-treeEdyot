<?php
$adminName = "Administrator";

$stats = [
    "totalUsers"   => 4,
    "totalReports" => 3,
    "activeReports"=> 1,
    "completed"    => 1
];

$users = [
    [
        "name" => "Maria Santos",
        "email" => "maria@example.com",
        "role" => "Resident",
        "roleClass" => "badge-resident",
        "contact" => "+63 912 345 6789",
        "registered" => "1/15/2025"
    ],
    [
        "name" => "Juan Dela Cruz",
        "email" => "juan@barangay.gov",
        "role" => "Barangay Official",
        "roleClass" => "badge-barangay",
        "contact" => "+63 923 456 7890",
        "registered" => "1/10/2025"
    ],
    [
        "name" => "Carlos Reyes",
        "email" => "carlos@cityeng.gov",
        "role" => "City Engineer",
        "roleClass" => "badge-engineer",
        "contact" => "+63 934 567 8901",
        "registered" => "1/5/2025"
    ],
    [
        "name" => "Admin User",
        "email" => "admin@system.gov",
        "role" => "Admin",
        "roleClass" => "badge-admin",
        "contact" => "+63 945 678 9012",
        "registered" => "1/1/2025"
    ]
];

$reports = [
    [
        "id" => "RPT-1001",
        "type" => "Clogged Drain",
        "location" => "Barangay Mabolo",
        "severity" => "High",
        "severityClass" => "badge-high",
        "status" => "Ongoing",
        "statusClass" => "badge-ongoing",
        "dateFiled" => "1/12/2025",
        "info" => "Heavy debris blocking main street drain."
    ],
    [
        "id" => "RPT-1002",
        "type" => "Flooding",
        "location" => "Colon Street",
        "severity" => "Medium",
        "severityClass" => "badge-medium",
        "status" => "Completed",
        "statusClass" => "badge-completed",
        "dateFiled" => "1/8/2025",
        "info" => "Minor flooding after heavy rain, cleared by team."
    ],
    [
        "id" => "RPT-1003",
        "type" => "Illegal Dumping",
        "location" => "Lahug Creek",
        "severity" => "Low",
        "severityClass" => "badge-low",
        "status" => "Pending",
        "statusClass" => "badge-pending",
        "dateFiled" => "1/5/2025",
        "info" => "Garbage piles near creek entry point."
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eco-Drainage Monitoring System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header class="top-bar">
        <div class="top-bar-left">
            <span class="system-title">Eco-Drainage Monitoring System</span>
        </div>
        <div class="top-bar-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($adminName); ?>!</span>
            <button class="logout-btn">Logout</button>
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
                <p class="stat-value"><?php echo $stats["totalUsers"]; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Total Reports</p>
                <p class="stat-value"><?php echo $stats["totalReports"]; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Active Reports</p>
                <p class="stat-value"><?php echo $stats["activeReports"]; ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Completed</p>
                <p class="stat-value"><?php echo $stats["completed"]; ?></p>
            </div>
        </section>

        <section class="content-row">
            <div class="map-panel">
                <div class="map-placeholder">
                    Reports Map - Cebu City
                </div>
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
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                        <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                        <td><span class="badge <?php echo $user["roleClass"]; ?>">
                                            <?php echo htmlspecialchars($user["role"]); ?>
                                        </span></td>
                                        <td><?php echo htmlspecialchars($user["contact"]); ?></td>
                                        <td><?php echo htmlspecialchars($user["registered"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

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
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report["id"]); ?></td>
                                        <td><?php echo htmlspecialchars($report["type"]); ?></td>
                                        <td><?php echo htmlspecialchars($report["location"]); ?></td>
                                        <td>
                                            <span class="badge <?php echo $report["severityClass"]; ?>">
                                                <?php echo htmlspecialchars($report["severity"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $report["statusClass"]; ?>">
                                                <?php echo htmlspecialchars($report["status"]); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report["dateFiled"]); ?></td>
                                        <td><?php echo htmlspecialchars($report["info"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="activity-logs" class="logs-table">
                        
                    </div>
                </div>
            </div>
        </section>

    </main>

    <script>
        const tabs = document.querySelectorAll('.logs-tab');
        const tables = document.querySelectorAll('.logs-table');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active-tab'));
                tab.classList.add('active-tab');

                const target = tab.getAttribute('data-target');
                tables.forEach(table => {
                    if (table.id === target) {
                        table.classList.add('active-table');
                    } else {
                        table.classList.remove('active-table');
                    }
                });
            });
        });
    </script>
</body>
</html>
