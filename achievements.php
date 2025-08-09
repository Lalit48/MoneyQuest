<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_points = isset($_SESSION['points']) ? (int)$_SESSION['points'] : 0;
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$error = '';

// Get achievements data
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM achievements ORDER BY points_required ASC");
    $stmt->execute();
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Failed to load achievements: ' . $e->getMessage();
    $achievements = [];
}

// Fallback default achievements if none available
if (empty($achievements)) {
    $achievements = [
        ['id' => 1, 'title' => 'First Quiz', 'description' => 'Complete your first quiz', 'points_required' => 10, 'icon' => 'fas fa-clipboard-check'],
        ['id' => 2, 'title' => 'Stock Trader', 'description' => 'Make your first stock purchase', 'points_required' => 25, 'icon' => 'fas fa-chart-line'],
        ['id' => 3, 'title' => 'Quiz Master', 'description' => 'Complete 5 quizzes', 'points_required' => 50, 'icon' => 'fas fa-trophy'],
        ['id' => 4, 'title' => 'All-Rounder', 'description' => 'Unlock all starter achievements', 'points_required' => 100, 'icon' => 'fas fa-medal'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - MoneyQuest</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- Chart.js (not used directly here, kept for parity) -->
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

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            background: var(--darker-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; width: 100%; }
        @media (max-width: 1280px) { .container { max-width: 1000px; } }
        @media (max-width: 1024px) { .container { max-width: 900px; } }
        @media (max-width: 768px) { .container { max-width: 100%; padding: 0 1rem; } }
        @media (max-width: 480px) { .container { padding: 0 0.75rem; } }

        .navbar {
            position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; transition: all 0.3s ease;
            background: rgba(2, 6, 23, 0.8); backdrop-filter: blur(10px);
        }
        .navbar.scrolled { background: rgba(2, 6, 23, 0.95); box-shadow: 0 10px 30px -10px rgba(2, 6, 23, 0.5); }
        .nav-link { color: var(--text-secondary); font-weight: 500; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; }
        .nav-link:hover { color: var(--text-primary); background: var(--card-bg); transform: translateY(-2px); }
        .nav-btn-secondary { background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.5rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; }
        .nav-btn-secondary:hover { background: var(--glass-bg); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .nav-btn-primary { background: var(--gradient-primary); color: white; padding: 0.5rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; position: relative; overflow: hidden; z-index: 1; }
        .nav-btn-primary::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: var(--gradient-secondary); transition: all 0.4s ease; z-index: -1; }
        .nav-btn-primary:hover::before { left: 0; }
        .nav-btn-primary:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .nav-btn-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-primary); transition: all 0.3s ease; cursor: pointer; }
        .nav-btn-icon:hover { background: var(--glass-bg); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .mobile-nav { display: none; position: absolute; top: 100%; left: 0; width: 100%; background: var(--dark-bg); padding: 1rem; border-radius: 0 0 16px 16px; border-top: 1px solid var(--border-color); flex-direction: column; gap: 0.5rem; transform: translateY(-10px); opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .mobile-nav.active { transform: translateY(0); opacity: 1; visibility: visible; }
        .hamburger { display: none; cursor: pointer; }
        @media (max-width: 768px) { .desktop-nav { display: none; } .hamburger { display: block; } }

        .gradient-text { background: var(--gradient-primary); background-size: 200% 200%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: gradientShift 3s ease infinite; }
        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        .dashboard-section { padding-top: 100px; min-height: 100vh; background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%); position: relative; overflow: hidden; }
        .dashboard-section::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%); pointer-events: none; }

        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 2rem; transition: all 0.3s ease; position: relative; overflow: hidden; height: 100%; z-index: 30; pointer-events: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-primary); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary-color); }
        .stat-number { font-size: 2.2rem; font-weight: 800; margin-bottom: 0.25rem; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-shadow: 0 0 5px rgba(99, 102, 241, 0.3); position: relative; z-index: 30; }

        .feature-card-advanced { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 20px; padding: 2rem; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; text-decoration: none; color: var(--text-primary); z-index: 30; pointer-events: auto; cursor: default; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); }
        .feature-card-advanced::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%); opacity: 0; transition: opacity 0.4s ease; }
        .feature-card-advanced::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%); opacity: 0; transform: scale(0.5); transition: transform 0.6s ease, opacity 0.6s ease; }
        .feature-card-advanced:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 15px rgba(99, 102, 241, 0.5); border-color: rgba(99, 102, 241, 0.5); }
        .feature-card-advanced:hover::before { opacity: 1; }
        .feature-card-advanced:hover::after { opacity: 0.8; transform: scale(1); }
        .feature-icon-advanced { font-size: 2.5rem; margin: 0.5rem 0 1rem 0; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; position: relative; transition: all 0.4s ease; filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.3)); }
        .feature-card-advanced:hover .feature-icon-advanced { transform: scale(1.05) translateY(-3px); filter: drop-shadow(0 0 12px rgba(99, 102, 241, 0.5)); }
        .feature-badge { position: absolute; top: -10px; right: -10px; background: var(--gradient-secondary); color: white; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 20px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); transform: rotate(5deg); z-index: 40; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: rotate(5deg) scale(1); } 50% { transform: rotate(5deg) scale(1.1); } 100% { transform: rotate(5deg) scale(1); } }

        .page-transition-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 99999; }
        .transition-wipe { position: absolute; top: 0; width: 50%; height: 100%; background: var(--primary-color); transform: scaleY(0); transform-origin: bottom; transition: transform 0.8s cubic-bezier(0.86, 0, 0.07, 1); }
        .transition-wipe.left { left: 0; }
        .transition-wipe.right { right: 0; transition-delay: 0.1s; }
        body.is-transitioning .transition-wipe { transform: scaleY(1); transform-origin: top; }

        .badge-container { height: 150px; width: 100%; position: relative; }
    </style>
