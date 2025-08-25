<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Routine Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Responsive Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0.75rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }
        
        .nav-logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-modules {
            display: flex;
            gap: 0.5rem;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.3);
        }
        
        .nav-user {
            color: white;
            margin-left: 1rem;
            font-size: 0.85rem;
        }
        
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .nav-toggle {
                display: block;
            }
            
            .nav-menu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                flex-direction: column;
                padding: 1rem;
                display: none;
                gap: 0.5rem;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .nav-modules {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }
            
            .nav-link {
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-graduation-cap"></i> Student Organizer</a>
            </div>
            
            <button class="nav-toggle" onclick="toggleNav()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-menu" id="navMenu">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Main Navigation -->
                    <a href="../index.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    
                    <!-- 4 Modules -->
                    <div class="nav-modules">
                        <a href="../diary/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/diary/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-journal-whills"></i> Diary
                        </a>
                        <a href="../exercises/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/exercises/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-dumbbell"></i> Exercise
                        </a>
                        <a href="../money/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/money/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-wallet"></i> Money
                        </a>
                        <a href="../habits/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/habits/') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Habits
                        </a>
                    </div>
                    
                    <!-- User Actions -->
                    <a href="../auth/logout.php" class="nav-link logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <?php else: ?>
                    <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                    <a href="../auth/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="../auth/register.php" class="nav-link"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <script>
        function toggleNav() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }
        
        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navMenu').classList.remove('active');
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.navbar')) {
                document.getElementById('navMenu').classList.remove('active');
            }
        });
    </script>
    
    <main class="main-content"> 