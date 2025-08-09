<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $conn = getConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Email already registered';
            } else {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $password_hash]);
                
                $success = 'Registration successful! Please login.';
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MoneyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/cursor.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap');

        :root {
            --neon-blue: #00dffc;
            --neon-pink: #ff00ff;
            --dark-bg: #0a0a1a;
            --card-bg: rgba(10, 10, 26, 0.5);
            --border-color: rgba(0, 223, 252, 0.3);
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background-color: var(--dark-bg);
            color: white;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .background-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 223, 252, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 223, 252, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: pan 60s linear infinite;
        }

        @keyframes pan {
            from { background-position: 0 0; }
            to { background-position: 500px 500px; }
        }

        .signup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .signup-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 223, 252, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 480px;
            animation: slideIn 1s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }
        
        .signup-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-pink));
            border-radius: 22px;
            z-index: -1;
            filter: blur(10px);
            opacity: 0.5;
            animation: glow 4s linear infinite;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: perspective(500px) rotateX(-20deg) translateY(50px); }
            to { opacity: 1; transform: perspective(500px) rotateX(0) translateY(0); }
        }
        
        @keyframes glow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .logo-section h2 {
            font-weight: 700;
            font-size: 2.5rem;
            background: linear-gradient(45deg, #ff0055, #ff5500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 10px rgba(255, 0, 85, 0.5), 0 0 20px rgba(255, 85, 0, 0.5);
            animation: flicker 3s infinite alternate;
        }
        
        @keyframes flicker {
            0%, 18%, 22%, 25%, 53%, 57%, 100% {
                text-shadow:
                0 0 4px #fff,
                0 0 11px #fff,
                0 0 19px #fff,
                0 0 40px var(--neon-blue),
                0 0 80px var(--neon-blue),
                0 0 90px var(--neon-blue),
                0 0 100px var(--neon-blue),
                0 0 150px var(--neon-blue);
            }
            20%, 24%, 55% {        
                text-shadow: none;
            }
        }

        .logo-section p {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 15px;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.5);
            border-color: var(--neon-pink);
            box-shadow: 0 0 15px rgba(255, 0, 255, 0.5);
            color: white;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: var(--neon-blue);
            font-size: 1.2rem;
        }

        .btn-signup {
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-pink));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-signup::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .btn-signup:hover::before {
            left: 100%;
        }

        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 20px var(--neon-pink);
        }

        .login-link a {
            color: var(--neon-pink);
            text-decoration: none;
            transition: color 0.3s, text-shadow 0.3s;
        }

        .login-link a:hover {
            color: white;
            text-shadow: 0 0 10px var(--neon-pink);
        }

        .alert {
            background: rgba(255, 0, 255, 0.1);
            border: 1px solid rgba(255, 0, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="background-grid"></div>
    <div class="signup-container">
        <div class="signup-card">
            <div class="logo-section">
                <h2><i class="fas fa-coins me-2"></i>MoneyQuest</h2>
                <p class="text-muted">Create your account and start your financial journey!</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <br><a href="login.php" class="alert-link">Click here to login</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-signup">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
