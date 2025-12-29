// Login Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Basic validation
            if (!email || !password) {
                alert('Please fill in all fields');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }

            // Show loading state
            const submitBtn = loginForm.querySelector('.auth-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing in...';
            submitBtn.disabled = true;

            try {
                // Determine the correct API path
                const apiPath = window.location.protocol === 'file:' 
                    ? 'http://localhost/online-learning-api/api/users/login.php'
                    : 'api/users/login.php';

                // Make API call to login endpoint
                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                let data;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error('Server returned non-JSON response');
                }

                if (response.ok && data.token) {
                    // Store token in localStorage
                    localStorage.setItem('token', data.token);
                    
                    // Redirect to main page
                    window.location.href = 'index.html';
                } else {
                    // Show error message
                    alert(data.message || 'Invalid email or password. Please try again.');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Login error:', error);
                let errorMessage = 'An error occurred. Please try again later.';
                
                if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                    errorMessage = 'Network error: Could not connect to the server.\n\n' +
                        'Please make sure:\n' +
                        '1. XAMPP Apache is running\n' +
                        '2. You are accessing via: http://localhost/online-learning-api/login.html';
                }
                
                alert(errorMessage);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
});

