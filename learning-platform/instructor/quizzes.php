<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireRole('Instructor');

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course']) ? intval($_GET['course']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Verify course ownership
$course = $conn->query("SELECT * FROM Courses WHERE Id = $courseId AND CreatedBy = $userId")->fetch_assoc();
if (!$course) {
    header('Location: courses.php');
    exit();
}

// Create Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $title = sanitize($_POST['title']);
    $passingScore = intval($_POST['passing_score']);
    $timeLimit = intval($_POST['time_limit']);
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        $stmt = $conn->prepare("INSERT INTO Quizzes (CourseId, Title, PassingScore, TimeLimit) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $courseId, $title, $passingScore, $timeLimit);
        if ($stmt->execute()) {
            $quizId = $conn->insert_id;
            $message = 'Quiz created! Now add questions.';
            $action = 'manage';
        }
    }
}

// Add Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $questionText = sanitize($_POST['question_text']);
    $questionType = sanitize($_POST['question_type']);
    
    if (empty($questionText)) {
        $error = 'Question text is required';
    } else {
        $stmt = $conn->prepare("INSERT INTO Questions (QuizId, QuestionText, QuestionType) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $quizId, $questionText, $questionType);
        if ($stmt->execute()) {
            $questionId = $conn->insert_id;
            
            // Add answers
            if (isset($_POST['answers']) && is_array($_POST['answers'])) {
                foreach ($_POST['answers'] as $index => $answerText) {
                    if (!empty($answerText)) {
                        $isCorrect = isset($_POST['correct_answers']) && in_array($index, $_POST['correct_answers']) ? 1 : 0;
                        $ansStmt = $conn->prepare("INSERT INTO Answers (QuestionId, AnswerText, IsCorrect) VALUES (?, ?, ?)");
                        $ansStmt->bind_param("isi", $questionId, $answerText, $isCorrect);
                        $ansStmt->execute();
                    }
                }
            }
            
            $message = 'Question added successfully!';
        }
    }
}

// Delete Quiz
if (isset($_GET['delete_quiz'])) {
    $deleteId = intval($_GET['delete_quiz']);
    $conn->query("DELETE FROM Quizzes WHERE Id = $deleteId AND CourseId = $courseId");
    $message = 'Quiz deleted successfully!';
    $action = 'list';
}

// Delete Question
if (isset($_GET['delete_question'])) {
    $deleteQId = intval($_GET['delete_question']);
    $conn->query("DELETE FROM Questions WHERE Id = $deleteQId");
    $message = 'Question deleted successfully!';
}

$page_title = 'Manage Quizzes - ' . $course['Title'];
include '../includes/header.php';

