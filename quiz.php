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
    $stmt = $conn->prepare("SELECT quiz_id, title, category, description, difficulty FROM quizzes ORDER BY difficulty, category, title");
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
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Cursor -->
    <link rel="stylesheet" href="public/css/cursor.css">
    
    <!-- Particles.js -->
    <script src="public/js/particles.min.js"></script>
    
    <style>
        /* Modern CSS Variables */
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f97316;
            --error-color: #ef4444;
            --dark-bg: #0f172a;
            --darker-bg: #020617;
            --card-bg: rgba(255, 255, 255, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --gradient-secondary: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ef4444 100%);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-xl: 0 35px 60px -12px rgba(0, 0, 0, 0.35);
        }
        
        /* Modern Typography */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            background: var(--darker-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* Modern Container System */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            width: 100%;
        }
        
        @media (max-width: 1280px) {
            .container {
                max-width: 1000px;
            }
        }
        
        @media (max-width: 1024px) {
            .container {
                max-width: 900px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 0.75rem;
            }
        }
        
        /* Modern Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .navbar.scrolled {
            background: rgba(2, 6, 23, 0.95);
            box-shadow: 0 10px 30px -10px rgba(2, 6, 23, 0.5);
        }
        
        .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .nav-link:hover {
            color: var(--text-primary);
            background: var(--card-bg);
            transform: translateY(-2px);
        }
        
        .nav-btn-secondary {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .nav-btn-secondary:hover {
            background: var(--glass-bg);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-btn-primary {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .nav-btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-secondary);
            transition: all 0.4s ease;
            z-index: -1;
        }
        
        .nav-btn-primary:hover::before {
            left: 0;
        }
        
        .nav-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .mobile-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: var(--dark-bg);
            padding: 1rem;
            border-radius: 0 0 16px 16px;
            border-top: 1px solid var(--border-color);
            flex-direction: column;
            gap: 0.5rem;
            transform: translateY(-10px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-nav.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .hamburger {
            display: none;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }

            .hamburger {
                display: block;
            }
        }
        
        /* Modern Gradient Text */
        .gradient-text {
            background: var(--gradient-primary);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Quiz Section Styles */
        .quiz-section {
            padding-top: 100px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .quiz-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .quiz-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        .quiz-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .quiz-selection-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            cursor: pointer;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        .quiz-selection-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.6), 0 0 20px rgba(99, 102, 241, 0.4);
            border-color: rgba(99, 102, 241, 0.6);
            background: rgba(15, 23, 42, 0.8);
        }
        
        .quiz-selection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 50%, rgba(236, 72, 153, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            border-radius: 20px;
        }
        
        .quiz-selection-card:hover::before {
            opacity: 1;
        }
        
        .question-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            background: rgba(15, 23, 42, 0.6);
            border-color: var(--primary-color);
        }
        
        .option-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 1rem 1.5rem;
            margin: 0.5rem 0;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
            cursor: pointer;
            color: var(--text-primary);
            font-weight: 500;
            backdrop-filter: blur(5px);
        }
        
        .option-btn:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
            transform: translateX(5px);
        }
        
        .option-btn.selected {
            border-color: var(--primary-color);
            background: var(--gradient-primary);
            color: white;
            transform: translateX(10px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-submit {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: bold;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-secondary);
            transition: all 0.4s ease;
            z-index: -1;
        }
        
        .btn-submit:hover::before {
            left: 0;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .progress-bar {
            height: 12px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 6px;
            transition: width 0.5s ease;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            backdrop-filter: blur(10px);
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
        
        /* Page Transition */
        .page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 99999;
        }
        
        .transition-wipe {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            background: var(--primary-color);
            transform: scaleY(0);
            transform-origin: bottom;
            transition: transform 0.8s cubic-bezier(0.86, 0, 0.07, 1);
        }
        
        .transition-wipe.left {
            left: 0;
        }
        
        .transition-wipe.right {
            right: 0;
            transition-delay: 0.1s;
        }
        
        body.is-transitioning .transition-wipe {
            transform: scaleY(1);
            transform-origin: top;
        }
        
        /* Category Badge */
        .category-badge {
            background: var(--gradient-primary);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        /* Quiz Badge */
        .quiz-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--gradient-secondary);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: rotate(5deg);
            z-index: 40;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: rotate(5deg) scale(1); }
            50% { transform: rotate(5deg) scale(1.1); }
            100% { transform: rotate(5deg) scale(1); }
        }
    </style>
