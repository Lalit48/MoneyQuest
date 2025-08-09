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
        
        .quiz-main-container {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.9) 100%);
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
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.8), transparent);
        }
        
        .quiz-main-container::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .quiz-selection-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
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
            transition: opacity 0.5s ease;
        }
        
        .quiz-selection-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        }
        
        .quiz-selection-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 
                0 35px 60px -10px rgba(0, 0, 0, 0.5),
                0 0 30px rgba(99, 102, 241, 0.3),
                0 0 60px rgba(139, 92, 246, 0.2);
            border-color: rgba(99, 102, 241, 0.6);
        }
        
        .quiz-selection-card:hover::before {
            opacity: 1;
        }
        
        .quiz-selection-card:hover::after {
            transform: scaleX(1);
        }
        
        .quiz-selection-card:hover .card-icon {
            transform: scale(1.2) rotateY(180deg);
        }
        
        .quiz-selection-card:hover .card-title {
            color: #8b5cf6;
        }
        
        .quiz-selection-card:hover .start-button {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            transform: scale(1.05);
        }
        
        .card-icon {
            font-size: 3.5rem;
            color: #6366f1;
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(99, 102, 241, 0.3));
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            transition: all 0.4s ease;
            margin-bottom: 0.5rem;
        }
        
        .card-description {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        
        .start-button {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(139, 92, 246, 0.8));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .quiz-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #10b981, #059669);
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
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Responsive Design Enhancements */
        @media (max-width: 768px) {
            .quiz-main-container {
                padding: 2rem;
                border-radius: 24px;
            }
            
            .quiz-selection-card {
                height: 250px;
                padding: 2rem;
            }
            
            .card-icon {
                font-size: 3rem;
            }
            
            .card-title {
                font-size: 1.25rem;
            }
            
            .card-description {
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .quiz-main-container {
                padding: 1.5rem;
            }
            
            .quiz-selection-card {
                height: 220px;
                padding: 1.5rem;
            }
            
            .card-icon {
                font-size: 2.5rem;
                margin-bottom: 0.75rem;
            }
            
            .quiz-badge, .category-badge {
                font-size: 0.6rem;
                padding: 0.3rem 0.6rem;
            }
        }
        
        /* Additional Visual Effects */
        .quiz-selection-card {
            position: relative;
            overflow: hidden;
        }
        
        .quiz-selection-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(99, 102, 241, 0.1) 60deg, transparent 120deg);
            opacity: 0;
            animation: rotate 8s linear infinite;
            transition: opacity 0.5s ease;
        }
        
        .quiz-selection-card:hover::before {
            opacity: 1;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Enhanced Glow Effects */
        .quiz-selection-card:hover {
            box-shadow: 
                0 35px 60px -10px rgba(0, 0, 0, 0.5),
                0 0 30px rgba(99, 102, 241, 0.3),
                0 0 60px rgba(139, 92, 246, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        /* Tooltip Styles */
        .tooltip {
            position: absolute;
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(99, 102, 241, 0.3);
            z-index: 1000;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .tooltip.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Loading Animation */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top: 3px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Success Animation */
        .success-checkmark {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: #10b981;
            stroke-miterlimit: 10;
            margin: 0 auto;
            box-shadow: inset 0px 0px 0px #10b981;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }
        
        .success-checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: #10b981;
            fill: none;
            animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .success-checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
        }
        
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        
        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #10b981;
            }
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
            <div class="quiz-main-container" data-aos="fade-up" data-aos-delay="100">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold mb-4">
                        <i class="fas fa-brain mr-3 text-indigo-400 floating-element"></i>
                        <span class="gradient-text">Select a Quiz</span>
                    </h2>
                    <div class="w-20 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 mx-auto rounded-full"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($quizzes as $index => $quiz): ?>
                        <div class="quiz-selection-card" onclick="loadQuiz(<?php echo $quiz['quiz_id']; ?>)" data-aos="zoom-in" data-aos-delay="<?php echo 200 + ($index * 100); ?>">
                            <?php if ($index < 2): ?>
                                <span class="quiz-badge">NEW</span>
                            <?php endif; ?>
                            <span class="category-badge"><?php echo htmlspecialchars($quiz['category']); ?></span>
                            
                            <div class="flex flex-col items-center justify-center flex-1">
                                <div class="card-icon">
                                    <?php 
                                    $icons = [
                                        'Budgeting' => 'fas fa-wallet',
                                        'Investing' => 'fas fa-chart-line',
                                        'Savings' => 'fas fa-piggy-bank',
                                        'Credit' => 'fas fa-credit-card',
                                        'Insurance' => 'fas fa-shield-alt',
                                        'Taxes' => 'fas fa-calculator'
                                    ];
                                    $icon = $icons[$quiz['category']] ?? 'fas fa-brain';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <p class="card-description">Master essential financial concepts and earn rewards while learning</p>
                            </div>
                            
                            <div class="start-button">
                                <i class="fas fa-play mr-2"></i>
                                Start Quiz
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Stats Section -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 rounded-2xl border border-indigo-500/20">
                        <div class="text-3xl font-bold text-indigo-400 mb-2">
                            <i class="fas fa-trophy mr-2"></i>
                            <?php echo count($quizzes); ?>
                        </div>
                        <div class="text-gray-300">Available Quizzes</div>
                    </div>
                    <div class="text-center p-6 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-2xl border border-green-500/20">
                        <div class="text-3xl font-bold text-green-400 mb-2">
                            <i class="fas fa-coins mr-2"></i>
                            <?php echo number_format($points); ?>
                        </div>
                        <div class="text-gray-300">Your Points</div>
                    </div>
                    <div class="text-center p-6 bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-2xl border border-purple-500/20">
                        <div class="text-3xl font-bold text-purple-400 mb-2">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            <?php echo number_format($wallet_balance, 2); ?>
                        </div>
                        <div class="text-gray-300">Wallet Balance</div>
                    </div>
                </div>
            </div>
            
            <!-- Quiz Questions -->
            <div id="quiz-questions" class="quiz-main-container" style="display: none;" data-aos="fade-up" data-aos-delay="300">
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
            
            // Advanced GSAP Animations
            gsap.registerPlugin(ScrollTrigger);
            
            // Main container entrance animation
            gsap.from('.quiz-main-container', {
                duration: 1.2,
                y: 80,
                opacity: 0,
                scale: 0.95,
                ease: 'power3.out',
                delay: 0.3
            });
            
            // Quiz cards staggered animation
            gsap.from('.quiz-selection-card', {
                duration: 1,
                y: 100,
                opacity: 0,
                scale: 0.8,
                rotation: 5,
                stagger: {
                    amount: 0.6,
                    from: "start"
                },
                ease: 'back.out(1.7)',
                delay: 0.8
            });
            
            // Stats cards animation
            gsap.from('.quiz-main-container .grid > div', {
                duration: 0.8,
                y: 50,
                opacity: 0,
                stagger: 0.1,
                ease: 'power2.out',
                delay: 1.5
            });
            
            // Floating animation for icons
            gsap.to('.floating-element', {
                y: -10,
                duration: 2,
                ease: 'power1.inOut',
                yoyo: true,
                repeat: -1
            });
            
            // Enhanced hover effects for quiz cards
            $('.quiz-selection-card').each(function(index) {
                const card = this;
                const icon = $(card).find('.card-icon i')[0];
                const title = $(card).find('.card-title')[0];
                const button = $(card).find('.start-button')[0];
                
                $(card).hover(
                    function() {
                        // Hover in
                        gsap.to(card, {
                            duration: 0.4,
                            y: -15,
                            scale: 1.03,
                            ease: 'power2.out'
                        });
                        
                        gsap.to(icon, {
                            duration: 0.6,
                            scale: 1.2,
                            rotationY: 180,
                            ease: 'back.out(1.7)'
                        });
                        
                        gsap.to(title, {
                            duration: 0.3,
                            color: '#8b5cf6',
                            ease: 'power2.out'
                        });
                        
                        gsap.to(button, {
                            duration: 0.3,
                            scale: 1.05,
                            ease: 'power2.out'
                        });
                    },
                    function() {
                        // Hover out
                        gsap.to(card, {
                            duration: 0.4,
                            y: 0,
                            scale: 1,
                            ease: 'power2.out'
                        });
                        
                        gsap.to(icon, {
                            duration: 0.6,
                            scale: 1,
                            rotationY: 0,
                            ease: 'back.out(1.7)'
                        });
                        
                        gsap.to(title, {
                            duration: 0.3,
                            color: '#f8fafc',
                            ease: 'power2.out'
                        });
                        
                        gsap.to(button, {
                            duration: 0.3,
                            scale: 1,
                            ease: 'power2.out'
                        });
                    }
                );
                
                // Click animation
                $(card).on('click', function() {
                    gsap.to(card, {
                        duration: 0.1,
                        scale: 0.98,
                        ease: 'power2.out',
                        yoyo: true,
                        repeat: 1
                    });
                });
            });
            
            // Particle effect on hover
            $('.quiz-selection-card').hover(function() {
                createParticles(this);
            });
            
            function createParticles(element) {
                const rect = element.getBoundingClientRect();
                const particleCount = 5;
                
                for (let i = 0; i < particleCount; i++) {
                    const particle = $('<div class="particle"></div>').css({
                        position: 'fixed',
                        width: '4px',
                        height: '4px',
                        background: 'linear-gradient(45deg, #6366f1, #8b5cf6)',
                        borderRadius: '50%',
                        pointerEvents: 'none',
                        zIndex: 1000,
                        left: rect.left + Math.random() * rect.width,
                        top: rect.top + Math.random() * rect.height
                    });
                    
                    $('body').append(particle);
                    
                    gsap.to(particle[0], {
                        duration: 1.5,
                        y: -50,
                        x: (Math.random() - 0.5) * 100,
                        opacity: 0,
                        scale: 0,
                        ease: 'power2.out',
                        onComplete: () => particle.remove()
                    });
                }
            }
            
            // Enhanced loadQuiz function with animations
            window.loadQuiz = function(quizId) {
                // Show loading state
                const clickedCard = event.currentTarget;
                const originalContent = $(clickedCard).html();
                
                $(clickedCard).html(`
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="loading-spinner mb-4"></div>
                        <div class="text-indigo-300">Loading Quiz...</div>
                    </div>
                `);
                
                // Animate other cards out
                $('.quiz-selection-card').not(clickedCard).each(function(index) {
                    gsap.to(this, {
                        duration: 0.5,
                        opacity: 0.3,
                        scale: 0.95,
                        delay: index * 0.05,
                        ease: 'power2.out'
                    });
                });
                
                // Simulate loading and load quiz
                setTimeout(() => {
                    $.ajax({
                        url: 'quiz.php',
                        type: 'GET',
                        data: { quiz_id: quizId },
                        success: function(response) {
                            // Hide quiz selection
                            gsap.to('.quiz-main-container', {
                                duration: 0.6,
                                y: -50,
                                opacity: 0,
                                ease: 'power2.inOut',
                                onComplete: function() {
                                    $('.quiz-main-container').first().hide();
                                    
                                    // Load and show quiz questions
                                    $('#quiz-questions').show();
                                    gsap.fromTo('#quiz-questions', 
                                        { y: 50, opacity: 0 },
                                        { duration: 0.8, y: 0, opacity: 1, ease: 'power2.out' }
                                    );
                                }
                            });
                        },
                        error: function() {
                            // Restore original content on error
                            $(clickedCard).html(originalContent);
                            $('.quiz-selection-card').css({ opacity: 1, transform: 'scale(1)' });
                        }
                    });
                }, 1000);
            };
            
            // Add tooltips to quiz cards
            $('.quiz-selection-card').each(function() {
                const card = $(this);
                const category = card.find('.category-badge').text();
                const title = card.find('.card-title').text();
                
                const tooltip = $(`
                    <div class="tooltip">
                        <strong>${title}</strong><br>
                        Category: ${category}<br>
                        <em>Click to start learning!</em>
                    </div>
                `);
                
                $('body').append(tooltip);
                
                card.hover(
                    function(e) {
                        const rect = this.getBoundingClientRect();
                        tooltip.css({
                            left: rect.left + rect.width / 2 - tooltip.outerWidth() / 2,
                            top: rect.top - tooltip.outerHeight() - 10
                        }).addClass('show');
                    },
                    function() {
                        tooltip.removeClass('show');
                    }
                );
            });
            
            // Add sound effects (optional - requires audio files)
            function playSound(type) {
                // You can add actual audio files here
                // const audio = new Audio(`sounds/${type}.mp3`);
                // audio.play().catch(() => {}); // Ignore errors if audio fails
            }
            
            // Enhanced click feedback
            $('.quiz-selection-card').on('click', function() {
                playSound('click');
                
                // Create ripple effect
                const ripple = $('<div class="ripple"></div>').css({
                    position: 'absolute',
                    borderRadius: '50%',
                    background: 'rgba(99, 102, 241, 0.3)',
                    pointerEvents: 'none',
                    transform: 'scale(0)',
                    animation: 'ripple-effect 0.6s linear'
                });
                
                $(this).append(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
            
            // Add CSS for ripple effect
            $('<style>').text(`
                @keyframes ripple-effect {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                .ripple {
                    width: 20px;
                    height: 20px;
                    left: 50%;
                    top: 50%;
                    margin-left: -10px;
                    margin-top: -10px;
                }
            `).appendTo('head');
            
            // Add keyboard navigation
            let currentCardIndex = 0;
            const cards = $('.quiz-selection-card');
            
            $(document).keydown(function(e) {
                if (cards.length === 0) return;
                
                switch(e.which) {
                    case 37: // left arrow
                        currentCardIndex = Math.max(0, currentCardIndex - 1);
                        break;
                    case 39: // right arrow
                        currentCardIndex = Math.min(cards.length - 1, currentCardIndex + 1);
                        break;
                    case 13: // enter
                        cards.eq(currentCardIndex).click();
                        return;
                    default:
                        return;
                }
                
                // Update focus styles
                cards.removeClass('keyboard-focus');
                cards.eq(currentCardIndex).addClass('keyboard-focus');
                
                // Smooth scroll to focused card
                cards.eq(currentCardIndex)[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                
                e.preventDefault();
            });
            
            // Add CSS for keyboard focus
            $('<style>').text(`
                .keyboard-focus {
                    outline: 2px solid #6366f1 !important;
                    outline-offset: 4px !important;
                }
            `).appendTo('head');
        });
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
