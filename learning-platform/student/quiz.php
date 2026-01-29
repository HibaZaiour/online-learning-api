<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Student');

$userId = $_SESSION['user_id'];
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get quiz info
$quiz = $conn->query("SELECT q.*, c.Title as CourseTitle FROM Quizzes q 
                      JOIN Courses c ON q.CourseId = c.Id 
                      WHERE q.Id = $quizId")->fetch_assoc();

if (!$quiz) {
    header('Location: courses.php');
    exit();
}

// Check enrollment
$enrollment = $conn->query("SELECT * FROM Enrollments WHERE UserId = $userId AND CourseId = {$quiz['CourseId']}")->num_rows;
if ($enrollment == 0) {
    header('Location: courses.php');
    exit();
}

// Get questions
$questions = $conn->query("SELECT * FROM Questions WHERE QuizId = $quizId ORDER BY Id");

// Process quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $totalQuestions = $questions->num_rows;
    $questions->data_seek(0);
    
    while ($question = $questions->fetch_assoc()) {
        $questionId = $question['Id'];
        $correctAnswers = $conn->query("SELECT Id FROM Answers WHERE QuestionId = $questionId AND IsCorrect = 1");
        $correctIds = [];
        while ($ans = $correctAnswers->fetch_assoc()) {
            $correctIds[] = $ans['Id'];
        }
        
        $userAnswer = isset($_POST['question_' . $questionId]) ? $_POST['question_' . $questionId] : [];
        if (!is_array($userAnswer)) {
            $userAnswer = [$userAnswer];
        }
        
        // Check if answer is correct
        if (count($userAnswer) == count($correctIds) && empty(array_diff($userAnswer, array_map('strval', $correctIds)))) {
            $score++;
        }
    }
    
    $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;
    
    // Save attempt
    $conn->query("INSERT INTO QuizAttempts (QuizId, UserId, Score) VALUES ($quizId, $userId, $percentage)");
    
    // Check if passed and should generate certificate
    $passed = $percentage >= $quiz['PassingScore'];
    
    if ($passed) {
        // Check if all course requirements are met for certificate
        $courseId = $quiz['CourseId'];
        
        // Check all lessons completed
        $totalLessons = $conn->query("SELECT COUNT(*) as total FROM Lessons WHERE CourseId = $courseId")->fetch_assoc()['total'];
        $completedLessons = $conn->query("
            SELECT COUNT(DISTINCT lc.LessonId) as completed 
            FROM LessonCompletion lc 
            JOIN Lessons l ON lc.LessonId = l.Id 
            WHERE l.CourseId = $courseId AND lc.UserId = $userId
        ")->fetch_assoc()['completed'];
        
        // Check all quizzes passed
        $courseQuizzes = $conn->query("SELECT COUNT(*) as total FROM Quizzes WHERE CourseId = $courseId")->fetch_assoc()['total'];
        $passedQuizzes = $conn->query("
            SELECT COUNT(DISTINCT qa.QuizId) as passed 
            FROM QuizAttempts qa 
            JOIN Quizzes q ON qa.QuizId = q.Id 
            WHERE q.CourseId = $courseId 
            AND qa.UserId = $userId 
            AND qa.Score >= q.PassingScore
        ")->fetch_assoc()['passed'];
        
        // Generate certificate if all requirements met
        if ($completedLessons >= $totalLessons && $passedQuizzes >= $courseQuizzes && $totalLessons > 0) {
            $certExists = $conn->query("SELECT * FROM Certificates WHERE CourseId = $courseId AND UserId = $userId")->num_rows;
            if ($certExists == 0) {
                $conn->query("INSERT INTO Certificates (CourseId, UserId) VALUES ($courseId, $userId)");
            }
        }
    }
    
    header("Location: quiz.php?id=$quizId&result=1&score=$percentage&passed=" . ($passed ? '1' : '0'));
    exit();
}

// Show results if redirected after submission
$showResults = isset($_GET['result']);
$resultScore = isset($_GET['score']) ? intval($_GET['score']) : 0;
$resultPassed = isset($_GET['passed']) && $_GET['passed'] == '1';

$page_title = $quiz['Title'];
include '../includes/header.php';

$questions->data_seek(0);
?>

<div class="container quiz-container">
    <?php if ($showResults): ?>
        <!-- Results -->
        <div class="card text-center">
            <div style="font-size: 4rem; margin-bottom: 1rem;">
                <?php echo $resultPassed ? '🎉' : '📝'; ?>
            </div>
            <h1 style="color: var(--primary-blue); margin-bottom: 1rem;">
                <?php echo $resultPassed ? 'Congratulations!' : 'Keep Practicing!'; ?>
            </h1>
            <p style="font-size: 1.5rem; margin-bottom: 1rem;">
                Your Score: <strong style="color: var(--primary-blue);"><?php echo $resultScore; ?>%</strong>
            </p>
            <p style="color: var(--text-light); margin-bottom: 2rem;">
                Passing Score: <?php echo $quiz['PassingScore']; ?>%
            </p>
            
            <?php if ($resultPassed): ?>
                <div class="alert alert-success">
                    ✓ You passed the quiz! Great job!
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    You need <?php echo $quiz['PassingScore']; ?>% to pass. Review the lesson and try again!
                </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                <a href="lesson.php?course=<?php echo $quiz['CourseId']; ?>" class="btn btn-primary">
                    Back to Course
                </a>
                <?php if (!$resultPassed): ?>
                    <a href="quiz.php?id=<?php echo $quizId; ?>" class="btn btn-secondary">
                        Retake Quiz
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Quiz Taking Interface -->
        <div class="card">
            <div class="flex-between mb-3">
                <div>
                    <h1 style="color: var(--primary-blue); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($quiz['Title']); ?></h1>
                    <p style="color: var(--text-light);"><?php echo htmlspecialchars($quiz['CourseTitle']); ?></p>
                </div>
                <?php if ($quiz['TimeLimit']): ?>
                    <div style="text-align: right;">
                        <div style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.25rem;">Time Remaining</div>
                        <div id="timer" style="font-size: 1.5rem; font-weight: 600; color: var(--primary-blue);">
                            <span id="minutes"><?php echo $quiz['TimeLimit']; ?></span>:<span id="seconds">00</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info">
                Passing Score: <?php echo $quiz['PassingScore']; ?>% | 
                Questions: <?php echo $questions->num_rows; ?>
            </div>
        </div>

        <form method="POST" id="quizForm">
            <?php 
            $qNum = 1;
            while ($question = $questions->fetch_assoc()): 
                $answers = $conn->query("SELECT * FROM Answers WHERE QuestionId = {$question['Id']}");
            ?>
                <div class="question-card">
                    <div class="question-number">Question <?php echo $qNum++; ?> of <?php echo $questions->num_rows; ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question['QuestionText']); ?></div>
                    
                    <?php while ($answer = $answers->fetch_assoc()): ?>
                        <label class="answer-option">
                            <?php if ($question['QuestionType'] === 'MSQ'): ?>
                                <input type="checkbox" name="question_<?php echo $question['Id']; ?>[]" 
                                       value="<?php echo $answer['Id']; ?>">
                            <?php else: ?>
                                <input type="radio" name="question_<?php echo $question['Id']; ?>" 
                                       value="<?php echo $answer['Id']; ?>" required>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($answer['AnswerText']); ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
            <?php endwhile; ?>
            
            <div class="card">
                <div class="flex-between">
                    <a href="lesson.php?course=<?php echo $quiz['CourseId']; ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" name="submit_quiz" class="btn btn-success">
                        Submit Quiz
                    </button>
                </div>
            </div>
        </form>

        <?php if ($quiz['TimeLimit']): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    startQuizTimer(<?php echo $quiz['TimeLimit'] * 60; ?>, document.getElementById('timer'));
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>