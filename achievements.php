<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$nameFromSession = isset($_SESSION['name']) ? (string)$_SESSION['name'] : 'User';

$errorMessage = '';
$userName = $nameFromSession;
$userPoints = 0;
$achievements = [];

try {
    $conn = getConnection();

    // Fetch fresh user points and name from DB to avoid stale session data
    $userStmt = $conn->prepare('SELECT name, points FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        // User disappeared; force logout
        header('Location: logout.php');
        exit();
    }

    $userName = (string)$userRow['name'];
    $userPoints = (int)$userRow['points'];

    // Keep session in sync with DB
    $_SESSION['points'] = $userPoints;
    $_SESSION['name'] = $userName;

    // Fetch achievements
    $achStmt = $conn->prepare('SELECT id, title, description, points_required, icon FROM achievements ORDER BY points_required ASC, id ASC');
    $achStmt->execute();
    $achievements = (array)$achStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorMessage = 'Failed to load achievements. Please try again later.';
    // In development, you may want to expose: $e->getMessage()
}

if (empty($achievements)) {
    $achievements = [
        ['id' => 1, 'title' => 'First Quiz',   'description' => 'Complete your first quiz',     'points_required' => 10,  'icon' => 'fas fa-clipboard-check'],
        ['id' => 2, 'title' => 'Stock Trader', 'description' => 'Make your first stock trade',  'points_required' => 25,  'icon' => 'fas fa-chart-line'],
        ['id' => 3, 'title' => 'Quiz Master',  'description' => 'Complete 5 quizzes',           'points_required' => 50,  'icon' => 'fas fa-trophy'],
        ['id' => 4, 'title' => 'All-Rounder',  'description' => 'Unlock all starter badges',    'points_required' => 100, 'icon' => 'fas fa-medal'],
    ];
}

// Compute unlocked stats
$unlockedCount = 0;
$totalAchievements = count($achievements);
foreach ($achievements as $a) {
    if ($userPoints >= (int)$a['points_required']) {
        $unlockedCount++;
    }
}
$overallProgressPercent = $totalAchievements > 0 ? (int)floor(($unlockedCount / $totalAchievements) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Achievements - MoneyQuest</title>

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />

    <!-- GSAP & AOS for smooth animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Three.js for lightweight 3D badges -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- Particles background -->
    <script src="public/js/particles.min.js"></script>

    <!-- Custom Cursor -->
    <link rel="stylesheet" href="public/css/cursor.css" />

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
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --gradient-secondary: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ef4444 100%);
        }

        body { background: var(--darker-bg); color: var(--text-primary); }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; }

        .navbar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: rgba(2, 6, 23, 0.7); backdrop-filter: blur(10px); transition: all 0.3s ease; }
        .navbar.scrolled { background: rgba(2, 6, 23, 0.95); box-shadow: 0 10px 30px -10px rgba(2, 6, 23, 0.5); }
        .nav-link { color: var(--text-secondary); font-weight: 500; padding: 0.5rem 1rem; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; }
        .nav-link:hover { color: var(--text-primary); background: var(--card-bg); }
        .nav-btn-secondary { background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; }
        .nav-btn-primary { background: var(--gradient-primary); color: white; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; }

        .dashboard-section { padding-top: 100px; min-height: 100vh; position: relative; overflow: hidden; background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%); }

        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease; }
        .stat-card:hover { transform: translateY(-4px); border-color: var(--primary-color); box-shadow: 0 15px 35px rgba(0,0,0,0.35); }
        .stat-number { font-size: 2rem; font-weight: 800; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        .card { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 18px; padding: 1.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease; position: relative; overflow: hidden; }
        .card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5); border-color: rgba(99, 102, 241, 0.5); }
        .card .badge { position: absolute; top: 10px; right: 10px; background: var(--gradient-secondary); color: white; font-size: 0.7rem; font-weight: 800; padding: 0.25rem 0.6rem; border-radius: 9999px; box-shadow: 0 4px 10px rgba(0,0,0,0.25); }

        .badge-canvas { width: 100%; height: 140px; position: relative; }

        .filter-chip { border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-secondary); padding: 0.35rem 0.75rem; border-radius: 9999px; cursor: pointer; transition: all 0.2s ease; }
        .filter-chip.active, .filter-chip:hover { color: white; border-color: var(--primary-color); background: rgba(99, 102, 241, 0.2); }

        .search-input { background: rgba(2, 6, 23, 0.6); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.6rem 0.9rem; color: white; outline: none; width: 100%; }
        .search-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }

        .page-transition-overlay { position: fixed; inset: 0; pointer-events: none; z-index: 99999; }
        .transition-wipe { position: absolute; top: 0; width: 50%; height: 100%; background: var(--primary-color); transform: scaleY(0); transform-origin: bottom; transition: transform 0.8s cubic-bezier(0.86, 0, 0.07, 1); }
        .transition-wipe.left { left: 0; }
        .transition-wipe.right { right: 0; transition-delay: 0.1s; }
        body.is-transitioning .transition-wipe { transform: scaleY(1); transform-origin: top; }
    </style>
