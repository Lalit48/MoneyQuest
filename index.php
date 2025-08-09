<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyQuest - Master Money the Fun Way!</title>
    
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/MotionPathPlugin.min.js"></script>
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Lottie -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    
    <!-- Tilt.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.2/vanilla-tilt.min.js"></script>
    
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <!-- Custom Cursor -->
    <link rel="stylesheet" href="public/css/cursor.css">
    
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

        /* Modern How It Works - Flow Diagram */
        .how-it-works-flow {
            display: flex;
            align-items: center;
            position: relative;
            padding: 2rem 0;
        }

        .flow-line {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--border-color);
            transform: translateY(-50%);
            z-index: 0;
        }

        .flow-line-progress {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--gradient-primary);
            width: 0;
            transition: width 0.5s ease-out;
        }

        .flow-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .flow-step:hover {
            transform: scale(1.05);
        }

        .flow-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
            border: 4px solid var(--border-color);
            background: var(--dark-bg);
            transition: all 0.4s ease;
        }

        .flow-step.active .flow-icon {
            border-color: var(--primary-color);
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }

        .flow-content {
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: opacity 0.4s ease, visibility 0.4s ease, transform 0.4s ease;
            position: absolute;
            width: 250px;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 1rem;
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .flow-step.active .flow-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) translateX(-50%);
        }

        @media (max-width: 768px) {
            .how-it-works-flow {
                flex-direction: column;
            }

            .flow-line {
                width: 4px;
                height: 100%;
                left: 50%;
                transform: translateX(-50%);
            }

            .flow-line-progress {
                width: 100%;
                height: 0;
                transition: height 0.5s ease-out;
            }

            .flow-step {
                margin-bottom: 2rem;
            }

            .flow-content {
                position: static;
                transform: none;
                width: 100%;
                margin-top: 0;
                text-align: center;
            }
        }

        /* Parallax effect */
        .parallax-bg {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        /* Testimonial Carousel */
        .testimonial-carousel-container {
            position: relative;
            width: 100%;
            height: 400px;
            perspective: 1000px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .testimonial-carousel {
            position: relative;
            width: 300px;
            height: 200px;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.77, 0, 0.175, 1);
        }

        .testimonial-card-carousel {
            position: absolute;
            width: 300px;
            height: auto;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            backface-visibility: hidden;
            box-shadow: var(--shadow-lg);
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .carousel-nav.prev {
            left: 10%;
        }

        .carousel-nav.next {
            right: 10%;
        }
        @media (max-width: 768px) {
            .testimonial-carousel-container {
                height: 500px; /* Adjust height for mobile */
            }
            .testimonial-carousel {
                transform: scale(0.7);
            }
            .carousel-nav.prev {
                left: 5%;
            }
            .carousel-nav.next {
                right: 5%;
            }
        }

        /* Mobile Navigation */
        .mobile-nav {
            display: none;
            flex-direction: column;
            background: rgba(15, 23, 42, 0.95);
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            padding: 1rem 0;
        }

        .mobile-nav.active {
            display: flex;
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
        
        /* Modern Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            padding-top: 80px; /* Add padding to account for fixed navbar */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(245, 158, 11, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Modern Glassmorphism */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
        }
        
        .glass-strong {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Modern Buttons */
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 500;
            padding: 1rem 2rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: var(--glass-bg);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Modern Cards */
        .feature-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        /* Modern Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.95);
            box-shadow: var(--shadow-lg);
        }
        
        /* Navigation Links */
        .nav-link {
            color: var(--text-primary);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.2);
        }
        
        .nav-link:active {
            transform: translateY(0);
        }
        
        /* Navigation Button Icons */
        .nav-btn-icon {
            color: var(--text-primary);
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .nav-btn-icon::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }
        
        .nav-btn-icon:hover::before {
            width: 100%;
            height: 100%;
        }
        
        .nav-btn-icon:hover {
            color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px) scale(1.1);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        /* Navigation Secondary Button */
        .nav-btn-secondary {
            color: var(--text-primary);
            background: transparent;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .nav-btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .nav-btn-secondary:hover::before {
            left: 100%;
        }
        
        .nav-btn-secondary:hover {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.2);
        }
        
        /* Navigation Primary Button */
        .nav-btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .nav-btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .nav-btn-primary:hover::before {
            left: 100%;
        }
        
        .nav-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4);
        }
        
        .nav-btn-primary:active {
            transform: translateY(-1px);
        }
        
        /* Modern Stats */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.3s ease;
        }
        
        /* Calculating effect styles */
        .stat-number.calculating {
            animation: calculatingPulse 0.5s ease-in-out infinite;
            position: relative;
            text-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }
        
        .stat-number.calculating::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.3), transparent);
            animation: calculatingShimmer 1s ease-in-out infinite;
        }
        
        .stat-number.calculating::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #6366f1, #8b5cf6, #ec4899, #6366f1);
            background-size: 400% 400%;
            animation: borderGlow 2s ease-in-out infinite;
            border-radius: 8px;
            z-index: -1;
        }
        
        @keyframes calculatingPulse {
            0%, 100% { 
                transform: scale(1);
                filter: brightness(1);
            }
            50% { 
                transform: scale(1.05);
                filter: brightness(1.2);
            }
        }
        
        @keyframes calculatingShimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @keyframes borderGlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .stat-number.animated {
            animation: counterComplete 0.5s ease-out;
        }
        
        @keyframes counterComplete {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* Modern Loading */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--darker-bg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Modern Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
            
            .hero-section {
                padding-top: 70px; /* Slightly less padding on mobile */
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .stat-number {
                font-size: 1.75rem;
            }
            
            .hero-section {
                padding-top: 60px; /* Even less padding on small mobile */
            }
        }

        /* Mobile Navigation */
        @media (max-width: 768px) {
            .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            
            .nav-btn-secondary,
            .nav-btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            
            .nav-btn-icon {
                width: 35px;
                height: 35px;
            }
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
    </style>
</head>
<body>
    <!-- Page Transition Overlay -->
    <div class="page-transition-overlay">
        <div class="transition-wipe left"></div>
        <div class="transition-wipe right"></div>
    </div>

    <!-- Modern Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div class="text-white text-lg font-semibold">Loading MoneyQuest...</div>
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
                    <a href="#features" class="nav-link">
                        <i class="fas fa-star mr-2"></i>Features
                    </a>
                    <a href="#how-it-works" class="nav-link">
                        <i class="fas fa-cogs mr-2"></i>How It Works
                    </a>
                    <a href="#testimonials" class="nav-link">
                        <i class="fas fa-comments mr-2"></i>Reviews
                    </a>
                    <a href="signup.php" class="nav-link page-transition-link">
                        <i class="fas fa-user-plus mr-2"></i>Sign Up
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="sound-toggle" onclick="toggleSound()" class="nav-btn-icon" title="Mute Sound">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <a href="login.php" class="nav-btn-secondary hidden md:flex page-transition-link">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="signup.php" class="nav-btn-primary hidden md:flex page-transition-link">
                        <i class="fas fa-rocket mr-2"></i>Get Started
                    </a>
                    <div class="hamburger md:hidden">
                        <i class="fas fa-bars text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="mobile-nav">
                <a href="#features" class="nav-link">
                    <i class="fas fa-star mr-2"></i>Features
                </a>
                <a href="#how-it-works" class="nav-link">
                    <i class="fas fa-cogs mr-2"></i>How It Works
                </a>
                <a href="#testimonials" class="nav-link">
                    <i class="fas fa-comments mr-2"></i>Reviews
                </a>
                <a href="signup.php" class="nav-link page-transition-link">
                    <i class="fas fa-user-plus mr-2"></i>Sign Up
                </a>
                <a href="login.php" class="nav-link page-transition-link">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <a href="signup.php" class="nav-link page-transition-link">
                    <i class="fas fa-rocket mr-2"></i>Get Started
                </a>
            </div>
        </div>
    </nav>
            </div>
        </div>
    </nav>

    <!-- Modern Hero Section -->
    <section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden pt-20 parallax-bg" style="background-image: url('https://images.unsplash.com/photo-1518546312222-202c25ba34b4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');">
        <div id="particles-js" class="absolute inset-0"></div>
        <div id="threejs-container" class="absolute inset-0"></div>
        
        <div class="container relative z-10">
            <div class="text-center max-w-4xl mx-auto" data-aos="fade-up" data-aos-duration="1500">
                <h1 class="hero-title text-5xl md:text-7xl font-black text-white mb-8 leading-tight">
                    Master Money the 
                    <span class="gradient-text">Fun Way!</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-12 max-w-3xl mx-auto leading-relaxed">
                    Transform your financial future with gamified learning, virtual trading, and interactive challenges. 
                    Join thousands of learners on their journey to financial literacy!
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-16">
                    <a href="signup.php" class="btn-primary text-lg px-8 py-4 page-transition-link">
                        <i class="fas fa-rocket mr-3"></i>Start Your Quest
                    </a>
                    <a href="#demo" class="btn-secondary text-lg px-8 py-4 page-transition-link">
                        <i class="fas fa-play mr-3"></i>Watch Demo
                    </a>
                </div>
                
                <!-- Modern Stats Preview -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
                    <div class="stat-card">
                        <div class="stat-number mb-2" data-target="5000">0</div>
                        <div class="text-gray-300">Active Players</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number mb-2" data-target="15000">0</div>
                        <div class="text-gray-300">Quizzes Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number mb-2" data-target="25000">0</div>
                        <div class="text-gray-300">Virtual Money Traded</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modern Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <div class="flex flex-col items-center text-white">
                <span class="text-sm mb-2">Scroll to explore</span>
                <i class="fas fa-chevron-down text-2xl text-indigo-400"></i>
            </div>
        </div>
    </section>

    <!-- Modern How It Works Section -->
    <section id="how-it-works" class="py-24 bg-gradient-to-b from-slate-900 to-slate-800 relative overflow-hidden">
        <div id="how-it-works-threejs-container" class="absolute inset-0 z-0 opacity-20"></div>
        <div class="container relative z-10">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    How It Works
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                    Start your financial learning journey in just a few simple steps
                </p>
            </div>

            <div class="how-it-works-flow" data-aos="fade-up" data-aos-delay="200">
                <div class="flow-line"><div class="flow-line-progress"></div></div>
                
                <div class="flow-step active" data-step="0">
                    <div class="flow-icon">
                        <i class="fas fa-user-plus text-3xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-4">Sign Up</h3>
                </div>

                <div class="flow-step" data-step="1">
                    <div class="flow-icon">
                        <i class="fas fa-question-circle text-3xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-4">Play Quizzes</h3>
                </div>

                <div class="flow-step" data-step="2">
                    <div class="flow-icon">
                        <i class="fas fa-chart-line text-3xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-4">Trade Stocks</h3>
                </div>

                <div class="flow-step" data-step="3">
                    <div class="flow-icon">
                        <i class="fas fa-trophy text-3xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-4">Earn Rewards</h3>
                </div>
            </div>

            <div class="mt-16 relative h-48">
                <div class="flow-content-wrapper">
                    <div class="flow-content active" data-content="0">
                        <h4 class="text-2xl font-bold text-white mb-3">Sign Up & Create Avatar</h4>
                        <p class="text-gray-300">Create your account and get 1000 virtual coins to start your journey.</p>
                    </div>
                    <div class="flow-content" data-content="1">
                        <h4 class="text-2xl font-bold text-white mb-3">Play Finance Quizzes</h4>
                        <p class="text-gray-300">Test your knowledge with interactive quizzes on budgeting and investing.</p>
                    </div>
                    <div class="flow-content" data-content="2">
                        <h4 class="text-2xl font-bold text-white mb-3">Simulate Stock Trading</h4>
                        <p class="text-gray-300">Practice investing with real-time stock data in our virtual simulator.</p>
                    </div>
                    <div class="flow-content" data-content="3">
                        <h4 class="text-2xl font-bold text-white mb-3">Earn Rewards & Badges</h4>
                        <p class="text-gray-300">Compete with other players and unlock achievements on the leaderboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Stats Dashboard -->
    <section class="py-24 bg-gradient-to-r from-indigo-900 to-purple-900 relative">
        <div class="container">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    Platform Statistics
                </h2>
                <p class="text-xl text-gray-300">See how our community is growing</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">
                <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-number text-6xl mb-4" data-target="5000">0</div>
                    <p class="text-white text-xl font-semibold">Active Players</p>
                    <div class="w-16 h-1 bg-gradient-to-r from-indigo-400 to-purple-500 mx-auto mt-4 rounded-full"></div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-number text-6xl mb-4" data-target="25000">0</div>
                    <p class="text-white text-xl font-semibold">Virtual Money Traded</p>
                    <div class="w-16 h-1 bg-gradient-to-r from-green-400 to-blue-500 mx-auto mt-4 rounded-full"></div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-number text-6xl mb-4" data-target="15000">0</div>
                    <p class="text-white text-xl font-semibold">Quizzes Completed</p>
                    <div class="w-16 h-1 bg-gradient-to-r from-purple-400 to-pink-500 mx-auto mt-4 rounded-full"></div>
                </div>
            </div>
            
            <!-- Modern Chart -->
            <div class="glass rounded-2xl p-8" data-aos="fade-up" data-aos-delay="400">
                <canvas id="earningsChart" height="250"></canvas>
            </div>
        </div>
    </section>

    <!-- Modern Testimonials Section -->
    <section id="testimonials" class="py-24 bg-gradient-to-r from-slate-900 to-slate-800 relative">
        <div class="container">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-5xl md:text-6xl font-bold text-white mb-6">
                    What Our Users Say
                </h2>
                <p class="text-xl text-gray-300">Join thousands of satisfied learners</p>
            </div>
            
            <div class="testimonial-carousel-container">
                <div class="testimonial-carousel">
                    <div class="testimonial-card-carousel">
                        <div class="flex items-center mb-6">
                            <img src="https://placehold.co/80x80/6366f1/ffffff.png?text=JS" alt="User" class="w-16 h-16 rounded-full mr-4 ring-4 ring-indigo-400/30">
                            <div>
                                <h4 class="font-bold text-white text-xl">John Smith</h4>
                                <div class="flex text-yellow-400 mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                                <span class="text-xs text-indigo-400 bg-indigo-400/20 px-3 py-1 rounded-full">Earning Journey</span>
                            </div>
                        </div>
                        <p class="text-gray-300 leading-relaxed">"MoneyQuest made learning finance fun and engaging. The quizzes are challenging but rewarding!"</p>
                    </div>
                    <div class="testimonial-card-carousel">
                        <div class="flex items-center mb-6">
                            <img src="https://placehold.co/80x80/8b5cf6/ffffff.png?text=MJ" alt="User" class="w-16 h-16 rounded-full mr-4 ring-4 ring-purple-400/30">
                            <div>
                                <h4 class="font-bold text-white text-xl">Maria Johnson</h4>
                                <div class="flex text-yellow-400 mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                                <span class="text-xs text-purple-400 bg-purple-400/20 px-3 py-1 rounded-full">Earning Journey</span>
                            </div>
                        </div>
                        <p class="text-gray-300 leading-relaxed">"The stock simulator is amazing! I learned so much about investing without risking real money."</p>
                    </div>
                    <div class="testimonial-card-carousel">
                        <div class="flex items-center mb-6">
                            <img src="https://placehold.co/80x80/ec4899/ffffff.png?text=DL" alt="User" class="w-16 h-16 rounded-full mr-4 ring-4 ring-pink-400/30">
                            <div>
                                <h4 class="font-bold text-white text-xl">David Lee</h4>
                                <div class="flex text-yellow-400 mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                                <span class="text-xs text-pink-400 bg-pink-400/20 px-3 py-1 rounded-full">Earning Journey</span>
                            </div>
                        </div>
                        <p class="text-gray-300 leading-relaxed">"Finally, a platform that makes financial education accessible and enjoyable for everyone!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Call-to-Action Section -->
    <section class="py-24 bg-gradient-to-r from-indigo-600 to-purple-600 relative overflow-hidden">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="text-5xl md:text-6xl font-bold text-white mb-8">
                    Your Financial Adventure Starts Now 🚀
                </h2>
                <p class="text-xl text-white/90 mb-10 max-w-3xl mx-auto">
                    Join thousands of learners and start your journey to financial literacy today
                </p>
                <a href="signup.php" class="btn-primary text-xl px-12 py-6 bg-white text-indigo-600 hover:bg-gray-100 page-transition-link">
                    <i class="fas fa-rocket mr-3"></i>Join Free
                </a>
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
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors duration-300">Features</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white transition-colors duration-300">How It Works</a></li>
                        <li><a href="#testimonials" class="text-gray-400 hover:text-white transition-colors duration-300">Reviews</a></li>
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
            
            <div class="border-t border-slate-800 mt-12 pt-8 text-center">
                <p class="text-gray-400">&copy; 2024 MoneyQuest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Modern JavaScript for MoneyQuest Landing Page
        
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100,
            easing: 'ease-out-cubic'
        });

        // Initialize GSAP
        gsap.registerPlugin(ScrollTrigger, MotionPathPlugin);

        // Global variables
        let currentSlide = 0;
        let isAnimating = false;

        // Modern Loading Screen
        $(document).ready(function() {
            setTimeout(function() {
                $('#loading').addClass('hidden');
                initAllFeatures();
            }, 2000);
        });

        // Initialize all features
        function initAllFeatures() {
            initParticles();
            initThreeJS();
            initHowItWorksThreeJS();
            animate();
            initChart();
            initCounters();
            initInteractiveElements();
            initScrollAnimations();
            initTestimonialCarousel();
            initPageTransitions();
        }

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
                            random: false
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

        // Modern Counter Animation with Sound
        function initCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            // Create audio context for sound effects
            let audioContext;
            let soundEnabled = true;
            
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                console.log('Audio context not supported');
                soundEnabled = false;
            }
            
            // Function to play counting sound
            function playCountSound() {
                if (!audioContext || !soundEnabled) return;
                
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(1200, audioContext.currentTime + 0.1);
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            }
            
            // Function to play completion sound
            function playCompletionSound() {
                if (!audioContext || !soundEnabled) return;
                
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(2000, audioContext.currentTime + 0.2);
                
                gainNode.gain.setValueAtTime(0.15, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            }
            
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                        const target = parseInt(entry.target.getAttribute('data-target'));
                        const duration = 2.5;
                        let current = 0;
                        const increment = target / (duration * 60); // 60fps
                        let lastTime = 0;
                        let soundCounter = 0;
                        
                        // Add calculating effect class
                        entry.target.classList.add('calculating');
                        
                        function updateCounter(timestamp) {
                            if (!lastTime) lastTime = timestamp;
                            const deltaTime = timestamp - lastTime;
                            
                            if (deltaTime >= 16) { // ~60fps
                                current += increment * deltaTime;
                                
                                if (current < target) {
                                    // Play counting sound periodically
                                    soundCounter++;
                                    if (soundCounter % 10 === 0) { // Play sound every 10 frames
                                        playCountSound();
                                    }
                                    
                                    entry.target.textContent = Math.floor(current).toLocaleString();
                                    lastTime = timestamp;
                                    requestAnimationFrame(updateCounter);
                                } else {
                                    // Final value
                                    entry.target.textContent = target.toLocaleString();
                                    entry.target.classList.remove('calculating');
                                    entry.target.classList.add('animated');
                                    
                                    // Play completion sound
                                    playCompletionSound();
                                    
                                    // Add celebration effect
                                    gsap.to(entry.target, {
                                        duration: 0.3,
                                        scale: 1.2,
                                        ease: "back.out(1.7)",
                                        yoyo: true,
                                        repeat: 1
                                    });
                                }
                            } else {
                                requestAnimationFrame(updateCounter);
                            }
                        }
                        
                        requestAnimationFrame(updateCounter);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5, rootMargin: '0px 0px -100px 0px' });

            counters.forEach(counter => {
                counterObserver.observe(counter);
            });
            
            // Add sound toggle functionality
            window.toggleSound = function() {
                soundEnabled = !soundEnabled;
                const soundBtn = document.getElementById('sound-toggle');
                if (soundBtn) {
                    soundBtn.innerHTML = soundEnabled ? 
                        '<i class="fas fa-volume-up"></i>' : 
                        '<i class="fas fa-volume-mute"></i>';
                    soundBtn.title = soundEnabled ? 'Mute Sound' : 'Unmute Sound';
                }
            };
        }

        // Modern Three.js Animation
        let scene, camera, renderer, coin, rupeeSymbol;

        function initThreeJS() {
            if (typeof THREE === 'undefined') return;
            
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0);
            
            const container = document.getElementById('threejs-container');
            if (container) {
                container.appendChild(renderer.domElement);
            }
            
            // Create modern spinning coin
            const coinGeometry = new THREE.CylinderGeometry(2, 2, 0.2, 32);
            const coinMaterial = new THREE.MeshPhongMaterial({ 
                color: 0x6366f1,
                shininess: 100,
                emissive: 0x6366f1,
                emissiveIntensity: 0.2
            });
            coin = new THREE.Mesh(coinGeometry, coinMaterial);
            coin.position.set(-5, 2, -10);
            scene.add(coin);
            
            // Create floating rupee symbol
            const rupeeGeometry = new THREE.SphereGeometry(1, 32, 32);
            const rupeeMaterial = new THREE.MeshPhongMaterial({ 
                color: 0x8b5cf6,
                transparent: true,
                opacity: 0.8,
                emissive: 0x8b5cf6,
                emissiveIntensity: 0.3
            });
            rupeeSymbol = new THREE.Mesh(rupeeGeometry, rupeeMaterial);
            rupeeSymbol.position.set(5, -2, -10);
            scene.add(rupeeSymbol);
            
            // Add lighting
            const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(10, 10, 5);
            scene.add(directionalLight);
            
            camera.position.z = 15;
        }

        function animate() {
            requestAnimationFrame(animate);
            
            if (coin) {
                coin.rotation.y += 0.02;
                coin.rotation.x += 0.01;
                coin.position.y = Math.sin(Date.now() * 0.001) * 0.5;
            }
            
            if (rupeeSymbol) {
                rupeeSymbol.rotation.y += 0.01;
                rupeeSymbol.position.y = Math.sin(Date.now() * 0.001) * 2;
                rupeeSymbol.position.x = Math.cos(Date.now() * 0.001) * 2;
            }
            
            if (renderer && scene && camera) {
                renderer.render(scene, camera);
            }
        }

        function onWindowResize() {
            if (camera && renderer) {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            }
        }

        // Modern Chart.js with Animation
        function initChart() {
            const canvas = document.getElementById('earningsChart');
            if (!canvas) return;

            const chartData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                datasets: [{
                    label: 'User Earnings Growth',
                    data: [], // Initially empty
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            };

            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: { color: 'white', font: { size: 12, weight: 'bold' } },
                        grid: { color: 'rgba(255, 255, 255, 0.1)', drawBorder: false }
                    },
                    y: {
                        ticks: { color: 'white', font: { size: 12, weight: 'bold' } },
                        grid: { color: 'rgba(255, 255, 255, 0.1)', drawBorder: false }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            };

            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.8)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0.1)');
            chartData.datasets[0].backgroundColor = gradient;

            const earningsChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: chartOptions
            });

            const chartObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    setTimeout(() => {
                        earningsChart.data.datasets[0].data = [1000, 1500, 2200, 3000, 4200, 5500, 7200, 9000];
                        earningsChart.update();
                    }, 500);
                    chartObserver.unobserve(canvas);
                }
            }, { threshold: 0.5 });

            chartObserver.observe(canvas);
        }

        // Modern Interactive Elements
        function initInteractiveElements() {
            // Smooth scrolling
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 80
                    }, 1000, 'easeInOutCubic');
                }
            });

            // Modern hover effects
            $('.feature-card').hover(
                function() {
                    gsap.to(this, { duration: 0.3, y: -8, scale: 1.02, ease: "power2.out" });
                },
                function() {
                    gsap.to(this, { duration: 0.3, y: 0, scale: 1, ease: "power2.out" });
                }
            );

            // Modern button effects
            $('.btn-primary, .btn-secondary').on('click', function(e) {
                const button = $(this);
                const ripple = $('<span class="ripple"></span>');
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.css({
                    width: size,
                    height: size,
                    left: x,
                    top: y
                });
                
                button.append(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Navigation link effects
            $('.nav-link').hover(
                function() {
                    gsap.to(this, { duration: 0.3, y: -2, ease: "power2.out" });
                },
                function() {
                    gsap.to(this, { duration: 0.3, y: 0, ease: "power2.out" });
                }
            );
            
            // Navigation button effects
            $('.nav-btn-primary, .nav-btn-secondary').hover(
                function() {
                    gsap.to(this, { duration: 0.3, y: -3, ease: "power2.out" });
                },
                function() {
                    gsap.to(this, { duration: 0.3, y: 0, ease: "power2.out" });
                }
            );
            
            // Sound toggle button effect
            $('#sound-toggle').hover(
                function() {
                    gsap.to(this, { duration: 0.3, scale: 1.1, ease: "power2.out" });
                },
                function() {
                    gsap.to(this, { duration: 0.3, scale: 1, ease: "power2.out" });
                }
            );
        }

        // Modern Scroll Animations
        function initScrollAnimations() {
            // Hero section animations
            gsap.from(".hero-title", {
                duration: 1.5,
                y: 100,
                opacity: 0,
                ease: "power3.out",
                stagger: 0.2
            });

            // Stats animation
            gsap.from(".stat-number", {
                duration: 1.5,
                scale: 0,
                opacity: 0,
                ease: "back.out(1.7)",
                scrollTrigger: {
                    trigger: ".stat-number",
                    start: "top 80%",
                    toggleActions: "play none none reverse"
                }
            });

            // How It Works flow animation
            const flowSteps = document.querySelectorAll('.flow-step');
            const flowContents = document.querySelectorAll('.flow-content');
            const flowLineProgress = document.querySelector('.flow-line-progress');
            let activeStep = 0;

            function activateStep(stepIndex) {
                if (isAnimating) return;
                isAnimating = true;

                activeStep = stepIndex;

                // Animate out old content
                gsap.to('.flow-content.active', {
                    duration: 0.3,
                    opacity: 0,
                    y: 20,
                    ease: 'power2.in',
                    onComplete: () => {
                        flowContents.forEach(c => c.classList.remove('active'));
                        
                        // Animate in new content
                        const newContent = document.querySelector(`.flow-content[data-content="${stepIndex}"]`);
                        newContent.classList.add('active');
                        gsap.fromTo(newContent, {
                            opacity: 0,
                            y: -20
                        }, {
                            duration: 0.5,
                            opacity: 1,
                            y: 0,
                            ease: 'power2.out',
                            onComplete: () => {
                                isAnimating = false;
                            }
                        });
                    }
                });

                // Update steps
                flowSteps.forEach((s, i) => {
                    if (i === stepIndex) {
                        s.classList.add('active');
                        gsap.to(s.querySelector('.flow-icon'), {
                            duration: 0.4,
                            scale: 1.2,
                            ease: 'back.out(1.7)'
                        });
                    } else {
                        s.classList.remove('active');
                        gsap.to(s.querySelector('.flow-icon'), {
                            duration: 0.4,
                            scale: 1,
                            ease: 'power2.out'
                        });
                    }
                });

                // Update progress bar
                const progress = (stepIndex / (flowSteps.length - 1)) * 100;
                gsap.to(flowLineProgress, {
                    duration: 0.5,
                    width: `${progress}%`,
                    ease: 'power2.out'
                });
            }

            flowSteps.forEach(step => {
                step.addEventListener('click', () => {
                    const stepIndex = parseInt(step.dataset.step);
                    activateStep(stepIndex);
                });
            });

            // Auto-play animation
            let autoPlayInterval = setInterval(() => {
                let nextStep = (activeStep + 1) % flowSteps.length;
                activateStep(nextStep);
            }, 5000);

            // Pause auto-play on hover
            const howItWorksSection = document.getElementById('how-it-works');
            howItWorksSection.addEventListener('mouseenter', () => {
                clearInterval(autoPlayInterval);
            });

            howItWorksSection.addEventListener('mouseleave', () => {
                autoPlayInterval = setInterval(() => {
                    let nextStep = (activeStep + 1) % flowSteps.length;
                    activateStep(nextStep);
                }, 5000);
            });

            // Hamburger menu toggle
            const hamburger = document.querySelector('.hamburger');
            const mobileNav = document.querySelector('.mobile-nav');
            hamburger.addEventListener('click', () => {
                mobileNav.classList.toggle('active');
            });
        }

        // Window resize handler
        window.addEventListener('resize', onWindowResize);

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

        // Testimonial Carousel
        function initTestimonialCarousel() {
            const carouselContainer = document.querySelector('.testimonial-carousel-container');
            const carousel = document.querySelector('.testimonial-carousel');
            const cards = document.querySelectorAll('.testimonial-card-carousel');
            let angle = 0;
            const radius = 350;
            let autoPlayInterval;

            function positionCards() {
                const numCards = cards.length;
                cards.forEach((card, i) => {
                    const cardAngle = (i / numCards) * 360;
                    const totalAngle = angle + cardAngle;
                    const x = Math.sin(totalAngle * Math.PI / 180) * radius;
                    const z = Math.cos(totalAngle * Math.PI / 180) * radius;
                    const y = 0;
                    card.style.transform = `translateX(${x}px) translateY(${y}px) translateZ(${z}px) rotateY(${-totalAngle}deg)`;
                });
            }

            function rotateCarousel() {
                angle -= 360 / cards.length;
                carousel.style.transform = `rotateY(${angle}deg)`;
            }

            function startAutoPlay() {
                autoPlayInterval = setInterval(rotateCarousel, 3000);
            }

            function stopAutoPlay() {
                clearInterval(autoPlayInterval);
            }

            carouselContainer.addEventListener('mouseenter', stopAutoPlay);
            carouselContainer.addEventListener('mouseleave', startAutoPlay);

            positionCards();
            startAutoPlay();
        }

        // Modern Three.js for "How It Works" Section
        let howItWorksScene, howItWorksCamera, howItWorksRenderer, howItWorksObject;

        function initHowItWorksThreeJS() {
            if (typeof THREE === 'undefined') return;

            const container = document.getElementById('how-it-works-threejs-container');
            if (!container) return;

            howItWorksScene = new THREE.Scene();
            howItWorksCamera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            howItWorksRenderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            howItWorksRenderer.setSize(container.clientWidth, container.clientHeight);
            howItWorksRenderer.setClearColor(0x000000, 0);
            container.appendChild(howItWorksRenderer.domElement);

            // Create a wide, flat, and visually interesting 3D object
            const geometry = new THREE.TorusKnotGeometry(10, 1, 100, 8, 3, 4);
            const material = new THREE.MeshStandardMaterial({
                color: 0x8b5cf6,
                metalness: 0.8,
                roughness: 0.2,
                emissive: 0x8b5cf6,
                emissiveIntensity: 0.1
            });
            howItWorksObject = new THREE.Mesh(geometry, material);
            howItWorksObject.scale.set(1, 0.2, 1); // Make it flat
            howItWorksScene.add(howItWorksObject);

            // Add lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            howItWorksScene.add(ambientLight);

            const pointLight = new THREE.PointLight(0xffffff, 1);
            pointLight.position.set(5, 10, 5);
            howItWorksScene.add(pointLight);

            howItWorksCamera.position.z = 15;

            // Handle responsive design
            window.addEventListener('resize', onHowItWorksResize);
            animateHowItWorks();
        }

        function animateHowItWorks() {
            requestAnimationFrame(animateHowItWorks);
            if (howItWorksObject) {
                howItWorksObject.rotation.x += 0.001;
                howItWorksObject.rotation.y += 0.002;
            }
            if (howItWorksRenderer && howItWorksScene && howItWorksCamera) {
                howItWorksRenderer.render(howItWorksScene, howItWorksCamera);
            }
        }

        function onHowItWorksResize() {
            const container = document.getElementById('how-it-works-threejs-container');
            if (howItWorksCamera && howItWorksRenderer && container) {
                howItWorksCamera.aspect = container.clientWidth / container.clientHeight;
                howItWorksCamera.updateProjectionMatrix();
                howItWorksRenderer.setSize(container.clientWidth, container.clientHeight);
            }
        }
    </script>
    <script src="public/js/cursor.js"></script>
</body>
</html>
