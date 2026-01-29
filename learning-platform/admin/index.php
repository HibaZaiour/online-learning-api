<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Admin');

$page_title = 'Admin Dashboard';
include '../includes/header.php';

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM Users")->fetch_assoc()['count'];
$totalCourses = $conn->query("SELECT COUNT(*) as count FROM Courses")->fetch_assoc()['count'];
$totalEnrollments = $conn->query("SELECT COUNT(*) as count FROM Enrollments")->fetch_assoc()['count'];
$totalInstructors = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Instructor' AND IsApproved = 1")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Student'")->fetch_assoc()['count'];
$pendingInstructors = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Instructor' AND IsApproved = 0")->fetch_assoc()['count'];

// Recent users
$recentUsers = $conn->query("SELECT * FROM Users ORDER BY CreatedAt DESC LIMIT 5");
$pendingApprovals = $conn->query("SELECT * FROM Users WHERE Role = 'Instructor' AND IsApproved = 0 ORDER BY CreatedAt DESC");
// Course statistics
$courseStats = $conn->query("
    SELECT c.Title, c.IsPublished, u.FullName as InstructorName,
           (SELECT COUNT(*) FROM Enrollments WHERE CourseId = c.Id) as Enrollments
    FROM Courses c
    JOIN Users u ON c.CreatedBy = u.Id
    ORDER BY Enrollments DESC
    LIMIT 10
");
?>

<div class="container dashboard">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Manage platform users and content</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Total Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $totalEnrollments; ?></h3>
            <p>Total Enrollments</p>
        </div>
    </div>

    <!-- User Breakdown -->
    <div class="grid grid-2 mb-3">
        <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">User Breakdown</h3>
            <div style="padding: 1rem; background: #F8FBFD; border-radius: 8px; margin-bottom: 0.75rem;">
                <div class="flex-between">
                    <span>👨‍🏫 Instructors</span>
                    <strong style="color: var(--primary-blue);"><?php echo $totalInstructors; ?></strong>
                </div>
            </div>
            <div style="padding: 1rem; background: #F8FBFD; border-radius: 8px; margin-bottom: 0.75rem;">
                <div class="flex-between">
                    <span>👨‍🎓 Students</span>
                    <strong style="color: var(--primary-blue);"><?php echo $totalStudents; ?></strong>
                </div>
            </div>
            <div style="padding: 1rem; background: #F8FBFD; border-radius: 8px;">
                <div class="flex-between">
                    <span>👥 Admins</span>
                    <strong style="color: var(--primary-blue);">
                        <?php echo $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Admin'")->fetch_assoc()['count']; ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="users.php" class="btn btn-primary">👥 Manage Users</a>
                <a href="courses.php" class="btn btn-secondary">📚 Manage Courses</a>
                <a href="users.php?action=create" class="btn btn-secondary">➕ Create User</a>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="card mb-3">
        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Recent Users</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $recentUsers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                            <td><?php echo htmlspecialchars($user['Email']); ?></td>
                            <td>
                                <span class="badge" style="background: 
                                    <?php 
                                    echo $user['Role'] === 'Admin' ? '#FADBD8' : 
                                         ($user['Role'] === 'Instructor' ? '#FEF5E7' : '#D5F4E6'); 
                                    ?>; color: 
                                    <?php 
                                    echo $user['Role'] === 'Admin' ? '#C0392B' : 
                                         ($user['Role'] === 'Instructor' ? '#D68910' : '#27AE60'); 
                                    ?>;">
                                    <?php echo $user['Role']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: right; margin-top: 1rem;">
            <a href="users.php" class="btn btn-primary">View All Users</a>
        </div>
    </div>

    <!-- Top Courses -->
    <?php if ($courseStats && $courseStats->num_rows > 0): ?>
        <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Popular Courses</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Enrollments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courseStats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['Title']); ?></td>
                                <td><?php echo htmlspecialchars($course['InstructorName']); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $course['IsPublished'] ? '#D5F4E6' : '#FADBD8'; ?>; 
                                                                            color: <?php echo $course['IsPublished'] ? '#27AE60' : '#C0392B'; ?>;">
                                        <?php echo $course['IsPublished'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $course['Enrollments']; ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>