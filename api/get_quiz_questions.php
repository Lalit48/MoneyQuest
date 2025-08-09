<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_GET['quiz_id'])) {
    echo json_encode(['success' => false, 'message' => 'Quiz ID required']);
    exit();
}

try {
    $conn = getConnection();
    $quiz_id = $_GET['quiz_id'];
    
    // Get quiz details
    $stmt = $conn->prepare("SELECT quiz_id, title, category FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found']);
        exit();
    }
    
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get questions
    $stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'quiz' => $quiz,
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 