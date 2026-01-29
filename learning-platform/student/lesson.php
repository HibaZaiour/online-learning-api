<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Student');

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course']) ? intval($_GET['course']) : 0;
$lessonId = isset($_GET['lesson']) ? intval($_GET['lesson']) : 0;

// Check enrollment
$enrollment = $conn->query("SELECT * FROM Enrollments WHERE UserId = $userId AND CourseId = $courseId");
if ($enrollment->num_rows == 0) {
    header('Location: courses.php');
    exit();
}

// Get course info
$course = $conn->query("SELECT * FROM Courses WHERE Id = $courseId")->fetch_assoc();

// Get all lessons
$lessons = $conn->query("SELECT l.*, 
                         (SELECT COUNT(*) FROM LessonCompletion WHERE LessonId = l.Id AND UserId = $userId) as IsCompleted
                         FROM Lessons l 
                         WHERE CourseId = $courseId 
                         ORDER BY OrderNum ASC");

// Get current lesson (first lesson if not specified)
if ($lessonId == 0 && $lessons->num_rows > 0) {
    $lessons->data_seek(0);
    $firstLesson = $lessons->fetch_assoc();
    $lessonId = $firstLesson['Id'];
    $lessons->data_seek(0);
}

$currentLesson = $conn->query("SELECT * FROM Lessons WHERE Id = $lessonId")->fetch_assoc();

// Mark lesson as complete
if (isset($_POST['complete_lesson'])) {
    $checkComplete = $conn->query("SELECT * FROM LessonCompletion WHERE LessonId = $lessonId AND UserId = $userId");
    if ($checkComplete->num_rows == 0) {
        $conn->query("INSERT INTO LessonCompletion (LessonId, UserId) VALUES ($lessonId, $userId)");
    }
    header("Location: lesson.php?course=$courseId&lesson=$lessonId");
    exit();
}

// Calculate progress
$totalLessons = $conn->query("SELECT COUNT(*) as count FROM Lessons WHERE CourseId = $courseId")->fetch_assoc()['count'];
$completedLessons = $conn->query("SELECT COUNT(*) as count FROM LessonCompletion lc 
                                  JOIN Lessons l ON lc.LessonId = l.Id 
                                  WHERE l.CourseId = $courseId AND lc.UserId = $userId")->fetch_assoc()['count'];
$progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

// Check if all quizzes are passed
$totalQuizzes = $conn->query("SELECT COUNT(*) as count FROM Quizzes WHERE CourseId = $courseId")->fetch_assoc()['count'];
$passedQuizzes = $conn->query("
    SELECT COUNT(DISTINCT qa.QuizId) as passed 
    FROM QuizAttempts qa 
    JOIN Quizzes q ON qa.QuizId = q.Id 
    WHERE q.CourseId = $courseId 
    AND qa.UserId = $userId 
    AND qa.Score >= q.PassingScore
")->fetch_assoc()['passed'];

// Check if certificate should be generated
$canGetCertificate = ($completedLessons >= $totalLessons && $passedQuizzes >= $totalQuizzes && $totalLessons > 0);

// Check if certificate already exists
$existingCert = $conn->query("SELECT * FROM Certificates WHERE CourseId = $courseId AND UserId = $userId")->fetch_assoc();

// Auto-generate certificate if conditions met and doesn't exist
if ($canGetCertificate && !$existingCert) {
    $conn->query("INSERT INTO Certificates (CourseId, UserId) VALUES ($courseId, $userId)");
    $existingCert = $conn->query("SELECT * FROM Certificates WHERE CourseId = $courseId AND UserId = $userId")->fetch_assoc();
}

$page_title = $course['Title'];
include '../includes/header.php';
?>

<div class="container dashboard">
    <div style="margin-bottom: 2rem;">
        <a href="courses.php" style="color: var(--primary-blue); text-decoration: none;">← Back to Courses</a>
        <h1 style="color: var(--primary-blue); margin: 1rem 0;"><?php echo htmlspecialchars($course['Title']); ?></h1>
        
        <div style="margin: 1rem 0;">
            <div class="flex-between mb-1">
                <span>Course Progress</span>
                <span style="font-weight: 600; color: var(--primary-blue);"><?php echo $progress; ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            
            <?php if ($canGetCertificate && $existingCert): ?>
                <div class="alert alert-success mt-2">
                    <div class="flex-between" style="align-items: center;">
                        <div>
                            <strong>🎉 Congratulations!</strong>
                            <p style="margin: 0.5rem 0 0 0;">You've completed this course and earned your certificate!</p>
                        </div>
                        <a href="certificate.php?id=<?php echo $existingCert['Id']; ?>" 
                           class="btn btn-success" style="white-space: nowrap;">
                            📜 Download Certificate
                        </a>
                    </div>
                </div>
            <?php elseif ($progress == 100 && $totalQuizzes > 0): ?>
                <div class="alert alert-info mt-2">
                    ℹ️ Complete all quizzes with passing scores to earn your certificate! 
                    (<?php echo $passedQuizzes; ?>/<?php echo $totalQuizzes; ?> quizzes passed)
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-2" style="gap: 2rem; align-items: start;">
        <!-- Lesson Content -->
        <div>
            <?php if ($currentLesson): ?>
                <div class="card">
                    <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($currentLesson['Title']); ?>
                    </h2>
                    
                    <?php if ($currentLesson['VideoUrl']): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <?php if (strpos($currentLesson['VideoUrl'], 'youtube.com') !== false || strpos($currentLesson['VideoUrl'], 'youtu.be') !== false): 
                                // Extract YouTube video ID
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $currentLesson['VideoUrl'], $match);
                                $videoId = $match[1] ?? '';
                            ?>
                                <iframe width="100%" height="400" 
                                        src="https://www.youtube.com/embed/<?php echo $videoId; ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen
                                        style="border-radius: 8px;"></iframe>
                            <?php else: ?>
                                <video controls width="100%" style="border-radius: 8px;">
                                    <source src="<?php echo htmlspecialchars($currentLesson['VideoUrl']); ?>">
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="line-height: 1.8; color: var(--text-dark);">
                        <?php echo nl2br(htmlspecialchars($currentLesson['Content'])); ?>
                    </div>
                    
                    <?php 
                    $isCompleted = $conn->query("SELECT * FROM LessonCompletion WHERE LessonId = $lessonId AND UserId = $userId")->num_rows > 0;
                    ?>
                    
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--light-blue);">
                        <?php if ($isCompleted): ?>
                            <div class="alert alert-success">✓ Lesson Completed</div>
                        <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="complete_lesson" class="btn btn-success">
                                    Mark as Complete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Check for quizzes -->
                <?php 
                $quizzes = $conn->query("SELECT * FROM Quizzes WHERE LessonId = $lessonId OR (CourseId = $courseId AND LessonId IS NULL)");
                if ($quizzes->num_rows > 0):
                ?>
                    <div class="card mt-2">
                        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Practice Quizzes</h3>
                        <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                            <div style="padding: 1rem; background: #F8FBFD; border-radius: 8px; margin-bottom: 1rem;">
                                <h4><?php echo htmlspecialchars($quiz['Title']); ?></h4>
                                <p style="color: var(--text-light); margin: 0.5rem 0;">
                                    Passing Score: <?php echo $quiz['PassingScore']; ?>%
                                    <?php if ($quiz['TimeLimit']): ?>
                                        | Time Limit: <?php echo $quiz['TimeLimit']; ?> minutes
                                    <?php endif; ?>
                                </p>
                                <a href="quiz.php?id=<?php echo $quiz['Id']; ?>" class="btn btn-primary">Take Quiz</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card text-center">
                    <p>Select a lesson from the sidebar to begin.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lesson List Sidebar -->
        <div>
            <div class="card">
                <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Lessons</h3>
                
                <?php if ($lessons->num_rows > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php while ($lesson = $lessons->fetch_assoc()): ?>
                            <a href="?course=<?php echo $courseId; ?>&lesson=<?php echo $lesson['Id']; ?>" 
                               style="text-decoration: none;">
                                <div style="padding: 1rem; background: <?php echo $lesson['Id'] == $lessonId ? 'var(--light-blue)' : '#F8FBFD'; ?>; 
                                            border-radius: 8px; border: 2px solid <?php echo $lesson['Id'] == $lessonId ? 'var(--primary-blue)' : 'transparent'; ?>; 
                                            transition: all 0.3s;">
                                    <div class="flex-between">
                                        <span style="font-weight: 600; color: var(--text-dark);">
                                            <?php echo $lesson['IsCompleted'] ? '✓' : '○'; ?> 
                                            <?php echo htmlspecialchars($lesson['Title']); ?>
                                        </span>
                                        <?php if ($lesson['EstimatedDuration']): ?>
                                            <span style="font-size: 0.85rem; color: var(--text-light);">
                                                <?php echo $lesson['EstimatedDuration']; ?> min
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light); text-align: center;">No lessons available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>