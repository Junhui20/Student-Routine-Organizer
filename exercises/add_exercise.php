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

$errors = [];
$success = false;

// Set default weight 
$user_weight = 70; 

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get exercises from database for dropdown with MET values
    $stmt = $conn->prepare("SELECT id, category, exercise_name, met_value FROM exercises ORDER BY category, exercise_name");
    $stmt->execute();
    $exercise_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $errors[] = "Error loading exercises: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exercise_category = trim($_POST['exercise_category']);
    $exercise_ref_id = isset($_POST['exercise_ref_id']) ? (int)$_POST['exercise_ref_id'] : null;
    $custom_exercise = trim($_POST['custom_exercise']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $calories_burned = (int)$_POST['calories_burned'];
    $exercise_date = $_POST['exercise_date'];
    $exercise_time = $_POST['exercise_time'];
    $user_input_weight = isset($_POST['user_weight']) ? (float)$_POST['user_weight'] : $user_weight;
    $notes = trim($_POST['notes']); // Add notes field
    
    // Validation
    if (empty($exercise_category)) {
        $errors[] = "Exercise category is required.";
    }
    
    if ($exercise_category === 'custom') {
        if (empty($custom_exercise)) {
            $errors[] = "Custom exercise name is required.";
        }
        $exercise_ref_id = null; // No reference for custom exercises
    } else {
        if (!$exercise_ref_id) {
            $errors[] = "Please select an exercise.";
        }
    }
    
    if ($duration_minutes <= 0) {
        $errors[] = "Duration must be greater than 0 minutes.";
    }
    
    if (empty($exercise_date)) {
        $errors[] = "Exercise date is required.";
    } elseif (strtotime($exercise_date) > time()) {
        $errors[] = "Exercise date cannot be in the future.";
    }
    
    if (empty($exercise_time)) {
        $errors[] = "Exercise time is required.";
    }
    
    if ($calories_burned <= 0) {
        $errors[] = "Calories burned must be greater than 0.";
    }
    
    // Validate weight input
    if ($user_input_weight <= 0 || $user_input_weight > 500) {
        $errors[] = "Weight must be between 1 and 500 kg.";
    }
    
    // Set Malaysia timezone for validation
    date_default_timezone_set('Asia/Kuala_Lumpur');
    
    // Combine date and time for timestamp validation
    $exercise_datetime = $exercise_date . ' ' . $exercise_time;
    $selected_timestamp = strtotime($exercise_datetime);
    $current_timestamp = time();
    
    if ($selected_timestamp > $current_timestamp) {
        $errors[] = "Exercise date and time cannot be in the future (Malaysia time).";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            if ($exercise_category === 'custom') {
                // For custom exercises, store in exercise_type field (backward compatibility)
                $stmt = $conn->prepare("
                    INSERT INTO exercise_tracker (user_id, exercise_type, duration_minutes, calories_burned, exercise_date, exercise_time, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $custom_exercise, $duration_minutes, $calories_burned, $exercise_date, $exercise_time, $notes])) {
                    header('Location: index.php?success=added');
                    exit();
                }
            } else {
                // For predefined exercises, use exercise_ref_id if column exists
                // First check if exercise_ref_id column exists
                $stmt = $conn->prepare("SHOW COLUMNS FROM exercise_tracker LIKE 'exercise_ref_id'");
                $stmt->execute();
                $column_exists = $stmt->fetch();
                
                if ($column_exists) {
                    // Get exercise name for backward compatibility
                    $stmt = $conn->prepare("SELECT exercise_name FROM exercises WHERE id = ?");
                    $stmt->execute([$exercise_ref_id]);
                    $exercise_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $exercise_name = $exercise_data['exercise_name'];
                    
                    $stmt = $conn->prepare("
                        INSERT INTO exercise_tracker (user_id, exercise_ref_id, exercise_type, duration_minutes, calories_burned, exercise_date, exercise_time, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$_SESSION['user_id'], $exercise_ref_id, $exercise_name, $duration_minutes, $calories_burned, $exercise_date, $exercise_time, $notes])) {
                        header('Location: index.php?success=added');
                        exit();
                    }
                } else {
                    // Fallback to old structure
                    $stmt = $conn->prepare("SELECT exercise_name FROM exercises WHERE id = ?");
                    $stmt->execute([$exercise_ref_id]);
                    $exercise_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $exercise_name = $exercise_data['exercise_name'];
                    
                    $stmt = $conn->prepare("
                        INSERT INTO exercise_tracker (user_id, exercise_type, duration_minutes, calories_burned, exercise_date, exercise_time, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$_SESSION['user_id'], $exercise_name, $duration_minutes, $calories_burned, $exercise_date, $exercise_time, $notes])) {
                        header('Location: index.php?success=added');
                        exit();
                    }
                }
            }
            
            $errors[] = "Failed to save exercise. Please try again.";
        } catch(Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Set Malaysia timezone for all date operations
date_default_timezone_set('Asia/Kuala_Lumpur');

// Set default date and time (Malaysia time)
$default_date = date('Y-m-d');
$default_time = date('H:i');

// Group exercises by category and count them
$grouped_exercises = [];
$category_counts = [];
foreach ($exercise_options as $exercise) {
    $grouped_exercises[$exercise['category']][] = $exercise;
    $category_counts[$exercise['category']] = ($category_counts[$exercise['category']] ?? 0) + 1;
}

// Icon mapping for exercise categories
$category_icons = [
    'Running & Jogging' => 'fas fa-running',
    'Bicycling' => 'fas fa-biking',
    'Yoga & Pilates' => 'fas fa-spa',
    'Gym & Calisthenics' => 'fas fa-dumbbell',
    'Dance' => 'fas fa-music',
    'Sports' => 'fas fa-basketball-ball',
    'Water Activities' => 'fas fa-swimmer',
    'Recreational' => 'fas fa-hiking',
    'Martial Arts' => 'fas fa-fist-raised',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exercise - Student Organizer</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exercise-dropdown {
            position: relative;
        }
        
        .exercise-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
        }
        
        .exercise-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .custom-input {
            margin-top: 0.5rem;
            display: none;
        }
        
        .custom-input.show {
            display: block;
        }
        
        .time-date-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .duration-calories-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .weight-duration-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.0rem;
            align-items: end;
        }
        .weight-duration-grid .form-group {
    display: flex;
    flex-direction: column;
    height: 75%;
}


        
        @media (max-width: 768px) {
            .time-date-grid, .duration-calories-grid, .weight-duration-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .calorie-info {
            background: #e8f4fd;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 5px 5px 0;
        }
        
        .calorie-info i {
            color: #007bff;
            margin-right: 0.5rem;
        }
        
        .auto-calc-indicator {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }
        
        .auto-calc-indicator.show {
            display: block;
        }
        
        .weight-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 5px 5px 0;
        }
        
        .weight-info i {
            color: #856404;
            margin-right: 0.5rem;
        }
       
