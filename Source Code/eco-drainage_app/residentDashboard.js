document.addEventListener("DOMContentLoaded", () => {
    const DEFAULT_CENTER = [10.3157, 123.8854]; // Cebu City
    const DEFAULT_ZOOM = 12;

    let map = null;
    let markersLayer = null;

    // Initialize OSM map immediately (even if no reports exist)
    function initMap() {
        map = L.map("map").setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);
    }

 // Create better report card UI
function createReportCard(report) {
    const div = document.createElement("div");
    div.className = "reportItem";

    const idFormat = report.id ? `RPT${String(report.id).padStart(3, '0')}` : "Report";
    const type = escapeHtml(report.reportType || "Drainage Issue");
    const status = escapeHtml(report.status || "Pending");
    const statusClass = `status-${status.toLowerCase()}`;
    const severity = escapeHtml(report.severity || "Minor");
    const desc = escapeHtml(report.description || "");
    const loc = escapeHtml(report.location || "");
    const filed = escapeHtml(report.dateFiled || "");

// Use server-root relative path
const imgURL = report.image ? `/eco-drainage_app/${report.image}` : "/eco-drainage_app/uploads/defaultImage.jpg";


    


    div.innerHTML = `
        <div class="reportImage">
            <img src="${imgURL}" alt="Report Photo">
        </div>

        <div class="reportContent">
            <div class="reportTopRow">
                <span class="reportID">${idFormat}</span>
                <span class="statusBadge ${statusClass}">${status}</span>
                <span class="severityBadge">${severity}</span>
            </div>

            <div class="reportType">${type}</div>

            <p class="reportDesc">${desc}</p>

            <div class="reportMeta">
                <span class="metaItem">
                    📍 ${loc}
                </span>
                <span class="metaItem">
                    📅 Filed: ${filed}
                </span>
            </div>
        </div>

        <button class="viewBtn" onclick="viewReport(${report.id})">
            👁 View Details
        </button>
    `;

    return div;
}



// Button action placeholder
function viewReport(id) {
    window.location.href = `viewReport.php?id=${id}`;
}


    function escapeHtml(str) {
        if (!str && str !== 0) return "";
        return String(str).replace(/[&<>"']/g, c => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "'": "&#39;"
        }[c]));
    }

    // Add marker to OSM map for a single report
    function addMarker(report) {
        const lat = parseFloat(report.latitude ?? report.lat);
        const lng = parseFloat(report.longitude ?? report.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

        const marker = L.marker([lat, lng]).addTo(markersLayer);
        const title = escapeHtml(report.reportType || "Report");
        const loc = escapeHtml(report.location || "");
        const content = `<strong>${title}</strong><br>${loc}`;
        marker.bindPopup(content);
        return marker;
    }

    // Load reports from backend
    async function loadReports() {
        const reportsList = document.getElementById("reportsList");
        if (!reportsList) return;

        // Ensure we have a user email to request (backend requires it)
        if (!USER_EMAIL) {
            reportsList.innerHTML = "<p>User email not available. Please login again.</p>";
            return;
        }

        try {
            const resp = await fetch("getReport.php", { cache: "no-store" });
            if (!resp.ok) throw new Error("Network response was not ok");

            const data = await resp.json();

            if (!data || data.success !== true) {
                reportsList.innerHTML = "<p>No reports found.</p>";
                // Still keep map centered on Cebu
                map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
                return;
            }

            // Update stats (expects data.stats object)
            const set = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = (value ?? 0);
            };

            set("totalReports", data.stats?.total ?? 0);
            set("pendingReports", data.stats?.pending ?? 0);
            set("ongoingReports", data.stats?.ongoing ?? 0);
            set("completedReports", data.stats?.completed ?? 0);

            // Render recent reports
            reportsList.innerHTML = "";
            const reports = Array.isArray(data.reports) ? data.reports : [];

            if (reports.length === 0) {
                reportsList.innerHTML = "<p>No reports yet.</p>";
            } else {
                reports.forEach(r => {
                    reportsList.appendChild(createReportCard(r));
                });
            }

            // Add markers
            markersLayer.clearLayers();
            const addedMarkers = [];

            reports.forEach(r => {
                const marker = addMarker(r);
                if (marker) addedMarkers.push(marker);
            });

            if (addedMarkers.length > 0) {
                const group = L.featureGroup(addedMarkers);
                map.fitBounds(group.getBounds(), { padding: [40, 40] });
            } else {
                // No markers - keep Cebu default view
                map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
            }

        } catch (err) {
            console.error("Error loading reports:", err);
            reportsList.innerHTML = "<p>Error loading reports.</p>";
            map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }
    }

    // Logout handling
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            fetch("logout.php", { method: "POST", credentials: "same-origin" })
                .finally(() => { window.location.href = "login.html"; });
        });
    }

    // Start
    initMap();
    loadReports();
});
