<?php
require_once 'config/database.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $stmt = $conn->prepare("SELECT Id, FullName, Email, HashedPassword, Role, IsApproved FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['HashedPassword'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['Id'];
                $_SESSION['user_name'] = $user['FullName'];
                $_SESSION['user_email'] = $user['Email'];
                $_SESSION['role'] = $user['Role'];
                
                // Redirect based on role
                switch ($user['Role']) {
                    case 'Admin':
                        header('Location: admin/index.php');
                        break;
                    case 'Instructor':
                        header('Location: instructor/index.php');
                        break;
                    default:
                        header('Location: student/index.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back!</h1>
                <p>Login to continue your learning journey</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                
                <p class="text-center mt-3">
                    Don't have an account? <a href="register.php" style="color: var(--primary-blue); font-weight: 600;">Register here</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>