</head>
<body>
    <!-- Page Transition Overlay -->
    <div class="page-transition-overlay" aria-hidden="true">
        <div class="transition-wipe left"></div>
        <div class="transition-wipe right"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar py-4" id="navbar">
        <div class="container">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center space-x-3 nav-link !p-0">
                    <i class="fas fa-coins text-2xl text-indigo-400" aria-hidden="true"></i>
                    <span class="text-xl font-bold" style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">MoneyQuest</span>
                </a>
                <div class="hidden md:flex space-x-6">
                    <a href="quiz.php" class="nav-link page-transition-link"><i class="fas fa-question-circle mr-2" aria-hidden="true"></i>Quizzes</a>
                    <a href="stocks.php" class="nav-link page-transition-link"><i class="fas fa-chart-line mr-2" aria-hidden="true"></i>Stocks</a>
                    <a href="leaderboard.php" class="nav-link page-transition-link"><i class="fas fa-trophy mr-2" aria-hidden="true"></i>Leaderboard</a>
                    <a href="achievements.php" class="nav-link page-transition-link"><i class="fas fa-medal mr-2" aria-hidden="true"></i>Achievements</a>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="profile.php" class="nav-btn-secondary hidden md:inline-flex page-transition-link">
                        <i class="fas fa-user-circle mr-2" aria-hidden="true"></i><?= htmlspecialchars($userName) ?>
                    </a>
                    <a href="logout.php" class="nav-btn-primary hidden md:inline-flex page-transition-link">
                        <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout
                    </a>
                    <button class="md:hidden nav-link !p-2" id="hamburger" aria-label="Toggle menu"><i class="fas fa-bars text-2xl" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="hidden flex-col space-y-2 pt-3" id="mobile-nav">
                <a href="quiz.php" class="nav-link page-transition-link"><i class="fas fa-question-circle mr-2" aria-hidden="true"></i>Quizzes</a>
                <a href="stocks.php" class="nav-link page-transition-link"><i class="fas fa-chart-line mr-2" aria-hidden="true"></i>Stocks</a>
                <a href="leaderboard.php" class="nav-link page-transition-link"><i class="fas fa-trophy mr-2" aria-hidden="true"></i>Leaderboard</a>
                <a href="achievements.php" class="nav-link page-transition-link"><i class="fas fa-medal mr-2" aria-hidden="true"></i>Achievements</a>
                <a href="profile.php" class="nav-link page-transition-link"><i class="fas fa-user-circle mr-2" aria-hidden="true"></i>Profile</a>
                <a href="logout.php" class="nav-link page-transition-link"><i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Achievements Section -->
    <section class="dashboard-section">
        <div id="particles-js" class="absolute inset-0 -z-10"></div>
        <div id="threejs-bg" class="absolute inset-0 -z-10"></div>

        <div class="container relative z-10 py-12">
            <div class="text-center mb-10" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-extrabold mb-3" style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Achievements</h1>
                <p class="text-gray-300">Track your progress and unlock badges</p>
            </div>

            <!-- Top Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number"><?= number_format($userPoints) ?></div>
                            <p class="text-gray-400">Total Points</p>
                        </div>
                        <i class="fas fa-star text-3xl text-indigo-400" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number"><?= $unlockedCount ?></div>
                            <p class="text-gray-400">Achievements Unlocked</p>
                        </div>
                        <i class="fas fa-medal text-3xl text-indigo-400" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="stat-number"><?= $totalAchievements ?></div>
                            <p class="text-gray-400">Total Achievements</p>
                        </div>
                        <i class="fas fa-list text-3xl text-indigo-400" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="mb-10" data-aos="fade-up" data-aos-delay="150">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-gray-300">Overall Progress</div>
                    <div class="text-gray-400 text-sm"><?= $overallProgressPercent ?>%</div>
                </div>
                <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden border border-slate-600/40">
                    <div class="bg-indigo-500 h-2" style="width: <?= $overallProgressPercent ?>%"></div>
                </div>
            </div>

            <!-- Controls -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center gap-2" role="tablist" aria-label="Achievement filters">
                    <button class="filter-chip active" data-filter="all" aria-selected="true">All</button>
                    <button class="filter-chip" data-filter="unlocked" aria-selected="false">Unlocked</button>
                    <button class="filter-chip" data-filter="locked" aria-selected="false">Locked</button>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative w-full md:w-80">
                        <input id="search-input" class="search-input" type="search" placeholder="Search achievements..." aria-label="Search achievements" />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"><i class="fas fa-magnifying-glass" aria-hidden="true"></i></span>
                    </div>
                    <button id="sort-btn" class="filter-chip" data-sort="asc" aria-label="Sort by points">Sort: Points ↑</button>
                </div>
            </div>

            <!-- Error message -->
            <?php if (!empty($errorMessage)): ?>
                <div class="mb-6 p-4 rounded-lg border border-red-500/30 bg-red-500/10 text-red-200" role="alert">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Achievements Grid -->
            <div id="achievements-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-aos="fade-up" data-aos-delay="250">
                <?php foreach ($achievements as $achievement): ?>
                    <?php
                        $required = (int)$achievement['points_required'];
                        $isUnlocked = $userPoints >= $required;
                        $progress = $required > 0 ? min(100, (int)floor(($userPoints / $required) * 100)) : 100;
                        $status = $isUnlocked ? 'unlocked' : 'locked';
                    ?>
                    <article class="card" data-status="<?= $status ?>" data-title="<?= htmlspecialchars(strtolower($achievement['title'])) ?>" data-points="<?= $required ?>">
                        <?php if ($isUnlocked): ?>
                            <span class="badge" aria-label="Unlocked">Unlocked</span>
                        <?php endif; ?>

                        <div class="badge-canvas mb-3" id="badge-<?= (int)$achievement['id'] ?>" aria-hidden="true"></div>

                        <div class="text-2xl mb-1" style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <i class="<?= htmlspecialchars($achievement['icon']) ?>" aria-hidden="true"></i>
                        </div>
                        <h2 class="text-xl font-bold mb-1"><?= htmlspecialchars($achievement['title']) ?></h2>
                        <p class="text-gray-300 mb-4"><?= htmlspecialchars($achievement['description']) ?></p>

                        <div class="w-full bg-slate-700/60 rounded-full h-2 overflow-hidden mb-2 border border-slate-600/40" aria-label="Progress" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="bg-indigo-500 h-2" style="width: <?= $progress ?>%"></div>
                        </div>
                        <div class="text-sm text-gray-400"><?= (int)$userPoints ?> / <?= $required ?> points</div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-14">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <i class="fas fa-coins text-3xl text-indigo-400" aria-hidden="true"></i>
                        <span class="text-3xl font-bold" style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">MoneyQuest</span>
                    </div>
                    <p class="text-gray-400">Your Money Journey Starts Here 🚀</p>
                </div>
                <div>
                    <h3 class="font-bold mb-4 text-lg">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="quiz.php" class="text-gray-400 hover:text-white transition-colors page-transition-link">Quizzes</a></li>
                        <li><a href="stocks.php" class="text-gray-400 hover:text-white transition-colors page-transition-link">Stocks</a></li>
                        <li><a href="leaderboard.php" class="text-gray-400 hover:text-white transition-colors page-transition-link">Leaderboard</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-4 text-lg">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-4 text-lg">Follow Us</h3>
                    <div class="flex space-x-4 text-2xl">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Facebook"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="LinkedIn"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-10 pt-6 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> MoneyQuest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Init AOS
        AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true });

        // Sticky navbar
        document.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (!navbar) return;
            if (window.scrollY > 50) navbar.classList.add('scrolled'); else navbar.classList.remove('scrolled');
        });

        // Mobile nav
        const hamburger = document.getElementById('hamburger');
        const mobileNav = document.getElementById('mobile-nav');
        if (hamburger && mobileNav) {
            hamburger.addEventListener('click', () => mobileNav.classList.toggle('hidden'));
        }

        // Page transitions
        function initPageTransitions() {
            document.querySelectorAll('.page-transition-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (!href || href === '#' || href.startsWith('#')) return;
                    e.preventDefault();
                    document.body.classList.add('is-transitioning');
                    setTimeout(() => { window.location.href = href; }, 800);
                });
            });
        }
        initPageTransitions();

        // Particles
        function initParticles() {
            if (typeof particlesJS === 'undefined') return;
            const container = document.getElementById('particles-js');
            if (!container) return;
            container.style.pointerEvents = 'none';
            particlesJS('particles-js', {
                particles: {
                    number: { value: 50, density: { enable: true, value_area: 800 } },
                    color: { value: '#6366f1' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.5, random: true },
                    size: { value: 3, random: true },
                    line_linked: { enable: true, distance: 150, color: '#6366f1', opacity: 0.4, width: 1 },
                    move: { enable: true, speed: 4, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' }, resize: true }
                },
                retina_detect: true
            });
        }
        initParticles();

        // 3D badge (per card) - minimal but pretty
        const badgeRegistry = {};
        function createBadge(containerId, isUnlocked) {
            const container = document.getElementById(containerId);
            if (!container || typeof THREE === 'undefined') return;

            const width = container.clientWidth || 320;
            const height = container.clientHeight || 140;

            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 100);
            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(width, height);
            container.appendChild(renderer.domElement);

            const group = new THREE.Group();

            const baseGeom = new THREE.TorusGeometry(1.6, 0.25, 16, 64);
            const baseMat = new THREE.MeshStandardMaterial({ color: isUnlocked ? 0xFFD166 : 0x808080, metalness: 0.6, roughness: 0.35 });
            const ring = new THREE.Mesh(baseGeom, baseMat);
            group.add(ring);

            const innerGeom = new THREE.SphereGeometry(0.9, 24, 24);
            const innerMat = new THREE.MeshStandardMaterial({ color: isUnlocked ? 0x6C63FF : 0x555555, metalness: 0.4, roughness: 0.6 });
            const sphere = new THREE.Mesh(innerGeom, innerMat);
            group.add(sphere);

            scene.add(group);

            const ambient = new THREE.AmbientLight(0xffffff, 0.6); scene.add(ambient);
            const dir = new THREE.DirectionalLight(0xffffff, 0.8); dir.position.set(2, 3, 4); scene.add(dir);

            camera.position.z = 4.5;

            function animate() {
                requestAnimationFrame(animate);
                group.rotation.y += 0.01;
                if (isUnlocked) group.rotation.x = Math.sin(Date.now() * 0.002) * 0.15;
                renderer.render(scene, camera);
            }
            animate();

            badgeRegistry[containerId] = { scene, camera, renderer, group };
        }

        function initBadges() {
            document.querySelectorAll('.card').forEach(card => {
                const badge = card.querySelector('.badge-canvas');
                if (!badge) return;
                const isUnlocked = card.getAttribute('data-status') === 'unlocked';
                createBadge(badge.id, isUnlocked);
            });
        }
        initBadges();

        // Filters, search, sort
        (function initFilters() {
            const chips = document.querySelectorAll('.filter-chip');
            const grid = document.getElementById('achievements-grid');
            const searchInput = document.getElementById('search-input');
            const sortBtn = document.getElementById('sort-btn');
            if (!grid) return;

            function apply() {
                const active = document.querySelector('.filter-chip.active')?.getAttribute('data-filter') || 'all';
                const query = (searchInput?.value || '').trim().toLowerCase();
                const sortDir = sortBtn?.getAttribute('data-sort') || 'asc';
                const cards = Array.from(grid.children);

                // Filter
                cards.forEach(card => {
                    const status = card.getAttribute('data-status');
                    const title = card.getAttribute('data-title') || '';
                    const matchesStatus = active === 'all' || status === active;
                    const matchesQuery = !query || title.includes(query);
                    (matchesStatus && matchesQuery) ? (card.classList.remove('hidden')) : (card.classList.add('hidden'));
                });

                // Sort by points required
                const visibleCards = cards.filter(c => !c.classList.contains('hidden'));
                visibleCards.sort((a, b) => {
                    const pa = parseInt(a.getAttribute('data-points') || '0', 10);
                    const pb = parseInt(b.getAttribute('data-points') || '0', 10);
                    return sortDir === 'asc' ? (pa - pb) : (pb - pa);
                });
                visibleCards.forEach(c => grid.appendChild(c));
            }

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    chips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    apply();
                });
            });
            searchInput?.addEventListener('input', () => apply());
            sortBtn?.addEventListener('click', () => {
                const dir = sortBtn.getAttribute('data-sort') === 'asc' ? 'desc' : 'asc';
                sortBtn.setAttribute('data-sort', dir);
                sortBtn.textContent = `Sort: Points ${dir === 'asc' ? '↑' : '↓'}`;
                apply();
            });

            apply();
        })();

        // Entrance animations
        gsap.from('.stat-card', { duration: 1, y: 40, opacity: 0, stagger: 0.2, ease: 'power3.out' });
        gsap.from('.card', { duration: 1, y: 40, opacity: 0, stagger: 0.05, ease: 'power3.out', delay: 0.3 });
    </script>

    <script src="public/js/cursor.js"></script>
</body>
</html>