// Get all quizzes
$quizzes = $conn->query("SELECT q.*, 
                         (SELECT COUNT(*) FROM Questions WHERE QuizId = q.Id) as QuestionCount
                         FROM Quizzes q 
                         WHERE q.CourseId = $courseId 
                         ORDER BY q.Id DESC");

// Get quiz details for manage view
$currentQuiz = null;
$questions = null;
if ($action === 'manage' && $quizId > 0) {
    $currentQuiz = $conn->query("SELECT * FROM Quizzes WHERE Id = $quizId AND CourseId = $courseId")->fetch_assoc();
    if ($currentQuiz) {
        $questions = $conn->query("SELECT * FROM Questions WHERE QuizId = $quizId ORDER BY Id");
    }
}
?>

<div class="container dashboard">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action === 'create'): ?>
        <!-- Create Quiz Form -->
        <div class="dashboard-header">
            <h1>Create New Quiz</h1>
            <a href="?course=<?php echo $courseId; ?>" class="btn btn-secondary">← Back</a>
        </div>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Quiz Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Passing Score (%)</label>
                        <input type="number" name="passing_score" class="form-control" min="0" max="100" value="70" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time Limit (minutes, 0 = unlimited)</label>
                        <input type="number" name="time_limit" class="form-control" min="0" value="0">
                    </div>
                </div>

                <button type="submit" name="create_quiz" class="btn btn-primary">Create Quiz</button>
            </form>
        </div>

    <?php elseif ($action === 'manage' && $currentQuiz): ?>
        <!-- Manage Quiz Questions -->
        <div class="dashboard-header">
            <div>
                <a href="?course=<?php echo $courseId; ?>" style="color: var(--primary-blue); text-decoration: none;">← Back to Quizzes</a>
                <h1 style="margin-top: 0.5rem;"><?php echo htmlspecialchars($currentQuiz['Title']); ?></h1>
                <p style="color: var(--text-light);">
                    Passing Score: <?php echo $currentQuiz['PassingScore']; ?>% | 
                    Time Limit: <?php echo $currentQuiz['TimeLimit'] > 0 ? $currentQuiz['TimeLimit'] . ' min' : 'Unlimited'; ?>
                </p>
            </div>
        </div>

        <!-- Add Question Form -->
        <div class="card mb-3">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Add New Question</h3>
            <form method="POST" id="questionForm">
                <div class="form-group">
                    <label class="form-label">Question Text *</label>
                    <textarea name="question_text" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Question Type</label>
                    <select name="question_type" class="form-control" id="questionType" onchange="updateAnswerType()">
                        <option value="MCQ">Multiple Choice (Single Answer)</option>
                        <option value="MSQ">Multiple Select (Multiple Answers)</option>
                        <option value="TF">True/False</option>
                    </select>
                </div>

                <div id="answersContainer">
                    <label class="form-label">Answer Options</label>
                    <div id="answersList">
                        <div class="answer-input mb-2">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="checkbox" name="correct_answers[]" value="0">
                                <input type="text" name="answers[]" class="form-control" placeholder="Answer option" required>
                            </div>
                        </div>
                        <div class="answer-input mb-2">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="checkbox" name="correct_answers[]" value="1">
                                <input type="text" name="answers[]" class="form-control" placeholder="Answer option" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addAnswer()" id="addAnswerBtn">+ Add Answer Option</button>
                </div>

                <div class="alert alert-info mt-2">
                    ℹ️ Check the box next to correct answer(s)
                </div>

                <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <!-- Existing Questions -->
        <?php if ($questions && $questions->num_rows > 0): ?>
            <div class="card">
                <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Questions</h3>
                <?php 
                $qNum = 1;
                while ($question = $questions->fetch_assoc()): 
                    $answers = $conn->query("SELECT * FROM Answers WHERE QuestionId = {$question['Id']}");
                ?>
                    <div style="padding: 1.5rem; background: #F8FBFD; border-radius: 8px; margin-bottom: 1rem;">
                        <div class="flex-between mb-2">
                            <strong style="color: var(--primary-blue);">Question <?php echo $qNum++; ?></strong>
                            <div>
                                <span class="badge" style="background: #EBF5FB; color: #2E86C1;">
                                    <?php echo $question['QuestionType']; ?>
                                </span>
                                <a href="?course=<?php echo $courseId; ?>&action=manage&id=<?php echo $quizId; ?>&delete_question=<?php echo $question['Id']; ?>" 
                                   onclick="return confirm('Delete this question?');"
                                   class="btn btn-danger" style="padding: 0.25rem 0.75rem; margin-left: 0.5rem;">Delete</a>
                            </div>
                        </div>
                        
                        <p style="margin-bottom: 1rem;"><?php echo htmlspecialchars($question['QuestionText']); ?></p>
                        
                        <div style="padding-left: 1rem;">
                            <?php while ($answer = $answers->fetch_assoc()): ?>
                                <div style="padding: 0.5rem; margin-bottom: 0.5rem; background: white; border-radius: 4px; 
                                            border-left: 3px solid <?php echo $answer['IsCorrect'] ? 'var(--success)' : 'var(--light-blue)'; ?>;">
                                    <?php echo $answer['IsCorrect'] ? '✓' : '○'; ?> 
                                    <?php echo htmlspecialchars($answer['AnswerText']); ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <p style="color: var(--text-light);">No questions added yet. Create your first question above!</p>
            </div>
        <?php endif; ?>

        <script>
        let answerCount = 2;
        function addAnswer() {
            const list = document.getElementById('answersList');
            const div = document.createElement('div');
            div.className = 'answer-input mb-2';
            div.innerHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="checkbox" name="correct_answers[]" value="${answerCount}">
                    <input type="text" name="answers[]" class="form-control" placeholder="Answer option" required>
                </div>
            `;
            list.appendChild(div);
            answerCount++;
        }

        function updateAnswerType() {
            const type = document.getElementById('questionType').value;
            const checkboxes = document.querySelectorAll('#answersList input[type="checkbox"]');
            if (type === 'TF') {
                document.getElementById('answersContainer').innerHTML = `
                    <label class="form-label">Answer Options</label>
                    <div id="answersList">
                        <div class="answer-input mb-2">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="radio" name="correct_answers[]" value="0" required>
                                <input type="text" name="answers[]" class="form-control" value="True" readonly>
                            </div>
                        </div>
                        <div class="answer-input mb-2">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="radio" name="correct_answers[]" value="1" required>
                                <input type="text" name="answers[]" class="form-control" value="False" readonly>
                            </div>
                        </div>
                    </div>
                `;
                answerCount = 2;
            } else if (type === 'MCQ') {
                checkboxes.forEach(cb => cb.type = 'radio');
            } else {
                checkboxes.forEach(cb => cb.type = 'checkbox');
            }
        }
        </script>

    <?php else: ?>
        <!-- List View -->
        <div class="dashboard-header">
            <div>
                <a href="courses.php" style="color: var(--primary-blue); text-decoration: none;">← Back to Courses</a>
                <h1 style="margin-top: 0.5rem;">Quizzes: <?php echo htmlspecialchars($course['Title']); ?></h1>
            </div>
            <a href="?course=<?php echo $courseId; ?>&action=create" class="btn btn-primary">+ Create Quiz</a>
        </div>

        <?php if ($quizzes && $quizzes->num_rows > 0): ?>
            <div class="grid grid-2">
                <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                    <div class="card">
                        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($quiz['Title']); ?>
                        </h3>
                        
                        <div style="margin-bottom: 1rem; color: var(--text-light);">
                            <div>📝 <?php echo $quiz['QuestionCount']; ?> questions</div>
                            <div>🎯 Passing Score: <?php echo $quiz['PassingScore']; ?>%</div>
                            <div>⏱️ Time Limit: <?php echo $quiz['TimeLimit'] > 0 ? $quiz['TimeLimit'] . ' min' : 'Unlimited'; ?></div>
                        </div>
                        
                        <div class="flex gap-1">
                            <a href="?course=<?php echo $courseId; ?>&action=manage&id=<?php echo $quiz['Id']; ?>" 
                               class="btn btn-primary" style="flex: 1;">Manage Questions</a>
                            <a href="?course=<?php echo $courseId; ?>&delete_quiz=<?php echo $quiz['Id']; ?>" 
                               onclick="return confirm('Delete this quiz and all its questions?');"
                               class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
                <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">No Quizzes Yet</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Create quizzes to test your students' knowledge!
                </p>
                <a href="?course=<?php echo $courseId; ?>&action=create" class="btn btn-primary">Create First Quiz</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>