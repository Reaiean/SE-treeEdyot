// Select the form and message paragraph
const registerForm = document.getElementById('registerForm');
const message = document.getElementById('message');

registerForm.addEventListener('submit', async (e) => {
  e.preventDefault(); // prevent default form submission

  // Collect form values
  const roleID = parseInt(document.getElementById('role').value);
  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  const contactNumber = document.getElementById('contactNumber').value.trim();
  const address = document.getElementById('address').value.trim();

  // Clear previous messages
  message.textContent = '';
  message.style.color = 'red';

  // Check required fields
  if (!roleID || !firstName || !lastName || !email || !password || !confirmPassword || !contactNumber || !address) {
    message.textContent = 'Please fill in all required fields.';
    return;
  }

  // Check if passwords match
  if (password !== confirmPassword) {
    message.textContent = 'Passwords do not match.';
    return;
  }

  // Prepare data for backend
  const userData = { roleID, firstName, lastName, email, password, contactNumber, address };

  try {
    const response = await fetch('register.php', {          
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData)
    });

    // For debugging: read raw response text
    const text = await response.text();
    console.log('Raw response:', text);

    // Parse JSON safely
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
