<?php
session_start();
require_once "db_config.php";

if (!isset($_SESSION['user_email'])) {
    header("Location: login.html");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid report ID");
}

$reportID = intval($_GET['id']);

$sql = "
SELECT r.*, 
       u.firstName, u.lastName
FROM reports r
INNER JOIN users u ON r.userID = u.userID
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
$reporterName = $report['firstName'] . " " . $report['lastName'];

$imageRel = !empty($report['photoPath']) 
    ? $report['photoPath'] 
    : "uploads/defaultImage.jpg";

$imagePath = "/eco-drainage_app/" . $imageRel;

$lat = $report['latitude'] ?? $report['lat'] ?? null;
$lng = $report['longitude'] ?? $report['lng'] ?? null;

$severity = !empty($report['severity']) ? $report['severity'] : "N/A";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Details</title>

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
            padding: 18px 25px;
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
            margin-left: 10px;
            font-size: 14px;
            color: #fff;
        }
        .status-pending { background: #f39c12; }
        .status-ongoing { background: #007bff; }
        .status-completed { background: #2ecc71; }

        .severityBadge {
            background: #ffb8b8;
            color: #a10000;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
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
    </style>
</head>

<body>

<div class="container">

    <button class="backBtn" onclick="window.location.href='residentDashboard.php'">
        ← Back to Dashboard
    </button>

    <div class="report-header">
        <div class="report-header-top">
            RPT<?= str_pad($report['id'], 3, '0', STR_PAD_LEFT); ?>

            <span class="statusBadge status-<?= strtolower($report['status']); ?>">
                <?= htmlspecialchars($report['status']); ?>
            </span>

            <span class="severityBadge">
                <?= htmlspecialchars($severity); ?>
            </span>
        </div>

        <div class="report-sub">
            Reporter: <strong><?= htmlspecialchars($reporterName); ?></strong>
        </div>
    </div>

    <h3><?= htmlspecialchars($report['reportType']); ?></h3>

    <div class="report-image">
        <img src="<?= $imagePath; ?>" alt="Report Image">
    </div>

    <p><strong>Description:</strong><br>
        <?= nl2br(htmlspecialchars($report['description'])); ?>
    </p>

    <hr>

    <p><strong>Location:</strong><br>
        <?= htmlspecialchars($report['location']); ?>
    </p>
    <p><strong>Coordinates: </strong><?= $lat . ", " . $lng; ?></p>

    <div id="map"></div>

    <hr>

    <p><strong>Timeline:</strong><br>
        Filed: <?= date("F d, Y h:i A", strtotime($report['dateFiled'])); ?>
    </p>

</div>

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
