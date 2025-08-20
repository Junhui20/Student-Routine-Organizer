<?php
require_once '../includes/SessionManager.php';
require_once '../includes/ErrorHandler.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    ErrorHandler::logApplicationError('Session initialization failed in add_habit.php', 'SESSION', ['error' => $e->getMessage()]);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect if not logged in
if (!SessionManager::isAuthenticated()) {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';

    try {
        $habit_name = trim($_POST['habit_name']);
        $description = trim($_POST['description']);
        $frequency = $_POST['frequency'];
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        // Validation
        if (empty($habit_name) || empty($frequency) || empty($start_date)) {
            $error = "Habit name, frequency, and start date are required.";
        } elseif (strlen($habit_name) > 255) {
            $error = "Habit name must be 255 characters or less.";
        } else {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                INSERT INTO habits (user_id, habit_name, description, frequency, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([SessionManager::getCurrentUserId(), $habit_name, $description, $frequency, $start_date, $end_date])) {
                $success = "Habit added successfully!";
                header("Location: index.php?added=1");
                exit();
            } else {
                $error = "Failed to add habit. Please try again.";
                ErrorHandler::logDatabaseError(
                    'Failed to insert habit',
                    "INSERT INTO habits (user_id, habit_name, description, frequency, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)",
                    [SessionManager::getCurrentUserId(), $habit_name, $description, $frequency, $start_date, $end_date]
                );
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        ErrorHandler::logDatabaseError('Database error in add_habit.php', '', ['error' => $e->getMessage()]);
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        ErrorHandler::logApplicationError('General error in add_habit.php', 'APPLICATION', ['error' => $e->getMessage()]);
    }
}

$default_date = date('Y-m-d');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-plus"></i> Add New Habit</h2>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Habits
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="habit_name"><i class="fas fa-list"></i> Habit Name</label>
            <input type="text" id="habit_name" name="habit_name"
                   value="<?= isset($_POST['habit_name']) ? htmlspecialchars($_POST['habit_name']) : ''; ?>"
                   placeholder="e.g., Drink 8 glasses of water"
                   maxlength="255" required>
        </div>

        <div class="form-group">
            <label for="description"><i class="fas fa-align-left"></i> Description</label>
            <textarea id="description" name="description" placeholder="Describe your habit..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="frequency"><i class="fas fa-clock"></i> Frequency</label>
            <select id="frequency" name="frequency" required>
                <option value="">Select frequency...</option>
                <option value="daily" <?= (isset($_POST['frequency']) && $_POST['frequency'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?= (isset($_POST['frequency']) && $_POST['frequency'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                <option value="custom" <?= (isset($_POST['frequency']) && $_POST['frequency'] == 'custom') ? 'selected' : ''; ?>>Custom</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_date"><i class="fas fa-calendar-plus"></i> Start Date</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= isset($_POST['start_date']) ? $_POST['start_date'] : $default_date; ?>" required>
        </div>

        <div class="form-group">
            <label for="end_date"><i class="fas fa-calendar-minus"></i> End Date (optional)</label>
            <input type="date" id="end_date" name="end_date"
                   value="<?= isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save Habit
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

</main>
</body>
</html>
