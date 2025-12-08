document.addEventListener("DOMContentLoaded", () => {
    const DEFAULT_CENTER = [10.3157, 123.8854]; // Cebu City
    const DEFAULT_ZOOM = 12;

    let map = null;
    let markersLayer = null;

    // Initialize Map (Same style as Resident)
    function initMap() {
        map = L.map("map").setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);
    }

    // Helper for safe HTML
    function escapeHtml(str) {
        if (!str && str !== 0) return "";
        return String(str).replace(/[&<>"']/g, c => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;"
        }[c]));
    }

    // --- Create Staff Report Card (Implements Diagram Branch 3) ---
    function createStaffReportCard(report) {
        const div = document.createElement("div");
        div.className = "reportItem staffCardHeight"; // uses resident style class + staff specific override

        const idFormat = `RPT${String(report.id).padStart(3, '0')}`;
        const type = escapeHtml(report.reportType || "Drainage Issue");
        const status = escapeHtml(report.status || "Pending");
        const statusClass = `status-${status.toLowerCase()}`;
        const loc = escapeHtml(report.location || "");
        const desc = escapeHtml(report.description || "");
        
        // Image handling (using relative path logic from residentDashboard.js)
        const imgURL = report.image ? `/eco-drainage_app/${report.image}` : "images/defaultImage.jpg";

        // Current values for inputs (if they exist in DB)
        const currentRemarks = escapeHtml(report.remarks || "");

        /* HTML Structure based on Diagram:
           1. Info Details
           2. INPUTS: Status Dropdown & Remarks
           3. ACTION: Update Status Button
        */
        div.innerHTML = `
            <div class="reportImage">
                <img src="${imgURL}" alt="Report Photo" onerror="this.src='images/defaultImage.jpg'">
            </div>

            <div class="reportContent">
                <div class="reportTopRow">
                    <span class="reportID">${idFormat}</span>
                    <span class="statusBadge ${statusClass}">${status}</span>
                </div>

                <div class="reportType">${type}</div>
                <p class="reportDesc">${desc}</p>
                <div class="reportMeta">📍 ${loc}</div>

                <div class="staffActionArea">
                    <div class="inputGroup">
                        <label>Status:</label>
                        <select id="status-${report.id}" class="staffSelect">
                            <option value="Pending" ${status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Ongoing" ${status === 'Ongoing' ? 'selected' : ''}>Ongoing</option>
                            <option value="Completed" ${status === 'Completed' ? 'selected' : ''}>Completed</option>
                        </select>
                    </div>
                    <div class="inputGroup">
                        <label>Remarks:</label>
                        <input type="text" id="remarks-${report.id}" class="staffInput" 
                               placeholder="Enter remarks..." value="${currentRemarks}">
                    </div>
                </div>
            </div>

            <div class="staffBtnGroup">
                 <button class="viewBtn" onclick="window.location.href='viewReport.php?id=${report.id}'">
                    👁 Details
                </button>
                
                <button class="updateBtn" onclick="updateReport(${report.id})">
                    💾 Update Status
                </button>
            </div>
        `;

        return div;
    }

    // --- Diagram Logic: Update Status ---
    window.updateReport = async function(id) {
        const newStatus = document.getElementById(`status-${id}`).value;
        const newRemarks = document.getElementById(`remarks-${id}`).value;

        if(!confirm(`Update Report #${id} to '${newStatus}'?`)) return;

        try {
            // NOTE: You must create 'staffUpdateReport.php' to handle the SQL UPDATE
            const formData = new FormData();
            formData.append('report_id', id);
            formData.append('status', newStatus);
            formData.append('remarks', newRemarks);

            const resp = await fetch("staffUpdateReport.php", {
                method: "POST",
                body: formData
            });

            const data = await resp.json();
            if (data.success) {
                alert("Report updated successfully.");
                loadStaffReports(); // Reload to refresh list/map
            } else {
                alert("Update failed: " + (data.message || "Unknown error"));
            }
        } catch(err) {
            console.error(err);
            alert("Error connecting to server.");
        }
    };

    // Add marker (Diagram Branch 1)
    function addMarker(report) {
        const lat = parseFloat(report.latitude ?? report.lat);
        const lng = parseFloat(report.longitude ?? report.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

        const marker = L.marker([lat, lng]).addTo(markersLayer);
        
        // Popup shows basic info
        const content = `<strong>${escapeHtml(report.reportType)}</strong><br>
                         Status: ${escapeHtml(report.status)}`;
        marker.bindPopup(content);
        return marker;
    }

    // Load Data
    async function loadStaffReports() {
        const reportsList = document.getElementById("reportsList");
        
        try {
            // NOTE: You must create 'getStaffReports.php' which selects WHERE assigned_staff_id = ?
            const resp = await fetch("getStaffReports.php", { cache: "no-store" });
            
            if (!resp.ok) {
                 // Fallback for demo purposes if file doesn't exist yet
                console.warn("getStaffReports.php not found. Ensure backend is created.");
                reportsList.innerHTML = "<p>Error: Create 'getStaffReports.php' to fetch data.</p>";
                return;
            }

            const data = await resp.json();
            
            // 1. Update Stats
            if (data.stats) {
                document.getElementById("totalAssigned").textContent = data.stats.total || 0;
                document.getElementById("pendingReports").textContent = data.stats.pending || 0;
                document.getElementById("ongoingReports").textContent = data.stats.ongoing || 0;
                document.getElementById("completedReports").textContent = data.stats.completed || 0;
            }

            // 2. Render List
            reportsList.innerHTML = "";
            markersLayer.clearLayers();
            
            const reports = data.reports || [];
            const addedMarkers = [];

            if (reports.length === 0) {
                reportsList.innerHTML = "<p>No tasks assigned to you currently.</p>";
            } else {
                reports.forEach(r => {
                    // Create List Item
                    reportsList.appendChild(createStaffReportCard(r));
                    
                    // Create Map Pin
                    const m = addMarker(r);
                    if(m) addedMarkers.push(m);
                });
            }

            // 3. Center Map
            if (addedMarkers.length > 0) {
                const group = L.featureGroup(addedMarkers);
                map.fitBounds(group.getBounds(), { padding: [40, 40] });
            } else {
                map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
            }

        } catch (err) {
            console.error(err);
            reportsList.innerHTML = "<p>Error loading tasks.</p>";
        }
    }

    // Logout
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            fetch("logout.php", { method: "POST" })
                .finally(() => { window.location.href = "login.html"; });
        });
    }

    // Init
    initMap();
    loadStaffReports();
});