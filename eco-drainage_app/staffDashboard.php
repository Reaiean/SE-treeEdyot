<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? null) != 3) {
    header("Location: login.html");
    exit();
}
$userName = $_SESSION['user_name'] ?? 'Staff Member';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Staff Dashboard - Eco-Drainage</title>
    <link rel="stylesheet" href="residentDashboard.css">
    <link rel="stylesheet" href="staffDashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
      /* small modal styles (kept here for simplicity) */
      .modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.4);
        z-index: 9999;
      }
      .modal.open { display:flex; }
      .modal-card {
        background: white;
        border-radius: 8px;
        width: 92%;
        max-width: 720px;
        padding: 16px;
      }
      .modal-row { display:flex; gap:10px; margin-bottom:10px; align-items:center; }
      .modal-row label { min-width: 100px; font-weight:600; font-size:0.9rem; }
      .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:12px; }
    </style>
</head>
<body>

<header class="topbar">
    <h1 class="appTitle">Eco-Drainage (Staff Portal)</h1>
    <div class="userMenu">
        <button class="notifBtn" title="Notifications">🔔</button>
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
        <div class="card"><h3>Assigned to Me</h3><p id="totalAssigned">0</p></div>
        <div class="card"><h3>Pending</h3><p id="pendingReports">0</p></div>
        <div class="card"><h3>Ongoing</h3><p id="ongoingReports">0</p></div>
        <div class="card"><h3>Completed</h3><p id="completedReports">0</p></div>
    </section>

    <section class="mainLayout">
        <div class="mapColumn card">
            <h2 class="sectionTitle">Assigned Area Map</h2>
            <div id="map"></div>
        </div>

        <aside class="reportsColumn card">
            <div class="reportsColumnHeader">
                <h2>My Assigned Reports</h2>
            </div>
            <div id="reportsList" class="reportsList">
                <p>Loading assigned tasks...</p>
            </div>
        </aside>
    </section>
</main>

<!-- Update modal -->
<div id="updateModal" class="modal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true">
    <h3 id="modalTitle">Update Report</h3>
    <div id="modalContent">
      <div class="modal-row"><label>Report</label><div id="modalReportId"></div></div>
      <div class="modal-row"><label>Current Status</label><div id="modalCurrentStatus"></div></div>
      <div class="modal-row"><label>Action</label>
        <select id="modalNewStatus"><option value="">Select</option></select>
      </div>
      <div class="modal-row"><label>Remarks</label>
        <textarea id="modalRemarks" rows="3" style="flex:1"></textarea>
      </div>
      <div class="modal-row"><label>Proof (image)</label>
        <input type="file" id="modalProof" accept="image/*">
      </div>
    </div>
    <div class="modal-actions">
      <button id="modalCancel" class="viewBtn">Cancel</button>
      <button id="modalSubmit" class="updateBtn">Update</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const STAFF_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
<script src="staffDashboard.js"></script>
</body>
</html>