</head>
<body>
    <!-- Page Transition Overlay -->
    <div class="page-transition-overlay">
        <div class="transition-wipe left"></div>
        <div class="transition-wipe right"></div>
    </div>

    <!-- Navigation -->
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
                <a href="profile.php" class="nav-link page-transition-link">
                    <i class="fas fa-user-circle mr-2"></i>Profile
                </a>
                <a href="logout.php" class="nav-link page-transition-link">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Achievements Section -->
    <section class="dashboard-section">
        <div id="particles-js" class="absolute inset-0 z-0"></div>
        <div id="threejs-container" class="absolute inset-0 z-0"></div>

        <div class="container relative z-10 py-12">
            <div class="text-center mb-10" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold mb-3">
                    <span class="gradient-text">Achievements</span>
                </h1>
                <p class="text-lg text-gray-300">Track your progress and unlock badges</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number"><?php echo number_format($user_points); ?></div>
                            <p class="text-gray-400">Total Points</p>
                        </div>
                        <div class="text-4xl text-indigo-400">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number">
                                <?php 
                                $unlocked_count = 0;
                                foreach ($achievements as $achievement) {
                                    if ($user_points >= $achievement['points_required']) {
                                        $unlocked_count++;
                                    }
                                }
                                echo $unlocked_count;
                                ?>
                            </div>
                            <p class="text-gray-400">Achievements Unlocked</p>
                        </div>
                        <div class="text-4xl text-indigo-400">
                            <i class="fas fa-medal"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number"><?php echo count($achievements); ?></div>
                            <p class="text-gray-400">Total Achievements</p>
                        </div>
                        <div class="text-4xl text-indigo-400">
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievements Grid -->
            <div class="bg-slate-800/30 rounded-2xl p-6 border border-slate-700/50" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-trophy mr-3 text-indigo-400"></i>
                    Your Achievements
                </h3>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 p-4 rounded-lg border border-red-500/30 bg-red-500/10 text-red-200">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($achievements as $achievement): ?>
                        <?php $is_unlocked = $user_points >= $achievement['points_required']; ?>
                        <?php $progress = $achievement['points_required'] > 0 ? min(100, ($user_points / $achievement['points_required']) * 100) : 100; ?>

                        <div class="feature-card-advanced">
                            <?php if ($is_unlocked): ?>
                                <span class="feature-badge">Unlocked</span>
                            <?php endif; ?>

                            <div class="badge-container mb-2" id="badge-<?php echo $achievement['id']; ?>"></div>

                            <div class="feature-icon-advanced">
                                <i class="<?php echo htmlspecialchars($achievement['icon']); ?>"></i>
                            </div>

                            <h4 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($achievement['title']); ?></h4>
                            <p class="text-gray-300 mb-4"><?php echo htmlspecialchars($achievement['description']); ?></p>

                            <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden mb-2 border border-slate-600/40">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="text-sm text-gray-400">
                                <?php echo (int)$user_points; ?> / <?php echo (int)$achievement['points_required']; ?> points
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
                        <li><a href="quiz.php" class="text-gray-400 hover:text-white transition-colors duration-300 page-transition-link">Quizzes</a></li>
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
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-facebook text-2xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-twitter text-2xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-instagram text-2xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-linkedin text-2xl"></i></a>
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
        AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true });

        // Sticky Navbar
        $(window).scroll(function() {
            const scrollTop = $(window).scrollTop();
            if (scrollTop > 50) { $('#navbar').addClass('scrolled'); } else { $('#navbar').removeClass('scrolled'); }
        });

        // Particles background
        function initParticles() {
            if (typeof particlesJS !== 'undefined') {
                const particlesContainer = document.getElementById('particles-js');
                if (particlesContainer) { particlesContainer.style.pointerEvents = 'none'; }
                particlesJS('particles-js', {
                    particles: {
                        number: { value: 50, density: { enable: true, value_area: 800 } },
                        color: { value: "#6366f1" },
                        shape: { type: "circle" },
                        opacity: { value: 0.5, random: true },
                        size: { value: 3, random: true },
                        line_linked: { enable: true, distance: 150, color: "#6366f1", opacity: 0.4, width: 1 },
                        move: { enable: true, speed: 4, direction: "none", random: false, straight: false, out_mode: "out", bounce: false }
                    },
                    interactivity: {
                        detect_on: "canvas",
                        events: { onhover: { enable: true, mode: "repulse" }, onclick: { enable: true, mode: "push" }, resize: true }
                    },
                    retina_detect: true
                });
            }
        }

        // Three.js starfield background
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
            const geometry = new THREE.SphereGeometry(0.1, 24, 24);
            const material = new THREE.MeshBasicMaterial({ color: 0xffffff });
            for (let i = 0; i < 200; i++) {
                const star = new THREE.Mesh(geometry, material);
                star.position.x = Math.random() * 100 - 50;
                star.position.y = Math.random() * 100 - 50;
                star.position.z = Math.random() * 100 - 50;
                star.userData = { rotationSpeed: Math.random() * 0.01, pulseSpeed: Math.random() * 0.01 };
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
            if (renderer && scene && camera) renderer.render(scene, camera);
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
                        setTimeout(() => { window.location.href = destination; }, 1000);
                    }
                });
            });
        }

        // Interactive Elements
        function initInteractiveElements() {
            const hamburger = document.querySelector('.hamburger');
            const mobileNav = document.querySelector('.mobile-nav');
            if (hamburger && mobileNav) {
                hamburger.addEventListener('click', () => { mobileNav.classList.toggle('active'); });
            }
            gsap.from('.stat-card', { duration: 1, y: 50, opacity: 0, stagger: 0.2, ease: 'power3.out' });
            gsap.from('.feature-card-advanced', { duration: 1, y: 50, opacity: 0, stagger: 0.1, ease: 'power3.out', delay: 0.4 });
        }

        // Badge creation for each achievement
        const badges = {};
        function createBadge(containerId, isUnlocked) {
            const container = document.getElementById(containerId);
            if (!container || typeof THREE === 'undefined') return;
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, container.offsetWidth / 150, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(container.offsetWidth, 150);
            container.appendChild(renderer.domElement);
            const badgeGroup = new THREE.Group();
            const baseGeometry = new THREE.CircleGeometry(2, 32);
            const baseMaterial = new THREE.MeshPhongMaterial({ color: isUnlocked ? 0xFFD700 : 0xcccccc, side: THREE.DoubleSide });
            const base = new THREE.Mesh(baseGeometry, baseMaterial);
            badgeGroup.add(base);
            const rimGeometry = new THREE.RingGeometry(1.8, 2, 32);
            const rimMaterial = new THREE.MeshPhongMaterial({ color: isUnlocked ? 0xFFA500 : 0x999999, side: THREE.DoubleSide });
            const rim = new THREE.Mesh(rimGeometry, rimMaterial);
            badgeGroup.add(rim);
            const centerGeometry = new THREE.CircleGeometry(1.5, 32);
            const centerMaterial = new THREE.MeshPhongMaterial({ color: isUnlocked ? 0x667eea : 0x666666, side: THREE.DoubleSide });
            const center = new THREE.Mesh(centerGeometry, centerMaterial);
            center.position.z = 0.1;
            badgeGroup.add(center);
            badgeGroup.position.z = 0;
            scene.add(badgeGroup);
            const ambientLight = new THREE.AmbientLight(0x404040); scene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1); directionalLight.position.set(10, 10, 5); scene.add(directionalLight);
            camera.position.z = 5;
            function animateBadge() {
                requestAnimationFrame(animateBadge);
                badgeGroup.rotation.y += 0.01;
                if (isUnlocked) { badgeGroup.rotation.x = Math.sin(Date.now() * 0.002) * 0.1; }
                renderer.render(scene, camera);
            }
            animateBadge();
            badges[containerId] = { scene, camera, renderer, badgeGroup };
        }

        function initBadges() {
            const achievementCards = document.querySelectorAll('.feature-card-advanced');
            achievementCards.forEach(card => {
                const badgeContainer = card.querySelector('.badge-container');
                if (badgeContainer) {
                    const isUnlocked = !!card.querySelector('.feature-badge');
                    createBadge(badgeContainer.id, isUnlocked);
                }
            });
        }

        // Sparkles for unlocked achievements
        function addSparkles() {
            const unlockedLabels = document.querySelectorAll('.feature-card-advanced .feature-badge');
            unlockedLabels.forEach(label => {
                const card = label.closest('.feature-card-advanced');
                if (!card) return;
                if (Math.random() < 0.3) {
                    const sparkle = document.createElement('div');
                    sparkle.innerHTML = '<i class="fas fa-star" style="color: #ffd700; position: absolute; animation: sparkle 2s infinite;"></i>';
                    sparkle.style.position = 'absolute';
                    sparkle.style.left = Math.random() * 100 + '%';
                    sparkle.style.top = Math.random() * 100 + '%';
                    sparkle.style.animationDelay = (Math.random() * 2) + 's';
                    card.appendChild(sparkle);
                    setTimeout(() => { sparkle.remove(); }, 2000);
                }
            });
        }
        setInterval(addSparkles, 2000);
    </script>

    <style>
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
    </style>

    <script>
        // Initialize when ready
        $(document).ready(function() {
            initParticles();
            initThreeJS();
            initPageTransitions();
            initInteractiveElements();
            initBadges();
        });
    </script>

    <script src="public/js/cursor.js"></script>
</body>
</html>
