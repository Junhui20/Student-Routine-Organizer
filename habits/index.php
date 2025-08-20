<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

$c = db();
$uid = current_user_id();

$sql = "
  SELECT h.*,
       EXISTS(
         SELECT 1 FROM habit_logs l
         WHERE l.habit_id = h.habit_id
           AND l.log_date = CURDATE()
           AND l.status = 'done'
       ) AS done_today,
       -- streak count (continuous days ending today)
       (
         SELECT COUNT(*)
         FROM habit_logs l
         WHERE l.habit_id = h.habit_id
           AND l.status='done'
           AND l.log_date <= CURDATE()
           AND l.log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       ) AS streak_count,
       -- 7-day completion rate
       (
         SELECT ROUND(SUM(CASE WHEN status='done' THEN 1 ELSE 0 END)/7*100)
         FROM habit_logs l4
         WHERE l4.habit_id = h.habit_id
           AND l4.log_date >= CURDATE() - INTERVAL 6 DAY
       ) AS week_progress
FROM habits h
WHERE h.user_id = ?
ORDER BY h.category, h.created_at DESC

";

$stmt = $c->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$habits = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/header.php';
?>

<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
    <h2><i class="fas fa-leaf"></i> Habit Tracker</h2>
    <a href="add_habit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Habit</a>
  </div>

  <?php
  $current_cat = '';
  if (empty($habits)) {
      echo '<p>No habits yet. Add one!</p>';
  } else {
      foreach ($habits as $h) {
          $cat = $h['category'] ?: 'Uncategorized';
          if ($cat !== $current_cat) {
              if ($current_cat !== '') echo '</div>'; // close previous category container
              echo "<h3 style='margin-top:1.5rem;'>" . htmlspecialchars($cat) . "</h3>";
              echo "<div class='category-group' style='display:flex;flex-wrap:wrap;gap:1rem;'>";
              $current_cat = $cat;
          }
          // card
          ?>
          <div class="entry-card" data-habit-id="<?= (int)$h['habit_id'] ?>" style="width:320px;padding:1rem;border:1px solid #eee;border-radius:8px;">
            <div class="entry-header">
              <h4><?= htmlspecialchars($h['habit_name']) ?></h4>
              <div class="entry-meta">
                <small><?= htmlspecialchars($h['frequency']) ?> • start <?= htmlspecialchars(date('M d, Y', strtotime($h['start_date']))) ?></small>
                <?php if ((int)$h['is_active'] === 0): ?><span style="color:#999;margin-left:.5rem;">(inactive)</span><?php endif; ?>
              </div>
            </div>
            <div style="margin-top:.4rem;color:#555;font-size:0.9rem;">
  <strong>Streak:</strong> <?= (int)($h['streak_count'] ?? 0) ?> days<br>
  <strong>Last 7 days:</strong> 
  <div style="background:#eee;border-radius:4px;width:100%;height:8px;overflow:hidden;display:inline-block;vertical-align:middle;">
    <div style="background:#28a745;height:8px;width:<?= (int)($h['week_progress'] ?? 0) ?>%;"></div>
  </div>
  <span style="font-size:0.8rem;"><?= (int)($h['week_progress'] ?? 0) ?>%</span>
</div>


            <?php if (!empty($h['description'])): ?>
              <div class="entry-content" style="margin-top:.5rem;"><?= nl2br(htmlspecialchars($h['description'])) ?></div>
            <?php endif; ?>

            <div style="display:flex;gap:.5rem;margin-top:.75rem;align-items:center;">
              <?php if ((int)$h['done_today'] === 1): ?>
                <form method="post" action="toggle_today.php" style="display:inline;">
                  <input type="hidden" name="habit_id" value="<?= (int)$h['habit_id'] ?>">
                  <input type="hidden" name="action" value="unmark">
                  <button class="btn btn-secondary btn-small" type="submit"><i class="fas fa-undo"></i> Unmark</button>
                </form>
              <?php else: ?>
                <?php if ($h['habit_type'] === 'timer'): ?>
                  <button class="btn btn-success btn-small" onclick="openTimer(<?= (int)$h['habit_id'] ?>, <?= (int)($h['timer_duration'] ?? 0) ?>)">
                    <i class="fas fa-play"></i> Start (<?= (int)($h['timer_duration'] ?? 0) ?> min)
                  </button>
                <?php else: ?>
                  <form method="post" action="toggle_today.php" style="display:inline;">
                    <input type="hidden" name="habit_id" value="<?= (int)$h['habit_id'] ?>">
                    <input type="hidden" name="action" value="mark">
                    <input type="hidden" name="status" value="done">
                    <button class="btn btn-success btn-small" type="submit"><i class="fas fa-check"></i> Mark Done</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>

              <a class="btn btn-warning btn-small" href="edit_habit.php?id=<?= (int)$h['habit_id'] ?>"><i class="fas fa-edit"></i> Edit</a>
              
              <a class="btn btn-info btn-small" href="habits_detail.php?id=<?= (int)$h['habit_id'] ?>">
  <i class="fas fa-history"></i> History
</a>


              <!-- delete uses a small POST form -->
              <form method="post" action="delete_habit.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$h['habit_id'] ?>">
                <button class="btn btn-danger btn-small" onclick="return confirm('Delete? This removes logs too.')"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>
          </div>
          <?php
      } // foreach
      if ($current_cat !== '') echo '</div>'; // close last category group
  }
  ?>

