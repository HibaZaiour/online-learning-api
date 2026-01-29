<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Instructor');

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course']) ? intval($_GET['course']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$lessonId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Verify course ownership
$course = $conn->query("SELECT * FROM Courses WHERE Id = $courseId AND CreatedBy = $userId")->fetch_assoc();
if (!$course) {
    header('Location: courses.php');
    exit();
}

// Create/Update Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $videoUrl = sanitize($_POST['video_url']);
    $duration = intval($_POST['duration']);
    $order = intval($_POST['order_num']);
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        if ($lessonId > 0) {
            $stmt = $conn->prepare("UPDATE Lessons SET Title=?, Content=?, VideoUrl=?, EstimatedDuration=?, OrderNum=? WHERE Id=? AND CourseId=?");
            $stmt->bind_param("sssiii i", $title, $content, $videoUrl, $duration, $order, $lessonId, $courseId);
            if ($stmt->execute()) {
                $message = 'Lesson updated successfully!';
                $action = 'list';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO Lessons (CourseId, Title, Content, VideoUrl, EstimatedDuration, OrderNum) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiii", $courseId, $title, $content, $videoUrl, $duration, $order);
            if ($stmt->execute()) {
                $message = 'Lesson created successfully!';
                $action = 'list';
            }
        }
    }
}

// Delete Lesson
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM Lessons WHERE Id = $deleteId AND CourseId = $courseId");
    $message = 'Lesson deleted successfully!';
    $action = 'list';
}

$page_title = 'Manage Lessons - ' . $course['Title'];
include '../includes/header.php';

// Get lesson for editing
$editLesson = null;
if ($action === 'edit' && $lessonId > 0) {
    $editLesson = $conn->query("SELECT * FROM Lessons WHERE Id = $lessonId AND CourseId = $courseId")->fetch_assoc();
}

// Get all lessons
$lessons = $conn->query("SELECT * FROM Lessons WHERE CourseId = $courseId ORDER BY OrderNum ASC");
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
            <h1><?php echo $action === 'edit' ? 'Edit Lesson' : 'Create New Lesson'; ?></h1>
            <div>
                <a href="?course=<?php echo $courseId; ?>" class="btn btn-secondary">← Back to Lessons</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="?course=<?php echo $courseId; ?>&action=<?php echo $action; ?>&id=<?php echo $lessonId; ?>">
                <div class="form-group">
                    <label class="form-label">Lesson Title *</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $editLesson ? htmlspecialchars($editLesson['Title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Video URL (YouTube or direct link)</label>
                    <input type="url" name="video_url" class="form-control" 
                           placeholder="https://www.youtube.com/watch?v=..."
                           value="<?php echo $editLesson ? htmlspecialchars($editLesson['VideoUrl']) : ''; ?>">
                    <small style="color: var(--text-light);">YouTube, Vimeo, or direct video file URL</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Lesson Content</label>
                    <textarea name="content" class="form-control" rows="10"
                              placeholder="Write your lesson content here..."><?php echo $editLesson ? htmlspecialchars($editLesson['Content']) : ''; ?></textarea>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Estimated Duration (minutes)</label>
                        <input type="number" name="duration" class="form-control" min="1"
                               value="<?php echo $editLesson ? $editLesson['EstimatedDuration'] : '10'; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Order Number</label>
                        <input type="number" name="order_num" class="form-control" min="1"
                               value="<?php echo $editLesson ? $editLesson['OrderNum'] : ($lessons->num_rows + 1); ?>">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'edit' ? 'Update Lesson' : 'Create Lesson'; ?>
                    </button>
                    <a href="?course=<?php echo $courseId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- List View -->
        <div class="dashboard-header">
            <div>
                <a href="courses.php" style="color: var(--primary-blue); text-decoration: none;">← Back to Courses</a>
                <h1 style="margin-top: 0.5rem;">Lessons: <?php echo htmlspecialchars($course['Title']); ?></h1>
            </div>
            <a href="?course=<?php echo $courseId; ?>&action=create" class="btn btn-primary">+ Add Lesson</a>
        </div>

        <?php if ($lessons && $lessons->num_rows > 0): ?>
            <div class="card">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Video</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($lesson = $lessons->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $lesson['OrderNum']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($lesson['Title']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-light);">
                                        <?php echo htmlspecialchars(substr($lesson['Content'], 0, 60)) . '...'; ?>
                                    </small>
                                </td>
                                <td><?php echo $lesson['EstimatedDuration']; ?> min</td>
                                <td>
                                    <?php if ($lesson['VideoUrl']): ?>
                                        <span style="color: var(--success);">✓ Yes</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <a href="?course=<?php echo $courseId; ?>&action=edit&id=<?php echo $lesson['Id']; ?>" 
                                           class="btn btn-primary" style="padding: 0.5rem 1rem;">Edit</a>
                                        <a href="?course=<?php echo $courseId; ?>&delete=<?php echo $lesson['Id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this lesson?');"
                                           class="btn btn-danger" style="padding: 0.5rem 1rem;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
                <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">No Lessons Yet</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Start adding lessons to your course!
                </p>
                <a href="?course=<?php echo $courseId; ?>&action=create" class="btn btn-primary">Add First Lesson</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>