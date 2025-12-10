document.addEventListener("DOMContentLoaded", () => {
    const DEFAULT_CENTER = [10.3157, 123.8854]; // Cebu City
    const DEFAULT_ZOOM = 12;
    let map = null;
    let markersLayer = null;

    function initMap() {
        map = L.map("map").setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors"
        }).addTo(map);
        markersLayer = L.layerGroup().addTo(map);
    }

    function createReportCard(report) {
        const div = document.createElement("div");
        div.className = "reportItem";
        div.id = `reportCard-${report.id}`;

        const idFormat = report.id ? `RPT${String(report.id).padStart(3,'0')}` : "Report";
        const type = escapeHtml(report.reportType || "Drainage Issue");
        const status = escapeHtml(report.status || "Pending");
        const statusClass = `status-${status.toLowerCase()}`;
        const severity = escapeHtml(report.severity || "Minor");
        const severityClass = `severity-${severity.toLowerCase()}`;
        const desc = escapeHtml(report.description || "");
        const loc = escapeHtml(report.location || "");
        const filed = escapeHtml(report.dateFiled || "");

        const imgURL = report.image ? `/eco-drainage_app/${report.image}` : "/eco-drainage_app/uploads/defaultImage.jpg";

        div.innerHTML = `
            <div class="reportTopContainer">
                <div class="reportImage">
                    <img src="${imgURL}" alt="Report Photo">
                </div>
                <div class="reportContent">
                    <div class="reportTopRow">
                        <span class="reportID">${idFormat}</span>
                        <span class="statusBadge ${statusClass}">${status}</span>
                        <span class="severityBadge ${severityClass}">${severity}</span>
                    </div>
                    <div class="reportType">${type}</div>
                    <p class="reportDesc">${desc}</p>
                    <div class="reportMeta">
                        <span class="metaItem">📍 ${loc}</span>
                        <span class="metaItem">📅 Filed: ${filed}</span>
                    </div>
                </div>
            </div>
            <div class="reportActions">
                <button class="updateBtn" onclick="takeAction(${report.id})">Take Action</button>
                <button class="viewBtn" onclick="viewReport(${report.id})">View Details</button>
            </div>
        `;
        return div;
    }

    function takeAction(reportId) {
        const card = document.getElementById(`reportCard-${reportId}`);
        if (!card) return;
        if (card.querySelector(".actionFormContainer")) return; // Already open

        const actionButtons = card.querySelector(".reportActions");
        actionButtons.style.display = "none";

        const formDiv = document.createElement("div");
        formDiv.className = "actionFormContainer";

        formDiv.innerHTML = `
            <div class="actionForm">
                <label><strong>Update Status</strong></label>
                <select class="updateStatus">
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                </select>

                <label style="margin-top:8px;"><strong>Add Remarks *</strong></label>
                <textarea class="updateRemarks" placeholder="Enter your inspection notes and action taken..." required></textarea>

                <div class="actionButtons">
                    <button class="submitUpdateBtn">Submit Update</button>
                    <button class="cancelUpdateBtn">Cancel</button>
                </div>
            </div>
        `;

        card.appendChild(formDiv);
        formDiv.querySelector(".updateStatus").focus();

        // Cancel
        formDiv.querySelector(".cancelUpdateBtn").addEventListener("click", () => {
            formDiv.remove();
            actionButtons.style.display = "flex";
        });

        // Submit
        formDiv.querySelector(".submitUpdateBtn").addEventListener("click", async () => {
            const newStatus = formDiv.querySelector(".updateStatus").value;
            const remarks = formDiv.querySelector(".updateRemarks").value.trim();
            if (!remarks) { alert("Please enter remarks before submitting."); return; }

            try {
                const resp = await fetch("updateReport.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ reportId, status: newStatus, remarks })
                });
                const data = await resp.json();
                if (data.success) {
                    const badge = card.querySelector(".statusBadge");
                    badge.textContent = newStatus;
                    badge.className = "statusBadge status-" + newStatus.toLowerCase();
                    formDiv.remove();
                    actionButtons.style.display = "flex";
                } else alert(data.error || "Failed to update report.");
            } catch (err) {
                console.error(err);
                alert("Error updating report.");
            }
        });
    }

    function viewReport(id) {
        window.location.href = `viewReport.php?id=${id}`;
    }
    window.viewReport = viewReport;
    window.takeAction = takeAction;

    function escapeHtml(str) {
        if (!str && str !== 0) return "";
        return String(str).replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));
    }

    function addMarker(report) {
        const lat = parseFloat(report.latitude ?? report.lat);
        const lng = parseFloat(report.longitude ?? report.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

        const marker = L.marker([lat, lng]).addTo(markersLayer);
        const title = escapeHtml(report.reportType || "Report");
        const loc = escapeHtml(report.location || "");
        marker.bindPopup(`<strong>${title}</strong><br>${loc}`);
        return marker;
    }

    async function loadReports() {
        const reportsList = document.getElementById("reportsList");
        if (!reportsList) return;

        try {
            const resp = await fetch("getAssignedReports.php", { cache: "no-store" });
            const data = await resp.json();

            const set = (id, value) => { const el = document.getElementById(id); if(el) el.textContent = value ?? 0; };
            set("totalAssigned", data.stats?.total ?? 0);
            set("pendingReports", data.stats?.pending ?? 0);
            set("ongoingReports", data.stats?.ongoing ?? 0);
            set("completedReports", data.stats?.completed ?? 0);

            reportsList.innerHTML = "";
            const reports = Array.isArray(data.reports) ? data.reports : [];
            if (reports.length === 0) reportsList.innerHTML = "<p>No assigned reports.</p>";
            else reports.forEach(r => reportsList.appendChild(createReportCard(r)));

            markersLayer.clearLayers();
            const addedMarkers = reports.map(r => addMarker(r)).filter(m => m);
            if (addedMarkers.length) map.fitBounds(L.featureGroup(addedMarkers).getBounds(), { padding: [40,40] });
            else map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);

        } catch (err) {
            console.error(err);
            reportsList.innerHTML = "<p>Error loading assigned reports.</p>";
            map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }
    }

    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            fetch("logout.php", { method: "POST", credentials: "same-origin" })
                .finally(() => { window.location.href = "login.html"; });
        });
    }

    initMap();
    loadReports();
});
