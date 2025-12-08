// ------------------------------
// TAB SWITCHING
// ------------------------------
document.addEventListener("DOMContentLoaded", () => {
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
});

// ------------------------------
// GOOGLE MAP + STREET VIEW
// ------------------------------
function initMap() {
  const cebu = { lat: 10.3157, lng: 123.8854 };

  const map = new google.maps.Map(document.getElementById("map"), {
    center: cebu,
    zoom: 13,
    streetViewControl: true
  });

  const streetView = new google.maps.StreetViewPanorama(
    document.getElementById("street-view"),
    {
      position: cebu,
      pov: { heading: 100, pitch: 0 },
      zoom: 1
    }
  );

  map.setStreetView(streetView);

  map.addListener("click", (e) => {
    streetView.setPosition(e.latLng);
  });
}