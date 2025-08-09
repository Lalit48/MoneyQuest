<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
try {
    $conn = getConnection();
    
    // Get user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get portfolio summary
    $stmt = $conn->prepare("
        SELECT p.stock_symbol, p.quantity, p.avg_price, s.current_price, s.name,
               (p.quantity * s.current_price) as current_value,
               (p.quantity * s.current_price - p.quantity * p.avg_price) as profit_loss
        FROM portfolio p
        JOIN stocks s ON p.stock_symbol = s.symbol
        WHERE p.user_id = ?
        ORDER BY p.stock_symbol
    ");
    $stmt->execute([$user_id]);
    $portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent transactions
    $stmt = $conn->prepare("
        SELECT type, amount, description, timestamp
        FROM transactions
        WHERE user_id = ?
        ORDER BY timestamp DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get achievements
    $stmt = $conn->prepare("
        SELECT title, description, points_required, icon
        FROM achievements
        WHERE points_required <= ?
        ORDER BY points_required ASC
    ");
    $stmt->execute([$user['points']]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = 'Failed to load profile data: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - MoneyQuest</title>
    
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
            --primary-color: #6c5ce7;
            --secondary-color: #8b5cf6;
            --accent-color: #00cec9;
            --success-color: #10b981;
            --warning-color: #f97316;
            --error-color: #ef4444;
            --dark-bg: #0f172a;
            --darker-bg: #020617;
            --card-bg: rgba(30, 41, 59, 0.7);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --gradient-primary: linear-gradient(90deg, #6c5ce7, #00cec9, #a29bfe);
            --gradient-secondary: linear-gradient(135deg, #ff0844, #ffb199);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            --glow-shadow: 0 0 15px rgba(108, 92, 231, 0.5);
        }
        
        /* Modern Typography */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            background: var(--darker-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--darker-bg);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
            border-radius: 10px;
            border: 2px solid var(--darker-bg);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, var(--accent-color), var(--primary-color));
            cursor: pointer;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }
        
        .nav-btn-primary:hover::before {
            left: 100%;
        }
        
        .nav-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Profile Section Styles */
        .profile-section {
            padding-top: 100px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            position: relative;
        }
        
        .gradient-text {
            background: var(--gradient-primary);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 5s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Profile Card Styles */
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
            z-index: 30;
            pointer-events: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            background-size: 200% auto;
            animation: gradientShift 5s ease infinite;
        }
        
        .profile-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(108, 92, 231, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(0, 206, 201, 0.15) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }
        
        .profile-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: var(--shadow-lg), var(--glow-shadow);
            border-color: var(--primary-color);
        }
        
        .profile-card:hover::after {
            opacity: 1;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .profile-avatar::after {
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
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
            z-index: 30;
            pointer-events: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-primary);
            background-size: 200% auto;
            animation: gradientShift 5s ease infinite;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(108, 92, 231, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0.5;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-lg), 0 0 15px rgba(108, 92, 231, 0.3);
            border-color: var(--primary-color);
        }
        
        .stat-card:hover::after {
            transform: scale(1.5);
            opacity: 0.7;
        }
        
        .stat-card:hover .text-5xl {
            transform: scale(1.1) rotate(5deg);
            color: var(--accent-color);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 5px rgba(99, 102, 241, 0.3);
            position: relative;
            z-index: 30;
        }
        
        .section-title {
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .transaction-item {
            background: rgba(15, 23, 42, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }
        
        .transaction-item:hover {
            transform: translateX(5px) scale(1.01);
            background: rgba(15, 23, 42, 0.5);
            box-shadow: 0 0 10px rgba(108, 92, 231, 0.2);
        }
        
        .transaction-amount {
            font-weight: bold;
        }
        
        .transaction-amount.positive {
            color: var(--success-color);
        }
        
        .transaction-amount.negative {
            color: var(--error-color);
        }
        
        .portfolio-item {
            background: rgba(15, 23, 42, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--success-color);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }
        
        .portfolio-item:hover {
            transform: translateX(5px) scale(1.01);
            background: rgba(15, 23, 42, 0.5);
            box-shadow: 0 0 10px rgba(108, 92, 231, 0.2);
        }
        
        .profit {
            color: var(--success-color);
            font-weight: bold;
        }
        
        .loss {
            color: var(--error-color);
            font-weight: bold;
        }
        
        .achievement-badge {
            display: inline-block;
            background: var(--gradient-primary);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .achievement-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            z-index: -1;
        }
        
        .achievement-badge:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2), 0 0 10px rgba(108, 92, 231, 0.4);
        }
        
        .achievement-badge:hover::before {
            left: 100%;
        }
        
        .achievement-badge i {
            margin-right: 5px;
            transform: scale(1);
            transition: transform 0.3s ease;
        }
        
        .achievement-badge:hover i {
            transform: scale(1.2) rotate(10deg);
        }
        
        /* Feature Card Advanced - For Portfolio and Transactions */
        .feature-card-advanced {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            z-index: 30;
            pointer-events: auto;
            cursor: pointer;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .feature-card-advanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            background-size: 200% auto;
            animation: gradientShift 5s ease infinite;
        }
        
        .feature-card-advanced::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(108, 92, 231, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0.5;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .feature-card-advanced:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 15px rgba(99, 102, 241, 0.5);
            border-color: rgba(99, 102, 241, 0.5);
        }
        
        .feature-card-advanced:hover::before {
            opacity: 1;
        }
        
        .feature-card-advanced:hover::after {
            transform: scale(1.2);
            opacity: 0.7;
        }
        
        /* Badge for new features */
        .feature-badge {
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
    <!-- Custom Cursor -->
    <div class="cursor-dot-outline"></div>
    <div class="cursor-dot"></div>
    
    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 bg-darker-bg z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="relative">
                <div class="inline-block animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-indigo-500"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <i class="fas fa-user text-indigo-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container py-4">
            <div class="flex items-center justify-between">
                <a href="dashboard.php" class="flex items-center space-x-3 page-transition-link">
                    <div class="bg-indigo-500/20 p-2 rounded-lg">
                        <i class="fas fa-coins text-indigo-400 text-2xl"></i>
                    </div>
                    <span class="text-xl font-bold">MoneyQuest</span>
                </a>
                <div class="hidden md:flex space-x-8 desktop-nav">
                    <a href="quiz.php" class="nav-link page-transition-link">
                        <i class="fas fa-question-circle mr-2"></i>Quizzes
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
                    <a href="dashboard.php" class="nav-btn-secondary hidden md:flex page-transition-link">
                        <i class="fas fa-home mr-2"></i>Dashboard
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
                <a href="quiz.php" class="nav-link page-transition-link">
                    <i class="fas fa-question-circle mr-2"></i>Quizzes
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
                <a href="dashboard.php" class="nav-link page-transition-link">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="logout.php" class="nav-link page-transition-link">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="profile-section">
        <div id="particles-js" class="absolute inset-0 z-0"></div>
        <div id="threejs-container" class="absolute inset-0 z-0"></div>
        
        <div class="container relative z-10 py-12">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="gradient-text"><?php echo htmlspecialchars($user['name']); ?>'s</span> Profile
                </h1>
                <p class="text-xl text-gray-300">
                    Track your progress and manage your financial journey
                </p>
            </div>
            
            <!-- Profile Header -->
            <div class="profile-card mb-12" data-aos="fade-up" data-aos-delay="100">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="profile-avatar mb-6 md:mb-0 md:mr-8">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="text-center md:text-left">
                        <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="text-gray-400 mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-gray-400">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12 relative z-30" style="position: relative; z-index: 30;" data-aos="fade-up" data-aos-delay="150">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="stat-number">$<?php echo number_format($user['wallet_balance'], 2); ?></h3>
                            <p class="text-gray-400 text-lg">Wallet Balance</p>
                        </div>
                        <div class="text-5xl text-indigo-400">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="stat-number"><?php echo number_format($user['points']); ?></h3>
                            <p class="text-gray-400 text-lg">Total Points</p>
                        </div>
                        <div class="text-5xl text-indigo-400">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="stat-number"><?php echo count($portfolio); ?></h3>
                            <p class="text-gray-400 text-lg">Stocks Owned</p>
                        </div>
                        <div class="text-5xl text-indigo-400">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="stat-number"><?php echo count($achievements); ?></h3>
                            <p class="text-gray-400 text-lg">Achievements</p>
                        </div>
                        <div class="text-5xl text-indigo-400">
                            <i class="fas fa-medal"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Portfolio and Transactions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12" data-aos="fade-up" data-aos-delay="200">
                <!-- Portfolio Summary -->
                <div class="feature-card-advanced">
                    <span class="feature-badge">LIVE</span>
                    <h4 class="text-2xl font-bold mb-6 flex items-center">
                        <i class="fas fa-briefcase mr-3 text-indigo-400"></i>
                        Portfolio Summary
                    </h4>
                    
                    <?php if (empty($portfolio)): ?>
                        <div class="flex flex-col items-center justify-center h-64 text-center">
                            <div class="text-5xl text-indigo-400 mb-4">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <p class="text-xl text-gray-400">You don't have any stocks in your portfolio yet.</p>
                            <a href="stocks.php" class="mt-4 bg-indigo-500/20 px-4 py-2 rounded-full text-sm font-medium text-indigo-300 hover:bg-indigo-500/30 transition-all">
                                <i class="fas fa-plus-circle mr-1"></i> Start Investing
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 overflow-y-auto max-h-80">
                            <?php foreach ($portfolio as $item): ?>
                                <div class="portfolio-item">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h6 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($item['stock_symbol']); ?></h6>
                                            <p class="text-gray-400"><?php echo htmlspecialchars($item['name']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold"><?php echo $item['quantity']; ?> shares</div>
                                            <div class="text-gray-400">$<?php echo number_format($item['current_value'], 2); ?></div>
                                            <div class="<?php echo $item['profit_loss'] >= 0 ? 'profit' : 'loss'; ?>">
                                                <?php echo ($item['profit_loss'] >= 0 ? '+' : '') . '$' . number_format($item['profit_loss'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Transactions -->
                <div class="feature-card-advanced">
                    <h4 class="text-2xl font-bold mb-6 flex items-center">
                        <i class="fas fa-history mr-3 text-indigo-400"></i>
                        Recent Transactions
                    </h4>
                    
                    <?php if (empty($transactions)): ?>
                        <div class="flex flex-col items-center justify-center h-64 text-center">
                            <div class="text-5xl text-indigo-400 mb-4">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <p class="text-xl text-gray-400">No transactions yet.</p>
                            <p class="text-gray-400">Your financial activity will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 overflow-y-auto max-h-80">
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="transaction-item">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold text-lg"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                            <div class="text-gray-400 text-sm"><?php echo date('M j, Y g:i A', strtotime($transaction['timestamp'])); ?></div>
                                        </div>
                                        <div class="transaction-amount <?php echo $transaction['amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo ($transaction['amount'] >= 0 ? '+' : '') . '$' . number_format($transaction['amount'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Achievements -->
            <div class="profile-card" data-aos="fade-up" data-aos-delay="250">
                <h4 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-medal mr-3 text-indigo-400"></i>
                    Achievements Unlocked
                </h4>
                
                <?php if (empty($achievements)): ?>
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="text-5xl text-indigo-400 mb-4">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <p class="text-xl text-gray-400">No achievements unlocked yet.</p>
                        <p class="text-gray-400">Keep learning and completing activities to earn badges!</p>
                    </div>
                <?php else: ?>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="achievement-badge">
                                <i class="<?php echo $achievement['icon']; ?> mr-2"></i>
                                <?php echo htmlspecialchars($achievement['title']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/cursor.js"></script>
    
    <!-- Initialize AOS -->    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false,
            delay: 100
        });
        
        // Handle loading screen
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading screen with a smooth fade out
            setTimeout(function() {
                const loadingScreen = document.getElementById('loading-screen');
                loadingScreen.style.transition = 'opacity 0.5s ease';
                loadingScreen.style.opacity = '0';
                
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                }, 500);
            }, 800);
        });
    </script>
    
    <!-- Initialize Particles.js -->    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS("particles-js", {
                "particles": {
                    "number": {
                        "value": 80,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": "#6c5ce7"
                    },
                    "shape": {
                        "type": "circle",
                        "stroke": {
                            "width": 0,
                            "color": "#000000"
                        },
                        "polygon": {
                            "nb_sides": 5
                        }
                    },
                    "opacity": {
                        "value": 0.3,
                        "random": false,
                        "anim": {
                            "enable": false,
                            "speed": 1,
                            "opacity_min": 0.1,
                            "sync": false
                        }
                    },
                    "size": {
                        "value": 3,
                        "random": true,
                        "anim": {
                            "enable": false,
                            "speed": 40,
                            "size_min": 0.1,
                            "sync": false
                        }
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#a29bfe",
                        "opacity": 0.2,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 2,
                        "direction": "none",
                        "random": false,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false,
                        "attract": {
                            "enable": false,
                            "rotateX": 600,
                            "rotateY": 1200
                        }
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "grab"
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        },
                        "resize": true
                    },
                    "modes": {
                        "grab": {
                            "distance": 140,
                            "line_linked": {
                                "opacity": 0.5
                            }
                        },
                        "bubble": {
                            "distance": 400,
                            "size": 40,
                            "duration": 2,
                            "opacity": 8,
                            "speed": 3
                        },
                        "repulse": {
                            "distance": 200,
                            "duration": 0.4
                        },
                        "push": {
                            "particles_nb": 4
                        },
                        "remove": {
                            "particles_nb": 2
                        }
                    }
                },
                "retina_detect": true
            });
        });
    </script>
    
    <!-- Initialize Three.js Background -->    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Three.js background setup
            const container = document.getElementById('threejs-container');
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 50;
            
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            container.appendChild(renderer.domElement);
            
            // Create stars
            const starsGeometry = new THREE.BufferGeometry();
            const starsMaterial = new THREE.PointsMaterial({
                color: 0xffffff,
                size: 0.7,
                transparent: true,
                opacity: 0.8
            });
            
            const starsVertices = [];
            for (let i = 0; i < 1000; i++) {
                const x = (Math.random() - 0.5) * 2000;
                const y = (Math.random() - 0.5) * 2000;
                const z = (Math.random() - 0.5) * 2000;
                starsVertices.push(x, y, z);
            }
            
            starsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starsVertices, 3));
            const stars = new THREE.Points(starsGeometry, starsMaterial);
            scene.add(stars);
            
            // Animation
            function animate() {
                requestAnimationFrame(animate);
                stars.rotation.x += 0.0001;
                stars.rotation.y += 0.0001;
                renderer.render(scene, camera);
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
            
            animate();
        });
    </script>
</body>
</html>
