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
    
    <!-- Enhanced Performance & SEO -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    
    <!-- Progressive Web App -->
    <link rel="manifest" href="public/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="apple-touch-icon" href="public/icons/icon-192x192.png">
    
    <!-- TailwindCSS with Custom Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#f59e0b',
                        success: '#10b981',
                        warning: '#f97316',
                        error: '#ef4444',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulse-glow 2s infinite',
                        'slide-up': 'slide-up 0.8s ease-out',
                        'scale-in': 'scale-in 0.6s ease-out',
                        'shimmer': 'shimmer 2s infinite',
                        'bounce-gentle': 'bounce-gentle 2s infinite',
                    }
                }
            }
        }
    </script>
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="public/css/cursor.css">
    <script src="public/js/particles.min.js"></script>
    
    <style>
        /* Modern CSS Variables & Design System */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f97316;
            --error: #ef4444;
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
            --border-radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Enhanced Typography */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            overflow-x: hidden;
            background: var(--darker-bg);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Enhanced Accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Focus Management */
        .focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: var(--border-radius);
        }
        
        /* Modern Container System */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            width: 100%;
        }
        
        @media (max-width: 1280px) { .container { max-width: 1200px; } }
        @media (max-width: 1024px) { .container { max-width: 960px; } }
        @media (max-width: 768px) { .container { max-width: 100%; padding: 0 1.5rem; } }
        @media (max-width: 480px) { .container { padding: 0 1rem; } }
        
        /* Enhanced Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            transition: var(--transition);
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
        }
        
        .navbar.scrolled {
            background: rgba(2, 6, 23, 0.95);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            position: relative;
        }
        
        .nav-link:hover, .nav-link:focus {
            color: var(--text-primary);
            background: var(--card-bg);
            transform: translateY(-2px);
        }
        
        .nav-btn-secondary {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .nav-btn-secondary:hover, .nav-btn-secondary:focus {
            background: var(--glass-bg);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-btn-primary {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .nav-btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gradient-secondary);
            opacity: 0;
            transition: var(--transition);
        }
        
        .nav-btn-primary:hover::before, .nav-btn-primary:focus::before {
            opacity: 1;
        }
        
        .nav-btn-primary:hover, .nav-btn-primary:focus {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Mobile Navigation */
        .mobile-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: var(--dark-bg);
            padding: 1.5rem;
            border-radius: 0 0 20px 20px;
            border-top: 1px solid var(--border-color);
            flex-direction: column;
            gap: 0.75rem;
            transform: translateY(-10px);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .mobile-nav.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .hamburger {
            display: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .hamburger:hover, .hamburger:focus {
            background: var(--card-bg);
        }
        
        @media (max-width: 768px) {
            .desktop-nav { display: none; }
            .hamburger { display: block; }
        }
        
        /* Enhanced Gradient Text */
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
        
        /* Main Quiz Section */
        .quiz-section {
            padding-top: 120px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .quiz-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        
        /* Enhanced Quiz Container */
        .quiz-main-container {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.9) 100%);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 32px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .quiz-main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }
        
        .quiz-main-container::after {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        /* Enhanced Quiz Cards */
        .quiz-selection-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: var(--transition-slow);
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.3),
                0 10px 10px -5px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .quiz-selection-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
            opacity: 0;
            transition: var(--transition);
        }
        
        .quiz-selection-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), #06b6d4);
            transform: scaleX(0);
            transition: var(--transition-slow);
        }
        
        .quiz-selection-card:hover, .quiz-selection-card:focus {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 
                0 35px 60px -10px rgba(0, 0, 0, 0.5),
                0 0 30px rgba(99, 102, 241, 0.3),
                0 0 60px rgba(139, 92, 246, 0.2);
            border-color: rgba(99, 102, 241, 0.6);
        }
        
        .quiz-selection-card:hover::before, .quiz-selection-card:focus::before {
            opacity: 1;
        }
        
        .quiz-selection-card:hover::after, .quiz-selection-card:focus::after {
            transform: scaleX(1);
        }
        
        .quiz-selection-card:hover .card-icon, .quiz-selection-card:focus .card-icon {
            transform: scale(1.2) rotateY(180deg);
        }
        
        .quiz-selection-card:hover .card-title, .quiz-selection-card:focus .card-title {
            color: var(--secondary);
        }
        
        .quiz-selection-card:hover .start-button, .quiz-selection-card:focus .start-button {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scale(1.05);
        }
        
        /* Enhanced Card Elements */
        .card-icon {
            font-size: 3.5rem;
            color: var(--primary);
            transition: var(--transition-slow);
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(99, 102, 241, 0.3));
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            transition: var(--transition);
            margin-bottom: 0.5rem;
        }
        
        .card-description {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }
        
        .start-button {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(139, 92, 246, 0.8));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        /* Enhanced Badges */
        .quiz-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            animation: pulse-glow 2s infinite;
        }
        
        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        /* Enhanced Animations */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            }
            50% {
                box-shadow: 0 4px 25px rgba(16, 185, 129, 0.7);
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes slide-up {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes scale-in {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @keyframes bounce-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Enhanced Question Styles */
        .question-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
            backdrop-filter: blur(15px);
            transition: var(--transition);
            position: relative;
        }
        
        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0;
            transition: var(--transition);
        }
        
        .question-card:hover {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .question-card:hover::before {
            opacity: 1;
        }
        
        .option-btn {
            display: block;
            width: 100%;
            text-align: left;
            padding: 1.25rem 1.75rem;
            margin: 0.75rem 0;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            transition: var(--transition);
            cursor: pointer;
            color: var(--text-primary);
            font-weight: 500;
            backdrop-filter: blur(5px);
            position: relative;
        }
        
        .option-btn::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
            border-radius: 0 2px 2px 0;
            transform: scaleY(0);
            transition: var(--transition);
        }
        
        .option-btn:hover, .option-btn:focus {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: translateX(8px);
        }
        
        .option-btn:hover::before, .option-btn:focus::before {
            transform: scaleY(1);
        }
        
        .option-btn.selected {
            border-color: var(--primary);
            background: var(--gradient-primary);
            color: white;
            transform: translateX(12px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .option-btn.selected::before {
            transform: scaleY(1);
            background: white;
        }
        
        /* Enhanced Submit Button */
        .btn-submit {
            background: var(--gradient-primary);
            border: none;
            border-radius: 16px;
            padding: 1.25rem 2.5rem;
            font-weight: bold;
            color: white;
            font-size: 1.1rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gradient-secondary);
            opacity: 0;
            transition: var(--transition);
        }
        
        .btn-submit:hover::before, .btn-submit:focus::before {
            opacity: 1;
        }
        
        .btn-submit:hover, .btn-submit:focus {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Enhanced Progress Bar */
        .progress-bar {
            height: 12px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 6px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        /* Enhanced Alert Styles */
        .alert {
            padding: 1.25rem 1.75rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
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
        
        /* Enhanced Stats Cards */
        .stats-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.6), rgba(30, 41, 59, 0.8));
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), transparent);
            opacity: 0;
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .stats-card:hover::before {
            opacity: 1;
        }
        
        /* Quiz Timer */
        .quiz-timer {
            position: fixed;
            top: 100px;
            right: 20px;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            backdrop-filter: blur(20px);
            z-index: 500;
            display: none;
        }
        
        .timer-display {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Loading States */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .quiz-main-container {
                padding: 2rem;
                border-radius: 24px;
            }
            
            .quiz-selection-card {
                height: 280px;
                padding: 2rem;
            }
            
            .card-icon {
                font-size: 3rem;
            }
            
            .card-title {
                font-size: 1.25rem;
            }
            
            .question-card {
                padding: 2rem;
            }
            
            .option-btn {
                padding: 1rem 1.25rem;
            }
            
            .quiz-timer {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .quiz-main-container {
                padding: 1.5rem;
            }
            
            .quiz-selection-card {
                height: 250px;
                padding: 1.5rem;
            }
            
            .card-icon {
                font-size: 2.5rem;
                margin-bottom: 0.75rem;
            }
            
            .question-card {
                padding: 1.5rem;
            }
            
            .quiz-badge, .category-badge {
                font-size: 0.6rem;
                padding: 0.3rem 0.6rem;
            }
        }
        
        /* Dark mode improvements */
        @media (prefers-color-scheme: dark) {
            :root {
                --card-bg: rgba(255, 255, 255, 0.08);
                --glass-bg: rgba(255, 255, 255, 0.12);
                --border-color: rgba(255, 255, 255, 0.15);
            }
        }
        
        /* High contrast mode */
        @media (prefers-contrast: high) {
            .quiz-selection-card {
                border-width: 2px;
            }
            
            .option-btn {
                border-width: 2px;
            }
            
            .alert {
                border-width: 2px;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--darker-bg);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Enhanced Navigation -->
    <nav class="navbar py-4" id="navbar" role="navigation" aria-label="Main navigation">
        <div class="container">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <i class="fas fa-coins text-2xl text-indigo-400 animate-pulse" aria-hidden="true"></i>
                        <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-ping" aria-hidden="true"></div>
                    </div>
                    <span class="text-xl font-bold gradient-text">MoneyQuest</span>
                </div>
                <div class="hidden md:flex space-x-6 desktop-nav">
                    <a href="dashboard.php" class="nav-link" aria-label="Go to Dashboard">
                        <i class="fas fa-home mr-2" aria-hidden="true"></i>Dashboard
                    </a>
                    <a href="stocks.php" class="nav-link" aria-label="Go to Stocks">
                        <i class="fas fa-chart-line mr-2" aria-hidden="true"></i>Stocks
                    </a>
                    <a href="leaderboard.php" class="nav-link" aria-label="Go to Leaderboard">
                        <i class="fas fa-trophy mr-2" aria-hidden="true"></i>Leaderboard
                    </a>
                    <a href="achievements.php" class="nav-link" aria-label="Go to Achievements">
                        <i class="fas fa-medal mr-2" aria-hidden="true"></i>Achievements
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="nav-btn-secondary hidden md:flex" aria-label="Go to Profile">
                        <i class="fas fa-user-circle mr-2" aria-hidden="true"></i><?php echo htmlspecialchars($name); ?>
                    </a>
                    <a href="logout.php" class="nav-btn-primary hidden md:flex" aria-label="Logout">
                        <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout
                    </a>
                    <button class="hamburger md:hidden" aria-label="Toggle mobile menu" aria-expanded="false">
                        <i class="fas fa-bars text-2xl" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="mobile-nav" role="menu">
                <a href="dashboard.php" class="nav-link" role="menuitem">
                    <i class="fas fa-home mr-2" aria-hidden="true"></i>Dashboard
                </a>
                <a href="stocks.php" class="nav-link" role="menuitem">
                    <i class="fas fa-chart-line mr-2" aria-hidden="true"></i>Stocks
                </a>
                <a href="leaderboard.php" class="nav-link" role="menuitem">
                    <i class="fas fa-trophy mr-2" aria-hidden="true"></i>Leaderboard
                </a>
                <a href="achievements.php" class="nav-link" role="menuitem">
                    <i class="fas fa-medal mr-2" aria-hidden="true"></i>Achievements
                </a>
                <a href="profile.php" class="nav-link" role="menuitem">
                    <i class="fas fa-user-circle mr-2" aria-hidden="true"></i>Profile
                </a>
                <a href="logout.php" class="nav-link" role="menuitem">
                    <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Quiz Timer -->
    <div class="quiz-timer" id="quiz-timer" role="timer" aria-live="polite">
        <div class="flex items-center space-x-2">
            <i class="fas fa-clock text-primary" aria-hidden="true"></i>
            <span class="timer-display" id="timer-display">00:00</span>
        </div>
    </div>

    <!-- Main Quiz Section -->
    <main class="quiz-section" role="main">
        <div id="particles-js" class="absolute inset-0 z-0" aria-hidden="true"></div>
        <div id="threejs-container" class="absolute inset-0 z-0" aria-hidden="true"></div>
        
        <div class="container relative z-10 py-12">
            <!-- Header -->
            <header class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    <span class="gradient-text">Smart Financial Quizzes</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto">
                    Master financial concepts through interactive learning and earn real rewards
                </p>
            </header>
            
            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert" data-aos="fade-in" aria-live="polite">
                    <i class="fas fa-exclamation-triangle mr-3" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert" data-aos="fade-in" aria-live="polite">
                    <i class="fas fa-check-circle mr-3" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Quiz Selection -->
            <section class="quiz-main-container" data-aos="fade-up" data-aos-delay="100" aria-labelledby="quiz-selection-title">
                <div class="text-center mb-12">
                    <h2 id="quiz-selection-title" class="text-4xl font-bold mb-4">
                        <i class="fas fa-brain mr-3 text-indigo-400 floating-element" aria-hidden="true"></i>
                        <span class="gradient-text">Choose Your Challenge</span>
                    </h2>
                    <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 mx-auto rounded-full" aria-hidden="true"></div>
                    <p class="text-gray-300 mt-4 text-lg">Select a quiz category to test your knowledge and earn points</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php 
                    // Enhanced quiz data with better descriptions and difficulty levels
                    $quiz_display_order = [
                        [
                            'title' => 'Budgeting Basics', 
                            'category' => 'Budgeting', 
                            'is_new' => true,
                            'description' => 'Learn essential budgeting skills to take control of your finances',
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
                        <article class="quiz-selection-card" 
                                onclick="loadQuiz(<?php echo $current_quiz['quiz_id']; ?>)" 
                                data-aos="zoom-in" 
                                data-aos-delay="<?php echo 200 + ($index * 100); ?>"
                                tabindex="0"
                                role="button"
                                aria-label="Start <?php echo htmlspecialchars($current_quiz['title']); ?> quiz"
                                onkeydown="handleCardKeydown(event, <?php echo $current_quiz['quiz_id']; ?>)">
                            
                            <?php if ($display_quiz['is_new']): ?>
                                <span class="quiz-badge" aria-label="New quiz">NEW</span>
                            <?php endif; ?>
                            
                            <span class="category-badge"><?php echo htmlspecialchars($current_quiz['category']); ?></span>
                            
                            <div class="flex flex-col items-center justify-center flex-1">
                                <div class="card-icon" aria-hidden="true">
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
                                
                                <h3 class="card-title"><?php echo htmlspecialchars($current_quiz['title']); ?></h3>
                                
                                <p class="card-description"><?php echo htmlspecialchars($display_quiz['description']); ?></p>
                                
                                <div class="text-sm text-gray-400 mb-4 space-y-1">
                                    <div><i class="fas fa-signal mr-2" aria-hidden="true"></i><?php echo $display_quiz['difficulty']; ?></div>
                                    <div><i class="fas fa-clock mr-2" aria-hidden="true"></i><?php echo $display_quiz['estimated_time']; ?></div>
                                </div>
                            </div>
                            
                            <button class="start-button" aria-hidden="true">
                                <i class="fas fa-play mr-2"></i>
                                Start Quiz
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Enhanced Stats Section -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="stats-card">
                        <div class="text-4xl font-bold text-indigo-400 mb-3">
                            <i class="fas fa-trophy mr-2" aria-hidden="true"></i>
                            <?php echo count($quizzes); ?>
                        </div>
                        <div class="text-gray-300 font-medium">Available Quizzes</div>
                        <div class="text-sm text-gray-500 mt-1">More coming soon!</div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="text-4xl font-bold text-green-400 mb-3">
                            <i class="fas fa-coins mr-2" aria-hidden="true"></i>
                            <?php echo number_format($points); ?>
                        </div>
                        <div class="text-gray-300 font-medium">Your Points</div>
                        <div class="text-sm text-gray-500 mt-1">Keep learning to earn more!</div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="text-4xl font-bold text-purple-400 mb-3">
                            <i class="fas fa-dollar-sign mr-2" aria-hidden="true"></i>
                            <?php echo number_format($wallet_balance, 2); ?>
                        </div>
                        <div class="text-gray-300 font-medium">Wallet Balance</div>
                        <div class="text-sm text-gray-500 mt-1">Earn money by learning!</div>
                    </div>
                </div>
            </section>
            
            <!-- Quiz Questions Container -->
            <section id="quiz-questions" class="quiz-main-container" style="display: none;" data-aos="fade-up" data-aos-delay="300" aria-labelledby="quiz-title">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                    <div>
                        <h2 id="quiz-title" class="text-3xl md:text-4xl font-bold gradient-text mb-2"></h2>
                        <p class="text-gray-400">Answer all questions to complete the quiz</p>
                    </div>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <span id="question-counter" class="bg-indigo-500/20 px-4 py-2 rounded-full text-indigo-300 font-semibold"></span>
                        <button id="reset-quiz" class="text-gray-400 hover:text-white transition-colors" aria-label="Reset quiz">
                            <i class="fas fa-redo mr-2" aria-hidden="true"></i>Reset
                        </button>
                    </div>
                </div>
                
                <div class="progress-bar" role="progressbar" aria-label="Quiz progress">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                
                <form id="quiz-form" method="POST" novalidate>
                    <input type="hidden" name="quiz_id" id="quiz-id">
                    <div id="questions-container" role="group" aria-labelledby="quiz-title"></div>
                    
                    <div class="text-center mt-8">
                        <button type="submit" name="submit_quiz" class="btn-submit" disabled aria-describedby="submit-help">
                            <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>
                            Submit Quiz
                        </button>
                        <p id="submit-help" class="text-sm text-gray-400 mt-2">Complete all questions to enable submission</p>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="bg-slate-900 text-white py-16 relative" role="contentinfo">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <i class="fas fa-coins text-3xl text-indigo-400 animate-pulse" aria-hidden="true"></i>
                        <span class="text-3xl font-bold gradient-text">MoneyQuest</span>
                    </div>
                    <p class="text-gray-400">Your Financial Journey Starts Here 🚀</p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Facebook">
                            <i class="fab fa-facebook text-xl" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Twitter">
                            <i class="fab fa-twitter text-xl" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Instagram">
                            <i class="fab fa-instagram text-xl" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="LinkedIn">
                            <i class="fab fa-linkedin text-xl" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors duration-300">Dashboard</a></li>
                        <li><a href="stocks.php" class="text-gray-400 hover:text-white transition-colors duration-300">Stocks</a></li>
                        <li><a href="leaderboard.php" class="text-gray-400 hover:text-white transition-colors duration-300">Leaderboard</a></li>
                        <li><a href="achievements.php" class="text-gray-400 hover:text-white transition-colors duration-300">Achievements</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Learning</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Financial Basics</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Investment Guide</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Saving Tips</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Quiz Archive</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2023 MoneyQuest. All rights reserved. Built with ❤️ for financial education.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Enhanced App Initialization
        class QuizApp {
            constructor() {
                this.currentQuestion = 0;
                this.totalQuestions = 0;
                this.startTime = null;
                this.timerInterval = null;
                this.timeLimit = 30 * 60; // 30 minutes default
                this.answers = {};
                this.isSubmitting = false;
                
                this.init();
            }
            
            init() {
                this.initAOS();
                this.initParticles();
                this.initThreeJS();
                this.initEventListeners();
                this.initAccessibility();
                this.initPerformanceOptimizations();
            }
            
            initAOS() {
                AOS.init({
                    duration: 800,
                    easing: 'ease-out-cubic',
                    once: true,
                    offset: 50
                });
            }
            
            initParticles() {
                if (typeof particlesJS !== 'undefined') {
                    const particlesContainer = document.getElementById('particles-js');
                    if (particlesContainer) {
                        particlesContainer.style.pointerEvents = 'none';
                        
                        particlesJS('particles-js', {
                            particles: {
                                number: { value: 40, density: { enable: true, value_area: 800 } },
                                color: { value: "#6366f1" },
                                shape: { type: "circle" },
                                opacity: { value: 0.4, random: true },
                                size: { value: 3, random: true },
                                line_linked: {
                                    enable: true,
                                    distance: 150,
                                    color: "#6366f1",
                                    opacity: 0.3,
                                    width: 1
                                },
                                move: {
                                    enable: true,
                                    speed: 3,
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
                                    onhover: { enable: true, mode: "repulse" },
                                    onclick: { enable: true, mode: "push" },
                                    resize: true
                                }
                            },
                            retina_detect: true
                        });
                    }
                }
            }
            
            initThreeJS() {
                // Three.js background implementation
                if (typeof THREE === 'undefined') return;
                
                const container = document.getElementById('threejs-container');
                if (!container) return;
                
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
                const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
                
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setClearColor(0x000000, 0);
                container.appendChild(renderer.domElement);
                renderer.domElement.style.zIndex = '0';
                renderer.domElement.style.position = 'absolute';
                renderer.domElement.style.pointerEvents = 'none';
                
                // Create floating geometric shapes
                const shapes = [];
                const geometries = [
                    new THREE.TetrahedronGeometry(0.5),
                    new THREE.OctahedronGeometry(0.5),
                    new THREE.IcosahedronGeometry(0.5)
                ];
                
                for (let i = 0; i < 15; i++) {
                    const geometry = geometries[Math.floor(Math.random() * geometries.length)];
                    const material = new THREE.MeshBasicMaterial({ 
                        color: Math.random() > 0.5 ? 0x6366f1 : 0x8b5cf6,
                        wireframe: true,
                        transparent: true,
                        opacity: 0.3
                    });
                    
                    const shape = new THREE.Mesh(geometry, material);
                    shape.position.x = (Math.random() - 0.5) * 50;
                    shape.position.y = (Math.random() - 0.5) * 50;
                    shape.position.z = (Math.random() - 0.5) * 50;
                    
                    shape.userData = {
                        rotationSpeed: Math.random() * 0.02,
                        floatSpeed: Math.random() * 0.01 + 0.005
                    };
                    
                    scene.add(shape);
                    shapes.push(shape);
                }
                
                camera.position.z = 10;
                
                const animate = () => {
                    requestAnimationFrame(animate);
                    
                    shapes.forEach(shape => {
                        shape.rotation.x += shape.userData.rotationSpeed;
                        shape.rotation.y += shape.userData.rotationSpeed;
                        shape.position.y += Math.sin(Date.now() * shape.userData.floatSpeed) * 0.01;
                    });
                    
                    renderer.render(scene, camera);
                };
                
                animate();
                
                // Handle window resize
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });
            }
            
            initEventListeners() {
                // Navbar scroll effect
                window.addEventListener('scroll', this.handleNavbarScroll.bind(this));
                
                // Mobile menu toggle
                const hamburger = document.querySelector('.hamburger');
                const mobileNav = document.querySelector('.mobile-nav');
                
                hamburger?.addEventListener('click', () => {
                    const isActive = mobileNav.classList.toggle('active');
                    hamburger.setAttribute('aria-expanded', isActive);
                });
                
                // Quiz form submission
                const quizForm = document.getElementById('quiz-form');
                quizForm?.addEventListener('submit', this.handleQuizSubmit.bind(this));
                
                // Option selection
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('option-btn')) {
                        this.handleOptionSelection(e.target);
                    }
                });
                
                // Reset quiz
                const resetBtn = document.getElementById('reset-quiz');
                resetBtn?.addEventListener('click', this.resetQuiz.bind(this));
                
                // Keyboard navigation
                document.addEventListener('keydown', this.handleKeyboardNavigation.bind(this));
                
                // Page visibility API for timer
                document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
            }
            
            initAccessibility() {
                // Focus management
                this.setupFocusManagement();
                
                // ARIA live regions
                this.setupAriaLiveRegions();
                
                // High contrast mode detection
                if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
                    document.body.classList.add('high-contrast');
                }
                
                // Reduced motion support
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    document.body.classList.add('reduced-motion');
                }
            }
            
            initPerformanceOptimizations() {
                // Lazy load images
                this.lazyLoadImages();
                
                // Debounce scroll events
                this.debouncedScrollHandler = this.debounce(this.handleNavbarScroll.bind(this), 10);
                window.addEventListener('scroll', this.debouncedScrollHandler);
                
                // Intersection Observer for animations
                this.setupIntersectionObserver();
            }
            
            handleNavbarScroll() {
                const navbar = document.getElementById('navbar');
                const scrollTop = window.pageYOffset;
                
                if (scrollTop > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
            
            loadQuiz(quizId) {
                if (this.isSubmitting) return;
                
                const clickedCard = event.currentTarget;
                const originalContent = clickedCard.innerHTML;
                
                // Show loading state
                clickedCard.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="loading-spinner mb-4"></div>
                        <div class="text-indigo-300">Loading Quiz...</div>
                    </div>
                `;
                
                // Fade out other cards
                const allCards = document.querySelectorAll('.quiz-selection-card');
                allCards.forEach((card, index) => {
                    if (card !== clickedCard) {
                        gsap.to(card, {
                            duration: 0.5,
                            opacity: 0.3,
                            scale: 0.95,
                            delay: index * 0.02,
                            ease: 'power2.out'
                        });
                    }
                });
                
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
                        allCards.forEach(card => {
                            gsap.to(card, { duration: 0.3, opacity: 1, scale: 1 });
                        });
                        this.showAlert('error', 'Failed to load quiz. Please try again.');
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
                
                // Animate transition
                gsap.to('.quiz-main-container', {
                    duration: 0.6,
                    y: -50,
                    opacity: 0,
                    ease: 'power2.inOut',
                    onComplete: () => {
                        document.querySelector('.quiz-main-container').style.display = 'none';
                        document.getElementById('quiz-questions').style.display = 'block';
                        
                        gsap.fromTo('#quiz-questions', 
                            { y: 50, opacity: 0 },
                            { duration: 0.8, y: 0, opacity: 1, ease: 'power2.out' }
                        );
                        
                        // Focus management
                        document.getElementById('quiz-title').focus();
                        
                        // Show timer
                        document.getElementById('quiz-timer').style.display = 'block';
                    }
                });
                
                AOS.refresh();
            }
            
            createQuestionHTML(question, index) {
                return `
                    <fieldset class="question-card" data-aos="fade-up" data-aos-delay="${index * 100}">
                        <legend class="text-xl font-bold mb-4 text-indigo-300">Question ${index + 1}</legend>
                        <p class="text-lg mb-6">${this.escapeHtml(question.question_text)}</p>
                        <div class="space-y-3" role="radiogroup" aria-labelledby="question-${question.id}-label">
                            <button type="button" class="option-btn" 
                                    data-question="${question.id}" 
                                    data-option="A"
                                    role="radio"
                                    aria-checked="false"
                                    tabindex="0">
                                <strong class="text-indigo-400">A.</strong> ${this.escapeHtml(question.option_a)}
                            </button>
                            <button type="button" class="option-btn" 
                                    data-question="${question.id}" 
                                    data-option="B"
                                    role="radio"
                                    aria-checked="false"
                                    tabindex="-1">
                                <strong class="text-indigo-400">B.</strong> ${this.escapeHtml(question.option_b)}
                            </button>
                            <button type="button" class="option-btn" 
                                    data-question="${question.id}" 
                                    data-option="C"
                                    role="radio"
                                    aria-checked="false"
                                    tabindex="-1">
                                <strong class="text-indigo-400">C.</strong> ${this.escapeHtml(question.option_c)}
                            </button>
                            <button type="button" class="option-btn" 
                                    data-question="${question.id}" 
                                    data-option="D"
                                    role="radio"
                                    aria-checked="false"
                                    tabindex="-1">
                                <strong class="text-indigo-400">D.</strong> ${this.escapeHtml(question.option_d)}
                            </button>
                        </div>
                        <input type="hidden" name="answers[${question.id}]" id="answer-${question.id}">
                    </fieldset>
                `;
            }
            
            handleOptionSelection(optionBtn) {
                const questionId = optionBtn.dataset.question;
                const option = optionBtn.dataset.option;
                const fieldset = optionBtn.closest('fieldset');
                
                // Remove selected class from other options in this question
                fieldset.querySelectorAll('.option-btn').forEach(btn => {
                    btn.classList.remove('selected');
                    btn.setAttribute('aria-checked', 'false');
                    btn.setAttribute('tabindex', '-1');
                });
                
                // Add selected class to clicked option
                optionBtn.classList.add('selected');
                optionBtn.setAttribute('aria-checked', 'true');
                optionBtn.setAttribute('tabindex', '0');
                
                // Update hidden input
                document.getElementById(`answer-${questionId}`).value = option;
                this.answers[questionId] = option;
                
                // Update progress and submit button state
                this.updateProgress();
                this.updateSubmitButton();
                
                // Haptic feedback (if supported)
                this.triggerHapticFeedback();
            }
            
            updateProgress() {
                const answered = Object.keys(this.answers).length;
                const percentage = (answered / this.totalQuestions) * 100;
                
                document.getElementById('progress-fill').style.width = percentage + '%';
                document.getElementById('question-counter').textContent = `${answered}/${this.totalQuestions} answered`;
                
                // Update progress bar aria-valuenow
                const progressBar = document.querySelector('.progress-bar');
                progressBar.setAttribute('aria-valuenow', percentage);
                progressBar.setAttribute('aria-valuetext', `${answered} of ${this.totalQuestions} questions answered`);
            }
            
            updateSubmitButton() {
                const submitBtn = document.querySelector('.btn-submit');
                const answered = Object.keys(this.answers).length;
                const isComplete = answered === this.totalQuestions;
                
                submitBtn.disabled = !isComplete;
                submitBtn.setAttribute('aria-disabled', !isComplete);
                
                if (isComplete) {
                    submitBtn.classList.add('animate-pulse');
                    document.getElementById('submit-help').textContent = 'Ready to submit your quiz!';
                } else {
                    submitBtn.classList.remove('animate-pulse');
                    document.getElementById('submit-help').textContent = `Answer ${this.totalQuestions - answered} more question${this.totalQuestions - answered !== 1 ? 's' : ''} to enable submission`;
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
                }
                
                // Auto-submit when time runs out
                if (remaining <= 0) {
                    this.handleTimeUp();
                }
            }
            
            handleTimeUp() {
                clearInterval(this.timerInterval);
                this.showAlert('warning', 'Time\'s up! Your quiz will be submitted automatically.');
                
                setTimeout(() => {
                    this.handleQuizSubmit({ preventDefault: () => {} });
                }, 2000);
            }
            
            handleQuizSubmit(e) {
                e.preventDefault();
                
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                
                const submitBtn = document.querySelector('.btn-submit');
                const originalContent = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
                submitBtn.disabled = true;
                
                // Clear timer
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                }
                
                // Submit form
                const formData = new FormData(document.getElementById('quiz-form'));
                formData.append('submit_quiz', '1');
                
                fetch('quiz.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload page to show results
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error submitting quiz:', error);
                    this.showAlert('error', 'Failed to submit quiz. Please try again.');
                    
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                    this.isSubmitting = false;
                });
            }
            
            resetQuiz() {
                if (confirm('Are you sure you want to reset the quiz? All progress will be lost.')) {
                    this.answers = {};
                    
                    // Clear all selections
                    document.querySelectorAll('.option-btn').forEach(btn => {
                        btn.classList.remove('selected');
                        btn.setAttribute('aria-checked', 'false');
                    });
                    
                    // Clear hidden inputs
                    document.querySelectorAll('input[name^="answers"]').forEach(input => {
                        input.value = '';
                    });
                    
                    // Reset progress
                    this.updateProgress();
                    this.updateSubmitButton();
                    
                    // Reset timer
                    this.startTimer();
                    
                    // Scroll to top
                    document.getElementById('quiz-questions').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            }
            
            handleKeyboardNavigation(e) {
                const focused = document.activeElement;
                
                if (focused?.classList.contains('quiz-selection-card')) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        focused.click();
                    }
                }
                
                if (focused?.classList.contains('option-btn')) {
                    const fieldset = focused.closest('fieldset');
                    const options = Array.from(fieldset.querySelectorAll('.option-btn'));
                    const currentIndex = options.indexOf(focused);
                    
                    switch (e.key) {
                        case 'ArrowDown':
                        case 'ArrowRight':
                            e.preventDefault();
                            const nextIndex = (currentIndex + 1) % options.length;
                            options[nextIndex].focus();
                            break;
                        case 'ArrowUp':
                        case 'ArrowLeft':
                            e.preventDefault();
                            const prevIndex = (currentIndex - 1 + options.length) % options.length;
                            options[prevIndex].focus();
                            break;
                        case 'Enter':
                        case ' ':
                            e.preventDefault();
                            focused.click();
                            break;
                    }
                }
            }
            
            setupFocusManagement() {
                // Add focus-visible class for better focus indicators
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Tab') {
                        document.body.classList.add('using-keyboard');
                    }
                });
                
                document.addEventListener('mousedown', () => {
                    document.body.classList.remove('using-keyboard');
                });
            }
            
            setupAriaLiveRegions() {
                // Create aria-live region for announcements
                const liveRegion = document.createElement('div');
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.className = 'sr-only';
                liveRegion.id = 'live-region';
                document.body.appendChild(liveRegion);
            }
            
            announceToScreenReader(message) {
                const liveRegion = document.getElementById('live-region');
                if (liveRegion) {
                    liveRegion.textContent = message;
                    setTimeout(() => {
                        liveRegion.textContent = '';
                    }, 1000);
                }
            }
            
            showAlert(type, message) {
                const alertContainer = document.createElement('div');
                alertContainer.className = `alert alert-${type} fixed top-4 right-4 z-50 max-w-md animate-slide-up`;
                alertContainer.innerHTML = `
                    <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
                    <span>${this.escapeHtml(message)}</span>
                    <button class="ml-auto text-current opacity-70 hover:opacity-100" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                document.body.appendChild(alertContainer);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (alertContainer.parentElement) {
                        alertContainer.remove();
                    }
                }, 5000);
                
                // Announce to screen readers
                this.announceToScreenReader(message);
            }
            
            setupIntersectionObserver() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-scale-in');
                        }
                    });
                }, { threshold: 0.1 });
                
                document.querySelectorAll('.quiz-selection-card, .question-card').forEach(el => {
                    observer.observe(el);
                });
            }
            
            lazyLoadImages() {
                const images = document.querySelectorAll('img[data-src]');
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                images.forEach(img => imageObserver.observe(img));
            }
            
            handleVisibilityChange() {
                if (document.hidden && this.timerInterval) {
                    // Store timestamp when tab becomes hidden
                    this.hiddenTime = Date.now();
                } else if (!document.hidden && this.hiddenTime) {
                    // Adjust timer when tab becomes visible again
                    const hiddenDuration = Date.now() - this.hiddenTime;
                    this.startTime += hiddenDuration;
                    this.hiddenTime = null;
                }
            }
            
            triggerHapticFeedback() {
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
        
        // Global functions for backward compatibility
        function loadQuiz(quizId) {
            window.quizApp?.loadQuiz(quizId);
        }
        
        function handleCardKeydown(event, quizId) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                loadQuiz(quizId);
            }
        }
        
        // Initialize app when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            window.quizApp = new QuizApp();
        });
        
        // Service Worker registration for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
    
    <!-- Custom cursor script -->
    <script src="public/js/cursor.js"></script>
</body>
</html>
