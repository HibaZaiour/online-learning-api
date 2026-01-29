<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Admin');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

// Approve Instructor
if (isset($_GET['approve'])) {
    $approveId = intval($_GET['approve']);
    $stmt = $conn->prepare("UPDATE Users SET IsApproved = 1, ApprovedBy = ?, ApprovalDate = NOW() WHERE Id = ? AND Role = 'Instructor'");
    $stmt->bind_param("ii", $userId, $approveId);
    if ($stmt->execute()) {
        $message = 'Instructor approved successfully! They can now login.';
    }
}

// Reject Instructor (Delete the account)
if (isset($_GET['reject'])) {
    $rejectId = intval($_GET['reject']);
    $conn->query("DELETE FROM Users WHERE Id = $rejectId AND Role = 'Instructor' AND IsApproved = 0");
    $message = 'Instructor application rejected and removed.';
}

// Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $checkEmail = $conn->query("SELECT Id FROM Users WHERE Email = '$email'");
        if ($checkEmail->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $isApproved = ($role === 'Instructor') ? 0 : 1; // Instructors need approval by default
            
            $stmt = $conn->prepare("INSERT INTO Users (FullName, Email, HashedPassword, Role, IsApproved, ApprovedBy, ApprovalDate) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssiii", $fullName, $email, $hashedPassword, $role, $isApproved, $userId);
            if ($stmt->execute()) {
                $message = 'User created successfully!' . ($role === 'Instructor' && !$isApproved ? ' (Pending approval)' : '');
                $action = 'list';
            }
        }
    }
}

// Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $newPassword = $_POST['new_password'];
    
    if (empty($fullName) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE Users SET FullName=?, Email=?, Role=?, HashedPassword=? WHERE Id=?");
            $stmt->bind_param("ssssi", $fullName, $email, $role, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE Users SET FullName=?, Email=?, Role=? WHERE Id=?");
            $stmt->bind_param("sssi", $fullName, $email, $role, $userId);
        }
        
        if ($stmt->execute()) {
            $message = 'User updated successfully!';
            $action = 'list';
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM Users WHERE Id = $deleteId");
    $message = 'User deleted successfully!';
    $action = 'list';
}

$page_title = 'Manage Users';
include '../includes/header.php';

// Get user for editing
$editUser = null;
if ($action === 'edit' && $userId > 0) {
    $editUser = $conn->query("SELECT * FROM Users WHERE Id = $userId")->fetch_assoc();
}

// Get all users with search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$statusFilter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

$sql = "SELECT * FROM Users WHERE 1=1";
if ($search) {
    $sql .= " AND (FullName LIKE '%$search%' OR Email LIKE '%$search%')";
}
if ($roleFilter) {
    $sql .= " AND Role = '$roleFilter'";
}
if ($statusFilter === 'pending') {
    $sql .= " AND Role = 'Instructor' AND IsApproved = 0";
}
$sql .= " ORDER BY IsApproved ASC, CreatedAt DESC";

$users = $conn->query($sql);
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
            <h1><?php echo $action === 'edit' ? 'Edit User' : 'Create New User'; ?></h1>
            <a href="users.php" class="btn btn-secondary">← Back</a>
        </div>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required
                           value="<?php echo $editUser ? htmlspecialchars($editUser['FullName']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo $editUser ? htmlspecialchars($editUser['Email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="Student" <?php echo ($editUser && $editUser['Role'] === 'Student') ? 'selected' : ''; ?>>Student</option>
                        <option value="Instructor" <?php echo ($editUser && $editUser['Role'] === 'Instructor') ? 'selected' : ''; ?>>Instructor</option>
                        <option value="Admin" <?php echo ($editUser && $editUser['Role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <?php echo $action === 'edit' ? 'New Password (leave blank to keep current)' : 'Password *'; ?>
                    </label>
                    <input type="password" name="<?php echo $action === 'edit' ? 'new_password' : 'password'; ?>" 
                           class="form-control" <?php echo $action === 'create' ? 'required' : ''; ?>>
                </div>

                <div class="flex gap-2">
                    <button type="submit" name="<?php echo $action === 'edit' ? 'update_user' : 'create_user'; ?>" class="btn btn-primary">
                        <?php echo $action === 'edit' ? 'Update User' : 'Create User'; ?>
                    </button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- List View -->
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <a href="?action=create" class="btn btn-primary">+ Create User</a>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <form method="GET">
                <div class="grid grid-4">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="Student" <?php echo $roleFilter === 'Student' ? 'selected' : ''; ?>>Student</option>
                            <option value="Instructor" <?php echo $roleFilter === 'Instructor' ? 'selected' : ''; ?>>Instructor</option>
                            <option value="Admin" <?php echo $roleFilter === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="filter" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>⚠️ Pending Approval</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <?php if ($users && $users->num_rows > 0): ?>
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['Id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['FullName']); ?></strong></td>
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
                                    <td>
                                        <div class="flex gap-1">
                                            <a href="?action=edit&id=<?php echo $user['Id']; ?>" 
                                               class="btn btn-primary" style="padding: 0.5rem 1rem;">Edit</a>
                                            <?php if ($user['Id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?php echo $user['Id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this user?');"
                                                   class="btn btn-danger" style="padding: 0.5rem 1rem;">Delete</a>
                                            <?php endif; ?>
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
                <p style="color: var(--text-light);">No users found.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>