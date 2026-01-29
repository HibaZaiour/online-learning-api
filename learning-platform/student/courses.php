<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Student');

$page_title = 'Browse Courses';
include '../includes/header.php';

$userId = $_SESSION['user_id'];
$message = '';

// Handle enrollment
if (isset($_GET['enroll'])) {
    $courseId = intval($_GET['enroll']);
    $checkEnroll = $conn->query("SELECT * FROM Enrollments WHERE UserId = $userId AND CourseId = $courseId");
    
    if ($checkEnroll->num_rows == 0) {
        $conn->query("INSERT INTO Enrollments (UserId, CourseId) VALUES ($userId, $courseId)");
        $message = 'Successfully enrolled in the course!';
    } else {
        $message = 'You are already enrolled in this course.';
    }
}

// Get all published courses with enrollment status
$searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$difficulty = isset($_GET['difficulty']) ? sanitize($_GET['difficulty']) : '';

$sql = "SELECT c.*, u.FullName as InstructorName,
        (SELECT COUNT(*) FROM Enrollments WHERE CourseId = c.Id AND UserId = $userId) as IsEnrolled,
        (SELECT COUNT(*) FROM Lessons WHERE CourseId = c.Id) as LessonCount
        FROM Courses c
        JOIN Users u ON c.CreatedBy = u.Id
        WHERE c.IsPublished = 1";

if ($searchTerm) {
    $sql .= " AND (c.Title LIKE '%$searchTerm%' OR c.ShortDescription LIKE '%$searchTerm%')";
}
if ($category) {
    $sql .= " AND c.Category = '$category'";
}
if ($difficulty) {
    $sql .= " AND c.Difficulty = '$difficulty'";
}

$sql .= " ORDER BY c.CreatedAt DESC";
$courses = $conn->query($sql);

// Get categories
$categories = $conn->query("SELECT DISTINCT Category FROM Courses WHERE Category IS NOT NULL AND IsPublished = 1");
?>

<div class="container dashboard">
    <div class="dashboard-header">
        <h1>Browse Courses</h1>
        <p>Explore our collection of courses and start learning</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3">
        <form method="GET" action="">
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search courses..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['Category']; ?>" 
                                    <?php echo $category == $cat['Category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['Category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-control">
                        <option value="">All Levels</option>
                        <option value="Beginner" <?php echo $difficulty == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="Intermediate" <?php echo $difficulty == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="Advanced" <?php echo $difficulty == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
            </div>
            <div style="text-align: right;">
                <a href="courses.php" class="btn btn-secondary">Clear Filters</a>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Courses Grid -->
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
                        <p class="course-description">
                            <?php echo htmlspecialchars(substr($course['ShortDescription'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="course-meta">
                            <span>👤 <?php echo htmlspecialchars($course['InstructorName']); ?></span>
                            <span>📚 <?php echo $course['LessonCount']; ?> lessons</span>
                        </div>
                        
                        <div style="margin: 1rem 0;">
                            <span class="badge badge-<?php echo strtolower($course['Difficulty']); ?>">
                                <?php echo $course['Difficulty']; ?>
                            </span>
                            <?php if ($course['Category']): ?>
                                <span class="badge" style="background: #EBF5FB; color: #2E86C1;">
                                    <?php echo htmlspecialchars($course['Category']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($course['IsEnrolled'] > 0): ?>
                            <a href="lesson.php?course=<?php echo $course['Id']; ?>" 
                               class="btn btn-success" style="width: 100%;">
                                Continue Learning
                            </a>
                        <?php else: ?>
                            <a href="?enroll=<?php echo $course['Id']; ?>" 
                               class="btn btn-primary" style="width: 100%;">
                                Enroll Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card text-center">
            <p style="color: var(--text-light);">No courses found matching your criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>