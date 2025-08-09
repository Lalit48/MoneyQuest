<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link rel="stylesheet" href="public/css/cursor.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .leaderboard-container {
            padding: 20px 0;
        }
        
        .leaderboard-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .trophy-container {
            height: 200px;
            position: relative;
            margin-bottom: 30px;
        }
        
        .rank-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .rank-card:hover {
            transform: translateY(-5px);
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #333;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #e5e5e5 100%);
            color: #333;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%);
            color: white;
        }
        
        .rank-number {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
        }
        
        .user-info {
            flex-grow: 1;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-stats {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .badges {
            text-align: right;
        }
        
        .badge-count {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .current-user {
            border: 3px solid #ffd700;
            box-shadow: 0 0 20px rgba(255,215,0,0.5);
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

    <div class="container leaderboard-container">
        <div class="row">
            <div class="col-12">
                <h2 class="text-white mb-4">
                    <i class="fas fa-trophy me-2"></i>Leaderboard
                </h2>
                
                <!-- Three.js Trophy Animation -->
                <div class="leaderboard-card">
                    <div class="trophy-container" id="trophy-container"></div>
                </div>
                
                <!-- Leaderboard Table -->
                <div class="leaderboard-card">
                    <h4><i class="fas fa-list-ol me-2"></i>Top Players</h4>
                    
                    <?php if (empty($leaderboard)): ?>
                        <p class="text-muted">No players found.</p>
                    <?php else: ?>
                        <?php foreach ($leaderboard as $index => $player): ?>
                            <div class="rank-card <?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?> <?php echo $player['name'] === $_SESSION['name'] ? 'current-user' : ''; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <div class="rank-number">
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy"></i>
                                            <?php else: ?>
                                                #<?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($player['name']); ?>
                                                <?php if ($player['name'] === $_SESSION['name']): ?>
                                                    <i class="fas fa-user-circle ms-2"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-stats">
                                                <i class="fas fa-star me-1"></i><?php echo number_format($player['points']); ?> points
                                                <span class="ms-3">
                                                    <i class="fas fa-wallet me-1"></i>$<?php echo number_format($player['wallet_balance'], 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="badges">
                                            <div class="badge-count">
                                                <i class="fas fa-medal"></i>
                                                <span class="ms-1"><?php echo $player['badges_earned']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Three.js Trophy Animation
        let scene, camera, renderer, trophy;
        
        function initThreeJS() {
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, document.getElementById('trophy-container').offsetWidth / 200, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(document.getElementById('trophy-container').offsetWidth, 200);
            document.getElementById('trophy-container').appendChild(renderer.domElement);
            
            // Create trophy geometry
            const trophyGeometry = new THREE.Group();
            
            // Trophy base
            const baseGeometry = new THREE.CylinderGeometry(2, 2.5, 0.5, 32);
            const baseMaterial = new THREE.MeshPhongMaterial({ color: 0xFFD700 });
            const base = new THREE.Mesh(baseGeometry, baseMaterial);
            base.position.y = -1;
            trophyGeometry.add(base);
            
            // Trophy stem
            const stemGeometry = new THREE.CylinderGeometry(0.3, 0.3, 2, 32);
            const stemMaterial = new THREE.MeshPhongMaterial({ color: 0xFFD700 });
            const stem = new THREE.Mesh(stemGeometry, stemMaterial);
            stem.position.y = 0;
            trophyGeometry.add(stem);
            
            // Trophy cup
            const cupGeometry = new THREE.CylinderGeometry(1.5, 1, 1.5, 32);
            const cupMaterial = new THREE.MeshPhongMaterial({ color: 0xFFD700 });
            const cup = new THREE.Mesh(cupGeometry, cupMaterial);
            cup.position.y = 1.5;
            trophyGeometry.add(cup);
            
            trophy = trophyGeometry;
            scene.add(trophy);
            
            // Add lighting
            const ambientLight = new THREE.AmbientLight(0x404040);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(10, 10, 5);
            scene.add(directionalLight);
            
            camera.position.z = 8;
        }
        
        function animate() {
            requestAnimationFrame(animate);
            
            if (trophy) {
                trophy.rotation.y += 0.01;
                trophy.rotation.x = Math.sin(Date.now() * 0.001) * 0.1;
            }
            
            renderer.render(scene, camera);
        }
        
        function onWindowResize() {
            const container = document.getElementById('trophy-container');
            camera.aspect = container.offsetWidth / 200;
            camera.updateProjectionMatrix();
            renderer.setSize(container.offsetWidth, 200);
        }
        
        // Initialize Three.js
        initThreeJS();
        animate();
        
        window.addEventListener('resize', onWindowResize);
        
        // Add some sparkle effects
        function addSparkles() {
            const sparkles = document.createElement('div');
            sparkles.innerHTML = '<i class="fas fa-star" style="color: #ffd700; position: absolute; animation: sparkle 2s infinite;"></i>';
            sparkles.style.position = 'absolute';
            sparkles.style.left = Math.random() * 100 + '%';
            sparkles.style.top = Math.random() * 100 + '%';
            sparkles.style.animationDelay = Math.random() * 2 + 's';
            document.getElementById('trophy-container').appendChild(sparkles);
            
            setTimeout(() => {
                sparkles.remove();
            }, 2000);
        }
        
        // Add sparkles periodically
        setInterval(addSparkles, 1000);
    </script>
    <script src="public/js/cursor.js"></script>
    
    <style>
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1); }
        }
    </style>
</body>
</html>
