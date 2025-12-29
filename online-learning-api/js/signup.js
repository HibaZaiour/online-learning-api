// Sign Up Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    if (!signupForm) {
        console.error('Signup form not found!');
        return;
    }

    // Ensure button is not disabled
    const submitBtn = signupForm.querySelector('.auth-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
    }

    signupForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Basic validation
        if (!username || !email || !password || !confirmPassword) {
            alert('Please fill in all fields');
            return;
        }

        // Username validation
        if (username.length < 3) {
            alert('Username must be at least 3 characters long');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return;
        }

        // Password validation
        if (password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }

        // Confirm password validation
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        // Show loading state
        const submitBtn = signupForm.querySelector('.auth-btn');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Creating account...';
        submitBtn.disabled = true;

        try {
            // Determine the correct API path
            const apiPath = window.location.protocol === 'file:' 
                ? 'http://localhost/online-learning-api/api/users/create.php'
                : 'api/users/create.php';

            console.log('Making request to:', apiPath);
            console.log('Current location:', window.location.href);

            // Make API call to signup endpoint
            const response = await fetch(apiPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    password: password
                })
            });

            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            let data;
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
                console.log('Response data:', data);
            } else {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            }

            if (response.ok && data.message) {
                // Show success message
                alert('Account created successfully! Please log in.');
                
                // Redirect to login page
                window.location.href = 'login.html';
            } else {
                // Show error message
                const errorMsg = data.message || data.error || 'An error occurred. Please try again.';
                alert('Error: ' + errorMsg);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Signup error:', error);
            
            // More specific error messages
            let errorMessage = 'An error occurred while creating your account.';
            
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                errorMessage = 'Network error: Could not connect to the server.\n\n' +
                    'Please make sure:\n' +
                    '1. XAMPP Apache is running\n' +
                    '2. You are accessing via: http://localhost/online-learning-api/signup.html\n' +
                    '3. The API file exists at: api/users/create.php';
            } else {
                errorMessage = 'Error: ' + error.message;
            }
            
            alert(errorMessage);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    });
});

