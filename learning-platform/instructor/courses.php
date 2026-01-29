<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Instructor');

$userId = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Create/Update Course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $shortDesc = sanitize($_POST['short_description']);
    $longDesc = sanitize($_POST['long_description']);
    $category = sanitize($_POST['category']);
    $difficulty = sanitize($_POST['difficulty']);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title) || empty($shortDesc)) {
        $error = 'Title and short description are required';
    } else {
        if ($courseId > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE Courses SET Title=?, ShortDescription=?, LongDescription=?, Category=?, Difficulty=?, IsPublished=? WHERE Id=? AND CreatedBy=?");
            $stmt->bind_param("sssssiii", $title, $shortDesc, $longDesc, $category, $difficulty, $isPublished, $courseId, $userId);
            if ($stmt->execute()) {
                $message = 'Course updated successfully!';
                $action = 'list';
            }
        } else {
            // Create
            $stmt = $conn->prepare("INSERT INTO Courses (Title, ShortDescription, LongDescription, Category, Difficulty, CreatedBy, IsPublished) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssii", $title, $shortDesc, $longDesc, $category, $difficulty, $userId, $isPublished);
            if ($stmt->execute()) {
                $message = 'Course created successfully!';
                $courseId = $conn->insert_id;
                $action = 'list';
            }
        }
    }
}

// Delete Course
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM Courses WHERE Id = $deleteId AND CreatedBy = $userId");
    $message = 'Course deleted successfully!';
    $action = 'list';
}

// Toggle Publish
if (isset($_GET['toggle_publish'])) {
    $toggleId = intval($_GET['toggle_publish']);
    $conn->query("UPDATE Courses SET IsPublished = NOT IsPublished WHERE Id = $toggleId AND CreatedBy = $userId");
    $message = 'Course status updated!';
}

$page_title = $action === 'create' ? 'Create Course' : ($action === 'edit' ? 'Edit Course' : 'My Courses');
include '../includes/header.php';

// Get course for editing
$editCourse = null;
if ($action === 'edit' && $courseId > 0) {
    $editCourse = $conn->query("SELECT * FROM Courses WHERE Id = $courseId AND CreatedBy = $userId")->fetch_assoc();
}

// Get all instructor's courses
$courses = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM Enrollments WHERE CourseId = c.Id) as StudentCount,
           (SELECT COUNT(*) FROM Lessons WHERE CourseId = c.Id) as LessonCount
    FROM Courses c
    WHERE c.CreatedBy = $userId
    ORDER BY c.CreatedAt DESC
");
?>

<div class="container dashboard">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Form -->
        <div class="dashboard-header">
            <h1><?php echo $action === 'edit' ? 'Edit Course' : 'Create New Course'; ?></h1>
            <a href="courses.php" class="btn btn-secondary">← Back to Courses</a>
        </div>

        <div class="card">
            <form method="POST" action="?action=<?php echo $action; ?>&id=<?php echo $courseId; ?>">
                <div class="form-group">
                    <label class="form-label">Course Title *</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $editCourse ? htmlspecialchars($editCourse['Title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Short Description *</label>
                    <input type="text" name="short_description" class="form-control" required
                           placeholder="Brief description for course cards"
                           value="<?php echo $editCourse ? htmlspecialchars($editCourse['ShortDescription']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Full Description</label>
                    <textarea name="long_description" class="form-control" rows="6"
                              placeholder="Detailed course description"><?php echo $editCourse ? htmlspecialchars($editCourse['LongDescription']) : ''; ?></textarea>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" 
                               placeholder="e.g., Programming, Design, Business"
                               value="<?php echo $editCourse ? htmlspecialchars($editCourse['Category']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Difficulty Level</label>
                        <select name="difficulty" class="form-control">
                            <option value="Beginner" <?php echo ($editCourse && $editCourse['Difficulty'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($editCourse && $editCourse['Difficulty'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($editCourse && $editCourse['Difficulty'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" 
                               <?php echo ($editCourse && $editCourse['IsPublished']) ? 'checked' : ''; ?>>
                        <span class="form-label" style="margin: 0;">Publish course (make it visible to students)</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'edit' ? 'Update Course' : 'Create Course'; ?>
                    </button>
                    <a href="courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- List View -->
        <div class="dashboard-header">
            <h1>My Courses</h1>
            <a href="?action=create" class="btn btn-primary">+ Create New Course</a>
        </div>

        <?php if ($courses && $courses->num_rows > 0): ?>
            <div class="grid grid-2">
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="card">
                        <div class="flex-between mb-2">
                            <h3 style="color: var(--primary-blue); margin: 0;">
                                <?php echo htmlspecialchars($course['Title']); ?>
                            </h3>
                            <span class="badge" style="background: <?php echo $course['IsPublished'] ? '#D5F4E6' : '#FADBD8'; ?>; 
                                                                    color: <?php echo $course['IsPublished'] ? '#27AE60' : '#C0392B'; ?>;">
                                <?php echo $course['IsPublished'] ? 'Published' : 'Draft'; ?>
                            </span>
                        </div>

                        <p style="color: var(--text-light); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars(substr($course['ShortDescription'], 0, 100)) . '...'; ?>
                        </p>

                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-light);">
                            <span>👥 <?php echo $course['StudentCount']; ?> students</span>
                            <span>📚 <?php echo $course['LessonCount']; ?> lessons</span>
                            <span class="badge badge-<?php echo strtolower($course['Difficulty']); ?>">
                                <?php echo $course['Difficulty']; ?>
                            </span>
                        </div>

                        <div class="flex gap-1">
                            <a href="?action=edit&id=<?php echo $course['Id']; ?>" 
                               class="btn btn-primary" style="flex: 1;">Edit</a>
                            <a href="lessons.php?course=<?php echo $course['Id']; ?>" 
                               class="btn btn-secondary" style="flex: 1;">Lessons</a>
                            <a href="quizzes.php?course=<?php echo $course['Id']; ?>" 
                               class="btn btn-secondary" style="flex: 1;">Quizzes</a>
                        </div>

                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light-blue);">
                            <div class="flex-between">
                                <a href="?toggle_publish=<?php echo $course['Id']; ?>" 
                                   style="color: var(--primary-blue); text-decoration: none; font-size: 0.9rem;">
                                    <?php echo $course['IsPublished'] ? 'Unpublish' : 'Publish'; ?>
                                </a>
                                <a href="?delete=<?php echo $course['Id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this course? All lessons and quizzes will be deleted too.');"
                                   style="color: var(--danger); text-decoration: none; font-size: 0.9rem;">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">No Courses Yet</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Start creating your first course and share your knowledge with students!
                </p>
                <a href="?action=create" class="btn btn-primary">Create Your First Course</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>