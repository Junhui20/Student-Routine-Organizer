<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

$habit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($habit_id <= 0 || !habit_belongs_to_user($habit_id)) {
  header("Location: index.php");
  exit();
}

$c = db();
$uid = current_user_id();
$error = '';

$stmt = $c->prepare("SELECT habit_id, habit_name, description, frequency, start_date, end_date, category, habit_type, timer_duration, is_active FROM habits WHERE habit_id = ? AND user_id = ?");
$stmt->bind_param("ii", $habit_id, $uid);
$stmt->execute();
$res = $stmt->get_result();
$habit = $res->fetch_assoc();
$stmt->close();

if (!$habit) {
  header("Location: index.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $habit_name = trim($_POST['habit_name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $frequency = $_POST['frequency'] ?? '';
  $start_date = $_POST['start_date'] ?? '';
  $end_date = !empty($_POST['end_date'] ?? '') ? $_POST['end_date'] : null;
  $category = trim($_POST['category'] ?? 'Uncategorized');
  $habit_type = $_POST['habit_type'] ?? 'normal';
  $timer_duration = isset($_POST['timer_duration']) && $_POST['timer_duration'] !== '' ? (int) $_POST['timer_duration'] : null;
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  if ($habit_name === '' || $frequency === '' || $start_date === '') {
    $error = "Please fill required fields.";
  } else {
    $sql = "UPDATE habits SET habit_name=?, description=?, frequency=?, start_date=?, end_date=?, category=?, habit_type=?, timer_duration=?, is_active=? WHERE habit_id=? AND user_id=?";
    $stmt = $c->prepare($sql);
    // types: s s s s s s s i i i i => "sssssssiiii" (7 strings, 4 ints)
    $stmt->bind_param("sssssssiiii", $habit_name, $description, $frequency, $start_date, $end_date, $category, $habit_type, $timer_duration, $is_active, $habit_id, $uid);
    if ($stmt->execute()) {
      header("Location: index.php?updated=1");
      exit();
    } else {
      $error = "Failed to update.";
    }
    $stmt->close();
  }
}

include '../includes/header.php';
?>

<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
    <h2><i class="fas fa-edit"></i> Edit Habit</h2>
    <a href="index.php" class="btn btn-primary">Back</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" action="">
    <!-- same inputs as add_habit.php but with defaults from $habit -->
    <div class="form-group">
      <label>Category</label>
      <select name="category">
        <?php
        $cats = ['Physical Health', 'Mental Health', 'Spiritual Health', 'Social Health', 'Technology', 'Work', 'Finance', 'Home', 'Sleep', 'Creativity', 'Uncategorized'];
        $sel = $_POST['category'] ?? $habit['category'];
        foreach ($cats as $c) {
          $s = $sel === $c ? 'selected' : '';
          echo "<option value=\"" . htmlspecialchars($c) . "\" $s>" . htmlspecialchars($c) . "</option>";
        }
        ?>
      </select>
    </div>

    <div class="form-group">
      <label>Habit Name</label>
      <input type="text" name="habit_name" required
        value="<?= htmlspecialchars($_POST['habit_name'] ?? $habit['habit_name']) ?>">
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? $habit['description']) ?></textarea>
    </div>


    <div class="form-group">
      <label>Frequency</label>
      <select name="frequency">
        <option value="daily" <?= (($_POST['frequency'] ?? $habit['frequency']) === 'daily') ? 'selected' : ''; ?>>Daily
        </option>
        <option value="weekly" <?= (($_POST['frequency'] ?? $habit['frequency']) === 'weekly') ? 'selected' : ''; ?>>Weekly
        </option>
        <option value="custom" <?= (($_POST['frequency'] ?? $habit['frequency']) === 'custom') ? 'selected' : ''; ?>>Custom
        </option>
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

    <!-- Timer duration block -->
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
        <small style="color:#888;display:block;margin-top:0.5rem;">
          This is the default countdown length when starting this habit's timer.
        </small>
      </div>
    </div>


    <div class="form-group">
      <label>Start Date</label>
      <input type="date" name="start_date" required
        value="<?= htmlspecialchars($_POST['start_date'] ?? $habit['start_date']) ?>">
    </div>

    <div class="form-group">
      <label>End Date</label>
      <input type="date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? $habit['end_date']) ?>">
    </div>

    <div class="form-group">
      <label>Active</label>
      <input type="checkbox" name="is_active" <?= ((int) ($_POST['is_active'] ?? $habit['is_active']) === 1) ? 'checked' : '' ?>>
    </div>

    <div style="display:flex;gap:1rem;">
      <button class="btn btn-success" type="submit">Update</button>
      <a class="btn btn-secondary" href="index.php">Cancel</a>
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

  // On start date change
  startInput.addEventListener('change', () => {
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