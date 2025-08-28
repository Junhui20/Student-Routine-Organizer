<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get filter parameters
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $exercise_type_filter = $_GET['exercise_type'] ?? '';
    $min_duration = $_GET['min_duration'] ?? '';
    $max_duration = $_GET['max_duration'] ?? '';
    $min_calories = $_GET['min_calories'] ?? '';
    $max_calories = $_GET['max_calories'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'date_desc';
    
    // Build WHERE clause for filters
    $where_conditions = ["user_id = ?"];
    $params = [$_SESSION['user_id']];
    
    if (!empty($date_from)) {
        $where_conditions[] = "exercise_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "exercise_date <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($exercise_type_filter)) {
        $where_conditions[] = "exercise_type LIKE ?";
        $params[] = "%$exercise_type_filter%";
    }
    
    if (!empty($min_duration)) {
        $where_conditions[] = "duration_minutes >= ?";
        $params[] = $min_duration;
    }
    
    if (!empty($max_duration)) {
        $where_conditions[] = "duration_minutes <= ?";
        $params[] = $max_duration;
    }
    
    if (!empty($min_calories)) {
        $where_conditions[] = "calories_burned >= ?";
        $params[] = $min_calories;
    }
    
    if (!empty($max_calories)) {
        $where_conditions[] = "calories_burned <= ?";
        $params[] = $max_calories;
    }
    
    // Build ORDER BY clause
    $order_by = "ORDER BY exercise_date DESC, created_at DESC"; // default
    switch($sort_by) {
        case 'date_asc':
            $order_by = "ORDER BY exercise_date ASC, created_at ASC";
            break;
        case 'date_desc':
            $order_by = "ORDER BY exercise_date DESC, created_at DESC";
            break;
        case 'duration_asc':
            $order_by = "ORDER BY duration_minutes ASC";
            break;
        case 'duration_desc':
            $order_by = "ORDER BY duration_minutes DESC";
            break;
        case 'calories_asc':
            $order_by = "ORDER BY calories_burned ASC";
            break;
        case 'calories_desc':
            $order_by = "ORDER BY calories_burned DESC";
            break;
        case 'type_asc':
            $order_by = "ORDER BY exercise_type ASC";
            break;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get all exercises for the current user with filters (now including notes and exercise_time)
    $stmt = $conn->prepare("
        SELECT exercise_id, exercise_type, duration_minutes, calories_burned, exercise_date, created_at,
               exercise_time, notes
        FROM exercise_tracker 
        WHERE $where_clause 
        $order_by
    ");
    $stmt->execute($params);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics (without filters for overall stats)
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_exercises,
            SUM(duration_minutes) as total_minutes,
            SUM(calories_burned) as total_calories,
            AVG(duration_minutes) as avg_duration
        FROM exercise_tracker 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get this week's statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as week_exercises,
            SUM(duration_minutes) as week_minutes,
            SUM(calories_burned) as week_calories
        FROM exercise_tracker 
        WHERE user_id = ? AND exercise_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $week_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get unique exercise types for filter dropdown
    $stmt = $conn->prepare("
        SELECT DISTINCT exercise_type 
        FROM exercise_tracker 
        WHERE user_id = ? 
        ORDER BY exercise_type
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exercise_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(Exception $e) {
    $error = "Error loading exercises: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise Tracker - Student Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .exercise-time {
            color: #6c757d;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .filter-summary {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #1565c0;
        }
        
        .exercise-notes {
            margin-top: 1rem;
            padding: 1rem;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            border-left: 4px solid #f39c12;
        }
        
        .exercise-notes h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .exercise-notes p {
            margin: 0;
            color: #856404;
            line-height: 1.4;
            white-space: pre-wrap;
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/header.php'; ?>

    <main class="main-content">
        <div class="welcome-section">
            <h1><i class="fas fa-dumbbell"></i> Exercise Tracker</h1>
            <p>Track your workouts, monitor progress, and stay motivated!</p>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="container">
                <div class="alert alert-success">
                    <?php
                    switch($_GET['success']) {
                        case 'added':
                            echo '<i class="fas fa-check-circle"></i> Exercise added successfully!';
                            break;
                        case 'updated':
                            echo '<i class="fas fa-check-circle"></i> Exercise updated successfully!';
                            break;
                        case 'deleted':
                            echo '<i class="fas fa-check-circle"></i> Exercise deleted successfully!';
                            break;
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="container">
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="container">
            <h2><i class="fas fa-chart-line"></i> Your Exercise Statistics</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <h3><?php echo $stats['total_exercises'] ?? 0; ?></h3>
                    <p>Total Workouts</p>
                    <small class="text-muted"><?php echo $week_stats['week_exercises'] ?? 0; ?> this week</small>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h3><?php echo number_format($stats['total_minutes'] ?? 0); ?> min</h3>
                    <p>Total Exercise Time</p>
                    <small class="text-muted"><?php echo $week_stats['week_minutes'] ?? 0; ?> min this week</small>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-fire"></i></div>
                    <h3><?php echo number_format($stats['total_calories'] ?? 0); ?></h3>
                    <p>Calories Burned</p>
                    <small class="text-muted"><?php echo number_format($week_stats['week_calories'] ?? 0); ?> this week</small>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-stopwatch"></i></div>
                    <h3><?php echo round($stats['avg_duration'] ?? 0); ?> min</h3>
                    <p>Average Duration</p>
                    <small class="text-muted">Per workout session</small>
                </div>
            </div>
        </div>

        <!-- Add Exercise Button -->
        <div class="container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="add_exercise.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Exercise
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filter & Sort Exercises</h3>
                <form method="GET" action="">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="exercise_type">Exercise Type:</label>
                            <select id="exercise_type" name="exercise_type">
                                <option value="">All Types</option>
                                <?php foreach($exercise_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                            <?php echo ($exercise_type_filter == $type) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="min_duration">Min Duration (min):</label>
                            <input type="number" id="min_duration" name="min_duration" min="0" 
                                   value="<?php echo htmlspecialchars($min_duration); ?>" placeholder="0">
                        </div>
                        
                        <div class="filter-group">
                            <label for="max_duration">Max Duration (min):</label>
                            <input type="number" id="max_duration" name="max_duration" min="0" 
                                   value="<?php echo htmlspecialchars($max_duration); ?>" placeholder="999">
                        </div>
                        
                        <div class="filter-group">
                            <label for="min_calories">Min Calories:</label>
                            <input type="number" id="min_calories" name="min_calories" min="0" 
                                   value="<?php echo htmlspecialchars($min_calories); ?>" placeholder="0">
                        </div>
                        
                        <div class="filter-group">
                            <label for="max_calories">Max Calories:</label>
                            <input type="number" id="max_calories" name="max_calories" min="0" 
                                   value="<?php echo htmlspecialchars($max_calories); ?>" placeholder="9999">
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort_by">Sort By:</label>
                            <select id="sort_by" name="sort_by">
                                <option value="date_desc" <?php echo ($sort_by == 'date_desc') ? 'selected' : ''; ?>>Date (Newest First)</option>
                                <option value="date_asc" <?php echo ($sort_by == 'date_asc') ? 'selected' : ''; ?>>Date (Oldest First)</option>
                                <option value="duration_desc" <?php echo ($sort_by == 'duration_desc') ? 'selected' : ''; ?>>Duration (Longest First)</option>
                                <option value="duration_asc" <?php echo ($sort_by == 'duration_asc') ? 'selected' : ''; ?>>Duration (Shortest First)</option>
                                <option value="calories_desc" <?php echo ($sort_by == 'calories_desc') ? 'selected' : ''; ?>>Calories (Highest First)</option>
                                <option value="calories_asc" <?php echo ($sort_by == 'calories_asc') ? 'selected' : ''; ?>>Calories (Lowest First)</option>
                                <option value="type_asc" <?php echo ($sort_by == 'type_asc') ? 'selected' : ''; ?>>Exercise Type (A-Z)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <?php if(count($exercises) > 0 && (
                !empty($date_from) || !empty($date_to) || !empty($exercise_type_filter) || 
                !empty($min_duration) || !empty($max_duration) || !empty($min_calories) || !empty($max_calories)
            )): ?>
                <div class="filter-summary">
                    <i class="fas fa-info-circle"></i> 
                    Showing <?php echo count($exercises); ?> exercise(s) matching your filters.
                </div>
            <?php endif; ?>

            <h2><i class="fas fa-list"></i> Your Exercise History</h2>
            
            <?php if(empty($exercises)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <?php if(!empty($date_from) || !empty($date_to) || !empty($exercise_type_filter) || 
                             !empty($min_duration) || !empty($max_duration) || !empty($min_calories) || !empty($max_calories)): ?>
                        <h3>No exercises found matching your filters!</h3>
                        <p>Try adjusting your filter criteria or clear all filters to see all exercises.</p>
                        <a href="?" class="btn btn-secondary" style="margin-top: 1rem;">
                            <i class="fas fa-times"></i> Clear All Filters
                        </a>
                    <?php else: ?>
                        <h3>No exercises recorded yet!</h3>
                        <p>Start tracking your workouts to monitor your fitness progress.</p>
                        <a href="add_exercise.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add Your First Exercise
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach($exercises as $exercise): ?>
                    <div class="entry-card">
                        <div class="entry-header">
                            <div>
                                <h3 class="entry-title">
                                    <i class="fas fa-dumbbell"></i> <?php echo htmlspecialchars($exercise['exercise_type']); ?>
                                </h3>
                                <div class="entry-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($exercise['exercise_date'])); ?></span>
                                </div>
                            </div>
                            <div class="entry-actions">
                                <a href="edit_exercise.php?id=<?php echo $exercise['exercise_id']; ?>" class="btn btn-warning btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_exercise.php?id=<?php echo $exercise['exercise_id']; ?>" 
                                   class="btn btn-danger btn-small" 
                                   onclick="return confirm('Are you sure you want to delete this exercise record?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        
                        <div class="exercise-details">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                <?php if($exercise['exercise_time']): ?>
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; text-align: center;">
                                    <strong>Time</strong><br>
                                    <span style="color: #28a745; font-size: 1.1rem;"><?php echo date('g:i A', strtotime($exercise['exercise_time'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; text-align: center;">
                                    <strong>Duration</strong><br>
                                    <span style="color: #667eea; font-size: 1.2rem;"><?php echo $exercise['duration_minutes']; ?> min</span>
                                </div>
                                <?php if($exercise['calories_burned']): ?>
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; text-align: center;">
                                    <strong>Calories</strong><br>
                                    <span style="color: #e74c3c; font-size: 1.2rem;"><?php echo $exercise['calories_burned']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if(!empty($exercise['notes'])): ?>
                            <div class="exercise-notes">
                                <h4><i class="fas fa-sticky-note"></i> Notes</h4>
                                <p><?php echo htmlspecialchars($exercise['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>