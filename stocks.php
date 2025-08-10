<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle buy/sell transactions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $symbol = $_POST['symbol'];
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    
    if ($quantity <= 0) {
        $error = 'Quantity must be greater than 0';
    } else {
        try {
            $conn = getConnection();
            
            if ($action == 'buy') {
                $total_cost = $quantity * $price;
                
                // Check if user has enough balance
                if ($_SESSION['wallet_balance'] < $total_cost) {
                    $error = 'Insufficient funds';
                } else {
                    // Deduct from wallet
                    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                    $stmt->execute([$total_cost, $user_id]);
                    
                    // Add to portfolio
                    $stmt = $conn->prepare("INSERT INTO portfolio (user_id, stock_symbol, quantity, avg_price) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?, avg_price = ((avg_price * quantity) + (? * ?)) / (quantity + ?)");
                    $stmt->execute([$user_id, $symbol, $quantity, $price, $quantity, $price, $quantity, $quantity]);
                    
                    // Record transaction
                    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'stock_buy', ?, ?)");
                    $stmt->execute([$user_id, -$total_cost, "Bought $quantity shares of $symbol at $$price"]);
                    
                    $_SESSION['wallet_balance'] -= $total_cost;
                    $success = "Successfully bought $quantity shares of $symbol for $$total_cost";
                }
            } elseif ($action == 'sell') {
                // Check if user has enough shares
                $stmt = $conn->prepare("SELECT quantity FROM portfolio WHERE user_id = ? AND stock_symbol = ?");
                $stmt->execute([$user_id, $symbol]);
                $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$portfolio || $portfolio['quantity'] < $quantity) {
                    $error = 'Insufficient shares';
                } else {
                    $total_value = $quantity * $price;
                    
                    // Add to wallet
                    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                    $stmt->execute([$total_value, $user_id]);
                    
                    // Update portfolio
                    if ($portfolio['quantity'] == $quantity) {
                        $stmt = $conn->prepare("DELETE FROM portfolio WHERE user_id = ? AND stock_symbol = ?");
                        $stmt->execute([$user_id, $symbol]);
                    } else {
                        $stmt = $conn->prepare("UPDATE portfolio SET quantity = quantity - ? WHERE user_id = ? AND stock_symbol = ?");
                        $stmt->execute([$quantity, $user_id, $symbol]);
                    }
                    
                    // Record transaction
                    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'stock_sell', ?, ?)");
                    $stmt->execute([$user_id, $total_value, "Sold $quantity shares of $symbol at $$price"]);
                    
                    $_SESSION['wallet_balance'] += $total_value;
                    $success = "Successfully sold $quantity shares of $symbol for $$total_value";
                }
            }
        } catch (Exception $e) {
            $error = 'Transaction failed: ' . $e->getMessage();
        }
    }
}

