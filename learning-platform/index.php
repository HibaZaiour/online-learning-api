<?php
require_once 'config/database.php';
$page_title = 'Home';
include 'includes/header.php';

// Get published courses
$courses = $conn->query("SELECT c.*, u.FullName as InstructorName 
                         FROM Courses c 
                         JOIN Users u ON c.CreatedBy = u.Id 
                         WHERE c.IsPublished = 1 
                         ORDER BY c.CreatedAt DESC 
                         LIMIT 6");
?>

<div class="container" style="padding: 3rem 20px;">
    <!-- Hero Section -->
    <div style="text-align: center; padding: 4rem 0; background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%); border-radius: 20px; color: white; margin-bottom: 3rem;">
        <h1 style="font-size: 3rem; margin-bottom: 1rem;">Welcome to Your Learning Journey</h1>
        <p style="font-size: 1.3rem; margin-bottom: 2rem; opacity: 0.9;">Discover thousands of courses and start learning today</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="register.php" class="btn btn-primary" style="background: white; color: var(--primary-blue); font-size: 1.1rem;">Get Started</a>
                <a href="login.php" class="btn btn-secondary" style="border-color: white; color: white; font-size: 1.1rem;">Login</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="grid grid-3 mb-3">
        <div class="card text-center">
            <div style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;">📚</div>
            <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Quality Courses</h3>
            <p style="color: var(--text-light);">Learn from expert instructors with comprehensive course materials</p>
        </div>
        <div class="card text-center">
            <div style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;">🎯</div>
            <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Track Progress</h3>
            <p style="color: var(--text-light);">Monitor your learning journey and earn certificates</p>
        </div>
        <div class="card text-center">
            <div style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;">✨</div>
            <h3 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Interactive Quizzes</h3>
            <p style="color: var(--text-light);">Test your knowledge with engaging quizzes and assessments</p>
        </div>
    </div>

    <!-- Featured Courses -->
    <div style="margin-top: 3rem;">
        <h2 style="color: var(--primary-blue); margin-bottom: 2rem; text-align: center;">Featured Courses</h2>
        
        <?php if ($courses && $courses->num_rows > 0): ?>
            <div class="grid grid-3">
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="course-card">
                        <div class="course-thumbnail">
                            <?php if ($course['Thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars($course['Thumbnail']); ?>" alt="Course">
                            <?php else: ?>
                                📖
                            <?php endif; ?>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($course['Title']); ?></h3>
                            <p class="course-description"><?php echo htmlspecialchars(substr($course['ShortDescription'], 0, 100)) . '...'; ?></p>
                            
                            <div class="course-meta">
                                <span>👤 <?php echo htmlspecialchars($course['InstructorName']); ?></span>
                                <span class="badge badge-<?php echo strtolower($course['Difficulty']); ?>">
                                    <?php echo $course['Difficulty']; ?>
                                </span>
                            </div>
                            
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Student'): ?>
                                <a href="student/courses.php?view=<?php echo $course['Id']; ?>" class="btn btn-primary" style="width: 100%;">View Course</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="width: 100%;">Login to Enroll</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <p style="color: var(--text-light);">No courses available yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>