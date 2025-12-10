<?php
session_start();
require_once "db_config.php";

// Must be logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit;
}

$isAdmin   = (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1);
$userEmail = $_SESSION['user_email'] ?? '';

// Validate report ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid report ID");
}
$reportID = (int)$_GET['id'];

// If ADMIN submits "Assign To" form
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff_id'])) {

    $staffID = (int)$_POST['assign_staff_id'];

    if ($staffID > 0) {

        // Get staff name for activity logs
        $staffSql = "SELECT firstName, lastName FROM users WHERE userID = ?";
        $stmtStaff = $conn->prepare($staffSql);
        $stmtStaff->bind_param("i", $staffID);
        $stmtStaff->execute();
        $staffResult = $stmtStaff->get_result();
        $staffName = "Unknown Staff";

        if ($staffResult->num_rows > 0) {
            $staffRow = $staffResult->fetch_assoc();
            $staffName = $staffRow['firstName'] . " " . $staffRow['lastName'];
        }
        $stmtStaff->close();

        // Update assignment
        $updateSql = "UPDATE REPORTS SET assignedTo = ? WHERE id = ?";
        $stmtUp = $conn->prepare($updateSql);

        if ($stmtUp) {
            $stmtUp->bind_param("ii", $staffID, $reportID);
            $stmtUp->execute();
            $stmtUp->close();
        }

        // 🔥 Log the action
        logActivity(
            $conn,
            $_SESSION['user_id'],   // admin who assigned
            $reportID,              // which report
            "Assignment Updated",   // action title
            "Assigned to: " . $staffName // details
        );
    }

    // avoid form resubmission
    header("Location: viewReport.php?id=" . $reportID);
    exit;
}


/* ------------------  FETCH REPORT + REPORTER + ASSIGNED STAFF  ------------------ */

$sql = "
SELECT 
    r.*,
    ru.firstName  AS reporterFirstName,
    ru.lastName   AS reporterLastName,
    ru.email      AS reporterEmail,
    ru.contactNumber AS reporterContact,
    ru.address    AS reporterAddress,
    su.userID     AS staffUserID,
    su.firstName  AS staffFirstName,
    su.lastName   AS staffLastName,
    su.email      AS staffEmail
FROM REPORTS r
INNER JOIN users ru ON r.userID = ru.userID
LEFT JOIN users su  ON r.assignedTo = su.userID
WHERE r.id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reportID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Report not found.");
}

$report = $result->fetch_assoc();
$stmt->close();

// Reporter / staff data
$reporterName   = $report['reporterFirstName'] . " " . $report['reporterLastName'];
$reporterEmail  = $report['reporterEmail'];
$reporterContact= $report['reporterContact'];
$reporterAddress= $report['reporterAddress'];

$assignedStaffName  = (!empty($report['staffFirstName']))
    ? $report['staffFirstName'] . " " . $report['staffLastName']
    : null;
$assignedStaffEmail = $report['staffEmail'] ?? null;

// Image path
$imageRel = !empty($report['photoPath'])
    ? $report['photoPath']
    : "uploads/defaultImage.jpg";
$imagePath = "/eco-drainage_app/" . $imageRel;

// Coordinates
$lat = $report['latitude'] ?? $report['lat'] ?? null;
$lng = $report['longitude'] ?? $report['lng'] ?? null;

// Severity + status
$severity = !empty($report['severity']) ? $report['severity'] : "N/A";
$status   = !empty($report['status'])   ? $report['status']   : "Pending";

