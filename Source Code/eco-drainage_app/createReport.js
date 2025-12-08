// Form submission with AJAX
document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const msg = document.getElementById('message');
    const formData = new FormData(this);

    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";
    msg.textContent = "";

    fetch("createReport.php", { method: "POST", body: formData })
        .then(response => response.text()) // Get raw text first to debug
        .then(text => {
            try {
                // Try to parse the text as JSON
                const data = JSON.parse(text);
                
                if (data.success) {
                    msg.style.color = "green";
                    msg.textContent = "Report submitted successfully! Redirecting...";
                    setTimeout(() => window.location.href = "residentDashboard.php", 1500);
                } else {
                    msg.style.color = "red";
                    msg.textContent = data.message || "Unknown error";
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit Report";
                }
            } catch (e) {
                // If JSON parse fails, it means PHP returned an error/warning string
                console.error("Server returned invalid JSON:", text);
                msg.style.color = "red";
                msg.textContent = "Server Error. Check console for details.";
                submitBtn.disabled = false;
                submitBtn.textContent = "Submit Report";
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);
            msg.style.color = "red";
            msg.textContent = "Network connection failed.";
            submitBtn.disabled = false;
            submitBtn.textContent = "Submit Report";
        });
});

// Get Current Location button
document.getElementById('getLocationBtn').addEventListener('click', () => {
    const latInput = document.getElementById('latitude');
    const longInput = document.getElementById('longitude');
    const btn = document.getElementById('getLocationBtn');

    btn.textContent = "Locating...";

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            latInput.value = position.coords.latitude.toFixed(6);
            longInput.value = position.coords.longitude.toFixed(6);
            btn.textContent = "Location Found ✓";
        }, err => {
            alert("Unable to get location. Please allow location access.");
            btn.textContent = "Get Current Location";
        });
    } else {
        alert("Geolocation is not supported by this browser.");
    }
});