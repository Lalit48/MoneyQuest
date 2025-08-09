<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$wallet_balance = $_SESSION['wallet_balance'];
$points = $_SESSION['points'];

// Get leaderboard data
try {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT u.name, u.points, u.wallet_balance,
               (SELECT COUNT(*) FROM achievements a WHERE a.points_required <= u.points) as badges_earned
        FROM users u
        ORDER BY u.points DESC, u.wallet_balance DESC
        LIMIT 50
    ");
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Failed to load leaderboard: ' . $e->getMessage();
    $leaderboard = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - MoneyQuest</title>
    
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
        
        /* Leaderboard Specific Styles */
        .leaderboard-section {
            padding-top: 100px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .leaderboard-section::before {
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
        
        .leaderboard-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 30;
            pointer-events: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .trophy-container {
            height: 200px;
            position: relative;
            margin-bottom: 30px;
            border-radius: 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rank-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .rank-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .rank-card:hover::before {
            left: 100%;
        }
        
        .rank-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .rank-1 {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 237, 78, 0.2) 100%);
            border-color: #ffd700;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.2) 0%, rgba(229, 229, 229, 0.2) 100%);
            border-color: #c0c0c0;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.2) 0%, rgba(218, 165, 32, 0.2) 100%);
            border-color: #cd7f32;
        }
        
        .rank-number {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rank-1 .rank-number {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rank-2 .rank-number {
            background: linear-gradient(135deg, #c0c0c0 0%, #e5e5e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rank-3 .rank-number {
            background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .user-stats {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .badge-count {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .current-user {
            border: 2px solid #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(255, 215, 0, 0.1);
        }
        
        /* Featured Player Styles */
        .featured-player {
            position: relative;
            overflow: hidden;
        }
        
        .featured-player::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .crown-animation {
            animation: crown-bounce 2s infinite ease-in-out;
        }
        
        @keyframes crown-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
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
        
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
        
        .sparkle {
            animation: sparkle 2s infinite;
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
                    <a href="quiz.php" class="nav-link page-transition-link">
                        <i class="fas fa-question-circle mr-2"></i>Quizzes
                    </a>
                    <a href="stocks.php" class="nav-link page-transition-link">
                        <i class="fas fa-chart-line mr-2"></i>Stocks
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
                <a href="quiz.php" class="nav-link page-transition-link">
                    <i class="fas fa-question-circle mr-2"></i>Quizzes
                </a>
                <a href="stocks.php" class="nav-link page-transition-link">
                    <i class="fas fa-chart-line mr-2"></i>Stocks
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

    <!-- Leaderboard Section -->
    <section class="leaderboard-section">
        <div id="particles-js" class="absolute inset-0 z-0"></div>
        <div id="threejs-container" class="absolute inset-0 z-0"></div>
        
        <div class="container relative z-10 py-12">
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="gradient-text">Leaderboard</span>
                </h1>
                <p class="text-xl text-gray-300">
                    Compete with other players and climb the ranks
                </p>
            </div>
            
            <!-- Three.js Trophy Animation -->
            <div class="trophy-container mb-8" data-aos="fade-up" data-aos-delay="100" id="trophy-container">
                <!-- Three.js content will be inserted here -->
            </div>
            
            <!-- Top Players Section - Recreated with Enhanced Visibility -->
            <div class="leaderboard-card bg-gradient-to-br from-slate-800/90 to-slate-900/90 border-2 border-indigo-500/30 shadow-2xl" 
                 data-aos="fade-up" data-aos-delay="200" 
                 style="position: relative; z-index: 50; backdrop-filter: blur(20px);">
                
                <!-- Enhanced Header -->
                <div class="text-center mb-8">
                    <h3 class="text-3xl font-bold mb-4 flex items-center justify-center">
                        <i class="fas fa-trophy mr-3 text-yellow-400 text-4xl animate-pulse"></i>
                        <span class="gradient-text">Top Players</span>
                        <i class="fas fa-crown ml-3 text-yellow-400 text-2xl crown-animation"></i>
                    </h3>
                    <div class="w-24 h-1 bg-gradient-to-r from-yellow-400 to-orange-500 mx-auto rounded-full"></div>
                    <p class="text-gray-300 mt-3">Elite MoneyQuest Champions Leading the Way</p>
                </div>
                
                <?php 
                // Enhanced Top Players Data with Better Error Handling
                $display_players = [];
                
                // Enhanced featured players with more diversity
                $featured_champions = [
                    [
                        'name' => 'lalit Khekale',
                        'points' => 15850,
                        'wallet_balance' => 2475.50,
                        'badges_earned' => 12,
                        'status' => 'champion',
                        'title' => 'MoneyQuest Legend'
                    ],
                    [
                        'name' => 'Sarah Investment',
                        'points' => 14200,
                        'wallet_balance' => 2100.75,
                        'badges_earned' => 10,
                        'status' => 'master',
                        'title' => 'Investment Guru'
                    ],
                    [
                        'name' => 'Alex Budget',
                        'points' => 13500,
                        'wallet_balance' => 1850.25,
                        'badges_earned' => 9,
                        'status' => 'expert',
                        'title' => 'Budget Master'
                    ],
                    [
                        'name' => 'Emma Trader',
                        'points' => 12800,
                        'wallet_balance' => 1650.00,
                        'badges_earned' => 8,
                        'status' => 'pro',
                        'title' => 'Trading Pro'
                    ],
                    [
                        'name' => 'Mike Saver',
                        'points' => 11900,
                        'wallet_balance' => 1450.75,
                        'badges_earned' => 7,
                        'status' => 'skilled',
                        'title' => 'Savings Expert'
                    ]
                ];
                
                // Merge database data with featured players
                if (!empty($leaderboard)) {
                    $db_names = array_column($leaderboard, 'name');
                    $featured_names = array_column($featured_champions, 'name');
                    
                    // Add database players
                    foreach ($leaderboard as $player) {
                        $display_players[] = [
                            'name' => $player['name'],
                            'points' => (int)$player['points'],
                            'wallet_balance' => (float)$player['wallet_balance'],
                            'badges_earned' => (int)$player['badges_earned'],
                            'status' => 'player',
                            'title' => 'MoneyQuest Player'
                        ];
                    }
                    
                    // Add featured players if not in database
                    foreach ($featured_champions as $featured) {
                        if (!in_array($featured['name'], $db_names)) {
                            $display_players[] = $featured;
                        }
                    }
                } else {
                    // If no database data, use featured players
                    $display_players = $featured_champions;
                }
                
                // Enhanced sorting by points then wallet balance
                usort($display_players, function($a, $b) {
                    if ($a['points'] == $b['points']) {
                        return $b['wallet_balance'] <=> $a['wallet_balance'];
                    }
                    return $b['points'] <=> $a['points'];
                });
                
                // Limit to top 20 for better display
                $display_players = array_slice($display_players, 0, 20);
                ?>
                
                <?php if (empty($display_players)): ?>
                    <!-- Enhanced Empty State -->
                    <div class="text-center py-12 bg-gradient-to-br from-indigo-900/20 to-purple-900/20 rounded-2xl border border-indigo-500/20">
                        <div class="relative inline-block">
                            <i class="fas fa-users text-8xl text-indigo-400 opacity-50 mb-6"></i>
                            <div class="absolute top-0 right-0 animate-ping">
                                <i class="fas fa-star text-yellow-400"></i>
                            </div>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-3">No Champions Yet!</h4>
                        <p class="text-gray-300 text-lg mb-2">The leaderboard awaits its first heroes.</p>
                        <p class="text-gray-400">Complete quizzes, trade stocks, and earn your place among the legends!</p>
                        <div class="mt-6">
                            <a href="quiz.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-play mr-2"></i>
                                Start Your Journey
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Enhanced Player List -->
                    <div class="space-y-4">
                        <?php foreach ($display_players as $index => $player): ?>
                            <?php 
                            $is_lalit = (strtolower($player['name']) === 'lalit khekale');
                            $is_current_user = (isset($_SESSION['name']) && strtolower($player['name']) === strtolower($_SESSION['name']));
                            $rank = $index + 1;
                            
                            // Enhanced rank styling
                            $rank_class = '';
                            $rank_gradient = '';
                            $special_effects = '';
                            
                            if ($rank === 1) {
                                $rank_class = 'border-yellow-400 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 shadow-lg shadow-yellow-400/30';
                                $rank_gradient = 'bg-gradient-to-r from-yellow-400 to-orange-500';
                                $special_effects = 'animate-pulse';
                            } elseif ($rank === 2) {
                                $rank_class = 'border-gray-300 bg-gradient-to-r from-gray-400/20 to-gray-500/20 shadow-lg shadow-gray-400/20';
                                $rank_gradient = 'bg-gradient-to-r from-gray-300 to-gray-400';
                            } elseif ($rank === 3) {
                                $rank_class = 'border-orange-400 bg-gradient-to-r from-orange-500/20 to-yellow-600/20 shadow-lg shadow-orange-400/20';
                                $rank_gradient = 'bg-gradient-to-r from-orange-400 to-yellow-600';
                            } else {
                                $rank_class = 'border-indigo-500/30 bg-gradient-to-r from-indigo-900/20 to-purple-900/20';
                                $rank_gradient = 'bg-gradient-to-r from-indigo-400 to-purple-500';
                            }
                            
                            if ($is_current_user) {
                                $rank_class .= ' ring-2 ring-green-400 ring-opacity-50';
                                $special_effects .= ' transform hover:scale-102';
                            }
                            
                            if ($is_lalit) {
                                $special_effects .= ' featured-player';
                            }
                            ?>
                            
                            <div class="rank-card border-2 <?php echo $rank_class; ?> <?php echo $special_effects; ?> rounded-xl p-6 transition-all duration-300 hover:transform hover:scale-[1.02] hover:shadow-2xl backdrop-blur-lg" 
                                 data-aos="fade-up" 
                                 data-aos-delay="<?php echo 300 + ($index * 100); ?>"
                                 style="position: relative; z-index: <?php echo 40 - $index; ?>;">
                                
                                <!-- Rank Badge -->
                                <div class="absolute -top-3 -left-3 w-12 h-12 <?php echo $rank_gradient; ?> rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    <?php if ($rank <= 3): ?>
                                        <i class="fas fa-trophy text-white"></i>
                                    <?php else: ?>
                                        <?php echo $rank; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center justify-between ml-6">
                                    <!-- Player Info -->
                                    <div class="flex items-center space-x-4 flex-grow">
                                        <!-- Rank Display -->
                                        <div class="text-center min-w-[60px]">
                                            <div class="text-3xl font-bold <?php echo $rank_gradient; ?> bg-clip-text text-transparent">
                                                #<?php echo $rank; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Player Details -->
                                        <div class="flex-grow">
                                            <div class="flex items-center mb-2">
                                                <h4 class="text-xl font-bold text-white mr-3">
                                                    <?php echo htmlspecialchars($player['name']); ?>
                                                </h4>
                                                
                                                <?php if ($is_lalit): ?>
                                                    <div class="flex items-center space-x-2">
                                                        <i class="fas fa-crown text-yellow-400 text-lg crown-animation"></i>
                                                        <span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-black px-3 py-1 rounded-full text-xs font-bold">
                                                            LEGEND
                                                        </span>
                                                    </div>
                                                <?php elseif ($rank <= 3): ?>
                                                    <span class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-3 py-1 rounded-full text-xs font-bold">
                                                        <?php echo $rank === 1 ? 'CHAMPION' : ($rank === 2 ? 'MASTER' : 'EXPERT'); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($is_current_user): ?>
                                                    <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold ml-2">
                                                        <i class="fas fa-user mr-1"></i>YOU
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Player Title -->
                                            <p class="text-gray-300 text-sm mb-3 italic">
                                                <?php echo isset($player['title']) ? $player['title'] : 'MoneyQuest Player'; ?>
                                            </p>
                                            
                                            <!-- Stats -->
                                            <div class="flex flex-wrap gap-4 text-sm">
                                                <div class="flex items-center bg-black/20 px-3 py-1 rounded-lg">
                                                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                                                    <span class="text-white font-semibold">
                                                        <?php echo number_format($player['points']); ?>
                                                    </span>
                                                    <span class="text-gray-300 ml-1">pts</span>
                                                </div>
                                                <div class="flex items-center bg-black/20 px-3 py-1 rounded-lg">
                                                    <i class="fas fa-wallet text-green-400 mr-2"></i>
                                                    <span class="text-white font-semibold">
                                                        $<?php echo number_format($player['wallet_balance'], 2); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center bg-black/20 px-3 py-1 rounded-lg">
                                                    <i class="fas fa-medal text-purple-400 mr-2"></i>
                                                    <span class="text-white font-semibold">
                                                        <?php echo $player['badges_earned']; ?>
                                                    </span>
                                                    <span class="text-gray-300 ml-1">badges</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Achievement Badge -->
                                    <div class="text-center">
                                        <div class="w-16 h-16 <?php echo $rank <= 3 ? $rank_gradient : 'bg-gradient-to-br from-indigo-500 to-purple-600'; ?> rounded-full flex items-center justify-center shadow-lg">
                                            <i class="fas fa-medal text-white text-xl"></i>
                                        </div>
                                        <div class="text-xs text-gray-300 mt-1 font-semibold">
                                            LVL <?php echo min(99, intval($player['points'] / 100) + 1); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Special Messages for Top Players -->
                                <?php if ($is_lalit): ?>
                                    <div class="mt-4 pt-4 border-t border-yellow-400/30">
                                        <div class="bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-lg p-3">
                                            <div class="flex items-center text-yellow-300">
                                                <i class="fas fa-crown mr-2 text-lg"></i>
                                                <span class="font-semibold">MoneyQuest Legend</span>
                                            </div>
                                            <p class="text-sm text-yellow-200 mt-1">
                                                Leading the financial revolution with exceptional knowledge and skills!
                                            </p>
                                        </div>
                                    </div>
                                <?php elseif ($rank === 2): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-400/30">
                                        <div class="bg-gradient-to-r from-gray-400/20 to-gray-500/20 rounded-lg p-3">
                                            <div class="flex items-center text-gray-300">
                                                <i class="fas fa-trophy mr-2"></i>
                                                <span class="font-semibold">Silver Champion</span>
                                            </div>
                                            <p class="text-sm text-gray-300 mt-1">Outstanding performance in financial mastery!</p>
                                        </div>
                                    </div>
                                <?php elseif ($rank === 3): ?>
                                    <div class="mt-4 pt-4 border-t border-orange-400/30">
                                        <div class="bg-gradient-to-r from-orange-500/20 to-yellow-600/20 rounded-lg p-3">
                                            <div class="flex items-center text-orange-300">
                                                <i class="fas fa-trophy mr-2"></i>
                                                <span class="font-semibold">Bronze Champion</span>
                                            </div>
                                            <p class="text-sm text-orange-300 mt-1">Impressive financial knowledge and consistent growth!</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Enhanced Footer -->
                    <div class="mt-8 text-center p-6 bg-gradient-to-r from-indigo-900/30 to-purple-900/30 rounded-xl border border-indigo-500/20">
                        <h4 class="text-lg font-bold text-white mb-2">
                            <i class="fas fa-chart-line mr-2 text-green-400"></i>
                            Ready to Climb the Ranks?
                        </h4>
                        <p class="text-gray-300 mb-4">Complete quizzes, make smart investments, and earn your place among the MoneyQuest legends!</p>
                        <div class="flex justify-center space-x-4">
                            <a href="quiz.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-brain mr-2"></i>
                                Take Quiz
                            </a>
                            <a href="stocks.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-chart-line mr-2"></i>
                                Trade Stocks
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
                    <p class="text-gray-400">Your Money Journey Starts Here 🚀</p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-6 text-xl">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Dashboard</a></li>
                        <li><a href="quiz.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Quizzes</a></li>
                        <li><a href="stocks.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Stocks</a></li>
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
        
        // Three.js Trophy Animation
        let trophyScene, trophyCamera, trophyRenderer, trophy;
        
        function initTrophyAnimation() {
            const container = document.getElementById('trophy-container');
            if (!container || typeof THREE === 'undefined') return;
            
            trophyScene = new THREE.Scene();
            trophyCamera = new THREE.PerspectiveCamera(75, container.offsetWidth / 200, 0.1, 1000);
            trophyRenderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            trophyRenderer.setSize(container.offsetWidth, 200);
            trophyRenderer.setClearColor(0x000000, 0);
            container.appendChild(trophyRenderer.domElement);
            
            // Create trophy geometry
            const trophyGeometry = new THREE.Group();
            
            // Trophy base
            const baseGeometry = new THREE.CylinderGeometry(2, 2.5, 0.5, 32);
            const baseMaterial = new THREE.MeshPhongMaterial({ 
                color: 0xFFD700,
                shininess: 100,
                specular: 0xffffff
            });
            const base = new THREE.Mesh(baseGeometry, baseMaterial);
            base.position.y = -1;
            trophyGeometry.add(base);
            
            // Trophy stem
            const stemGeometry = new THREE.CylinderGeometry(0.3, 0.3, 2, 32);
            const stemMaterial = new THREE.MeshPhongMaterial({ 
                color: 0xFFD700,
                shininess: 100,
                specular: 0xffffff
            });
            const stem = new THREE.Mesh(stemGeometry, stemMaterial);
            stem.position.y = 0;
            trophyGeometry.add(stem);
            
            // Trophy cup
            const cupGeometry = new THREE.CylinderGeometry(1.5, 1, 1.5, 32);
            const cupMaterial = new THREE.MeshPhongMaterial({ 
                color: 0xFFD700,
                shininess: 100,
                specular: 0xffffff
            });
            const cup = new THREE.Mesh(cupGeometry, cupMaterial);
            cup.position.y = 1.5;
            trophyGeometry.add(cup);
            
            trophy = trophyGeometry;
            trophyScene.add(trophy);
            
            // Add lighting
            const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
            trophyScene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(10, 10, 5);
            directionalLight.castShadow = true;
            trophyScene.add(directionalLight);
            
            const pointLight = new THREE.PointLight(0xffd700, 0.5);
            pointLight.position.set(0, 5, 5);
            trophyScene.add(pointLight);
            
            trophyCamera.position.z = 8;
            animateTrophy();
        }
        
        function animateTrophy() {
            requestAnimationFrame(animateTrophy);
            
            if (trophy) {
                trophy.rotation.y += 0.01;
                trophy.rotation.x = Math.sin(Date.now() * 0.001) * 0.1;
                trophy.position.y = Math.sin(Date.now() * 0.002) * 0.2;
            }
            
            if (trophyRenderer && trophyScene && trophyCamera) {
                trophyRenderer.render(trophyScene, trophyCamera);
            }
        }
        
        function onTrophyResize() {
            const container = document.getElementById('trophy-container');
            if (container && trophyCamera && trophyRenderer) {
                trophyCamera.aspect = container.offsetWidth / 200;
                trophyCamera.updateProjectionMatrix();
                trophyRenderer.setSize(container.offsetWidth, 200);
            }
        }
        
        // Initialize all features
        function initAllFeatures() {
            initParticles();
            initThreeJS();
            initTrophyAnimation();
            initPageTransitions();
            initInteractiveElements();
            
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: false,
                mirror: true
            });
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
            const hamburger = document.querySelector('.hamburger');
            const mobileNav = document.querySelector('.mobile-nav');
            if (hamburger && mobileNav) {
                hamburger.addEventListener('click', () => {
                    mobileNav.classList.toggle('active');
                });
            }
            
            // GSAP Animations
            if (typeof gsap !== 'undefined') {
                gsap.from('.rank-card', {
                    duration: 1,
                    y: 50,
                    opacity: 0,
                    stagger: 0.1,
                    ease: 'power3.out',
                    delay: 0.5
                });
            }
        }
        
        // Add sparkles to trophy
        function addSparkles() {
            const container = document.getElementById('trophy-container');
            if (!container) return;
            
            const sparkle = document.createElement('div');
            sparkle.innerHTML = '<i class="fas fa-star sparkle" style="color: #ffd700; position: absolute; font-size: 1rem;"></i>';
            sparkle.style.position = 'absolute';
            sparkle.style.left = Math.random() * 80 + 10 + '%';
            sparkle.style.top = Math.random() * 80 + 10 + '%';
            sparkle.style.pointerEvents = 'none';
            sparkle.style.zIndex = '100';
            container.appendChild(sparkle);
            
            setTimeout(() => {
                if (sparkle.parentNode) {
                    sparkle.parentNode.removeChild(sparkle);
                }
            }, 2000);
        }
        
        // Initialize everything when document is ready
        $(document).ready(function() {
            initAllFeatures();
            
            // Add sparkles periodically
            setInterval(addSparkles, 1500);
            
            // Handle window resize
            window.addEventListener('resize', () => {
                onWindowResize();
                onTrophyResize();
            });
        });
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
