document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const message = document.getElementById("message");

  fetch("login.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        message.style.color = "green";
        message.textContent = "Login successful! Redirecting...";
        setTimeout(() => {
          window.location.href = "residentDashboard.php"; 
        }, 1500);
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
