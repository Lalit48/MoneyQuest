<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'moneyquest');

// Create connection
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database tables
function initializeDatabase() {
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->exec("USE " . DB_NAME);
    
    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            wallet_balance DECIMAL(10,2) DEFAULT 1000.00,
            points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS quizzes (
            quiz_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            category ENUM('Budgeting', 'Investing', 'Saving') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_id INT,
            question_text TEXT NOT NULL,
            option_a VARCHAR(255) NOT NULL,
            option_b VARCHAR(255) NOT NULL,
            option_c VARCHAR(255) NOT NULL,
            option_d VARCHAR(255) NOT NULL,
            correct_option CHAR(1) NOT NULL,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS stocks (
            symbol VARCHAR(10) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            current_price DECIMAL(10,2) NOT NULL,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS portfolio (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            stock_symbol VARCHAR(10),
            quantity INT NOT NULL,
            avg_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (stock_symbol) REFERENCES stocks(symbol)
        )",
        
        "CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            type ENUM('quiz_reward', 'stock_buy', 'stock_sell', 'achievement') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            points_required INT NOT NULL,
            icon VARCHAR(50) DEFAULT 'fas fa-trophy'
        )"
    ];
    
    foreach ($tables as $table) {
        $conn->exec($table);
    }
    
    // Insert sample data
    insertSampleData($conn);
}

function insertSampleData($conn) {
    // Sample quizzes
    $quizzes = [
        ['Budgeting Basics', 'Budgeting'],
        ['Investment Fundamentals', 'Investing'],
        ['Smart Saving Strategies', 'Saving']
    ];
    
    foreach ($quizzes as $quiz) {
        $stmt = $conn->prepare("INSERT IGNORE INTO quizzes (title, category) VALUES (?, ?)");
        $stmt->execute($quiz);
    }
    
    // Sample questions
    $questions = [
        [1, 'What is the 50/30/20 rule in budgeting?', '50% needs, 30% wants, 20% savings', '50% savings, 30% needs, 20% wants', '50% wants, 30% savings, 20% needs', 'Equal distribution of income', 'A'],
        [1, 'Which of the following is considered a fixed expense?', 'Entertainment', 'Rent', 'Dining out', 'Shopping', 'B'],
        [2, 'What is diversification in investing?', 'Putting all money in one stock', 'Spreading investments across different assets', 'Investing only in bonds', 'Saving money in a bank', 'B'],
        [2, 'What is compound interest?', 'Interest earned only on principal', 'Interest earned on principal and accumulated interest', 'A type of loan', 'A bank fee', 'B'],
        [3, 'What is an emergency fund?', 'Money for vacations', 'Savings for unexpected expenses', 'Investment portfolio', 'Retirement account', 'B']
    ];
    
    foreach ($questions as $question) {
        $stmt = $conn->prepare("INSERT IGNORE INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($question);
    }
    
    // Sample stocks
    $stocks = [
        ['AAPL', 'Apple Inc.', 150.00],
        ['GOOGL', 'Alphabet Inc.', 2800.00],
        ['MSFT', 'Microsoft Corporation', 300.00],
        ['AMZN', 'Amazon.com Inc.', 3300.00],
        ['TSLA', 'Tesla Inc.', 800.00]
    ];
    
    foreach ($stocks as $stock) {
        $stmt = $conn->prepare("INSERT IGNORE INTO stocks (symbol, name, current_price) VALUES (?, ?, ?)");
        $stmt->execute($stock);
    }
    
    // Sample achievements
    $achievements = [
        ['First Quiz', 'Complete your first quiz', 10, 'fas fa-star'],
        ['Quiz Master', 'Complete 5 quizzes', 50, 'fas fa-crown'],
        ['Stock Trader', 'Make your first stock purchase', 25, 'fas fa-chart-line'],
        ['Saver', 'Accumulate 5000 points', 100, 'fas fa-piggy-bank']
    ];
    
    foreach ($achievements as $achievement) {
        $stmt = $conn->prepare("INSERT IGNORE INTO achievements (title, description, points_required, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute($achievement);
    }
}

// Initialize database on first run
if (!function_exists('isDatabaseInitialized')) {
    function isDatabaseInitialized() {
        try {
            $conn = getConnection();
            $stmt = $conn->query("SELECT COUNT(*) FROM users");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    if (!isDatabaseInitialized()) {
        initializeDatabase();
    }
}
?> 