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
            category ENUM('Budgeting', 'Investing', 'Saving', 'Credit', 'Insurance', 'Taxes', 'Retirement', 'Real Estate', 'Cryptocurrency', 'Banking') NOT NULL,
            description TEXT,
            difficulty ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
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
    // Sample quizzes with descriptions and difficulty levels
    $quizzes = [
        ['Budget Like a Pro', 'Budgeting', 'Master the art of budgeting with the 50/30/20 rule and zero-based budgeting techniques', 'Beginner'],
        ['Investment Mastery', 'Investing', 'Discover stocks, bonds, ETFs, and build a diversified portfolio that grows your wealth', 'Intermediate'],
        ['Savings Superstar', 'Saving', 'Learn high-yield savings strategies, emergency funds, and compound interest magic', 'Beginner'],
        ['Credit Score Secrets', 'Credit', 'Unlock the mysteries of credit scores, reports, and how to build excellent credit', 'Beginner'],
        ['Insurance Essentials', 'Insurance', 'Protect your future with life, health, auto, and home insurance knowledge', 'Intermediate'],
        ['Tax Optimization', 'Taxes', 'Maximize deductions, understand tax brackets, and minimize your tax burden legally', 'Advanced'],
        ['Retirement Planning', 'Retirement', 'Build wealth for retirement with 401(k)s, IRAs, and long-term investment strategies', 'Intermediate'],
        ['Real Estate Riches', 'Real Estate', 'Explore property investment, mortgages, and real estate market fundamentals', 'Advanced'],
        ['Crypto Currency 101', 'Cryptocurrency', 'Navigate the world of Bitcoin, Ethereum, and blockchain technology safely', 'Intermediate'],
        ['Banking Basics', 'Banking', 'Master checking accounts, savings accounts, loans, and banking products', 'Beginner']
    ];
    
    foreach ($quizzes as $quiz) {
        $stmt = $conn->prepare("INSERT IGNORE INTO quizzes (title, category, description, difficulty) VALUES (?, ?, ?, ?)");
        $stmt->execute($quiz);
    }
    
    // Sample questions for different quizzes
    $questions = [
        // Budget Like a Pro (Quiz ID 1)
        [1, 'What is the 50/30/20 rule in budgeting?', '50% needs, 30% wants, 20% savings', '50% savings, 30% needs, 20% wants', '50% wants, 30% savings, 20% needs', 'Equal distribution of income', 'A'],
        [1, 'Which of the following is considered a fixed expense?', 'Entertainment', 'Rent', 'Dining out', 'Shopping', 'B'],
        [1, 'What is zero-based budgeting?', 'Starting with zero dollars', 'Assigning every dollar a purpose', 'Having no budget', 'Spending nothing', 'B'],
        
        // Investment Mastery (Quiz ID 2)
        [2, 'What is diversification in investing?', 'Putting all money in one stock', 'Spreading investments across different assets', 'Investing only in bonds', 'Saving money in a bank', 'B'],
        [2, 'What is compound interest?', 'Interest earned only on principal', 'Interest earned on principal and accumulated interest', 'A type of loan', 'A bank fee', 'B'],
        [2, 'What does ETF stand for?', 'Electronic Trading Fund', 'Exchange Traded Fund', 'Emergency Trust Fund', 'Equity Transfer Fund', 'B'],
        
        // Savings Superstar (Quiz ID 3)
        [3, 'What is an emergency fund?', 'Money for vacations', 'Savings for unexpected expenses', 'Investment portfolio', 'Retirement account', 'B'],
        [3, 'How many months of expenses should an emergency fund cover?', '1-2 months', '3-6 months', '12 months', '24 months', 'B'],
        [3, 'What is a high-yield savings account?', 'Account with high fees', 'Account with higher interest rates', 'Account for rich people', 'Account with minimum balance', 'B'],
        
        // Credit Score Secrets (Quiz ID 4)
        [4, 'What is the highest possible FICO credit score?', '750', '800', '850', '900', 'C'],
        [4, 'What factor has the biggest impact on your credit score?', 'Length of credit history', 'Payment history', 'Credit utilization', 'Types of credit', 'B'],
        [4, 'What is credit utilization?', 'How often you use credit', 'Percentage of available credit used', 'Number of credit cards', 'Credit limit amount', 'B'],
        
        // Insurance Essentials (Quiz ID 5)
        [5, 'What is a deductible?', 'Monthly insurance payment', 'Amount you pay before insurance kicks in', 'Insurance company profit', 'Type of insurance', 'B'],
        [5, 'What type of life insurance is temporary?', 'Whole life', 'Universal life', 'Term life', 'Variable life', 'C'],
        [5, 'What does comprehensive auto insurance cover?', 'Only accidents', 'Theft, vandalism, and natural disasters', 'Only liability', 'Only medical expenses', 'B'],
        
        // Tax Optimization (Quiz ID 6)
        [6, 'What is a tax deduction?', 'Money the government owes you', 'Amount that reduces taxable income', 'Extra tax you pay', 'Tax preparation fee', 'B'],
        [6, 'What is the standard deduction for 2023 (single filer)?', '$12,950', '$13,850', '$14,600', '$15,700', 'B'],
        [6, 'What is a tax credit?', 'Reduces taxable income', 'Dollar-for-dollar reduction in taxes owed', 'Interest on tax refund', 'Tax preparation software', 'B'],
        
        // Retirement Planning (Quiz ID 7)
        [7, 'What is a 401(k)?', 'Type of savings account', 'Employer-sponsored retirement plan', 'Government pension', 'Social Security number', 'B'],
        [7, 'What is employer matching?', 'Matching employee salary', 'Free money added to retirement contributions', 'Matching work hours', 'Employee benefit comparison', 'B'],
        [7, 'What is the main difference between traditional and Roth IRA?', 'Contribution limits', 'Tax treatment', 'Investment options', 'Age requirements', 'B'],
        
        // Real Estate Riches (Quiz ID 8)
        [8, 'What is a down payment?', 'Monthly mortgage payment', 'Upfront payment when buying property', 'Property tax', 'Insurance premium', 'B'],
        [8, 'What does PMI stand for?', 'Property Management Insurance', 'Private Mortgage Insurance', 'Public Mortgage Investment', 'Primary Market Index', 'B'],
        [8, 'What is equity in real estate?', 'Property value minus what you owe', 'Monthly rental income', 'Property appreciation', 'Mortgage interest rate', 'A'],
        
        // Crypto Currency 101 (Quiz ID 9)
        [9, 'What is Bitcoin?', 'A bank', 'A digital currency', 'A stock', 'A government bond', 'B'],
        [9, 'What is blockchain?', 'A type of chain', 'Distributed ledger technology', 'Banking software', 'Investment strategy', 'B'],
        [9, 'What is a cryptocurrency wallet?', 'Physical wallet for coins', 'Digital storage for cryptocurrencies', 'Bank account for crypto', 'Trading platform', 'B'],
        
        // Banking Basics (Quiz ID 10)
        [10, 'What is the difference between checking and savings accounts?', 'No difference', 'Checking for daily use, savings for storing money', 'Checking has higher interest', 'Savings for investments only', 'B'],
        [10, 'What is APY?', 'Annual Payment Year', 'Annual Percentage Yield', 'Account Processing Year', 'Automatic Payment Year', 'B'],
        [10, 'What is overdraft protection?', 'Account security feature', 'Service to prevent declined transactions', 'Insurance for banks', 'Investment protection', 'B']
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