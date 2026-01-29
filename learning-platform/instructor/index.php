<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Instructor');

$page_title = 'Instructor Dashboard';
include '../includes/header.php';

$userId = $_SESSION['user_id'];

// Get statistics
$totalCourses = $conn->query("SELECT COUNT(*) as count FROM Courses WHERE CreatedBy = $userId")->fetch_assoc()['count'];
$publishedCourses = $conn->query("SELECT COUNT(*) as count FROM Courses WHERE CreatedBy = $userId AND IsPublished = 1")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(DISTINCT e.UserId) as count FROM Enrollments e JOIN Courses c ON e.CourseId = c.Id WHERE c.CreatedBy = $userId")->fetch_assoc()['count'];

// Get recent courses
$recentCourses = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM Enrollments WHERE CourseId = c.Id) as StudentCount,
           (SELECT COUNT(*) FROM Lessons WHERE CourseId = c.Id) as LessonCount
    FROM Courses c
    WHERE c.CreatedBy = $userId
    ORDER BY c.CreatedAt DESC
    LIMIT 5
");

// Get quiz statistics
$quizStats = $conn->query("
    SELECT q.Title, c.Title as CourseTitle, 
           AVG(qa.Score) as AvgScore, 
           COUNT(qa.Id) as AttemptCount
    FROM Quizzes q
    JOIN Courses c ON q.CourseId = c.Id
    LEFT JOIN QuizAttempts qa ON q.Id = qa.QuizId
    WHERE c.CreatedBy = $userId
    GROUP BY q.Id
    ORDER BY qa.AttemptDate DESC
    LIMIT 5
");
?>

<div class="container dashboard">
    <div class="dashboard-header">
        <h1>Instructor Dashboard</h1>
        <p>Manage your courses and track student progress</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Total Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $publishedCourses; ?></h3>
            <p>Published Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Total Students</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-3">
        <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">Quick Actions</h2>
        <div class="flex gap-2">
            <a href="courses.php?action=create" class="btn btn-primary">📝 Create New Course</a>
            <a href="courses.php" class="btn btn-secondary">📚 Manage Courses</a>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="mb-3">
        <div class="flex-between mb-2">
            <h2 style="color: var(--primary-blue);">My Courses</h2>
            <a href="courses.php" class="btn btn-primary">View All</a>
        </div>

        <?php if ($recentCourses && $recentCourses->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Status</th>
                            <th>Students</th>
                            <th>Lessons</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $recentCourses->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['Title']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-light);">
                                        <?php echo htmlspecialchars(substr($course['ShortDescription'], 0, 60)) . '...'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge" style="background: <?php echo $course['IsPublished'] ? '#D5F4E6' : '#FADBD8'; ?>; 
                                                                            color: <?php echo $course['IsPublished'] ? '#27AE60' : '#C0392B'; ?>;">
                                        <?php echo $course['IsPublished'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td><?php echo $course['StudentCount']; ?></td>
                                <td><?php echo $course['LessonCount']; ?></td>
                                <td>
                                    <a href="courses.php?action=edit&id=<?php echo $course['Id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.5rem 1rem;">Edit</a>
                                    <a href="lessons.php?course=<?php echo $course['Id']; ?>" 
                                       class="btn btn-secondary" style="padding: 0.5rem 1rem;">Lessons</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <p style="color: var(--text-light); margin-bottom: 1rem;">You haven't created any courses yet.</p>
                <a href="courses.php?action=create" class="btn btn-primary">Create Your First Course</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quiz Performance -->
    <?php if ($quizStats && $quizStats->num_rows > 0): ?>
        <div class="card">
            <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">Quiz Performance</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Course</th>
                            <th>Attempts</th>
                            <th>Avg Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($quiz = $quizStats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['Title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['CourseTitle']); ?></td>
                                <td><?php echo $quiz['AttemptCount'] ?: 0; ?></td>
                                <td>
                                    <?php if ($quiz['AvgScore']): ?>
                                        <span style="color: var(--primary-blue); font-weight: 600;">
                                            <?php echo round($quiz['AvgScore'], 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">No attempts</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>