</div>

<!-- TIMER UI: modal / floating panel -->
<div id="timerModal" style="display:none;position:fixed;right:20px;bottom:20px;background:#fff;border:1px solid #ddd;padding:1rem;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.12);z-index:9999;">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <strong id="timerTitle">Timer</strong>
    <button onclick="closeTimer()" style="background:none;border:none;font-size:1.1rem;">✕</button>
  </div>
  <div style="margin-top:.5rem;">
    <div id="timerDisplay" style="font-size:1.6rem;font-weight:bold;text-align:center;">00:00</div>
    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:.5rem;">
      <button id="timerStart" class="btn btn-success btn-small">Start</button>
      <button id="timerPause" class="btn btn-secondary btn-small" disabled>Pause</button>
      <button id="timerStop" class="btn btn-danger btn-small" disabled>Stop</button>
    </div>
  </div>
  <small style="display:block;margin-top:.5rem;color:#666;text-align:center;">When you stop the timer the duration will be logged for today.</small>
</div>

<script>
let timerInterval = null;
let remainingSeconds = 0;
let timerHabitId = null;
let _timerDefaultSeconds = 0; // store initial total for elapsed calculation

function openTimer(habitId, defaultMinutes) {
  timerHabitId = habitId;
  const title = document.getElementById('timerTitle');
  title.textContent = 'Habit #' + habitId;

  if (defaultMinutes && defaultMinutes > 0) {
    remainingSeconds = defaultMinutes * 60;
  } else {
    remainingSeconds = 5 * 60; // fallback to 5 minutes
  }

  _timerDefaultSeconds = remainingSeconds; // store the starting total
  updateTimerDisplay();

  document.getElementById('timerModal').style.display = 'block';
  document.getElementById('timerStart').disabled = false;
  document.getElementById('timerPause').disabled = true;
  document.getElementById('timerStop').disabled = true;
}

function closeTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  document.getElementById('timerModal').style.display = 'none';
}

function updateTimerDisplay() {
  const min = Math.floor(remainingSeconds / 60);
  const sec = remainingSeconds % 60;
  document.getElementById('timerDisplay').textContent =
    String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
}

document.getElementById('timerStart').addEventListener('click', () => {
  if (timerInterval) return;

  document.getElementById('timerStart').disabled = true;
  document.getElementById('timerPause').disabled = false;
  document.getElementById('timerStop').disabled = false;

  timerInterval = setInterval(() => {
    remainingSeconds--;
    if (remainingSeconds < 0) {
      clearInterval(timerInterval);
      timerInterval = null;
      stopAndLogTimer(true); // auto-stop when time is up
    } else {
      updateTimerDisplay();
    }
  }, 1000);
});

document.getElementById('timerPause').addEventListener('click', () => {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
    document.getElementById('timerStart').disabled = false;
    document.getElementById('timerPause').disabled = true;
  }
});

document.getElementById('timerStop').addEventListener('click', () => {
  stopAndLogTimer(false);
});

function stopAndLogTimer(autoExpired) {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }

  // calculate elapsed minutes
  const elapsedSec = _timerDefaultSeconds - remainingSeconds;
  const elapsedMinutes = Math.max(1, Math.round(elapsedSec / 60)); // at least 1 min

  // send to server
  fetch('toggle_today.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:
      'habit_id=' + encodeURIComponent(timerHabitId) +
      '&action=mark' +
      '&status=done' +
      '&duration_minutes=' + encodeURIComponent(elapsedMinutes)
  })
    .then(() => {
      window.location.href = 'index.php?marked=1';
    })
    .catch(() => {
      alert('Could not log time. Try again.');
    });
}
</script>

