<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Student');

$userId = $_SESSION['user_id'];
$certId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get certificate
$cert = $conn->query("SELECT c.*, co.Title as CourseTitle, u.FullName as StudentName 
                      FROM Certificates c 
                      JOIN Courses co ON c.CourseId = co.Id 
                      JOIN Users u ON c.UserId = u.Id 
                      WHERE c.Id = $certId AND c.UserId = $userId")->fetch_assoc();

if (!$cert) {
    header('Location: index.php');
    exit();
}

$date = date('F d, Y', strtotime($cert['GeneratedAt']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($cert['CourseTitle']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .certificate-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 3rem;
            background: white;
            border: 10px solid var(--primary-blue);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 3px solid var(--light-blue);
        }
        .certificate-logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .certificate-title {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .certificate-body {
            text-align: center;
            padding: 2rem;
        }
        .student-name {
            font-size: 2rem;
            color: var(--text-dark);
            font-weight: bold;
            margin: 2rem 0;
            padding: 1rem;
            background: var(--light-blue);
            border-radius: 10px;
        }
        .course-title {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin: 1.5rem 0;
        }
        .certificate-footer {
            display: flex;
            justify-content: space-around;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 3px solid var(--light-blue);
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            width: 200px;
            border-top: 2px solid var(--text-dark);
            margin: 1rem auto 0.5rem;
        }
        @media print {
            .no-print {
                display: none;
            }
            .certificate-container {
                border: 10px solid var(--primary-blue);
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-header">
            <div class="certificate-logo">🎓</div>
            <h1 class="certificate-title">CERTIFICATE OF COMPLETION</h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">Online Learning Platform</p>
        </div>
        
        <div class="certificate-body">
            <p style="font-size: 1.2rem; color: var(--text-light); margin-bottom: 1rem;">
                This certifies that
            </p>
            
            <div class="student-name">
                <?php echo htmlspecialchars($cert['StudentName']); ?>
            </div>
            
            <p style="font-size: 1.2rem; color: var(--text-light); margin-bottom: 1rem;">
                has successfully completed
            </p>
            
            <div class="course-title">
                <?php echo htmlspecialchars($cert['CourseTitle']); ?>
            </div>
            
            <p style="font-size: 1.1rem; color: var(--text-light); margin-top: 2rem;">
                Completed on: <strong><?php echo $date; ?></strong>
            </p>
            
            <p style="font-size: 0.9rem; color: var(--text-light); margin-top: 1rem;">
                Certificate ID: <?php echo str_pad($cert['Id'], 8, '0', STR_PAD_LEFT); ?>
            </p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature">
                <div style="font-size: 2rem;">✍️</div>
                <div class="signature-line"></div>
                <p style="font-weight: 600;">Instructor Signature</p>
            </div>
            <div class="signature">
                <div style="font-size: 2rem;">✅</div>
                <div class="signature-line"></div>
                <p style="font-weight: 600;">Platform Director</p>
            </div>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin: 2rem;">
        <button onclick="window.print()" class="btn btn-primary">Print Certificate</button>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>