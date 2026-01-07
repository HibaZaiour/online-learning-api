<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Connect to DB
$conn = new mysqli("localhost", "root", "", "ids");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$conn->set_charset("utf8");

// Get difficulty filter if provided
$filterDifficulty = isset($_GET['difficulty']) && $_GET['difficulty'] !== '' ? $conn->real_escape_string($_GET['difficulty']) : null;

// Map filter values to database values: Easy->Beginner, Medium->Intermediate, Hard->Advanced
$dbDifficulty = null;
if ($filterDifficulty === 'Easy') {
    $dbDifficulty = 'Beginner';
} elseif ($filterDifficulty === 'Medium') {
    $dbDifficulty = 'Intermediate';
} elseif ($filterDifficulty === 'Hard') {
    $dbDifficulty = 'Advanced';
}

// Build query
$sql = "SELECT course_id, title, difficulty FROM course WHERE is_published = 1";

if ($dbDifficulty !== null) {
    $sql .= " AND difficulty = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dbDifficulty);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    // Map difficulty values: Beginner->Easy, Intermediate->Medium, Advanced->Hard
    $difficultyLevel = $row['difficulty'];
    if ($difficultyLevel === 'Beginner') {
        $difficultyLevel = 'Easy';
    } elseif ($difficultyLevel === 'Intermediate') {
        $difficultyLevel = 'Medium';
    } elseif ($difficultyLevel === 'Advanced') {
        $difficultyLevel = 'Hard';
    }
    
    $courses[] = [
        'course_id' => $row['course_id'],
        'title' => $row['title'],
        'difficulty' => $difficultyLevel
    ];
}

echo json_encode($courses);

$stmt->close();
$conn->close();
?>

