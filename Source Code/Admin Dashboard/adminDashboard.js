// ------------------------------
// DOM READY
// ------------------------------
document.addEventListener("DOMContentLoaded", () => {
  setupTabs();
  setupViewReportButton();
  initMap();
});

// ------------------------------
// TAB SWITCHING
// ------------------------------
function setupTabs() {
  const tabs = document.querySelectorAll(".logs-tab");
  const tables = document.querySelectorAll(".logs-table");

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("active-tab"));
      tab.classList.add("active-tab");

      const target = tab.getAttribute("data-target");
      tables.forEach(table => {
        table.classList.toggle("active-table", table.id === target);
      });
    });
  });
}

// ------------------------------
// VIEW REPORT BUTTON → REPORT LOGS TAB
// ------------------------------
function setupViewReportButton() {
  const viewReportBtn = document.querySelector(".file-report-btn");
  if (!viewReportBtn) return;

  viewReportBtn.addEventListener("click", () => {
    const reportTab = document.querySelector('.logs-tab[data-target="report-logs"]');
    const tabs = document.querySelectorAll(".logs-tab");
    const tables = document.querySelectorAll(".logs-table");
    const reportTable = document.getElementById("report-logs");

    // Activate Report Logs tab
    if (reportTab) {
      tabs.forEach(t => t.classList.remove("active-tab"));
      reportTab.classList.add("active-tab");
    }

    // Show Report Logs table
    if (reportTable) {
      tables.forEach(tbl => tbl.classList.remove("active-table"));
      reportTable.classList.add("active-table");
    }

    // Scroll to logs section
    const logsSection = document.querySelector(".logs-section");
    if (logsSection) {
      logsSection.scrollIntoView({ behavior: "smooth" });
    }
  });
}

// ------------------------------
// OPENSTREETMAP (LEAFLET) MAP
// ------------------------------
function initMap() {
  const mapElement = document.getElementById("map");
  if (!mapElement) return;

  // Cebu City coords
  const cebu = [10.3157, 123.8854];

  // Create map
  const map = L.map("map").setView(cebu, 13);

  // OpenStreetMap tiles
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap contributors"
  }).addTo(map);

  // Default marker
  let marker = L.marker(cebu).addTo(map);

  // Simple info panel under map
  const streetViewDiv = document.getElementById("street-view");
  if (streetViewDiv) {
    streetViewDiv.style.display = "flex";
    streetViewDiv.style.alignItems = "center";
    streetViewDiv.style.justifyContent = "center";
    streetViewDiv.style.padding = "8px";
    streetViewDiv.style.fontSize = "14px";
    streetViewDiv.style.textAlign = "center";
    streetViewDiv.innerHTML = `
      Click anywhere on the map to update the selected report location.<br>
      <strong>Current:</strong> Cebu City (10.3157, 123.8854)
    `;
  }

  // Update marker + info on click
  map.on("click", (e) => {
    const { lat, lng } = e.latlng;

    // Move marker
    if (marker) {
      marker.setLatLng(e.latlng);
    } else {
      marker = L.marker(e.latlng).addTo(map);
    }

    // Update info panel
    if (streetViewDiv) {
      streetViewDiv.innerHTML = `
        <strong>Selected Location:</strong><br>
        Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}
      `;
    }
  });
}