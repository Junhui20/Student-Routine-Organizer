<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set Malaysia timezone for all operations
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

$errors = [];
$exercise = null;

// Get exercise ID from URL
$exercise_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$exercise_id) {
    header('Location: index.php');
    exit();
}

// Get exercises from database for dropdown
$exercise_options = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM exercises ORDER BY category, exercise_name");
    $stmt->execute();
    $exercise_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the exercise record
    $stmt = $conn->prepare("SELECT * FROM exercise_tracker WHERE exercise_id = ? AND user_id = ?");
    $stmt->execute([$exercise_id, $_SESSION['user_id']]);
    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exercise) {
        header('Location: index.php');
        exit();
    }
    
} catch(Exception $e) {
    $errors[] = "Error loading exercise: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exercise_category = trim($_POST['exercise_category']);
    $exercise_type = trim($_POST['exercise_type']);
    $custom_exercise = trim($_POST['custom_exercise']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $calories_burned = (int)$_POST['calories_burned']; // Now required
    $exercise_date = $_POST['exercise_date'];
    $exercise_time = $_POST['exercise_time'];
    $notes = trim($_POST['notes']); // New notes field
    
    // Determine final exercise type
    if ($exercise_category === 'custom' && !empty($custom_exercise)) {
        $final_exercise_type = $custom_exercise;
    } else {
        $final_exercise_type = $exercise_type;
    }
    
    // Validation
    if (empty($exercise_category)) {
        $errors[] = "Exercise category is required.";
    }
    
    if (empty($final_exercise_type)) {
        $errors[] = "Exercise name is required.";
    }
    
    if ($exercise_category === 'custom' && empty($custom_exercise)) {
        $errors[] = "Custom exercise name is required.";
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
    
    // Notes validation (optional field with character limit)
    if (strlen($notes) > 500) {
        $errors[] = "Notes cannot exceed 500 characters.";
    }
    
    // Combine date and time for timestamp validation
    $exercise_datetime = $exercise_date . ' ' . $exercise_time;
    $selected_timestamp = strtotime($exercise_datetime);
    $current_timestamp = time();
    
    if ($selected_timestamp > $current_timestamp) {
        $errors[] = "Exercise date and time cannot be in the future (Malaysia time).";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE exercise_tracker 
                SET exercise_type = ?, duration_minutes = ?, calories_burned = ?, exercise_date = ?, exercise_time = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE exercise_id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([$final_exercise_type, $duration_minutes, $calories_burned, $exercise_date, $exercise_time, $notes, $exercise_id, $_SESSION['user_id']])) {
                header('Location: index.php?success=updated');
                exit();
            } else {
                $errors[] = "Failed to update exercise. Please try again.";
            }
        } catch(Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Group exercises by category
$grouped_exercises = [];
foreach ($exercise_options as $exercise_option) {
    $grouped_exercises[$exercise_option['category']][] = $exercise_option;
}

// Check if current exercise type is in predefined list
$current_exercise_type = $exercise['exercise_type'] ?? '';
$is_custom_exercise = true;
$current_category = '';
foreach ($exercise_options as $exercise_option) {
    if ($exercise_option['exercise_name'] === $current_exercise_type) {
        $is_custom_exercise = false;
        $current_category = $exercise_option['category'];
        break;
    }
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
    'Martial Arts' => 'fas fa-fist-raised',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exercise - Student Organizer</title>
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
        
        @media (max-width: 768px) {
            .time-date-grid, .duration-calories-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .timestamp-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 5px 5px 0;
        }
        
        .timestamp-info i {
            color: #856404;
            margin-right: 0.5rem;
        }
        
        .original-values {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .original-values h4 {
            margin-top: 0;
            color: #495057;
        }
        
        .original-value {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
            padding: 0.25rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .original-value:last-child {
            border-bottom: none;
        }
        
        .value-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .value-content {
            color: #495057;
        }
        
        .notes-textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
        }
        
        .notes-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .char-counter {
            text-align: right;
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .char-counter.warning {
            color: #ffc107;
        }
        
        .char-counter.danger {
            color: #dc3545;
        }
        
        .notes-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 0.75rem;
            white-space: pre-wrap;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/header.php'; ?>

    <main class="main-content">
        <div class="welcome-section">
            <h1><i class="fas fa-edit"></i> Edit Exercise</h1>
            <p>Update your workout record with detailed tracking and notes</p>
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

            <?php if ($exercise): ?>
                <div class="timestamp-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Editing Exercise Record:</strong> You can update any details of your workout including notes. Make sure to enter the correct calories burned for your exercise session.
                </div>

                <!-- Show Original Values -->
                <div class="original-values">
                    <h4><i class="fas fa-history"></i> Current Values</h4>
                    <div class="original-value">
                        <span class="value-label">Exercise Type:</span>
                        <span class="value-content"><?php echo htmlspecialchars($exercise['exercise_type']); ?></span>
                    </div>
                    <div class="original-value">
                        <span class="value-label">Duration:</span>
                        <span class="value-content"><?php echo $exercise['duration_minutes']; ?> minutes</span>
                    </div>
                    <div class="original-value">
                        <span class="value-label">Calories:</span>
                        <span class="value-content"><?php echo $exercise['calories_burned'] ? $exercise['calories_burned'] . ' calories' : 'Not recorded'; ?></span>
                    </div>
                    <div class="original-value">
                        <span class="value-label">Date:</span>
                        <span class="value-content"><?php echo date('M j, Y', strtotime($exercise['exercise_date'])); ?></span>
                    </div>
                    <?php if (isset($exercise['exercise_time'])): ?>
                    <div class="original-value">
                        <span class="value-label">Time:</span>
                        <span class="value-content"><?php echo date('g:i A', strtotime($exercise['exercise_time'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="original-value">
                        <span class="value-label">Notes:</span>
                        <span class="value-content">
                            <?php if (!empty($exercise['notes'])): ?>
                                <div class="notes-display"><?php echo htmlspecialchars($exercise['notes']); ?></div>
                            <?php else: ?>
                                <em>No notes recorded</em>
                            <?php endif; ?>
                        </span>
                    </div>
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
                                        <?php 
                                        $selected = '';
                                        if (isset($_POST['exercise_category'])) {
                                            $selected = ($_POST['exercise_category'] === $category) ? 'selected' : '';
                                        } else {
                                            if (!$is_custom_exercise && $current_category === $category) {
                                                $selected = 'selected';
                                            }
                                        }
                                        echo $selected;
                                        ?>>
                                    <?php echo htmlspecialchars($category); ?> (<?php echo count($exercises); ?> exercises)
                                </option>
                            <?php endforeach; ?>
                            <option value="custom" <?php 
                            $selected = '';
                            if (isset($_POST['exercise_category'])) {
                                $selected = ($_POST['exercise_category'] === 'custom') ? 'selected' : '';
                            } else {
                                if ($is_custom_exercise) {
                                    $selected = 'selected';
                                }
                            }
                            echo $selected;
                            ?>>
                                Custom / Other
                            </option>
                        </select>
                        <small class="text-muted">Select an exercise category first</small>
                    </div>

                    <div class="form-group">
                        <label for="exercise_type">
                            <i class="fas fa-dumbbell"></i> Exercise *
                        </label>
                        <div class="exercise-dropdown">
                            <select id="exercise_type" name="exercise_type" class="exercise-select" required disabled>
                                <option value="">Select a category first...</option>
                            </select>
                            
                            <div id="custom-input" class="custom-input <?php echo ($is_custom_exercise || (isset($_POST['exercise_category']) && $_POST['exercise_category'] === 'custom')) ? 'show' : ''; ?>">
                                <input type="text" 
                                       id="custom_exercise" 
                                       name="custom_exercise" 
                                       placeholder="Enter your custom exercise name..."
                                       value="<?php 
                                       if (isset($_POST['custom_exercise'])) {
                                           echo htmlspecialchars($_POST['custom_exercise']);
                                       } elseif ($is_custom_exercise) {
                                           echo htmlspecialchars($current_exercise_type);
                                       }
                                       ?>">
                            </div>
                        </div>
                        <small class="text-muted">Choose from our exercise database or enter a custom exercise</small>
                    </div>

                    <div class="time-date-grid">
                        <div class="form-group">
                            <label for="exercise_date">
                                <i class="fas fa-calendar"></i> Exercise Date *
                            </label>
                            <input type="date" 
                                   id="exercise_date" 
                                   name="exercise_date" 
                                   value="<?php echo isset($_POST['exercise_date']) ? htmlspecialchars($_POST['exercise_date']) : $exercise['exercise_date']; ?>"
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
                                   value="<?php 
                                   if (isset($_POST['exercise_time'])) {
                                       echo htmlspecialchars($_POST['exercise_time']);
                                   } elseif (isset($exercise['exercise_time'])) {
                                       echo $exercise['exercise_time'];
                                   } else {
                                       echo '12:00'; // Default if no time recorded
                                   }
                                   ?>"
                                   required>
                            <small class="text-muted">What time did you start?</small>
                        </div>
                    </div>

                    <div class="duration-calories-grid">
                        <div class="form-group">
                            <label for="duration_minutes">
                                <i class="fas fa-stopwatch"></i> Duration (Minutes) *
                            </label>
                            <input type="number" 
                                   id="duration_minutes" 
                                   name="duration_minutes" 
                                   value="<?php echo isset($_POST['duration_minutes']) ? htmlspecialchars($_POST['duration_minutes']) : $exercise['duration_minutes']; ?>"
                                   min="1" 
                                   max="600"
                                   placeholder="e.g., 30"
                                   required>
                            <small class="text-muted">How long did you exercise? (1-600 minutes)</small>
                        </div>

                        <div class="form-group">
                            <label for="calories_burned">
                                <i class="fas fa-fire"></i> Calories Burned *
                            </label>
                            <input type="number" 
                                   id="calories_burned" 
                                   name="calories_burned" 
                                   value="<?php echo isset($_POST['calories_burned']) ? htmlspecialchars($_POST['calories_burned']) : ($exercise['calories_burned'] ?? ''); ?>"
                                   min="1" 
                                   placeholder="e.g., 250"
                                   required>
                            <small class="text-muted">How many calories did you burn?</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">
                            <i class="fas fa-sticky-note"></i> Exercise Notes
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="notes-textarea"
                                  placeholder="Add notes about your workout... (e.g., how you felt, difficulty level, achievements, etc.)"
                                  maxlength="500"><?php 
                        if (isset($_POST['notes'])) {
                            echo htmlspecialchars($_POST['notes']);
                        } elseif (!empty($exercise['notes'])) {
                            echo htmlspecialchars($exercise['notes']);
                        }
                        ?></textarea>
                        <div id="char-counter" class="char-counter">
                            <span id="char-count">0</span>/500 characters
                        </div>
                        <small class="text-muted">Optional: Add any notes about your workout experience, achievements, or how you felt</small>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Exercise
                        </button>
                        <a href="index.php" class="btn" style="background-color: #6c757d; color: white;">
                            <i class="fas fa-arrow-left"></i> Back to Exercises
                        </a>
                    </div>
                </form>

                <!-- Exercise History -->
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
                    <h3><i class="fas fa-history"></i> Exercise Record Timeline</h3>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p><strong><i class="fas fa-plus-circle" style="color: #28a745;"></i> Originally Added:</strong></p>
                                <p style="margin-left: 1.5rem; color: #6c757d;">
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($exercise['created_at'])); ?>
                                </p>
                            </div>
                            <?php if ($exercise['updated_at'] != $exercise['created_at']): ?>
                            <div>
                                <p><strong><i class="fas fa-edit" style="color: #ffc107;"></i> Last Updated:</strong></p>
                                <p style="margin-left: 1.5rem; color: #6c757d;">
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($exercise['updated_at'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($exercise['exercise_time'])): ?>
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                            <p><strong><i class="fas fa-stopwatch" style="color: #007bff;"></i> Exercise Session:</strong></p>
                            <p style="margin-left: 1.5rem; color: #6c757d;">
                                <?php 
                                echo date('l, F j, Y', strtotime($exercise['exercise_date']));
                                if (isset($exercise['exercise_time'])) {
                                    echo ' at ' . date('g:i A', strtotime($exercise['exercise_time']));
                                }
                                ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Exercise data for JavaScript
        const exerciseData = <?php echo json_encode($exercise_options); ?>;
        const groupedExercises = <?php echo json_encode($grouped_exercises); ?>;
        
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
                if (!customInput.classList.contains('show')) {
                    customField.value = '';
                }
                
                // Populate exercises for selected category
                if (groupedExercises[this.value]) {
                    groupedExercises[this.value].forEach(function(exercise) {
                        const option = document.createElement('option');
                        option.value = exercise.exercise_name;
                        option.textContent = exercise.exercise_name;
                        
                        // Pre-select if this is the current exercise
                        <?php if (!$is_custom_exercise): ?>
                        if (exercise.exercise_name === '<?php echo addslashes($current_exercise_type); ?>') {
                            option.selected = true;
                        }
                        <?php endif; ?>
                        
                        exerciseSelect.appendChild(option);
                    });
                }
            }
        });
        
        // Character counter for notes
        const notesTextarea = document.getElementById('notes');
        const charCounter = document.getElementById('char-counter');
        const charCount = document.getElementById('char-count');
        
        function updateCharCounter() {
            const length = notesTextarea.value.length;
            charCount.textContent = length;
            
            // Update counter styling based on character count
            charCounter.classList.remove('warning', 'danger');
            if (length > 400) {
                charCounter.classList.add('danger');
            } else if (length > 300) {
                charCounter.classList.add('warning');
            }
        }
        
        notesTextarea.addEventListener('input', updateCharCounter);
        
        // Initialize character counter
        updateCharCounter();
        
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
            const customField = document.getElementById('custom_exercise');
            
            if (categorySelect.value === 'custom') {
                customField.required = true;
            }
            
            // Trigger change event to set up initial state
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>