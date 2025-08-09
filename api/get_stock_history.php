<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if symbol is provided
if (!isset($_GET['symbol'])) {
    echo json_encode(['error' => 'Stock symbol is required']);
    exit();
}

$symbol = $_GET['symbol'];
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30; // Default to 30 days

try {
    $conn = getConnection();
    
    // First check if the stock exists
    $stmt = $conn->prepare("SELECT symbol FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'Stock not found']);
        exit();
    }
    
    // Generate mock historical data based on current price
    $stmt = $conn->prepare("SELECT current_price FROM stocks WHERE symbol = ?");
    $stmt->execute([$symbol]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_price = $stock['current_price'];
    
    // Generate historical data points
    $data = [];
    $date = new DateTime();
    
    // Volatility factor - higher means more price variation
    $volatility = 0.03; 
    $price = $current_price;
    
    // Generate data points for the requested number of days
    for ($i = $days; $i >= 0; $i--) {
        $date_point = clone $date;
        $date_point->modify("-$i days");
        
        // Skip weekends
        $day_of_week = $date_point->format('N');
        if ($day_of_week > 5) { // 6 = Saturday, 7 = Sunday
            continue;
        }
        
        // Random price movement
        $change_percent = (mt_rand(-100, 100) / 100) * $volatility;
        $price_change = $price * $change_percent;
        
        // Ensure price doesn't go below 1
        $price = max(1, $price + $price_change);
        
        // Calculate OHLC (Open, High, Low, Close)
        $open = $price;
        $close = $price * (1 + ((mt_rand(-50, 50) / 100) * $volatility));
        $high = max($open, $close) * (1 + (mt_rand(10, 50) / 100) * $volatility);
        $low = min($open, $close) * (1 - (mt_rand(10, 50) / 100) * $volatility);
        
        // Ensure prices don't go below 1
        $low = max(1, $low);
        $close = max(1, $close);
        
        // Random volume
        $volume = mt_rand(10000, 1000000);
        
        $data[] = [
            'date' => $date_point->format('Y-m-d'),
            'open' => round($open, 2),
            'high' => round($high, 2),
            'low' => round($low, 2),
            'close' => round($close, 2),
            'volume' => $volume
        ];
        
        // Set price for next iteration
        $price = $close;
    }
    
    // Sort data by date (oldest to newest)
    usort($data, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    echo json_encode([
        'symbol' => $symbol,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch stock data: ' . $e->getMessage()]);
}
?>