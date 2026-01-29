<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Admin');

$message = '';

// Toggle publish
if (isset($_GET['toggle_publish'])) {
    $courseId = intval($_GET['toggle_publish']);
    $conn->query("UPDATE Courses SET IsPublished = NOT IsPublished WHERE Id = $courseId");
    $message = 'Course status updated!';
}

// Delete course
if (isset($_GET['delete'])) {
    $courseId = intval($_GET['delete']);
    $conn->query("DELETE FROM Courses WHERE Id = $courseId");
    $message = 'Course deleted successfully!';
}

$page_title = 'Manage Courses';
include '../includes/header.php';

// Get all courses with filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "SELECT c.*, u.FullName as InstructorName,
        (SELECT COUNT(*) FROM Enrollments WHERE CourseId = c.Id) as EnrollmentCount,
        (SELECT COUNT(*) FROM Lessons WHERE CourseId = c.Id) as LessonCount
        FROM Courses c
        JOIN Users u ON c.CreatedBy = u.Id
        WHERE 1=1";

if ($search) {
    $sql .= " AND (c.Title LIKE '%$search%' OR u.FullName LIKE '%$search%')";
}
if ($status === 'published') {
    $sql .= " AND c.IsPublished = 1";
} elseif ($status === 'draft') {
    $sql .= " AND c.IsPublished = 0";
}

$sql .= " ORDER BY c.CreatedAt DESC";
$courses = $conn->query($sql);
?>

<div class="container dashboard">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Manage All Courses</h1>
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <form method="GET">
            <div class="grid grid-3">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search courses or instructors" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Courses Table -->
    <?php if ($courses && $courses->num_rows > 0): ?>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Enrollments</th>
                            <th>Lessons</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $course['Id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['Title']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-light);">
                                        <?php echo htmlspecialchars(substr($course['ShortDescription'], 0, 50)) . '...'; ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($course['InstructorName']); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $course['IsPublished'] ? '#D5F4E6' : '#FADBD8'; ?>; 
                                                                            color: <?php echo $course['IsPublished'] ? '#27AE60' : '#C0392B'; ?>;">
                                        <?php echo $course['IsPublished'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                    <br>
                                    <span class="badge badge-<?php echo strtolower($course['Difficulty']); ?>">
                                        <?php echo $course['Difficulty']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $course['EnrollmentCount']; ?></strong></td>
                                <td><?php echo $course['LessonCount']; ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <a href="?toggle_publish=<?php echo $course['Id']; ?>" 
                                           class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                            <?php echo $course['IsPublished'] ? 'Unpublish' : 'Publish'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $course['Id']; ?>" 
                                           onclick="return confirm('Are you sure? This will delete all lessons and quizzes!');"
                                           class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card text-center">
            <p style="color: var(--text-light);">No courses found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>