</head>
<body>
    <!-- Page Transition Overlay -->
    <div class="page-transition-overlay">
        <div class="transition-wipe left"></div>
        <div class="transition-wipe right"></div>
    </div>

    <!-- Modern Navigation -->
    <nav class="navbar py-4" id="navbar">
        <div class="container">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <i class="fas fa-coins text-2xl text-indigo-400 animate-pulse"></i>
                        <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-ping"></div>
                    </div>
                    <span class="text-xl font-bold gradient-text">MoneyQuest</span>
                </div>
                <div class="hidden md:flex space-x-8 desktop-nav">
                    <a href="dashboard.php" class="nav-link page-transition-link">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="stocks.php" class="nav-link page-transition-link">
                        <i class="fas fa-chart-line mr-2"></i>Stocks
                    </a>
                    <a href="leaderboard.php" class="nav-link page-transition-link">
                        <i class="fas fa-trophy mr-2"></i>Leaderboard
                    </a>
                    <a href="achievements.php" class="nav-link page-transition-link">
                        <i class="fas fa-medal mr-2"></i>Achievements
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="nav-btn-secondary hidden md:flex page-transition-link">
                        <i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($name); ?>
                    </a>
                    <a href="logout.php" class="nav-btn-primary hidden md:flex page-transition-link">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                    <div class="hamburger md:hidden">
                        <i class="fas fa-bars text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="mobile-nav">
                <a href="dashboard.php" class="nav-link page-transition-link">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="stocks.php" class="nav-link page-transition-link">
                    <i class="fas fa-chart-line mr-2"></i>Stocks
                </a>
                <a href="leaderboard.php" class="nav-link page-transition-link">
                    <i class="fas fa-trophy mr-2"></i>Leaderboard
                </a>
                <a href="achievements.php" class="nav-link page-transition-link">
                    <i class="fas fa-medal mr-2"></i>Achievements
                </a>
                <a href="profile.php" class="nav-link page-transition-link">
                    <i class="fas fa-user-circle mr-2"></i>Profile
                </a>
                <a href="logout.php" class="nav-link page-transition-link">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Quiz Section -->
    <section class="quiz-section">
        <div id="particles-js" class="absolute inset-0 z-0"></div>
        <div id="threejs-container" class="absolute inset-0 z-0"></div>
        
        <div class="container relative z-10 py-12">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="gradient-text">Financial Quizzes</span>
                </h1>
                <p class="text-xl text-gray-300">
                    Test your knowledge and earn rewards
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert" data-aos="fade-in">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert" data-aos="fade-in">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Quiz Selection -->
            <div class="quiz-card" data-aos="fade-up" data-aos-delay="100">
                <h2 class="text-3xl font-bold mb-8 text-center">
                    <i class="fas fa-list mr-3 text-indigo-400"></i>
                    <span class="gradient-text">Select a Quiz</span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    $categoryIcons = [
                        'Budgeting' => 'fas fa-calculator',
                        'Investing' => 'fas fa-chart-line',
                        'Saving' => 'fas fa-piggy-bank',
                        'Credit' => 'fas fa-credit-card',
                        'Insurance' => 'fas fa-shield-alt',
                        'Taxes' => 'fas fa-file-invoice-dollar',
                        'Retirement' => 'fas fa-clock',
                        'Real Estate' => 'fas fa-home',
                        'Cryptocurrency' => 'fab fa-bitcoin',
                        'Banking' => 'fas fa-university'
                    ];
                    
                    $difficultyColors = [
                        'Beginner' => 'bg-green-500/20 text-green-300 border-green-500/30',
                        'Intermediate' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                        'Advanced' => 'bg-red-500/20 text-red-300 border-red-500/30'
                    ];
                    
                    foreach ($quizzes as $index => $quiz): 
                        $icon = $categoryIcons[$quiz['category']] ?? 'fas fa-brain';
                        $difficultyClass = $difficultyColors[$quiz['difficulty']] ?? 'bg-gray-500/20 text-gray-300 border-gray-500/30';
                    ?>
                        <div class="quiz-selection-card group" onclick="loadQuiz(<?php echo $quiz['quiz_id']; ?>)" data-aos="fade-up" data-aos-delay="<?php echo 150 + ($index * 50); ?>">
                            <!-- New Badge for first 3 quizzes -->
                            <?php if ($index < 3): ?>
                                <span class="quiz-badge">NEW</span>
                            <?php endif; ?>
                            
                            <!-- Difficulty Badge -->
                            <div class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-bold border <?php echo $difficultyClass; ?>">
                                <?php echo strtoupper($quiz['difficulty']); ?>
                            </div>
                            
                            <!-- Quiz Icon -->
                            <div class="text-5xl mb-6 mt-8 transition-all duration-300 group-hover:scale-110 group-hover:rotate-3">
                                <i class="<?php echo $icon; ?> text-indigo-400 group-hover:text-indigo-300"></i>
                            </div>
                            
                            <!-- Category Badge -->
                            <span class="category-badge mb-3"><?php echo htmlspecialchars($quiz['category']); ?></span>
                            
                            <!-- Quiz Title -->
                            <h3 class="text-xl font-bold mb-4 group-hover:text-white transition-colors duration-300">
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </h3>
                            
                            <!-- Quiz Description -->
                            <p class="text-gray-300 mb-6 text-sm leading-relaxed group-hover:text-gray-200 transition-colors duration-300">
                                <?php echo htmlspecialchars($quiz['description'] ?? 'Test your knowledge and earn rewards'); ?>
                            </p>
                            
                            <!-- Quiz Stats -->
                            <div class="flex justify-between items-center mb-4 text-xs text-gray-400">
                                <span><i class="fas fa-question-circle mr-1"></i> 3 Questions</span>
                                <span><i class="fas fa-clock mr-1"></i> 5 mins</span>
                                <span><i class="fas fa-star mr-1"></i> +50 Points</span>
                            </div>
                            
                            <!-- Start Button -->
                            <div class="bg-indigo-500/20 hover:bg-indigo-500/30 px-4 py-3 rounded-full text-sm font-medium text-indigo-300 hover:text-indigo-200 transition-all duration-300 border border-indigo-500/30 hover:border-indigo-500/50">
                                <i class="fas fa-play mr-2"></i> Start Quiz
                            </div>
                            
                            <!-- Hover Effect Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl pointer-events-none"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quiz Questions -->
            <div id="quiz-questions" class="quiz-card" style="display: none;" data-aos="fade-up" data-aos-delay="300">
                <div class="flex justify-between items-center mb-6">
                    <h2 id="quiz-title" class="text-3xl font-bold gradient-text"></h2>
                    <span id="question-counter" class="bg-indigo-500/20 px-4 py-2 rounded-full text-indigo-300 font-semibold"></span>
                </div>
                
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                
                <form id="quiz-form" method="POST">
                    <input type="hidden" name="quiz_id" id="quiz-id">
                    <div id="questions-container"></div>
                    
                    <div class="text-center mt-8">
                        <button type="submit" name="submit_quiz" class="btn-submit">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Modern Footer -->
    <footer class="bg-slate-900 text-white py-16 relative">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <i class="fas fa-coins text-3xl text-indigo-400 animate-pulse"></i>
                        <span class="text-3xl font-bold gradient-text">MoneyQuest</span>
                    </div>
                    <p class="text-gray-400">Your Financial Journey Starts Here 🚀</p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Dashboard</a></li>
                        <li><a href="stocks.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Stocks</a></li>
                        <li><a href="leaderboard.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Leaderboard</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <i class="fab fa-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <i class="fab fa-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <i class="fab fa-instagram text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                            <i class="fab fa-linkedin text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2023 MoneyQuest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true
        });
        
        // Modern Sticky Navbar
        $(window).scroll(function() {
            const scrollTop = $(window).scrollTop();
            if (scrollTop > 50) {
                $('#navbar').addClass('scrolled');
            } else {
                $('#navbar').removeClass('scrolled');
            }
        });
        
        // Modern Particle System
        function initParticles() {
            if (typeof particlesJS !== 'undefined') {
                const particlesContainer = document.getElementById('particles-js');
                if (particlesContainer) {
                    particlesContainer.style.pointerEvents = 'none';
                }
                
                particlesJS('particles-js', {
                    particles: {
                        number: {
                            value: 50,
                            density: {
                                enable: true,
                                value_area: 800
                            }
                        },
                        color: {
                            value: "#6366f1"
                        },
                        shape: {
                            type: "circle"
                        },
                        opacity: {
                            value: 0.5,
                            random: true
                        },
                        size: {
                            value: 3,
                            random: true
                        },
                        line_linked: {
                            enable: true,
                            distance: 150,
                            color: "#6366f1",
                            opacity: 0.4,
                            width: 1
                        },
                        move: {
                            enable: true,
                            speed: 4,
                            direction: "none",
                            random: false,
                            straight: false,
                            out_mode: "out",
                            bounce: false
                        }
                    },
                    interactivity: {
                        detect_on: "canvas",
                        events: {
                            onhover: {
                                enable: true,
                                mode: "repulse"
                            },
                            onclick: {
                                enable: true,
                                mode: "push"
                            },
                            resize: true
                        }
                    },
                    retina_detect: true
                });
            }
        }
        
        // Modern Three.js Background
        let scene, camera, renderer, stars = [];
        
        function initThreeJS() {
            if (typeof THREE === 'undefined') return;
            
            const container = document.getElementById('threejs-container');
            if (!container) return;
            
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            container.appendChild(renderer.domElement);
            renderer.domElement.style.zIndex = '0';
            renderer.domElement.style.position = 'absolute';
            renderer.domElement.style.pointerEvents = 'none';
            
            // Create stars
            const geometry = new THREE.SphereGeometry(0.1, 24, 24);
            const material = new THREE.MeshBasicMaterial({ color: 0xffffff });
            
            for (let i = 0; i < 200; i++) {
                const star = new THREE.Mesh(geometry, material);
                star.position.x = Math.random() * 100 - 50;
                star.position.y = Math.random() * 100 - 50;
                star.position.z = Math.random() * 100 - 50;
                star.userData = {
                    rotationSpeed: Math.random() * 0.01,
                    pulseSpeed: Math.random() * 0.01
                };
                scene.add(star);
                stars.push(star);
            }
            
            camera.position.z = 5;
            
            window.addEventListener('resize', onWindowResize);
            animate();
        }
        
        function animate() {
            requestAnimationFrame(animate);
            
            stars.forEach(star => {
                star.rotation.x += star.userData.rotationSpeed;
                star.rotation.y += star.userData.rotationSpeed;
                star.scale.x = 1 + Math.sin(Date.now() * star.userData.pulseSpeed) * 0.2;
                star.scale.y = 1 + Math.sin(Date.now() * star.userData.pulseSpeed) * 0.2;
                star.scale.z = 1 + Math.sin(Date.now() * star.userData.pulseSpeed) * 0.2;
            });
            
            renderer.render(scene, camera);
        }
        
        function onWindowResize() {
            if (camera && renderer) {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            }
        }
        
        // Page Transitions
        function initPageTransitions() {
            const transitionLinks = document.querySelectorAll('.page-transition-link');
            transitionLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const destination = this.getAttribute('href');
                    if (destination && destination !== '#' && !destination.startsWith('#')) {
                        e.preventDefault();
                        document.body.classList.add('is-transitioning');
                        setTimeout(() => {
                            window.location.href = destination;
                        }, 1000);
                    }
                });
            });
        }
        
        // Interactive Elements
        function initInteractiveElements() {
            // Hamburger menu toggle
            const hamburger = document.querySelector('.hamburger');
            const mobileNav = document.querySelector('.mobile-nav');
            hamburger.addEventListener('click', () => {
                mobileNav.classList.toggle('active');
            });
        }
        
        // Quiz functionality
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
                    <div class="question-card" data-aos="fade-up" data-aos-delay="${index * 100}">
                        <h3 class="text-xl font-bold mb-4 text-indigo-300">Question ${index + 1}</h3>
                        <p class="text-lg mb-6">${question.question_text}</p>
                        <div class="space-y-3">
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="A">
                                <strong class="text-indigo-400">A.</strong> ${question.option_a}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="B">
                                <strong class="text-indigo-400">B.</strong> ${question.option_b}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="C">
                                <strong class="text-indigo-400">C.</strong> ${question.option_c}
                            </button>
                            <button type="button" class="option-btn" data-question="${question.id}" data-option="D">
                                <strong class="text-indigo-400">D.</strong> ${question.option_d}
                            </button>
                        </div>
                        <input type="hidden" name="answers[${question.id}]" id="answer-${question.id}">
                    </div>
                `;
                $('#questions-container').append(questionHtml);
            });
            
            updateProgress();
            $('#quiz-questions').show();
            $('html, body').animate({ scrollTop: $('#quiz-questions').offset().top - 100 }, 500);
            
            // Re-initialize AOS for new content
            AOS.refresh();
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
            // Initialize all features
            initParticles();
            initThreeJS();
            initPageTransitions();
            initInteractiveElements();
            
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
            
            // GSAP Animations
            gsap.from('.quiz-card', {
                duration: 1,
                y: 50,
                opacity: 0,
                stagger: 0.2,
                ease: 'power3.out'
            });
        });
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