// If admin, we need the list of staff (roleID = 3)
$staffOptions = [];
if ($isAdmin) {
    $staffSql = "SELECT userID, firstName, lastName, email 
                 FROM users 
                 WHERE roleID = 3
                 ORDER BY firstName, lastName";
    if ($resStaff = $conn->query($staffSql)) {
        while ($row = $resStaff->fetch_assoc()) {
            $staffOptions[] = $row;
        }
        $resStaff->free();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Details</title>

    <!-- Leaflet (OpenStreetMap) CSS -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f4f4;
        }

        .container {
            width: 85%;
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 18px 25px 28px;
            border-radius: 10px;
        }

        .backBtn {
            background: none;
            border: none;
            color: #007bff;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .report-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .report-header-top {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .report-sub {
            margin-top: 4px;
            font-size: 14px;
            color: #555;
        }

        .statusBadge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 14px;
            color: #fff;
        }
        .status-pending   { background: #f39c12; }
        .status-ongoing   { background: #007bff; }
        .status-completed { background: #2ecc71; }

        .severityBadge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        /* severity colors */
        .severity-severe {
            background: #ff4b4b;
            color: #fff;
        }
        .severity-moderate {
            background: #ffb547;
            color: #4a3200;
        }
        .severity-minor {
            background: #4caf50;
            color: #fff;
        }

        .report-image img {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            border-radius: 8px;
            margin: 10px 0;
        }

        .section-title {
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 6px;
        }

        #map {
            height: 260px;
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
        }

        hr {
            margin-top: 15px;
        }

        /* Reporter + Assigned cards */
        .info-card {
            background: #f7fafc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 8px;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 18px;
            font-size: 14px;
        }

        .info-col {
            min-width: 180px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .assigned-card {
            background: #f1f6ff;
            border-radius: 8px;
            padding: 10px 16px;
            margin-top: 8px;
        }

        .assigned-badge {
            display: inline-block;
            margin-left: 8px;
            background: #111827;
            color: #fff;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
        }

        .assign-form {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .assign-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            min-width: 220px;
        }

        .assign-btn {
            padding: 6px 14px;
            border-radius: 6px;
            border: none;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
        }

        .assign-btn:hover {
            background: #1d4ed8;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Use history.back() so it works for both admin and resident -->
    <button class="backBtn" onclick="history.back()">
        ← Back
    </button>

    <!-- HEADER -->
    <div class="report-header">
        <div class="report-header-top">
            RPT<?= str_pad($report['id'], 3, '0', STR_PAD_LEFT); ?>

            <span class="statusBadge status-<?= strtolower($status); ?>">
                <?= htmlspecialchars($status); ?>
            </span>

            <?php
                $sevClass = 'severity-' . strtolower($severity);
            ?>
            <span class="severityBadge <?= $sevClass ?>">
                <?= htmlspecialchars($severity); ?>
            </span>
        </div>

        <div class="report-sub">
            Reporter: <strong><?= htmlspecialchars($reporterName); ?></strong>
        </div>
    </div>

    <!-- TYPE -->
    <h3><?= htmlspecialchars($report['reportType']); ?></h3>

    <!-- IMAGE -->
    <div class="report-image">
        <img src="<?= htmlspecialchars($imagePath); ?>" alt="Report Image">
    </div>

    <!-- DESCRIPTION -->
    <p><strong>Description:</strong><br>
        <?= nl2br(htmlspecialchars($report['description'])); ?>
    </p>

    <hr>

    <!-- LOCATION -->
    <p><strong>Location:</strong><br>
        <?= htmlspecialchars($report['location']); ?>
    </p>
    <p><strong>Coordinates: </strong><?= htmlspecialchars($lat . ", " . $lng); ?></p>

    <div id="map"></div>

    <hr>

    <!-- TIMELINE -->
    <p><strong>Timeline:</strong><br>
        Filed: <?= date("F d, Y h:i A", strtotime($report['dateFiled'])); ?>
    </p>

    <!-- REPORTER INFORMATION -->
    <div class="section-title">Reporter Information</div>
    <div class="info-card">
        <div class="info-row">
            <div class="info-col">
                <div class="info-label">Name:</div>
                <div><?= htmlspecialchars($reporterName); ?></div>
            </div>
            <div class="info-col">
                <div class="info-label">Contact:</div>
                <div><?= htmlspecialchars($reporterContact); ?></div>
            </div>
        </div>
        <div class="info-row" style="margin-top:8px;">
            <div class="info-col">
                <div class="info-label">Email:</div>
                <div><?= htmlspecialchars($reporterEmail); ?></div>
            </div>
            <div class="info-col">
                <div class="info-label">Address:</div>
                <div><?= htmlspecialchars($reporterAddress); ?></div>
            </div>
        </div>
    </div>

    <!-- ASSIGNED TO SECTION -->
    <div class="section-title" style="margin-top:18px;">Assigned To</div>

    <?php if ($assignedStaffName): ?>
        <!-- Already assigned: show name to BOTH admin & resident -->
        <div class="assigned-card">
            <div><strong><?= htmlspecialchars($assignedStaffName); ?></strong>
                <span class="assigned-badge">Maintenance Staff</span>
            </div>
            <?php if ($assignedStaffEmail): ?>
                <div class="muted"><?= htmlspecialchars($assignedStaffEmail); ?></div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- NOT assigned yet -->
        <?php if ($isAdmin): ?>
            <!-- Admin sees the dropdown + button -->
            <div class="assigned-card">
                <div class="muted">This report has not yet been assigned to a staff member.</div>

                <?php if (count($staffOptions) > 0): ?>
                    <form method="post" class="assign-form">
                        <label for="assign_staff_id"><strong>Assign to:</strong></label>
                        <select name="assign_staff_id" id="assign_staff_id" class="assign-select" required>
                            <option value="">Select staff…</option>
                            <?php foreach ($staffOptions as $st): ?>
                                <option value="<?= $st['userID']; ?>">
                                    <?= htmlspecialchars($st['firstName'] . " " . $st['lastName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="assign-btn">Assign</button>
                    </form>
                <?php else: ?>
                    <div class="muted" style="margin-top:6px;">
                        No staff with role "Maintenance Staff" found.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Resident / staff see read-only text -->
            <div class="assigned-card">
                <div class="muted">Not yet assigned</div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const lat = <?= json_encode($lat); ?>;
    const lng = <?= json_encode($lng); ?>;

    if (lat && lng) {
        const map = L.map('map').setView([lat, lng], 17);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng])
            .addTo(map)
            .bindPopup("Report Location")
            .openPopup();
    } else {
        document.getElementById("map").innerHTML =
            "<p style='padding:10px'>📍 Location unavailable</p>";
    }
</script>

</body>
</html>
