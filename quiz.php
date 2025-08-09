<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$wallet_balance = $_SESSION['wallet_balance'];
$points = $_SESSION['points'];
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
    <title>Smart Quizzes - MoneyQuest</title>
    <meta name="description" content="Test your financial knowledge with interactive quizzes and earn rewards">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* Simplified and reliable CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }
        
        .nav-link {
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nav-btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        /* Mobile menu */
        .mobile-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: rgba(2, 6, 23, 0.95);
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .mobile-nav.active {
            display: flex;
        }
        
        .hamburger {
            display: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .desktop-nav { display: none !important; }
            .hamburger { display: block !important; }
        }
        
        /* Main content */
        .main-content {
            margin-top: 100px;
            padding: 2rem 1rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Quiz container */
        .quiz-container {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        /* Quiz cards */
        .quiz-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            height: auto;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        
        .quiz-icon {
            font-size: 3rem;
            color: #6366f1;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .quiz-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .quiz-description {
            color: #94a3b8;
            margin-bottom: 1.5rem;
            text-align: center;
            flex-grow: 1;
        }
        
        .quiz-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .start-button {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        
        .start-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-new {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .badge-category {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .stats-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .stats-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #94a3b8;
            font-weight: 500;
        }
        
        .stats-description {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        /* Question styles */
        .question-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid #6366f1;
        }
        
        .option-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 1rem 1.5rem;
            margin: 0.5rem 0;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .option-btn:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .option-btn.selected {
            border-color: #6366f1;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        /* Progress bar */
        .progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 5px;
            transition: width 0.5s ease;
            width: 0%;
        }
        
        /* Submit button */
        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: bold;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: #86efac;
        }
        
        /* Footer */
        .footer {
            background: #1e293b;
            padding: 3rem 1rem;
            margin-top: 3rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #ffffff;
        }
        
        .footer-link {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        
        .footer-link:hover {
            color: #ffffff;
        }
        
        .footer-bottom {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #64748b;
        }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mt-4 { margin-top: 1rem; }
        .mr-2 { margin-right: 0.5rem; }
        .mr-3 { margin-right: 0.75rem; }
        
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        @media (min-width: 768px) {
            .md\\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        .flex {
            display: flex;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .space-x-2 > * + * {
            margin-left: 0.5rem;
        }
        
        .space-x-4 > * + * {
            margin-left: 1rem;
        }
        
        .hidden {
            display: none;
        }
        
        @media (min-width: 768px) {
            .md\\:flex {
                display: flex;
            }
            .md\\:hidden {
                display: none;
            }
        }
        
        /* Loading spinner */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top: 3px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Ensure visibility */
        .quiz-card,
        .stats-card,
        .quiz-container,
        .main-content {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }
        
        .grid {
            display: grid !important;
        }
        
        /* Timer */
        .quiz-timer {
            position: fixed;
            top: 80px;
            right: 20px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 1rem;
            z-index: 500;
            display: none;
        }
        
        .timer-display {
            font-family: monospace;
            font-size: 1.25rem;
            font-weight: 600;
            color: #6366f1;
        }
        
        @media (max-width: 768px) {
            .quiz-timer {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 1rem;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="main-content" style="margin-top: 0; padding: 0 1rem;">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-coins text-2xl text-indigo-400"></i>
                        <span class="text-xl font-bold gradient-text">MoneyQuest</span>
                    </div>
                </div>
                
                <div class="hidden md:flex space-x-4 desktop-nav">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="stocks.php" class="nav-link">
                        <i class="fas fa-chart-line mr-2"></i>Stocks
                    </a>
                    <a href="leaderboard.php" class="nav-link">
                        <i class="fas fa-trophy mr-2"></i>Leaderboard
                    </a>
                    <a href="achievements.php" class="nav-link">
                        <i class="fas fa-medal mr-2"></i>Achievements
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="nav-btn-secondary hidden md:flex">
                        <i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($name); ?>
                    </a>
                    <a href="logout.php" class="nav-btn-primary hidden md:flex">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                    <button class="hamburger md:hidden" onclick="toggleMobileNav()">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="mobile-nav" id="mobile-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="stocks.php" class="nav-link">
                    <i class="fas fa-chart-line mr-2"></i>Stocks
                </a>
                <a href="leaderboard.php" class="nav-link">
                    <i class="fas fa-trophy mr-2"></i>Leaderboard
                </a>
                <a href="achievements.php" class="nav-link">
                    <i class="fas fa-medal mr-2"></i>Achievements
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user-circle mr-2"></i>Profile
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Quiz Timer -->
    <div class="quiz-timer" id="quiz-timer">
        <div class="flex items-center space-x-2">
            <i class="fas fa-clock"></i>
            <span class="timer-display" id="timer-display">30:00</span>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                <span class="gradient-text">Financial Quizzes</span>
            </h1>
            <p class="text-xl text-gray-300">
                Test your knowledge and earn rewards
            </p>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-3"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Quiz Selection -->
        <div class="quiz-container" id="quiz-selection">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold mb-4">
                    <i class="fas fa-brain mr-3 text-indigo-400"></i>
                    <span class="gradient-text">Select a Quiz</span>
                </h2>
                <div style="width: 80px; height: 4px; background: linear-gradient(90deg, #6366f1, #8b5cf6); margin: 0 auto; border-radius: 2px;"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php 
                // Quiz data with enhanced descriptions
                $quiz_display_order = [
                    [
                        'title' => 'Budgeting Basics', 
                        'category' => 'Budgeting', 
                        'is_new' => true,
                        'description' => 'Master essential budgeting skills to take control of your finances',
                        'difficulty' => 'Beginner',
                        'estimated_time' => '5 min'
                    ],
                    [
                        'title' => 'Investment Fundamentals', 
                        'category' => 'Investing', 
                        'is_new' => true,
                        'description' => 'Discover the principles of smart investing and wealth building',
                        'difficulty' => 'Intermediate',
                        'estimated_time' => '7 min'
                    ],
                    [
                        'title' => 'Smart Saving Strategies', 
                        'category' => 'Saving', 
                        'is_new' => false,
                        'description' => 'Master proven saving techniques to secure your financial future',
                        'difficulty' => 'Beginner',
                        'estimated_time' => '4 min'
                    ]
                ];
                
                foreach ($quiz_display_order as $index => $display_quiz):
                    // Find matching quiz from database
                    $current_quiz = null;
                    foreach ($quizzes as $quiz) {
                        if ($quiz['title'] === $display_quiz['title']) {
                            $current_quiz = $quiz;
                            break;
                        }
                    }
                    if (!$current_quiz) continue;
                ?>
                    <div class="quiz-card" onclick="loadQuiz(<?php echo $current_quiz['quiz_id']; ?>)">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <?php if ($display_quiz['is_new']): ?>
                                    <span class="badge badge-new">NEW</span>
                                <?php endif; ?>
                                <span class="badge badge-category"><?php echo htmlspecialchars($current_quiz['category']); ?></span>
                            </div>
                            
                            <div class="quiz-icon">
                                <?php 
                                $icons = [
                                    'Budgeting' => 'fas fa-wallet',
                                    'Investing' => 'fas fa-chart-line',
                                    'Saving' => 'fas fa-piggy-bank',
                                    'Credit' => 'fas fa-credit-card',
                                    'Insurance' => 'fas fa-shield-alt',
                                    'Taxes' => 'fas fa-calculator'
                                ];
                                $icon = $icons[$current_quiz['category']] ?? 'fas fa-brain';
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            
                            <h3 class="quiz-title"><?php echo htmlspecialchars($current_quiz['title']); ?></h3>
                            <p class="quiz-description"><?php echo htmlspecialchars($display_quiz['description']); ?></p>
                            
                            <div class="quiz-meta">
                                <span><i class="fas fa-signal mr-2"></i><?php echo $display_quiz['difficulty']; ?></span>
                                <span><i class="fas fa-clock mr-2"></i><?php echo $display_quiz['estimated_time']; ?></span>
                            </div>
                        </div>
                        
                        <button class="start-button">
                            <i class="fas fa-play mr-2"></i>Start Quiz
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stats-card">
                    <div class="stats-value text-indigo-400">
                        <i class="fas fa-trophy mr-2"></i>
                        <?php echo count($quizzes); ?>
                    </div>
                    <div class="stats-label">Available Quizzes</div>
                    <div class="stats-description">More coming soon!</div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-value text-green-400">
                        <i class="fas fa-coins mr-2"></i>
                        <?php echo number_format($points); ?>
                    </div>
                    <div class="stats-label">Your Points</div>
                    <div class="stats-description">Keep learning to earn more!</div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-value text-purple-400">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        <?php echo number_format($wallet_balance, 2); ?>
                    </div>
                    <div class="stats-label">Wallet Balance</div>
                    <div class="stats-description">Earn money by learning!</div>
                </div>
            </div>
        </div>
        
        <!-- Quiz Questions Container -->
        <div class="quiz-container" id="quiz-questions" style="display: none;">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 id="quiz-title" class="text-3xl font-bold gradient-text mb-2"></h2>
                    <p class="text-gray-400">Answer all questions to complete the quiz</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="question-counter" style="background: rgba(99, 102, 241, 0.2); padding: 0.5rem 1rem; border-radius: 20px; color: #a5b4fc; font-weight: 600;"></span>
                    <button id="reset-quiz" class="text-gray-400" onclick="resetQuiz()" style="padding: 0.5rem; border: none; background: none; cursor: pointer;">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </button>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            
            <form id="quiz-form" method="POST">
                <input type="hidden" name="quiz_id" id="quiz-id">
                <div id="questions-container"></div>
                
                <div class="text-center mt-8">
                    <button type="submit" name="submit_quiz" class="btn-submit" id="submit-btn" disabled>
                        <i class="fas fa-paper-plane mr-2"></i>Submit Quiz
                    </button>
                    <p id="submit-help" class="mt-2 text-gray-400">Complete all questions to enable submission</p>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-coins text-2xl text-indigo-400"></i>
                    <span class="text-2xl font-bold gradient-text">MoneyQuest</span>
                </div>
                <p class="text-gray-400">Your Financial Journey Starts Here 🚀</p>
            </div>
            
            <div>
                <h4 class="footer-title">Quick Links</h4>
                <a href="dashboard.php" class="footer-link">Dashboard</a>
                <a href="stocks.php" class="footer-link">Stocks</a>
                <a href="leaderboard.php" class="footer-link">Leaderboard</a>
                <a href="achievements.php" class="footer-link">Achievements</a>
            </div>
            
            <div>
                <h4 class="footer-title">Learning</h4>
                <a href="#" class="footer-link">Financial Basics</a>
                <a href="#" class="footer-link">Investment Guide</a>
                <a href="#" class="footer-link">Saving Tips</a>
                <a href="#" class="footer-link">Quiz Archive</a>
            </div>
            
            <div>
                <h4 class="footer-title">Support</h4>
                <a href="#" class="footer-link">Help Center</a>
                <a href="#" class="footer-link">Contact Us</a>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2023 MoneyQuest. All rights reserved. Built with ❤️ for financial education.</p>
        </div>
    </footer>

    <script>
        // Quiz App with simplified, reliable functionality
        class QuizApp {
            constructor() {
                this.currentQuestion = 0;
                this.totalQuestions = 0;
                this.startTime = null;
                this.timerInterval = null;
                this.timeLimit = 30 * 60; // 30 minutes
                this.answers = {};
                this.isSubmitting = false;
                
                this.init();
            }
            
            init() {
                this.initEventListeners();
            }
            
            initEventListeners() {
                // Quiz form submission
                const quizForm = document.getElementById('quiz-form');
                if (quizForm) {
                    quizForm.addEventListener('submit', this.handleQuizSubmit.bind(this));
                }
                
                // Option selection
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('option-btn')) {
                        this.handleOptionSelection(e.target);
                    }
                });
            }
            
            loadQuiz(quizId) {
                if (this.isSubmitting) return;
                
                const clickedCard = event.currentTarget;
                const originalContent = clickedCard.innerHTML;
                
                // Show loading state
                clickedCard.innerHTML = `
                    <div style="text-align: center; padding: 2rem;">
                        <div class="loading-spinner"></div>
                        <div style="color: #a5b4fc; margin-top: 1rem;">Loading Quiz...</div>
                    </div>
                `;
                
                // Fetch quiz data
                fetch(`api/get_quiz_questions.php?quiz_id=${quizId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.displayQuiz(data.quiz, data.questions);
                        } else {
                            throw new Error(data.message || 'Failed to load quiz');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading quiz:', error);
                        clickedCard.innerHTML = originalContent;
                        alert('Failed to load quiz. Please try again.');
                    });
            }
            
            displayQuiz(quiz, questions) {
                document.getElementById('quiz-title').textContent = quiz.title;
                document.getElementById('quiz-id').value = quiz.quiz_id;
                
                const questionsContainer = document.getElementById('questions-container');
                questionsContainer.innerHTML = '';
                
                this.totalQuestions = questions.length;
                this.currentQuestion = 0;
                this.answers = {};
                
                questions.forEach((question, index) => {
                    const questionHtml = this.createQuestionHTML(question, index);
                    questionsContainer.innerHTML += questionHtml;
                });
                
                this.updateProgress();
                this.startTimer();
                
                // Show quiz questions
                document.getElementById('quiz-selection').style.display = 'none';
                document.getElementById('quiz-questions').style.display = 'block';
                document.getElementById('quiz-timer').style.display = 'block';
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            
            createQuestionHTML(question, index) {
                return `
                    <div class="question-card">
                        <h3 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: #a5b4fc;">Question ${index + 1}</h3>
                        <p style="font-size: 1.125rem; margin-bottom: 1.5rem; color: #ffffff;">${this.escapeHtml(question.question_text)}</p>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="A">
                                <strong style="color: #a5b4fc;">A.</strong> ${this.escapeHtml(question.option_a)}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="B">
                                <strong style="color: #a5b4fc;">B.</strong> ${this.escapeHtml(question.option_b)}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="C">
                                <strong style="color: #a5b4fc;">C.</strong> ${this.escapeHtml(question.option_c)}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="D">
                                <strong style="color: #a5b4fc;">D.</strong> ${this.escapeHtml(question.option_d)}
                            </button>
                        </div>
                        <input type="hidden" name="answers[${question.id}]" id="answer-${question.id}">
                    </div>
                `;
            }
            
            handleOptionSelection(optionBtn) {
                const questionId = optionBtn.dataset.question;
                const option = optionBtn.dataset.option;
                const questionCard = optionBtn.closest('.question-card');
                
                // Remove selected class from other options in this question
                questionCard.querySelectorAll('.option-btn').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                optionBtn.classList.add('selected');
                
                // Update hidden input
                document.getElementById(`answer-${questionId}`).value = option;
                this.answers[questionId] = option;
                
                // Update progress and submit button state
                this.updateProgress();
                this.updateSubmitButton();
            }
            
            updateProgress() {
                const answered = Object.keys(this.answers).length;
                const percentage = (answered / this.totalQuestions) * 100;
                
                document.getElementById('progress-fill').style.width = percentage + '%';
                document.getElementById('question-counter').textContent = `${answered}/${this.totalQuestions} answered`;
            }
            
            updateSubmitButton() {
                const submitBtn = document.getElementById('submit-btn');
                const answered = Object.keys(this.answers).length;
                const isComplete = answered === this.totalQuestions;
                
                submitBtn.disabled = !isComplete;
                
                if (isComplete) {
                    document.getElementById('submit-help').textContent = 'Ready to submit your quiz!';
                    submitBtn.style.opacity = '1';
                } else {
                    document.getElementById('submit-help').textContent = `Answer ${this.totalQuestions - answered} more question${this.totalQuestions - answered !== 1 ? 's' : ''} to enable submission`;
                    submitBtn.style.opacity = '0.5';
                }
            }
            
            startTimer() {
                this.startTime = Date.now();
                this.updateTimerDisplay();
                
                this.timerInterval = setInterval(() => {
                    this.updateTimerDisplay();
                }, 1000);
            }
            
            updateTimerDisplay() {
                if (!this.startTime) return;
                
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const remaining = Math.max(0, this.timeLimit - elapsed);
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                
                const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                document.getElementById('timer-display').textContent = display;
                
                // Warning colors
                const timerDisplay = document.getElementById('timer-display');
                if (remaining < 300) { // 5 minutes
                    timerDisplay.style.color = '#f97316'; // orange
                } else if (remaining < 60) { // 1 minute
                    timerDisplay.style.color = '#ef4444'; // red
                    timerDisplay.style.animation = 'blink 1s infinite';
                }
                
                // Auto-submit when time runs out
                if (remaining <= 0) {
                    this.handleTimeUp();
                }
            }
            
            handleTimeUp() {
                clearInterval(this.timerInterval);
                alert('Time\'s up! Your quiz will be submitted automatically.');
                
                setTimeout(() => {
                    this.handleQuizSubmit({ preventDefault: () => {} });
                }, 1000);
            }
            
            handleQuizSubmit(e) {
                e.preventDefault();
                
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                
                const submitBtn = document.getElementById('submit-btn');
                const originalContent = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
                submitBtn.disabled = true;
                
                // Clear timer
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                }
                
                // Submit form normally to handle PHP processing
                document.getElementById('quiz-form').submit();
            }
            
            resetQuiz() {
                if (confirm('Are you sure you want to reset the quiz? All progress will be lost.')) {
                    // Show quiz selection
                    document.getElementById('quiz-selection').style.display = 'block';
                    document.getElementById('quiz-questions').style.display = 'none';
                    document.getElementById('quiz-timer').style.display = 'none';
                    
                    // Clear timer
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                    }
                    
                    // Reset data
                    this.answers = {};
                    this.startTime = null;
                    
                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Global functions
        function loadQuiz(quizId) {
            window.quizApp?.loadQuiz(quizId);
        }
        
        function resetQuiz() {
            window.quizApp?.resetQuiz();
        }
        
        function toggleMobileNav() {
            const nav = document.getElementById('mobile-nav');
            nav.classList.toggle('active');
        }
        
        // Initialize app when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            window.quizApp = new QuizApp();
        });
        
        // Add blink animation for timer warning
        const style = document.createElement('style');
        style.textContent = `
            @keyframes blink {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0.5; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
