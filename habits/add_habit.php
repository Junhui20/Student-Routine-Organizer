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
    $end_date = !empty($_POST['end_date'] ?? '') ? $_POST['end_date'] : null;
    $category = trim($_POST['category'] ?? 'Uncategorized');
    $habit_type = $_POST['habit_type'] ?? 'normal';
    $timer_duration = isset($_POST['timer_duration']) && $_POST['timer_duration'] !== '' ? (int)$_POST['timer_duration'] : null;

    // simple validation
    if ($habit_name === '' || $frequency === '' || $start_date === '') {
        $error = "Habit name, frequency and start date are required.";
    } elseif (mb_strlen($habit_name) > 255) {
        $error = "Habit name must be 255 characters or less.";
    } elseif (!in_array($frequency, ['daily','weekly','custom'], true) || !in_array($habit_type, ['normal','timer'], true)) {
        $error = "Invalid input.";
    } else {
        $c = db();
        $sql = "INSERT INTO habits (user_id, habit_name, description, frequency, start_date, end_date, category, habit_type, timer_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $c->prepare($sql);
        $uid = current_user_id();
        // bind: i s s s s s s s i
        $stmt->bind_param("isssssssi", $uid, $habit_name, $description, $frequency, $start_date, $end_date, $category, $habit_type, $timer_duration);
        if ($stmt->execute()) {
            header("Location: index.php?added=1");
            exit();
        } else {
            $error = "Failed to add habit. Please try again.";
        }
        $stmt->close();
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
        $cats = ['Physical Health','Mental Health','Spiritual Health','Social Health','Technology','Work','Finance','Home','Sleep','Creativity','Uncategorized'];
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
            <input type="text" id="habit_name" name="habit_name"
                value="<?= htmlspecialchars($_POST['habit_name'] ?? '') ?>"
                placeholder="e.g., Drink 8 glasses of water" maxlength="255" required>
    </div>

    <div class="form-group">
            <label for="description"><i class="fas fa-align-left"></i> Description</label>
            <textarea id="description" name="description" placeholder="Describe your habit..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Frequency</label>
      <select name="frequency" required>
        <option value="">Select</option>
        <option value="daily" <?= (($_POST['frequency'] ?? '')==='daily')?'selected':''; ?>>Daily</option>
        <option value="weekly" <?= (($_POST['frequency'] ?? '')==='weekly')?'selected':''; ?>>Weekly</option>
        <option value="custom" <?= (($_POST['frequency'] ?? '')==='custom')?'selected':''; ?>>Custom</option>
      </select>
    </div>

<div class="form-group">
  <label><strong>Habit Type</strong></label>
  <select name="habit_type" id="habit_type" onchange="toggleTimerInput()" class="form-control" style="max-width:250px;">
    <option value="normal" <?= (($_POST['habit_type'] ?? '')==='normal')?'selected':''; ?>>Normal</option>
    <option value="timer" <?= (($_POST['habit_type'] ?? '')==='timer')?'selected':''; ?>>Timer (track minutes)</option>
  </select>
</div>

<!-- Timer duration block -->
<div id="timer_block" style="display: <?= (($_POST['habit_type'] ?? '')==='timer') ? 'block' : 'none' ?>;margin-top:1rem;">
  <div class="form-group" style="padding:1rem;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;">
    <label style="font-weight:bold;display:block;margin-bottom:0.5rem;">
      Default Timer Duration <small style="color:#666;">(in minutes)</small>
    </label>
    <div style="display:flex;align-items:center;gap:0.5rem;">
      <input type="number" 
             name="timer_duration" 
             min="1" 
             placeholder="e.g., 15" 
             value="<?= htmlspecialchars($_POST['timer_duration'] ?? '') ?>" 
             class="form-control" 
             style="width:100px;text-align:center;">
      <span style="color:#555;">minutes</span>
    </div>
    <small style="color:#888;display:block;margin-top:0.5rem;">
      This is the default countdown length when starting this habit's timer.
    </small>
  </div>
</div>


    <div class="form-group">
      <label>Start date</label>
      <input type="date" name="start_date" required value="<?= htmlspecialchars($_POST['start_date'] ?? $default_date) ?>">
    </div>

    <div class="form-group">
      <label>End date (optional)</label>
      <input type="date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
    </div>

    <div style="display:flex;gap:1rem;">
      <button class="btn btn-success" type="submit">Save Habit</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
function toggleTimerInput(){
  const v = document.getElementById('habit_type').value;
  document.getElementById('timer_block').style.display = v === 'timer' ? 'block' : 'none';
}
</script>
