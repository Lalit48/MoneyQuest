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
            
            <!-- Top Players Section -->
            <div class="leaderboard-card" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-trophy mr-3 text-yellow-400"></i>
                    Top Players
                </h3>
                
                <?php 
                // Recreated Top Players - featuring lalit Khekale
                $featured_players = [
                    [
                        'name' => 'lalit Khekale',
                        'points' => 15850,
                        'wallet_balance' => 2475.50,
                        'badges_earned' => 12,
                        'rank' => 1
                    ]
                ];
                
                // Merge with existing leaderboard data, ensuring lalit Khekale is featured prominently
                $top_players = [];
                $lalit_found = false;
                
                foreach ($leaderboard as $index => $player) {
                    if (strtolower($player['name']) === 'lalit khekale') {
                        $lalit_found = true;
                        // Update lalit's data to featured version if found in DB
                        $top_players[] = [
                            'name' => 'lalit Khekale',
                            'points' => max($player['points'], 15850),
                            'wallet_balance' => max($player['wallet_balance'], 2475.50),
                            'badges_earned' => max($player['badges_earned'], 12),
                            'rank' => 1
                        ];
                    } else {
                        $top_players[] = $player;
                    }
                }
                
                // If lalit wasn't found in DB, add featured data
                if (!$lalit_found) {
                    array_unshift($top_players, $featured_players[0]);
                }
                
                // Sort by points descending
                usort($top_players, function($a, $b) {
                    if ($a['points'] == $b['points']) {
                        return $b['wallet_balance'] <=> $a['wallet_balance'];
                    }
                    return $b['points'] <=> $a['points'];
                });
                
                // Limit to top 50
                $top_players = array_slice($top_players, 0, 50);
                ?>
                
                <?php if (empty($top_players)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-users text-6xl text-gray-400 mb-4"></i>
                        <p class="text-gray-400 text-lg">No players found yet.</p>
                        <p class="text-gray-500">Be the first to earn points and claim your spot!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($top_players as $index => $player): ?>
                            <?php 
                            $is_lalit = (strtolower($player['name']) === 'lalit khekale');
                            $is_current_user = (isset($_SESSION['name']) && $player['name'] === $_SESSION['name']);
                            ?>
                            <div class="rank-card <?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?> <?php echo $is_current_user ? 'current-user' : ''; ?> <?php echo $is_lalit ? 'border-2 border-yellow-400 shadow-lg shadow-yellow-400/20 featured-player' : ''; ?>" data-aos="fade-up" data-aos-delay="<?php echo 300 + ($index * 50); ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="rank-number w-16 flex-shrink-0">
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy text-2xl <?php echo $index == 0 ? 'text-yellow-400' : ($index == 1 ? 'text-gray-300' : 'text-orange-400'); ?>"></i>
                                            <?php else: ?>
                                                <span class="text-2xl">#<?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow">
                                            <div class="user-name flex items-center">
                                                <?php echo htmlspecialchars($player['name']); ?>
                                                <?php if ($is_lalit): ?>
                                                    <i class="fas fa-crown ml-2 text-yellow-400 crown-animation"></i>
                                                    <span class="ml-2 text-xs bg-yellow-400 text-black px-2 py-1 rounded-full font-bold">TOP PLAYER</span>
                                                <?php endif; ?>
                                                <?php if ($is_current_user): ?>
                                                    <i class="fas fa-user-circle ml-2 text-yellow-400"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-stats">
                                                <span class="inline-flex items-center mr-4">
                                                    <i class="fas fa-star mr-1 text-yellow-400"></i>
                                                    <?php echo number_format($player['points']); ?> points
                                                </span>
                                                <span class="inline-flex items-center">
                                                    <i class="fas fa-wallet mr-1 text-green-400"></i>
                                                    $<?php echo number_format($player['wallet_balance'], 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="badge-count <?php echo $is_lalit ? 'bg-yellow-400/20 border-yellow-400' : ''; ?>">
                                        <i class="fas fa-medal mr-1"></i>
                                        <span><?php echo $player['badges_earned']; ?></span>
                                    </div>
                                </div>
                                <?php if ($is_lalit): ?>
                                    <div class="mt-3 pt-3 border-t border-yellow-400/30">
                                        <div class="text-sm text-yellow-300 flex items-center">
                                            <i class="fas fa-trophy mr-2"></i>
                                            <span>MoneyQuest Champion - Leading with exceptional financial knowledge!</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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
