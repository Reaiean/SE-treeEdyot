document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const message = document.getElementById("message");

fetch("login.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ email, password })
})

  .then(response => response.json())
  .then(data => {
    if (data.success) {
      message.style.color = "green";
      message.textContent = "Login successful! Redirecting...";

      setTimeout(() => {
        // Redirect based on role_id
        switch (data.role_id) {
          case 1: // Admin
            window.location.href = "adminDashboard.php";
            break;
          case 2: // Resident
            window.location.href = "residentDashboard.php";
            break;
          case 3: // Maintenance Staff
            window.location.href = "staffDashboard.php"; // if exists
            break;
          default:
            window.location.href = "login.html";
        }
      }, 1000);
    } else {
      message.style.color = "red";
      message.textContent = data.message;
    }
  })
  .catch(error => {
    message.textContent = "Error connecting to server.";
    console.error(error);
  });
});
