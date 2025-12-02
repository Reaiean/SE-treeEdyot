const registerForm = document.getElementById('registerForm');
const message = document.getElementById('message');

registerForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const roleID = parseInt(document.getElementById('role').value);
  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  const contactNumber = document.getElementById('contactNumber').value.trim();
  const address = document.getElementById('address').value.trim();

  message.textContent = '';
  message.style.color = 'red';

  if (!roleID || !firstName || !lastName || !email || !password || !confirmPassword || !contactNumber || !address) {
    message.textContent = 'Please fill in all required fields.';
    return;
  }

  if (password !== confirmPassword) {
    message.textContent = 'Passwords do not match.';
    return;
  }

  const userData = { roleID, firstName, lastName, email, password, contactNumber, address };

  try {
    const response = await fetch('http://localhost:8000/eco-drainage_app/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData)
    });

    const text = await response.text();
    console.log('Raw response:', text);

    const result = JSON.parse(text);

    if (result.success) {
      message.style.color = 'green';
      message.textContent = 'Registration successful! Redirecting to login...';
      setTimeout(() => window.location.href = 'login.html', 2000);
    } else {
      message.textContent = result.message || 'Registration failed. Please try again.';
    }

  } catch (error) {
    message.textContent = 'Error connecting to server. Please try again later.';
    console.error('Registration error:', error);
  }
});
