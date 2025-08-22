<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

$error = '';
$default_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $habit_name = trim($_POST['habit_name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $frequency = $_POST['frequency'] ?? '';
  $start_date = $_POST['start_date'] ?? '';
  $end_date = $_POST['end_date'] ?? '';
  $category = trim($_POST['category'] ?? 'Uncategorized');
  $habit_type = $_POST['habit_type'] ?? 'normal';
  $timer_duration = isset($_POST['timer_duration']) && $_POST['timer_duration'] !== '' ? (int) $_POST['timer_duration'] : null;

  // Validation
  if ($habit_name === '' || $frequency === '' || $start_date === '' || $end_date === '') {
    $error = "Habit name, frequency, start date, and end date are required.";
  } elseif ($start_date < date('Y-m-d')) {
    $error = "Start date cannot be earlier than today.";
  } elseif ($end_date < $start_date) {
    $error = "End date cannot be earlier than start date.";
  } elseif (mb_strlen($habit_name) > 255) {
    $error = "Habit name must be 255 characters or less.";
  } elseif (!in_array($frequency, ['daily', 'weekly', 'custom'], true) || !in_array($habit_type, ['normal', 'timer'], true)) {
    $error = "Invalid input.";
  } else {
    $c = db();
    $sql = "INSERT INTO habits (user_id, habit_name, description, frequency, start_date, end_date, category, habit_type, timer_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $c->prepare($sql);
    $uid = current_user_id();
    $stmt->bind_param("isssssssi", $uid, $habit_name, $description, $frequency, $start_date, $end_date, $category, $habit_type, $timer_duration);
    if ($stmt->execute()) {
      header("Location: index.php?added=1");
      exit();
    } else {
      $error = "Failed to add habit. Please try again.";
    }
    $stmt->close();

    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "INSERT INTO habits 
        (user_id, habit_name, description, frequency, start_date, end_date, category, habit_type, timer_duration, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $c->prepare($sql);
    $uid = current_user_id();
    $stmt->bind_param(
      "issssssiii",
      $uid,
      $habit_name,
      $description,
      $frequency,
      $start_date,
      $end_date,
      $category,
      $habit_type,
      $timer_duration,
      $is_active
    );

  }
}

include '../includes/header.php';
?>

<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
    <h2><i class="fas fa-plus"></i> Add New Habit</h2>
    <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>Category</label>
      <select name="category" required>
        <?php
        $cats = ['Physical Health', 'Mental Health', 'Spiritual Health', 'Social Health', 'Technology', 'Work', 'Finance', 'Home', 'Sleep', 'Creativity', 'Uncategorized'];
        $sel = $_POST['category'] ?? 'Uncategorized';
        foreach ($cats as $c) {
          $s = $sel === $c ? 'selected' : '';
          echo "<option value=\"" . htmlspecialchars($c) . "\" $s>" . htmlspecialchars($c) . "</option>";
        }
        ?>
      </select>
    </div>

    <div class="form-group">
      <label for="habit_name"><i class="fas fa-list"></i> Habit Name</label>
      <input type="text" id="habit_name" name="habit_name" value="<?= htmlspecialchars($_POST['habit_name'] ?? '') ?>"
        placeholder="e.g., Drink 8 glasses of water" maxlength="255" required>
    </div>

    <div class="form-group">
      <label for="description"><i class="fas fa-align-left"></i> Description</label>
      <textarea id="description" name="description"
        placeholder="Describe your habit..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Frequency</label>
      <select name="frequency" required>
        <option value="">Select</option>
        <option value="daily" <?= (($_POST['frequency'] ?? '') === 'daily') ? 'selected' : ''; ?>>Daily</option>
        <option value="weekly" <?= (($_POST['frequency'] ?? '') === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
        <option value="custom" <?= (($_POST['frequency'] ?? '') === 'custom') ? 'selected' : ''; ?>>Custom</option>
      </select>
    </div>

    <div class="form-group">
      <label><strong>Habit Type</strong></label>
      <select name="habit_type" id="habit_type" onchange="toggleTimerInput()" class="form-control"
        style="max-width:250px;">
        <option value="normal" <?= (($_POST['habit_type'] ?? '') === 'normal') ? 'selected' : ''; ?>>Normal</option>
        <option value="timer" <?= (($_POST['habit_type'] ?? '') === 'timer') ? 'selected' : ''; ?>>Timer (track minutes)
        </option>
      </select>
    </div>

    <div id="timer_block"
      style="display: <?= (($_POST['habit_type'] ?? '') === 'timer') ? 'block' : 'none' ?>;margin-top:1rem;">
      <div class="form-group" style="padding:1rem;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;">
        <label style="font-weight:bold;display:block;margin-bottom:0.5rem;">
          Default Timer Duration <small style="color:#666;">(in minutes)</small>
        </label>
        <div style="display:flex;align-items:center;gap:0.5rem;">
          <input type="number" name="timer_duration" min="1" placeholder="e.g., 15"
            value="<?= htmlspecialchars($_POST['timer_duration'] ?? '') ?>" class="form-control"
            style="width:100px;text-align:center;">
          <span style="color:#555;">minutes</span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Start date</label>
      <input type="date" name="start_date" required
        value="<?= htmlspecialchars($_POST['start_date'] ?? $default_date) ?>" min="<?= date('Y-m-d') ?>">
    </div>

    <div class="form-group">
      <label>End date</label>
      <input type="date" name="end_date" required value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Active</label>
      <input type="checkbox" name="is_active" checked>
    </div>


    <div style="display:flex;gap:1rem;">
      <button class="btn btn-success" type="submit">Save Habit</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
  function toggleTimerInput() {
    const v = document.getElementById('habit_type').value;
    document.getElementById('timer_block').style.display = v === 'timer' ? 'block' : 'none';
  }

  // Get inputs
  const startInput = document.querySelector('input[name="start_date"]');
  const endInput = document.querySelector('input[name="end_date"]');

  // Get today's date in YYYY-MM-DD
  const today = new Date().toISOString().split('T')[0];

  // Set min for start date (cannot be before today)
  startInput.min = today;

  // On start date change
  startInput.addEventListener('change', () => {
    // Ensure start date is not before today
    if (startInput.value < today) {
      startInput.value = today;
    }

    // Set min for end date
    endInput.min = startInput.value;

    // Adjust end date if current value is before start date
    if (endInput.value < startInput.value) {
      endInput.value = startInput.value;
    }
  });

  // On page load, set end date min based on start date
  window.addEventListener('DOMContentLoaded', () => {
    endInput.min = startInput.value;
  });
</script>