.weight-input-section label[for="user_weight"] {
    margin-bottom: 1.5rem; 
}
        
        .calculation-details {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 3px;
            display: none;
        }
        
        .calculation-details.show {
            display: block;
        }
        
        .weight-input-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .weight-input-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #495057;
        }
        
        .weight-input-header i {
            margin-right: 0.5rem;
            color: #007bff;
        }
        
        .auto-calculate-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        
        .auto-calculate-btn:hover {
            background: #218838;
        }
        
        .auto-calculate-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .weight-source-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #e9ecef;
            border-radius: 3px;
        }

        .notes-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .notes-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #495057;
        }

        .notes-header i {
            margin-right: 0.5rem;
            color: #17a2b8;
        }

        .notes-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .notes-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .duration-with-button {
            display: flex;
            gap: 0.5rem;
            align-items: end;
        }

        .duration-input {
            flex: 1;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/header.php'; ?>

    <main class="main-content">
        <div class="welcome-section">
            <h1><i class="fas fa-plus"></i> Add New Exercise</h1>
            <p>Record your workout session with automatic calorie calculation</p>
        </div>

        <div class="container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="calorie-info">
                <i class="fas fa-calculator"></i>
                <strong>Smart Calorie Calculation:</strong> Select exercises from our database and enter your weight to automatically calculate calories burned based on MET values.
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="exercise_category">
                        <i class="fas fa-layer-group"></i> Exercise Category *
                    </label>
                    <select id="exercise_category" name="exercise_category" class="exercise-select" required>
                        <option value="">Select a category...</option>
                        <?php foreach ($grouped_exercises as $category => $exercises): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" 
                                    data-icon="<?php echo $category_icons[$category] ?? 'fas fa-dumbbell'; ?>"
                                    <?php echo (isset($_POST['exercise_category']) && $_POST['exercise_category'] === $category) ? 'selected' : ''; ?>>
                                <i class="<?php echo $category_icons[$category] ?? 'fas fa-dumbbell'; ?>"></i>
                                <?php echo htmlspecialchars($category); ?> (<?php echo $category_counts[$category]; ?> exercises)
                            </option>
                        <?php endforeach; ?>
                        <option value="custom" <?php echo (isset($_POST['exercise_category']) && $_POST['exercise_category'] === 'custom') ? 'selected' : ''; ?>>
                            <i class="fas fa-plus-circle"></i> Custom / Other
                        </option>
                    </select>
                    <small class="text-muted">Select an exercise category first</small>
                </div>

                <div class="form-group">
                    <label for="exercise_type">
                        <i class="fas fa-dumbbell"></i> Exercise *
                    </label>
                    <div class="exercise-dropdown">
                        <select id="exercise_type" name="exercise_ref_id" class="exercise-select" required disabled>
                            <option value="">Select a category first...</option>
                        </select>
                        
                        <div id="custom-input" class="custom-input <?php echo (isset($_POST['exercise_category']) && $_POST['exercise_category'] === 'custom') ? 'show' : ''; ?>">
                            <input type="text" 
                                   id="custom_exercise" 
                                   name="custom_exercise" 
                                   placeholder="Enter your custom exercise name..."
                                   value="<?php echo isset($_POST['custom_exercise']) ? htmlspecialchars($_POST['custom_exercise']) : ''; ?>">
                        </div>
                    </div>
                    <small class="text-muted">Choose a specific exercise from the selected category</small>
                    
                    <div id="met-info" class="auto-calc-indicator">
                        <i class="fas fa-info-circle"></i>
                        <span id="met-details"></span>
                    </div>
                </div>

                <!-- Weight and Duration Input Section -->
                <div class="weight-input-section">
                    <div class="weight-input-header">
                        <i class="fas fa-weight"></i>
                        Weight & Duration for Calorie Calculation
                    </div>
                    
                    <div class="weight-duration-grid">
                        <div class="form-group">
                            <label for="user_weight">
                                <i class="fas fa-balance-scale"></i> Your Weight (kg) *
                            </label>
                            <input type="number" 
                                   id="user_weight" 
                                   name="user_weight" 
                                   value="<?php echo isset($_POST['user_weight']) ? htmlspecialchars($_POST['user_weight']) : $user_weight; ?>"
                                   min="30" 
                                   max="500"
                                   step="0.1"
                                   placeholder="e.g., 70.5"
                                   required>
                            <small class="text-muted">Enter your current weight (30-500 kg)</small>
                        </div>

                        <div class="form-group">
                            <label for="duration_minutes">
                                <i class="fas fa-stopwatch"></i> Duration (Minutes) *
                            </label>
                            <div class="duration-with-button">
                                <input type="number" 
                                       id="duration_minutes" 
                                       name="duration_minutes" 
                                       value="<?php echo isset($_POST['duration_minutes']) ? htmlspecialchars($_POST['duration_minutes']) : ''; ?>"
                                       min="1" 
                                       max="600"
                                       placeholder="e.g., 30"
                                       required
                                       class="duration-input">
                                <button type="button" id="auto-calculate-btn" class="auto-calculate-btn" disabled>
                                    <i class="fas fa-calculator"></i>
                                    Auto Calculate
                                </button>
                            </div>
                            <small class="text-muted">How long did you exercise? (1-600 minutes)</small>
                        </div>
                    </div>
                </div>

                <div class="time-date-grid">
                    <div class="form-group">
                        <label for="exercise_date">
                            <i class="fas fa-calendar"></i> Exercise Date *
                        </label>
                        <input type="date" 
                               id="exercise_date" 
                               name="exercise_date" 
                               value="<?php echo isset($_POST['exercise_date']) ? htmlspecialchars($_POST['exercise_date']) : $default_date; ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               required>
                        <small class="text-muted">When did you do this exercise?</small>
                    </div>

                    <div class="form-group">
                        <label for="exercise_time">
                            <i class="fas fa-clock"></i> Exercise Time *
                        </label>
                        <input type="time" 
                               id="exercise_time" 
                               name="exercise_time" 
                               value="<?php echo isset($_POST['exercise_time']) ? htmlspecialchars($_POST['exercise_time']) : $default_time; ?>"
                               required>
                        <small class="text-muted">What time did you start?</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="calories_burned">
                        <i class="fas fa-fire"></i> Calories Burned *
                    </label>
                    <input type="number" 
                           id="calories_burned" 
                           name="calories_burned" 
                           value="<?php echo isset($_POST['calories_burned']) ? htmlspecialchars($_POST['calories_burned']) : ''; ?>"
                           min="1" 
                           placeholder="e.g., 250"
                           required>
                    <small class="text-muted">Calories will be calculated automatically for selected exercises, or enter manually</small>
                    
                    <div id="calc-details" class="calculation-details">
                        <strong>Calculation:</strong> <span id="calc-formula"></span>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="notes-section">
                    <div class="notes-header">
                        <i class="fas fa-sticky-note"></i>
                        Exercise Notes (Optional)
                    </div>
                    <div class="form-group">
                        <label for="notes">
                            <i class="fas fa-comment"></i> Notes
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="notes-textarea"
                                  placeholder="Add any notes about your exercise session... (e.g., how you felt, intensity level, location, equipment used, etc.)"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        <small class="text-muted">Optional: Add any additional details about your workout session</small>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Exercise
                    </button>
                    <a href="index.php" class="btn" style="background-color: #6c757d; color: white;">
                        <i class="fas fa-arrow-left"></i> Back to Exercises
                    </a>
                </div>
            </form>
        </div>

        <!-- Exercise Categories Info -->
        <div class="container">
            <h2><i class="fas fa-info-circle"></i> Exercise Categories</h2>
            <div class="feature-grid">
                <?php foreach ($category_icons as $category => $icon): ?>
                    <?php if (isset($grouped_exercises[$category])): ?>
                        <div class="feature-card">
                            <div class="feature-icon"><i class="<?php echo $icon; ?>"></i></div>
                            <h4><?php echo htmlspecialchars($category); ?></h4>
                            <p><?php echo $category_counts[$category]; ?> different exercises </p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Exercise data for JavaScript
        const exerciseData = <?php echo json_encode($exercise_options); ?>;
        const groupedExercises = <?php echo json_encode($grouped_exercises); ?>;
        const defaultWeight = <?php echo $user_weight; ?>;
        
        // Create exercise lookup by ID for quick access
        const exerciseLookup = {};
        exerciseData.forEach(exercise => {
            exerciseLookup[exercise.id] = exercise;
        });
        
        // Calculate calories using MET formula
        function calculateCalories(metValue, durationMinutes, weightKg) {
            // Formula: Duration (min) × (MET × 3.5 × weight in kg) / 200
            return Math.round(durationMinutes * (metValue * 3.5 * weightKg) / 200);
        }
        
        // Update calories when exercise, duration, or weight changes
        function updateCaloriesCalculation() {
            const exerciseSelect = document.getElementById('exercise_type');
            const durationInput = document.getElementById('duration_minutes');
            const weightInput = document.getElementById('user_weight');
            const caloriesInput = document.getElementById('calories_burned');
            const metInfo = document.getElementById('met-info');
            const metDetails = document.getElementById('met-details');
            const calcDetails = document.getElementById('calc-details');
            const calcFormula = document.getElementById('calc-formula');
            const autoCalcBtn = document.getElementById('auto-calculate-btn');
            
            const selectedExerciseId = exerciseSelect.value;
            const duration = parseInt(durationInput.value) || 0;
            const weight = parseFloat(weightInput.value) || defaultWeight;
            
            // Enable/disable auto calculate button
            if (selectedExerciseId && duration > 0 && weight > 0 && exerciseLookup[selectedExerciseId]) {
                autoCalcBtn.disabled = false;
                
                const exercise = exerciseLookup[selectedExerciseId];
                const metValue = parseFloat(exercise.met_value);
                
                // Show MET information
                metDetails.innerHTML = `MET Value: ${metValue} - Ready for auto calculation with ${weight} kg`;
                metInfo.classList.add('show');
                
            } else {
                autoCalcBtn.disabled = true;
                metInfo.classList.remove('show');
                calcDetails.classList.remove('show');
            }
        }
        
        // Auto calculate calories
        function autoCalculateCalories() {
            const exerciseSelect = document.getElementById('exercise_type');
            const durationInput = document.getElementById('duration_minutes');
            const weightInput = document.getElementById('user_weight');
            const caloriesInput = document.getElementById('calories_burned');
            const calcDetails = document.getElementById('calc-details');
            const calcFormula = document.getElementById('calc-formula');
            
            const selectedExerciseId = exerciseSelect.value;
            const duration = parseInt(durationInput.value) || 0;
            const weight = parseFloat(weightInput.value) || defaultWeight;
            
            if (selectedExerciseId && duration > 0 && weight > 0 && exerciseLookup[selectedExerciseId]) {
                const exercise = exerciseLookup[selectedExerciseId];
                const metValue = parseFloat(exercise.met_value);
                const calculatedCalories = calculateCalories(metValue, duration, weight);
                
                // Update calories input with animation
                caloriesInput.style.background = '#d4edda';
                caloriesInput.value = calculatedCalories;
                
                // Show calculation details
                calcFormula.innerHTML = `${duration} min × (${metValue} × 3.5 × ${weight} kg) ÷ 200 = ${calculatedCalories} calories`;
                calcDetails.classList.add('show');
                
                // Reset background after animation
                setTimeout(() => {
                    caloriesInput.style.background = '';
                }, 1000);
            }
        }
        
        // Handle category selection
        document.getElementById('exercise_category').addEventListener('change', function() {
            const exerciseSelect = document.getElementById('exercise_type');
            const customInput = document.getElementById('custom-input');
            const customField = document.getElementById('custom_exercise');
            
            // Clear exercise dropdown
            exerciseSelect.innerHTML = '<option value="">Select an exercise...</option>';
            
            if (this.value === 'custom') {
                // Show custom input
                exerciseSelect.disabled = true;
                customInput.classList.add('show');
                customField.required = true;
                customField.focus();
                
                // Hide MET info for custom exercises
                document.getElementById('met-info').classList.remove('show');
                document.getElementById('calc-details').classList.remove('show');
                document.getElementById('auto-calculate-btn').disabled = true;
                
            } else if (this.value === '') {
                // No category selected
                exerciseSelect.disabled = true;
                exerciseSelect.innerHTML = '<option value="">Select a category first...</option>';
                customInput.classList.remove('show');
                customField.required = false;
                customField.value = '';
                
            } else {
                // Category selected - populate exercises
                exerciseSelect.disabled = false;
                customInput.classList.remove('show');
                customField.required = false;
                customField.value = '';
                
                // Populate exercises for selected category
                if (groupedExercises[this.value]) {
                    groupedExercises[this.value].forEach(function(exercise) {
                        const option = document.createElement('option');
                        option.value = exercise.id;
                        option.textContent = `${exercise.exercise_name} (MET: ${exercise.met_value})`;
                        exerciseSelect.appendChild(option);
                    });
                }
            }
            
            // Update calculation state
            updateCaloriesCalculation();
        });
        
        // Event listeners
        document.getElementById('exercise_type').addEventListener('change', updateCaloriesCalculation);
        document.getElementById('duration_minutes').addEventListener('input', updateCaloriesCalculation);
        document.getElementById('user_weight').addEventListener('input', updateCaloriesCalculation);
        document.getElementById('auto-calculate-btn').addEventListener('click', autoCalculateCalories);
        
        // Validate date and time combination (Malaysia timezone)
        function validateDateTime() {
            const dateInput = document.getElementById('exercise_date');
            const timeInput = document.getElementById('exercise_time');
            
            if (dateInput.value && timeInput.value) {
                const selectedDateTime = new Date(dateInput.value + 'T' + timeInput.value);
                
                // Get current Malaysia time (UTC+8)
                const now = new Date();
                const malaysiaOffset = 8 * 60; // 8 hours in minutes
                const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
                const malaysiaTime = new Date(utc + (malaysiaOffset * 60000));
                
                if (selectedDateTime > malaysiaTime) {
                    alert('Exercise date and time cannot be in the future (Malaysia time)!');
                    // Reset to current Malaysia time if it's today
                    const todayMalaysia = malaysiaTime.toISOString().split('T')[0];
                    if (dateInput.value === todayMalaysia) {
                        const currentHour = malaysiaTime.getHours().toString().padStart(2, '0');
                        const currentMin = malaysiaTime.getMinutes().toString().padStart(2, '0');
                        timeInput.value = currentHour + ':' + currentMin;
                    }
                }
            }
        }
        
        document.getElementById('exercise_date').addEventListener('change', validateDateTime);
        document.getElementById('exercise_time').addEventListener('change', validateDateTime);
        
        // Initialize on page load
        window.addEventListener('load', function() {
            const categorySelect = document.getElementById('exercise_category');
            const exerciseTypeSelect = document.getElementById('exercise_type');
            
            // Restore form state on page load if there were validation errors
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
                
                // Small delay to ensure category change is processed
                setTimeout(function() {
                    if (exerciseTypeSelect.value) {
                        exerciseTypeSelect.dispatchEvent(new Event('change'));
                    }
                    // Update calculation state after form restoration
                    updateCaloriesCalculation();
                }, 100);
            }
        });
    </script>
</body>
</html>