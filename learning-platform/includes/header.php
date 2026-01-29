<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user = isset($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'],
    'role' => $_SESSION['role']
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Online Learning Platform</title>
    <link rel="stylesheet" href="/learning-platform/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="/learning-platform/index.php" class="logo">🎓 Learning Platform</a>
                <ul class="nav-links">
                    <?php if ($current_user): ?>
                        <?php if ($current_user['role'] === 'Student'): ?>
                            <li><a href="/learning-platform/student/index.php">My Dashboard</a></li>
                            <li><a href="/learning-platform/student/courses.php">Courses</a></li>
                        <?php elseif ($current_user['role'] === 'Instructor'): ?>
                            <li><a href="/learning-platform/instructor/index.php">Dashboard</a></li>
                            <li><a href="/learning-platform/instructor/courses.php">My Courses</a></li>
                        <?php elseif ($current_user['role'] === 'Admin'): ?>
                            <li><a href="/learning-platform/admin/index.php">Admin Panel</a></li>
                            <li><a href="/learning-platform/admin/users.php">Users</a></li>
                        <?php endif; ?>
                        <li><span>Welcome, <?php echo htmlspecialchars($current_user['name']); ?></span></li>
                        <li><a href="/learning-platform/logout.php" class="btn btn-primary">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/learning-platform/index.php">Home</a></li>
                        <li><a href="/learning-platform/login.php">Login</a></li>
                        <li><a href="/learning-platform/register.php" class="btn btn-primary">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>