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
    <link rel="stylesheet" href="css/style.css">
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
                <a href="index.php"><i class="fas fa-graduation-cap"></i> Student Organizer</a>
            </div>
            
            <button class="nav-toggle" onclick="toggleNav()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-menu" id="navMenu">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Main Navigation -->
                    <a href="index.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    
                    <!-- 4 Modules -->
                    <div class="nav-modules">
                        <a href="diary/index.php" class="nav-link">
                            <i class="fas fa-journal-whills"></i> Diary
                        </a>
                        <a href="exercises/index.php" class="nav-link">
                            <i class="fas fa-dumbbell"></i> Exercise
                        </a>
                        <a href="money/index.php" class="nav-link">
                            <i class="fas fa-wallet"></i> Money
                        </a>
                        <a href="habits/index.php" class="nav-link">
                            <i class="fas fa-check-circle"></i> Habits
                        </a>
                    </div>
                    
                    <!-- User Actions -->
                    <a href="auth/logout.php" class="nav-link logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <?php else: ?>
                    <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a>
                    <a href="auth/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="auth/register.php" class="nav-link"><i class="fas fa-user-plus"></i> Register</a>
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
        <div class="welcome-section">
            <h1><i class="fas fa-graduation-cap"></i> Student Routine Organizer</h1>
            <p>Your all-in-one platform to manage and improve your daily student life</p>
            <p style="font-size: 1.1rem; margin-top: 1rem;">Choose from our four powerful tools to organize your routine:</p>
        </div>

        <?php if(isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
            <div class="container">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> You have been successfully logged out!
                </div>
            </div>
        <?php endif; ?>

        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem;"><i class="fas fa-th-large"></i> Choose Your Module</h2>
            
            <div class="feature-grid">
                <!-- Exercise Tracker Module -->
                <div class="feature-card" style="border: 3px solid #667eea; position: relative;">
                    <div style="position: absolute; top: -10px; right: -10px; background: #667eea; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                        AVAILABLE
                    </div>
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Exercise Tracker</h3>
                    <p>Track your daily workouts, exercises, duration, and calories burned. Monitor your fitness progress and stay motivated.</p>
                    <div style="margin-top: 2rem;">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="exercises/index.php" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-chart-line"></i> View My Workouts
                            </a>
                            <a href="exercises/add_exercise.php" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-plus"></i> Log Workout
                            </a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-dumbbell"></i> Start Tracking
                            </a>
                        <?php endif; ?>
                    </div>
                    <small style="color: #667eea; font-weight: bold; display: block; margin-top: 1rem;">✅ Fully Functional</small>
                </div>

                <!-- Diary Journal Module -->
                <div class="feature-card" style="border: 3px solid #667eea; position: relative;">
                    <div style="position: absolute; top: -10px; right: -10px; background: #667eea; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                        AVAILABLE
                    </div>
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3>Diary Journal</h3>
                    <p>Record your daily thoughts, track moods, and reflect on your experiences. Keep a personal journal with date tracking.</p>
                    <div style="margin-top: 2rem;">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="diary/index.php" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-journal-whills"></i> View My Entries
                            </a>
                            <a href="diary/add_entry.php" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-plus"></i> New Entry
                            </a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-book-open"></i> Start Journaling
                            </a>
                        <?php endif; ?>
                    </div>
                    <small style="color: #667eea; font-weight: bold; display: block; margin-top: 1rem;">✅ Fully Functional</small>
                </div>

                <!-- Money Tracker Module - NOW AVAILABLE -->
                <div class="feature-card" style="border: 3px solid #667eea; position: relative;">
                    <div style="position: absolute; top: -10px; right: -10px; background: #667eea; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                        AVAILABLE
                    </div>
                    <div class="feature-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Money Tracker</h3>
                    <p>Manage your personal finances by tracking income and expenses. Categorize transactions and monitor your balance.</p>
                    <div style="margin-top: 2rem;">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="money/index.php" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-chart-pie"></i> View My Finances
                            </a>
                            <a href="money/add_transaction.php" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-plus"></i> Add Transaction
                            </a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-wallet"></i> Start Tracking Money
                            </a>
                        <?php endif; ?>
                    </div>
                    <small style="color: #667eea; font-weight: bold; display: block; margin-top: 1rem;">✅ Fully Functional</small>
                </div>

                <!-- Habit Tracker Module -->
                <div class="feature-card" style="border: 3px solid #667eea; position: relative;">
                    <div style="position: absolute; top: -10px; right: -10px; background: #667eea; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                        AVAILABLE
                    </div>
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Habit Tracker</h3>
                    <p>Build positive routines by setting personal habits and tracking daily progress. Monitor your consistency and growth.</p>
                    <div style="margin-top: 2rem;">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="habits/index.php" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">
                                <i class="fas fa-tasks"></i> View My Habits
                            </a>
                            <a href="habits/add_habit.php" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-plus"></i> Add New Habit
                            </a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-check-circle"></i> Start Tracking Habits
                            </a>
                        <?php endif; ?>
                    </div>
                    <small style="color: #667eea; font-weight: bold; display: block; margin-top: 1rem;">✅ Fully Functional</small>
                </div>
            </div>

        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="container">
            <h2><i class="fas fa-chart-line"></i> Your Activity Summary</h2>
            <?php
            try {
                require_once 'config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                
                // Get exercise statistics
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_exercises,
                        SUM(duration_minutes) as total_minutes,
                        SUM(calories_burned) as total_calories
                    FROM exercise_tracker 
                    WHERE user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $exercise_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as recent_exercises,
                        SUM(duration_minutes) as recent_minutes
                    FROM exercise_tracker 
                    WHERE user_id = ? AND exercise_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_exercise = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get diary statistics
                $stmt = $conn->prepare("SELECT COUNT(*) as total_entries FROM diary_entries WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $total_entries = $stmt->fetch(PDO::FETCH_ASSOC)['total_entries'];
                
                $stmt = $conn->prepare("SELECT COUNT(*) as recent_entries FROM diary_entries WHERE user_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_entries = $stmt->fetch(PDO::FETCH_ASSOC)['recent_entries'];
                
                // Get money statistics
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_transactions,
                        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expenses,
                        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as current_balance
                    FROM money_transactions 
                    WHERE user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $money_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as recent_transactions 
                    FROM money_transactions 
                    WHERE user_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_money = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<div class='feature-grid'>";

                // Exercise Stats
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-dumbbell'></i></div>";
                echo "<h3>" . ($exercise_stats['total_exercises'] ?? 0) . "</h3>";
                echo "<p>Workouts Logged</p>";
                echo "<small class='text-muted'>" . ($recent_exercise['recent_exercises'] ?? 0) . " this week</small>";
                echo "</div>";
            
                // Exercise Time Stats
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-clock'></i></div>";
                echo "<h3>" . number_format($exercise_stats['total_minutes'] ?? 0) . " min</h3>";
                echo "<p>Exercise Time</p>";
                echo "<small class='text-muted'>" . ($recent_exercise['recent_minutes'] ?? 0) . " min this week</small>";
                echo "</div>";
                
                // Diary Stats
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-book'></i></div>";
                echo "<h3>$total_entries</h3>";
                echo "<p>Diary Entries</p>";
                echo "<small class='text-muted'>$recent_entries this week</small>";
                echo "</div>";

                // Money Balance
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-wallet'></i></div>";
                $balance_color = ($money_stats['current_balance'] ?? 0) >= 0 ? '#28a745' : '#dc3545';
                echo "<h3 style='color: $balance_color'>RM " . number_format($money_stats['current_balance'] ?? 0, 2) . "</h3>";
                echo "<p>Current Balance</p>";
                echo "<small class='text-muted'>" . ($money_stats['total_transactions'] ?? 0) . " transactions</small>";
                echo "</div>";

                // Money Income
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-arrow-up' style='color: #28a745;'></i></div>";
                echo "<h3 style='color: #28a745'>RM " . number_format($money_stats['total_income'] ?? 0, 2) . "</h3>";
                echo "<p>Total Income</p>";
                echo "<small class='text-muted'>" . ($recent_money['recent_transactions'] ?? 0) . " recent transactions</small>";
                echo "</div>";

                // Money Expenses
                echo "<div class='feature-card'>";
                echo "<div class='feature-icon'><i class='fas fa-arrow-down' style='color: #dc3545;'></i></div>";
                echo "<h3 style='color: #dc3545'>RM " . number_format($money_stats['total_expenses'] ?? 0, 2) . "</h3>";
                echo "<p>Total Expenses</p>";
                echo "<small class='text-muted'>Money spent tracked</small>";
                echo "</div>";
                
                echo "</div>";
                
            } catch(Exception $e) {
                echo "<p class='text-muted'>Unable to load statistics at the moment.</p>";
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Team Information -->
        <div class="container">
            <h2 style="text-align: center;"><i class="fas fa-users"></i> Development Team</h2>
            <div class="feature-grid">
                <div class="feature-card text-center" style="border: 2px solid #27ae60;">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <h4>Exercise Tracker</h4>
                    <p><strong>Jooyee</strong></p>
                    <small style="color: #27ae60; font-weight: bold;">✅ Complete</small>
                </div>
                <div class="feature-card text-center" style="border: 2px solid #27ae60;">
                    <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                    <h4>Diary Journal</h4>
                    <p><strong>JunHui</strong></p>
                    <small style="color: #27ae60; font-weight: bold;">✅ Complete</small>
                </div>
                <div class="feature-card text-center" style="border: 2px solid #27ae60;">
                    <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                    <h4>Money Tracker</h4>
                    <p><strong>Wilson</strong></p>
                    <small style="color: #27ae60; font-weight: bold;">✅ Complete</small>
                </div>
                <div class="feature-card text-center" style="border: 2px solid #27ae60;">
                    <div class="feature-icon"><i class="fas fa-check-circle"></i></div>
                    <h4>Habit Tracker</h4>
                    <p><strong>Ng Xue En</strong></p>
                    <small style="color: #27ae60; font-weight: bold;">✅ Complete</small>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Student Routine Organizer - Team Project</p>
            <p>Built with PHP & MySQL | 3-Tier Architecture</p>
        </div>
    </footer>

    <script>
    function showComingSoon(moduleName) {
        alert(`${moduleName} module is currently being developed by another team member.\n\nThe Exercise Tracker, Diary Journal, and Money Tracker modules are fully functional and ready to use!`);
    }
    </script>
</body>
</html>