const loginForm = document.getElementById("loginForm");
const message = document.getElementById("message");

loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    message.textContent = "";
    message.style.color = "red";

    if (!email || !password) {
        message.textContent = "Please enter email and password.";
        return;
    }

    try {
        const response = await fetch("login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });

        const result = await response.json();
        console.log(result);

        if (!result.success) {
            message.textContent = result.message;
            return;
        }

        // Redirect BASED ON ROLE
        const roleID = parseInt(result.roleID);

        if (roleID === 1) {
            window.location.href = "admindashboard.php"; // ADMIN
        } else if (roleID === 2) {
            window.location.href = "residentDashboard.php"; // RESIDENT
        } else if (roleID === 3) {
            window.location.href = "maintenanceDashboard.php"; // OPTIONAL
        } else {
            message.textContent = "Unknown role.";
        }

    } catch (err) {
        console.error(err);
        message.textContent = "Server error. Please try again.";
    }
});
