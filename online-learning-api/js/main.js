// Main JavaScript for Layout Functionality

// Check authentication state and update UI
function checkAuthState() {
    const token = localStorage.getItem('token');
    const authButtons = document.getElementById('authButtons');
    const userMenu = document.getElementById('userMenu');
    const sidebar = document.getElementById('sidebar');
    const usernameDisplay = document.getElementById('usernameDisplay');

    const browseCoursesBtn = document.getElementById('browseCoursesBtn');

    if (token) {
        // User is logged in
        if (authButtons) authButtons.style.display = 'none';
        if (userMenu) userMenu.style.display = 'block';
        if (sidebar) sidebar.style.display = 'block';
        if (browseCoursesBtn) browseCoursesBtn.style.display = 'block';

        // Decode token to get username (simple base64 decode)
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            if (usernameDisplay && payload.username) {
                usernameDisplay.textContent = payload.username;
            }
        } catch (e) {
            console.error('Error decoding token:', e);
        }
    } else {
        // User is not logged in
        if (authButtons) authButtons.style.display = 'flex';
        if (userMenu) userMenu.style.display = 'none';
        if (sidebar) sidebar.style.display = 'none';
        if (browseCoursesBtn) browseCoursesBtn.style.display = 'none';
    }
}

// User Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Check auth state on page load
    checkAuthState();

    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenu = document.getElementById('userMenu');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    const logoutBtn = document.getElementById('logoutBtn');

    if (userMenuToggle && userMenu) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Close dropdown when clicking on menu items
        if (userMenuDropdown) {
            const menuItems = userMenuDropdown.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    userMenu.classList.remove('active');
                });
            });
        }
    }

    // Logout functionality
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('token');
            checkAuthState();
            window.location.href = 'index.html';
        });
    }

    // Search functionality
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');

    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                console.log('Searching for:', query);
                // Implement search functionality here
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    console.log('Searching for:', query);
                    // Implement search functionality here
                }
            }
        });
    }
});

