<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Student');

$page_title = 'Student Dashboard';
include '../includes/header.php';

$userId = $_SESSION['user_id'];

// Get enrolled courses count
$enrolledCount = $conn->query("SELECT COUNT(*) as count FROM Enrollments WHERE UserId = $userId")->fetch_assoc()['count'];

// Get completed lessons count
$completedLessons = $conn->query("SELECT COUNT(*) as count FROM LessonCompletion WHERE UserId = $userId")->fetch_assoc()['count'];

// Get certificates count
$certificates = $conn->query("SELECT COUNT(*) as count FROM Certificates WHERE UserId = $userId")->fetch_assoc()['count'];

// Get enrolled courses with progress
$enrolledCourses = $conn->query("
    SELECT c.*, u.FullName as InstructorName,
           (SELECT COUNT(*) FROM Lessons WHERE CourseId = c.Id) as TotalLessons,
           (SELECT COUNT(*) FROM LessonCompletion lc 
            JOIN Lessons l ON lc.LessonId = l.Id 
            WHERE l.CourseId = c.Id AND lc.UserId = $userId) as CompletedLessons
    FROM Courses c
    JOIN Enrollments e ON c.Id = e.CourseId
    JOIN Users u ON c.CreatedBy = u.Id
    WHERE e.UserId = $userId
    ORDER BY e.EnrolledAt DESC
");

// Get upcoming quizzes
$upcomingQuizzes = $conn->query("
    SELECT q.*, c.Title as CourseTitle
    FROM Quizzes q
    JOIN Courses c ON q.CourseId = c.Id
    JOIN Enrollments e ON c.Id = e.CourseId
    WHERE e.UserId = $userId
    AND q.Id NOT IN (SELECT QuizId FROM QuizAttempts WHERE UserId = $userId)
    LIMIT 5
");
?>

<div class="container dashboard">
    <div class="dashboard-header">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h1>
        <p>Continue your learning journey</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $enrolledCount; ?></h3>
            <p>Enrolled Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $completedLessons; ?></h3>
            <p>Completed Lessons</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #F39C12 0%, #E67E22 100%);">
            <h3><?php echo $certificates; ?></h3>
            <p>🏆 Certificates Earned</p>
        </div>
    </div>

    <!-- Certificates Section -->
    <?php if ($certificates > 0): ?>
        <?php $certList = $conn->query("
            SELECT cert.*, c.Title as CourseTitle 
            FROM Certificates cert 
            JOIN Courses c ON cert.CourseId = c.Id 
            WHERE cert.UserId = $userId 
            ORDER BY cert.GeneratedAt DESC
        "); ?>
        
        <div class="card mb-3" style="border: 2px solid #F39C12;">
            <div class="flex-between mb-2">
                <h2 style="color: #F39C12;">🏆 Your Certificates</h2>
            </div>
            <div class="grid grid-2">
                <?php while ($cert = $certList->fetch_assoc()): ?>
                    <div style="padding: 1.5rem; background: linear-gradient(135deg, #FFF9E6 0%, #FEF5E7 100%); 
                                border-radius: 12px; border: 2px solid #F39C12;">
                        <div style="font-size: 2rem; text-align: center; margin-bottom: 1rem;">🎓</div>
                        <h3 style="color: #D68910; margin-bottom: 0.5rem; text-align: center;">
                            <?php echo htmlspecialchars($cert['CourseTitle']); ?>
                        </h3>
                        <p style="text-align: center; color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                            Completed: <?php echo date('F d, Y', strtotime($cert['GeneratedAt'])); ?>
                        </p>
                        <div style="text-align: center;">
                            <a href="certificate.php?id=<?php echo $cert['Id']; ?>" 
                               class="btn btn-primary" style="background: #F39C12; width: 100%;">
                                📜 View & Download Certificate
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- My Courses -->
    <div style="margin-top: 2rem;">
        <div class="flex-between mb-2">
            <h2 style="color: var(--primary-blue);">My Courses</h2>
            <a href="courses.php" class="btn btn-primary">Browse All Courses</a>
        </div>

        <?php if ($enrolledCourses && $enrolledCourses->num_rows > 0): ?>
            <div class="grid grid-2">
                <?php while ($course = $enrolledCourses->fetch_assoc()): 
                    $progress = $course['TotalLessons'] > 0 
                        ? round(($course['CompletedLessons'] / $course['TotalLessons']) * 100) 
                        : 0;
                ?>
                    <div class="card">
                        <div class="flex-between mb-2">
                            <h3 class="course-title"><?php echo htmlspecialchars($course['Title']); ?></h3>
                            <span class="badge badge-<?php echo strtolower($course['Difficulty']); ?>">
                                <?php echo $course['Difficulty']; ?>
                            </span>
                        </div>
                        
                        <p style="color: var(--text-light); margin-bottom: 1rem;">
                            👤 <?php echo htmlspecialchars($course['InstructorName']); ?>
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div class="flex-between mb-1">
                                <span style="font-size: 0.9rem; color: var(--text-light);">Progress</span>
                                <span style="font-weight: 600; color: var(--primary-blue);"><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>
                        </div>
                        
                        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 1rem;">
                            <?php echo $course['CompletedLessons']; ?> of <?php echo $course['TotalLessons']; ?> lessons completed
                        </p>
                        
                        <a href="lesson.php?course=<?php echo $course['Id']; ?>" class="btn btn-primary" style="width: 100%;">
                            Continue Learning
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <p style="color: var(--text-light); margin-bottom: 1rem;">You haven't enrolled in any courses yet.</p>
                <a href="courses.php" class="btn btn-primary">Browse Courses</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Quizzes -->
    <?php if ($upcomingQuizzes && $upcomingQuizzes->num_rows > 0): ?>
        <div style="margin-top: 2rem;">
            <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">Upcoming Quizzes</h2>
            <div class="card">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Course</th>
                            <th>Passing Score</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($quiz = $upcomingQuizzes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['Title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['CourseTitle']); ?></td>
                                <td><?php echo $quiz['PassingScore']; ?>%</td>
                                <td>
                                    <a href="quiz.php?id=<?php echo $quiz['Id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                                        Start Quiz
                                    </a>
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