// Get stocks and portfolio data
try {
    $conn = getConnection();
    
    // Get all stocks
    $stmt = $conn->prepare("SELECT symbol, name, current_price FROM stocks ORDER BY symbol");
    $stmt->execute();
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's portfolio
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
    
} catch (Exception $e) {
    $error = 'Failed to load data: ' . $e->getMessage();
    $stocks = [];
    $portfolio = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market Simulator - MoneyQuest</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
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
            --glow-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
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
        
        /* Gradient Text Animation */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .gradient-text {
            background: var(--gradient-primary);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            animation: gradientShift 5s ease infinite;
        }
        
        /* Modern Card Styles */
        .card {
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
        
        .card::before {
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
        
        .card::after {
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
        
        .card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: var(--shadow-lg), 0 0 20px rgba(108, 92, 231, 0.2);
            border-color: var(--primary-color);
        }
        
        .card:hover::after {
            transform: scale(1.2);
            opacity: 0.7;
        }
        
        /* Stock Row Styles */
        .stock-row {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }
        
        .stock-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--gradient-primary);
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        
        .stock-row:hover {
            transform: translateY(-5px) scale(1.01);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-lg), 0 0 15px rgba(108, 92, 231, 0.3);
        }
        
        .stock-row:hover::before {
            opacity: 1;
            width: 6px;
        }
        
        .price-up {
            color: var(--success-color);
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .price-down {
            color: var(--error-color);
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .stock-row:hover .price-up {
            transform: scale(1.1);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .stock-row:hover .price-down {
            transform: scale(1.1);
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        /* Button Styles */
        .btn-trade {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            margin: 0 5px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-trade::before {
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
        
        .btn-trade:hover::before {
            left: 100%;
        }
        
        .btn-buy {
            background: var(--success-color);
            color: white;
        }
        
        .btn-buy:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-sell {
            background: var(--error-color);
            color: white;
        }
        
        .btn-sell:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        /* Modal Styles */
        .modal-content {
            background: var(--dark-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            color: var(--text-primary);
            backdrop-filter: blur(10px);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }
        
        .form-control {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            border-color: var(--primary-color);
        }
        
        /* Chart Styles */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 2rem;
        }
        
        /* Stock Details */
        .stock-details {
            display: none;
            margin-top: 2rem;
        }
        
        /* Profit/Loss Styles */
        .profit {
            color: var(--success-color);
            font-weight: bold;
        }
        
        .loss {
            color: var(--error-color);
            font-weight: bold;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .badge-live {
            background: var(--error-color);
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Particles Background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
            pointer-events: none;
        }
        
        /* Three.js Background */
        #threejs-container {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Custom Cursor -->
    <div class="cursor-dot-outline"></div>
    <div class="cursor-dot"></div>
    
    <!-- Particles.js Background -->
    <div id="particles-js"></div>
    
    <!-- Three.js Background -->
    <div id="threejs-container"></div>
    
    <!-- Loading Screen -->
    <div id="loading-screen" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--darker-bg); display: flex; justify-content: center; align-items: center; z-index: 9999;">
        <div class="text-center">
            <div class="loading-spinner mb-4"></div>
            <div class="mt-3 text-xl font-semibold gradient-text">Loading Market Data...</div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar py-4">
        <div class="container flex justify-between items-center">
            <a class="flex items-center" href="dashboard.php">
                <i class="fas fa-coins mr-2 text-2xl gradient-text"></i>
                <span class="text-xl font-bold">MoneyQuest</span>
            </a>
            <div class="flex items-center space-x-6">
                <span class="flex items-center bg-opacity-20 bg-white px-4 py-2 rounded-lg">
                    <i class="fas fa-wallet mr-2 text-yellow-400"></i>
                    <span class="font-medium">$<span id="wallet-balance"><?php echo number_format($_SESSION['wallet_balance'], 2); ?></span></span>
                </span>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home mr-1"></i>Dashboard
                </a>
                <a class="nav-btn-secondary" href="logout.php">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-24 mb-16 relative z-10">
        <!-- Page Header -->
        <div class="mb-8" data-aos="fade-up" data-aos-delay="100">
            <h1 class="text-4xl font-bold mb-2 flex items-center">
                <i class="fas fa-chart-line mr-3 text-indigo-400"></i>
                <span class="gradient-text">Stock Market Simulator</span>
            </h1>
            <p class="text-gray-400 max-w-2xl">Invest in virtual stocks, track your portfolio, and practice trading strategies without risking real money.</p>
        </div>
        
        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="bg-red-900 bg-opacity-50 border border-red-500 text-white px-4 py-3 rounded-lg mb-6 flex items-center" role="alert" data-aos="fade-up" data-aos-delay="150">
                <i class="fas fa-exclamation-triangle mr-3 text-red-400"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-900 bg-opacity-50 border border-green-500 text-white px-4 py-3 rounded-lg mb-6 flex items-center" role="alert" data-aos="fade-up" data-aos-delay="150">
                <i class="fas fa-check-circle mr-3 text-green-400"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Stock Chart Section -->
        <div class="card mb-8" data-aos="fade-up" data-aos-delay="200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-chart-area mr-2 text-indigo-400"></i>
                    <span>Market Overview</span>
                </h3>
                <div class="flex items-center">
                    <span class="badge badge-live mr-2">LIVE</span>
                    <select id="stock-selector" class="bg-opacity-20 bg-white border border-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($stocks as $stock): ?>
                            <option value="<?php echo $stock['symbol']; ?>"><?php echo $stock['symbol']; ?> - <?php echo $stock['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="chart-container">
                <div id="stock-chart"></div>
            </div>
            
            <div id="stock-details" class="stock-details">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Open</div>
                        <div class="text-xl font-bold" id="stock-open">$0.00</div>
                    </div>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">High</div>
                        <div class="text-xl font-bold text-green-500" id="stock-high">$0.00</div>
                    </div>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Low</div>
                        <div class="text-xl font-bold text-red-500" id="stock-low">$0.00</div>
                    </div>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Volume</div>
                        <div class="text-xl font-bold" id="stock-volume">0</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Summary -->
        <div class="card mb-8" data-aos="fade-up" data-aos-delay="300">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-briefcase mr-2 text-indigo-400"></i>
                    <span>Your Portfolio</span>
                </h3>
                <div>
                    <button id="refresh-portfolio" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh
                    </button>
                </div>
            </div>
            
            <?php if (empty($portfolio)): ?>
                <div class="text-center py-8">
                    <div class="text-6xl mb-4 text-gray-600"><i class="fas fa-folder-open"></i></div>
                    <p class="text-gray-400 mb-4">You don't have any stocks in your portfolio yet.</p>
                    <p class="text-sm text-gray-500">Start investing by purchasing stocks from the available options below.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-white">
                        <thead>
                            <tr class="text-left border-b border-gray-700">
                                <th class="pb-3">Stock</th>
                                <th class="pb-3">Quantity</th>
                                <th class="pb-3">Avg Price</th>
                                <th class="pb-3">Current Price</th>
                                <th class="pb-3">Current Value</th>
                                <th class="pb-3">Profit/Loss</th>
                                <th class="pb-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($portfolio as $item): ?>
                                <tr class="border-b border-gray-800 hover:bg-opacity-10 hover:bg-white transition-all duration-300">
                                    <td class="py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-indigo-900 flex items-center justify-center mr-3">
                                                <span class="font-bold"><?php echo substr($item['stock_symbol'], 0, 1); ?></span>
                                            </div>
                                            <div>
                                                <div class="font-bold"><?php echo htmlspecialchars($item['stock_symbol']); ?></div>
                                                <div class="text-sm text-white"><?php echo htmlspecialchars($item['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4"><?php echo $item['quantity']; ?></td>
                                    <td class="py-4">$<?php echo number_format($item['avg_price'], 2); ?></td>
                                    <td class="py-4">$<?php echo number_format($item['current_price'], 2); ?></td>
                                    <td class="py-4 font-bold">$<?php echo number_format($item['current_value'], 2); ?></td>
                                    <td class="py-4 <?php echo $item['profit_loss'] >= 0 ? 'profit' : 'loss'; ?>">
                                        <?php echo ($item['profit_loss'] >= 0 ? '+' : '') . '$' . number_format($item['profit_loss'], 2); ?>
                                        <div class="text-xs">
                                            <?php 
                                                $percent = ($item['profit_loss'] / ($item['quantity'] * $item['avg_price'])) * 100;
                                                echo ($percent >= 0 ? '+' : '') . number_format($percent, 2) . '%';
                                            ?>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex space-x-2">
                                            <button class="btn-trade btn-buy" onclick="openTradeModal('buy', '<?php echo $item['stock_symbol']; ?>', <?php echo $item['current_price']; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button class="btn-trade btn-sell" onclick="openTradeModal('sell', '<?php echo $item['stock_symbol']; ?>', <?php echo $item['current_price']; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Portfolio Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <?php 
                        $total_value = 0;
                        $total_cost = 0;
                        foreach ($portfolio as $item) {
                            $total_value += $item['current_value'];
                            $total_cost += $item['quantity'] * $item['avg_price'];
                        }
                        $total_profit_loss = $total_value - $total_cost;
                        $profit_loss_percent = ($total_cost > 0) ? ($total_profit_loss / $total_cost) * 100 : 0;
                    ?>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Total Investment</div>
                        <div class="text-xl font-bold">$<?php echo number_format($total_cost, 2); ?></div>
                    </div>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Current Value</div>
                        <div class="text-xl font-bold">$<?php echo number_format($total_value, 2); ?></div>
                    </div>
                    <div class="bg-opacity-20 bg-white p-4 rounded-lg">
                        <div class="text-gray-400 text-sm">Total Profit/Loss</div>
                        <div class="text-xl font-bold <?php echo $total_profit_loss >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                            <?php echo ($total_profit_loss >= 0 ? '+' : '') . '$' . number_format($total_profit_loss, 2); ?>
                            <span class="text-sm">
                                (<?php echo ($profit_loss_percent >= 0 ? '+' : '') . number_format($profit_loss_percent, 2); ?>%)
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Stocks -->
        <div class="card" data-aos="fade-up" data-aos-delay="400">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-list mr-2 text-indigo-400"></i>
                    <span>Available Stocks</span>
                </h3>
                <div class="relative">
                    <input type="text" id="stock-search" placeholder="Search stocks..." class="bg-opacity-20 bg-white border border-gray-700 text-white rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="stocks-grid">
                <?php foreach ($stocks as $stock): ?>
                    <div class="stock-row" data-symbol="<?php echo $stock['symbol']; ?>" data-name="<?php echo $stock['name']; ?>">
                        <div>
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-indigo-900 flex items-center justify-center mr-3">
                                    <span class="font-bold"><?php echo substr($stock['symbol'], 0, 1); ?></span>
                                </div>
                                <div>
                                    <h6 class="font-bold text-lg"><?php echo htmlspecialchars($stock['symbol']); ?></h6>
                                    <div class="text-sm text-gray-400"><?php echo htmlspecialchars($stock['name']); ?></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <strong class="price-up text-xl">$<?php echo number_format($stock['current_price'], 2); ?></strong>
                            </div>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <button class="btn-trade btn-buy" onclick="openTradeModal('buy', '<?php echo $stock['symbol']; ?>', <?php echo $stock['current_price']; ?>)">
                                <i class="fas fa-plus mr-1"></i>Buy
                            </button>
                            <button class="btn-trade btn-sell" onclick="openTradeModal('sell', '<?php echo $stock['symbol']; ?>', <?php echo $stock['current_price']; ?>)">
                                <i class="fas fa-minus mr-1"></i>Sell
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trade Modal -->
    <div class="modal" id="tradeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tradeModalTitle">Trade Stock</h5>
                    <button type="button" class="modal-close" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="tradeAction">
                        <input type="hidden" name="symbol" id="tradeSymbol">
                        <input type="hidden" name="price" id="tradePrice">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3 mb-md-0">
                                    <label class="form-label">Stock Symbol</label>
                                    <input type="text" class="form-control" id="tradeSymbolDisplay" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 mb-md-0">
                                    <label class="form-label">Current Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="tradePriceDisplay" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8 mt-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <span class="input-group-text">shares</span>
                                </div>
                                <div class="range-slider mt-2">
                                    <input type="range" class="form-range" id="quantity-range" min="1" max="100" value="1">
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <label class="form-label">Total Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="totalCost" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="tradeSubmitBtn">Confirm Trade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize particles.js
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize particles.js
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
                        "value": "#ffffff"
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
                        "value": 0.2,
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
                        "color": "#ffffff",
                        "opacity": 0.1,
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
                                "opacity": 0.3
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
            
            // Initialize Three.js background
            initThreeJsBackground();
            
            // Initialize custom cursor
            initCustomCursor();
            
            // Initialize stock chart
            initStockChart();
            
            // Initialize stock search functionality
            initStockSearch();
            
            // Hide loading screen after everything is loaded
            setTimeout(function() {
                const loadingScreen = document.getElementById('loading-screen');
                loadingScreen.style.opacity = '0';
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                }, 500);
            }, 1500);
            
            // Initialize AOS (Animate on Scroll)
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
        
        // Stock chart initialization
        function initStockChart() {
            const stockSelector = document.getElementById('stock-selector');
            let selectedStock = stockSelector.value;
            let stockChart;
            
            // Function to fetch stock data and update chart
            function fetchStockData(symbol, days = 30) {
                document.getElementById('loading-screen').style.display = 'block';
                fetch(`api/get_stock_history.php?symbol=${symbol}&days=${days}`)
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        updateStockChart(data);
                        updateStockDetails(data);
                        document.getElementById('loading-screen').style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        document.getElementById('loading-screen').style.display = 'none';
                        alert('Failed to load market data: ' + error.message);
                    });
            }
            
            // Function to update the stock chart
            function updateStockChart(data) {
                const seriesData = data.data.map(item => ({
                    x: new Date(item.date).getTime(),
                    y: [item.open, item.high, item.low, item.close]
                }));
                
                const options = {
                    series: [{
                        name: data.symbol,
                        data: seriesData
                    }],
                    chart: {
                        type: 'candlestick',
                        height: 350,
                        fontFamily: 'Poppins, sans-serif',
                        background: 'transparent',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        },
                        toolbar: {
                            show: true,
                            tools: {
                                download: false,
                                selection: true,
                                zoom: true,
                                zoomin: true,
                                zoomout: true,
                                pan: true,
                                reset: true
                            }
                        }
                    },
                    xaxis: {
                        type: 'datetime',
                        labels: {
                            style: {
                                colors: '#9ca3af'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        tooltip: {
                            enabled: true
                        },
                        labels: {
                            style: {
                                colors: '#9ca3af'
                            },
                            formatter: function(val) {
                                return '$' + val.toFixed(2);
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        strokeDashArray: 5,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        },
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                        padding: {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            left: 10
                        }
                    },
                    tooltip: {
                        theme: 'dark',
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const o = w.globals.seriesCandleO[seriesIndex][dataPointIndex];
                            const h = w.globals.seriesCandleH[seriesIndex][dataPointIndex];
                            const l = w.globals.seriesCandleL[seriesIndex][dataPointIndex];
                            const c = w.globals.seriesCandleC[seriesIndex][dataPointIndex];
                            const date = new Date(w.globals.seriesX[seriesIndex][dataPointIndex]).toLocaleDateString();
                            
                            return (
                                '<div class="apexcharts-tooltip-candlestick">' +
                                '<div>' + date + '</div>' +
                                '<div>Open: <span class="value">$' + o.toFixed(2) + '</span></div>' +
                                '<div>High: <span class="value">$' + h.toFixed(2) + '</span></div>' +
                                '<div>Low: <span class="value">$' + l.toFixed(2) + '</span></div>' +
                                '<div>Close: <span class="value">$' + c.toFixed(2) + '</span></div>' +
                                '</div>'
                            );
                        }
                    },
                    plotOptions: {
                        candlestick: {
                            colors: {
                                upward: '#10b981',
                                downward: '#ef4444'
                            },
                            wick: {
                                useFillColor: true
                            }
                        }
                    }
                };
                
                if (stockChart) {
                    stockChart.updateOptions(options);
                } else {
                    stockChart = new ApexCharts(document.getElementById('stock-chart'), options);
                    stockChart.render();
                }
            }
            
            // Function to update stock details
            function updateStockDetails(data) {
                const latestData = data.data[data.data.length - 1];
                
                document.getElementById('stock-open').textContent = '$' + latestData.open.toFixed(2);
                document.getElementById('stock-high').textContent = '$' + latestData.high.toFixed(2);
                document.getElementById('stock-low').textContent = '$' + latestData.low.toFixed(2);
                document.getElementById('stock-volume').textContent = latestData.volume.toLocaleString();
            }
            
            // Initial fetch
            fetchStockData(selectedStock);
            
            // Handle stock selection change
            stockSelector.addEventListener('change', function() {
                selectedStock = this.value;
                fetchStockData(selectedStock);
            });
            
            // Refresh button
            document.getElementById('refresh-portfolio').addEventListener('click', function() {
                location.reload();
            });
        }
        
        // Stock search functionality
        function initStockSearch() {
            const searchInput = document.getElementById('stock-search');
            const stocksGrid = document.getElementById('stocks-grid');
            const stockRows = stocksGrid.querySelectorAll('.stock-row');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                stockRows.forEach(row => {
                    const symbol = row.getAttribute('data-symbol').toLowerCase();
                    const name = row.getAttribute('data-name').toLowerCase();
                    
                    if (symbol.includes(searchTerm) || name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Custom cursor initialization
        function initCustomCursor() {
            const cursor = document.querySelector('.cursor-dot');
            const cursorOutline = document.querySelector('.cursor-dot-outline');
            
            document.addEventListener('mousemove', function(e) {
                gsap.to(cursor, {
                    x: e.clientX,
                    y: e.clientY,
                    duration: 0.1
                });
                
                gsap.to(cursorOutline, {
                    x: e.clientX,
                    y: e.clientY,
                    duration: 0.5
                });
            });
        }
        
        // Three.js background initialization
        function initThreeJsBackground() {
            const container = document.getElementById('threejs-container');
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 5;
            
            const renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            container.appendChild(renderer.domElement);
            
            // Create particles
            const particlesGeometry = new THREE.BufferGeometry();
            const particlesCount = 1000;
            
            const posArray = new Float32Array(particlesCount * 3);
            
            for (let i = 0; i < particlesCount * 3; i++) {
                posArray[i] = (Math.random() - 0.5) * 10;
            }
            
            particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
            
            const particlesMaterial = new THREE.PointsMaterial({
                size: 0.02,
                color: 0x5e35b1,
                transparent: true,
                opacity: 0.5
            });
            
            const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
            scene.add(particlesMesh);
            
            // Animation
            function animate() {
                requestAnimationFrame(animate);
                particlesMesh.rotation.x += 0.0005;
                particlesMesh.rotation.y += 0.0005;
                renderer.render(scene, camera);
            }
            
            animate();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
        
        // Duplicate initStockChart removed (using API-backed version above)
        
        function openTradeModal(action, symbol, price) {
            $('#tradeAction').val(action);
            $('#tradeSymbol').val(symbol);
            $('#tradePrice').val(price);
            $('#tradeSymbolDisplay').val(symbol);
            $('#tradePriceDisplay').val(price.toFixed(2));
            // Keep static modal title "Trade Stock"
            $('#tradeSubmitBtn').removeClass('btn-primary btn-success btn-danger').addClass(action === 'buy' ? 'btn-success' : 'btn-danger');
            
            // Reset quantity
            $('#quantity').val(1);
            $('#quantity-range').val(1);
            $('#totalCost').val(price.toFixed(2));
            
            var tradeModalEl = document.getElementById('tradeModal');
            var tradeModal = new bootstrap.Modal(tradeModalEl);
            tradeModal.show();
        }
        
        $(document).ready(function() {
            // Calculate total cost when quantity changes
            $('#quantity').on('input', function() {
                const quantity = parseInt($(this).val()) || 0;
                const price = parseFloat($('#tradePrice').val()) || 0;
                const total = quantity * price;
                $('#totalCost').val(total.toFixed(2));
                $('#quantity-range').val(quantity);
            });
            
            // Update quantity when range slider changes
            $('#quantity-range').on('input', function() {
                const quantity = parseInt($(this).val());
                $('#quantity').val(quantity);
                
                const price = parseFloat($('#tradePrice').val()) || 0;
                const total = quantity * price;
                $('#totalCost').val(total.toFixed(2));
            });
            
            // Initialize stock search functionality
            $('#stock-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.stock-row').each(function() {
                    const symbol = $(this).data('symbol').toLowerCase();
                    const name = $(this).data('name').toLowerCase();
                    
                    if (symbol.includes(searchTerm) || name.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
