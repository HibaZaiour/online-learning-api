<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /learning-platform/login.php');
        exit();
    }
}

// Redirect if not specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /learning-platform/index.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['role']
    ];
}

// Logout function
function logout() {
    session_destroy();
    header('Location: /learning-platform/login.php');
    exit();
}
?>