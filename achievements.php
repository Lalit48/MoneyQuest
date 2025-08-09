<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_points = $_SESSION['points'];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - MoneyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link rel="stylesheet" href="public/css/cursor.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .achievements-container {
            padding: 20px 0;
        }
        
        .achievements-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .achievement-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .achievement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .achievement-card.unlocked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .achievement-card.unlocked::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }
        
        .achievement-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .achievement-card:hover .achievement-icon {
            transform: scale(1.2) rotate(10deg);
        }
        
        .achievement-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .achievement-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .achievement-card.unlocked .achievement-description {
            color: rgba(255,255,255,0.9);
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 0.9rem;
            color: #666;
        }
        
        .achievement-card.unlocked .progress-text {
            color: rgba(255,255,255,0.9);
        }
        
        .badge-container {
            height: 150px;
            position: relative;
            margin-bottom: 20px;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 1.1rem;
            opacity: 0.9;
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

    <div class="container achievements-container">
        <div class="row">
            <div class="col-12">
                <h2 class="text-white mb-4">
                    <i class="fas fa-medal me-2"></i>Achievements
                </h2>
                
                <!-- Stats Summary -->
                <div class="stats-card">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-number"><?php echo number_format($user_points); ?></div>
                            <div class="stats-label">Total Points</div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-number">
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
                            <div class="stats-label">Achievements Unlocked</div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-number"><?php echo count($achievements); ?></div>
                            <div class="stats-label">Total Achievements</div>
                        </div>
                    </div>
                </div>
                
                <!-- Achievements Grid -->
                <div class="achievements-card">
                    <h4><i class="fas fa-trophy me-2"></i>Your Achievements</h4>
                    
                    <div class="row">
                        <?php foreach ($achievements as $achievement): ?>
                            <?php $is_unlocked = $user_points >= $achievement['points_required']; ?>
                            <?php $progress = min(100, ($user_points / $achievement['points_required']) * 100); ?>
                            
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="achievement-card <?php echo $is_unlocked ? 'unlocked' : ''; ?>">
                                    <div class="badge-container" id="badge-<?php echo $achievement['id']; ?>"></div>
                                    
                                    <div class="achievement-icon">
                                        <i class="<?php echo $achievement['icon']; ?>"></i>
                                    </div>
                                    
                                    <div class="achievement-title">
                                        <?php echo htmlspecialchars($achievement['title']); ?>
                                    </div>
                                    
                                    <div class="achievement-description">
                                        <?php echo htmlspecialchars($achievement['description']); ?>
                                    </div>
                                    
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                    
                                    <div class="progress-text">
                                        <?php echo $user_points; ?> / <?php echo $achievement['points_required']; ?> points
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Three.js Badge Animations
        const badges = {};
        
        function createBadge(containerId, isUnlocked) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, container.offsetWidth / 150, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(container.offsetWidth, 150);
            container.appendChild(renderer.domElement);
            
            // Create badge geometry
            const badgeGeometry = new THREE.Group();
            
            // Badge base
            const baseGeometry = new THREE.CircleGeometry(2, 32);
            const baseMaterial = new THREE.MeshPhongMaterial({ 
                color: isUnlocked ? 0xFFD700 : 0xcccccc,
                side: THREE.DoubleSide
            });
            const base = new THREE.Mesh(baseGeometry, baseMaterial);
            badgeGeometry.add(base);
            
            // Badge rim
            const rimGeometry = new THREE.RingGeometry(1.8, 2, 32);
            const rimMaterial = new THREE.MeshPhongMaterial({ 
                color: isUnlocked ? 0xFFA500 : 0x999999,
                side: THREE.DoubleSide
            });
            const rim = new THREE.Mesh(rimGeometry, rimMaterial);
            badgeGeometry.add(rim);
            
            // Badge center
            const centerGeometry = new THREE.CircleGeometry(1.5, 32);
            const centerMaterial = new THREE.MeshPhongMaterial({ 
                color: isUnlocked ? 0x667eea : 0x666666,
                side: THREE.DoubleSide
            });
            const center = new THREE.Mesh(centerGeometry, centerMaterial);
            center.position.z = 0.1;
            badgeGeometry.add(center);
            
            badgeGeometry.position.z = 0;
            scene.add(badgeGeometry);
            
            // Add lighting
            const ambientLight = new THREE.AmbientLight(0x404040);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(10, 10, 5);
            scene.add(directionalLight);
            
            camera.position.z = 5;
            
            // Animation function
            function animate() {
                requestAnimationFrame(animate);
                
                badgeGeometry.rotation.y += 0.01;
                if (isUnlocked) {
                    badgeGeometry.rotation.x = Math.sin(Date.now() * 0.002) * 0.1;
                }
                
                renderer.render(scene, camera);
            }
            
            animate();
            
            // Store reference
            badges[containerId] = { scene, camera, renderer, badgeGeometry };
        }
        
        // Initialize badges
        document.addEventListener('DOMContentLoaded', function() {
            const achievementCards = document.querySelectorAll('.achievement-card');
            achievementCards.forEach(card => {
                const badgeContainer = card.querySelector('.badge-container');
                if (badgeContainer) {
                    const isUnlocked = card.classList.contains('unlocked');
                    createBadge(badgeContainer.id, isUnlocked);
                }
            });
        });
        
        // Add sparkle effects for unlocked achievements
        function addSparkles() {
            const unlockedCards = document.querySelectorAll('.achievement-card.unlocked');
            unlockedCards.forEach(card => {
                if (Math.random() < 0.3) {
                    const sparkle = document.createElement('div');
                    sparkle.innerHTML = '<i class="fas fa-star" style="color: #ffd700; position: absolute; animation: sparkle 2s infinite;"></i>';
                    sparkle.style.position = 'absolute';
                    sparkle.style.left = Math.random() * 100 + '%';
                    sparkle.style.top = Math.random() * 100 + '%';
                    sparkle.style.animationDelay = Math.random() * 2 + 's';
                    card.appendChild(sparkle);
                    
                    setTimeout(() => {
                        sparkle.remove();
                    }, 2000);
                }
            });
        }
        
        // Add sparkles periodically
        setInterval(addSparkles, 2000);
    </script>
    <script src="public/js/cursor.js"></script>
    
    <style>
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
    </style>
</body>
</html>
