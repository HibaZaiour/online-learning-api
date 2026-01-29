<?php
require_once 'config/database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    
    // Validation
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Check if email exists
        $checkEmail = $conn->prepare("SELECT Id FROM Users WHERE Email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password and insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Set approval status based on role
            $isApproved = ($role === 'Instructor') ? 0 : 1; // Instructors need approval
            $stmt = $conn->prepare("INSERT INTO Users (FullName, Email, HashedPassword, Role, IsApproved) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $fullName, $email, $hashedPassword, $role, $isApproved);
            
            if ($stmt->execute()) {
              if ($role === 'Instructor') {
                    $success = 'Registration successful! Your instructor account is pending approval. You will be able to login once an admin approves your account.';
                } else {
                    $success = 'Registration successful! You can now login.';
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join our learning community today</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="login.php">Click here to login</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required 
                           minlength="6" placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">I want to:</label>
                    <select name="role" class="form-control" required>
                        <option value="Student">Learn (Student)</option>
                        <option value="Instructor">Teach (Instructor)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
                
                <p class="text-center mt-3">
                    Already have an account? <a href="login.php" style="color: var(--primary-blue); font-weight: 600;">Login here</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>