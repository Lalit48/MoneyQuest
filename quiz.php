<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    $quiz_id = $_POST['quiz_id'];
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    $total_questions = 0;
    
    try {
        $conn = getConnection();
        
        // Get correct answers
        $stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($questions as $question) {
            $total_questions++;
            if (isset($answers[$question['id']]) && $answers[$question['id']] === $question['correct_option']) {
                $score++;
            }
        }
        
        $percentage = ($score / $total_questions) * 100;
        $points_earned = round($percentage * 10); // 10 points per question
        $wallet_bonus = round($percentage * 5); // $5 per question
        
        // Update user stats
        $stmt = $conn->prepare("UPDATE users SET points = points + ?, wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$points_earned, $wallet_bonus, $user_id]);
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'quiz_reward', ?, ?)");
        $stmt->execute([$user_id, $wallet_bonus, "Quiz completion reward - Score: $score/$total_questions"]);
        
        // Update session
        $_SESSION['points'] += $points_earned;
        $_SESSION['wallet_balance'] += $wallet_bonus;
        
        $success = "Quiz completed! Score: $score/$total_questions ($percentage%) - Earned $points_earned points and $$wallet_bonus";
        
    } catch (Exception $e) {
        $error = 'Quiz submission failed: ' . $e->getMessage();
    }
}

// Get available quizzes
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT quiz_id, title, category FROM quizzes ORDER BY category, title");
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Failed to load quizzes: ' . $e->getMessage();
    $quizzes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - MoneyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/cursor.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .quiz-container {
            padding: 20px 0;
        }
        
        .quiz-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .option-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 15px;
            margin: 5px 0;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .option-btn:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .option-btn.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: bold;
            color: white;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .progress-bar {
            height: 10px;
            border-radius: 5px;
            background: #e9ecef;
            margin-bottom: 20px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 5px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-coins me-2"></i>MoneyQuest
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user-circle me-1"></i>Profile
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container quiz-container">
        <div class="row">
            <div class="col-12">
                <h2 class="text-white mb-4">
                    <i class="fas fa-question-circle me-2"></i>Financial Quizzes
                </h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Quiz Selection -->
                <div class="quiz-card">
                    <h4><i class="fas fa-list me-2"></i>Select a Quiz</h4>
                    <div class="row">
                        <?php foreach ($quizzes as $quiz): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($quiz['category']); ?></p>
                                        <button class="btn btn-primary btn-sm" onclick="loadQuiz(<?php echo $quiz['quiz_id']; ?>)">
                                            Start Quiz
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Quiz Questions -->
                <div id="quiz-questions" class="quiz-card" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 id="quiz-title"></h4>
                        <span id="question-counter" class="badge bg-primary"></span>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    
                    <form id="quiz-form" method="POST">
                        <input type="hidden" name="quiz_id" id="quiz-id">
                        <div id="questions-container"></div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" name="submit_quiz" class="btn btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Submit Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentQuestion = 0;
        let totalQuestions = 0;
        
        function loadQuiz(quizId) {
            $.ajax({
                url: 'api/get_quiz_questions.php',
                method: 'GET',
                data: { quiz_id: quizId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        displayQuiz(data.quiz, data.questions);
                    } else {
                        alert('Failed to load quiz: ' + data.message);
                    }
                },
                error: function() {
                    alert('Failed to load quiz');
                }
            });
        }
        
        function displayQuiz(quiz, questions) {
            $('#quiz-title').text(quiz.title);
            $('#quiz-id').val(quiz.quiz_id);
            $('#questions-container').empty();
            
            totalQuestions = questions.length;
            currentQuestion = 0;
            
            questions.forEach(function(question, index) {
                const questionHtml = `
                    <div class="question-card">
                        <h5>Question ${index + 1}</h5>
                        <p class="mb-3">${question.question_text}</p>
                        <div class="options">
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="A">
                                <strong>A.</strong> ${question.option_a}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="B">
                                <strong>B.</strong> ${question.option_b}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="C">
                                <strong>C.</strong> ${question.option_c}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="D">
                                <strong>D.</strong> ${question.option_d}
                            </button>
                        </div>
                        <input type="hidden" name="answers[${question.id}]" id="answer-${question.id}">
                    </div>
                `;
                $('#questions-container').append(questionHtml);
            });
            
            updateProgress();
            $('#quiz-questions').show();
            $('html, body').animate({ scrollTop: $('#quiz-questions').offset().top }, 500);
        }
        
        function updateProgress() {
            const answered = $('input[name^="answers"]').filter(function() {
                return $(this).val() !== '';
            }).length;
            
            const percentage = (answered / totalQuestions) * 100;
            $('#progress-fill').css('width', percentage + '%');
            $('#question-counter').text(`${answered}/${totalQuestions} answered`);
        }
        
        $(document).ready(function() {
            // Handle option selection
            $(document).on('click', '.option-btn', function() {
                const questionId = $(this).data('question');
                const option = $(this).data('option');
                
                // Remove selected class from other options in this question
                $(this).siblings('.option-btn').removeClass('selected');
                $(this).addClass('selected');
                
                // Set the hidden input value
                $(`#answer-${questionId}`).val(option);
                
                updateProgress();
            });
        });
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
