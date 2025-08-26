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
$q = $_GET['q'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';

$habits = array_filter($habits, function ($h) use ($q, $filter_category, $filter_status) {
  // Search by name
  $matches_search = !$q || stripos($h['habit_name'], $q) !== false;

  // Filter by category
  $matches_cat = !$filter_category || ($h['category'] ?: 'Uncategorized') === $filter_category;

  // Filter by status (active/inactive)
  $matches_status = true;
  if ($filter_status === 'active')
    $matches_status = (int) $h['is_active'] === 1;
  if ($filter_status === 'inactive')
    $matches_status = (int) $h['is_active'] === 0;

  return $matches_search && $matches_cat && $matches_status;
});


include '../includes/header.php';
?>


<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
    <h2><i class="fas fa-leaf"></i> Habit Tracker</h2>
    <a href="add_habit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Habit</a>
  </div>
  <div class="habit-controls" style="margin-bottom:2rem;">
    <!-- Collapsible Header -->
    <button id="toggleFilterBtn" class="btn btn-primary"
      style="width:100%;padding:0.5rem;;border:none;border-radius:4px;cursor:pointer;text-align:left;">
      <i class="fas fa-filter"></i> Search & Filters <span style="float:right;">&#9660;</span>
    </button>

    <div id="filterPanel" style="display:none;margin-top:0.5rem;">
      <form id="habitFilterForm" method="get" action=""
        style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:flex-start;">

        <!-- Search by habit name -->
        <input type="text" name="q" placeholder="Search habits..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
          style="flex:1 1 200px;padding:0.5rem;border:1px solid #ccc;border-radius:4px;">

        <!-- Filter by category -->
        <select name="category" style="flex:1 1 200px;padding:0.5rem;border:1px solid #ccc;border-radius:4px;">
          <option value="">All Categories</option>
          <?php
          $cats = ['Physical Health', 'Mental Health', 'Spiritual Health', 'Social Health', 'Technology', 'Work', 'Finance', 'Home', 'Sleep', 'Creativity', 'Uncategorized'];
          foreach ($cats as $cat_option) {
            $selected = (isset($_GET['category']) && $_GET['category'] === $cat_option) ? 'selected' : '';
            echo "<option value=\"" . htmlspecialchars($cat_option) . "\" $selected>" . htmlspecialchars($cat_option) . "</option>";
          }
          ?>
        </select>

        <!-- Filter by active/inactive -->
        <select name="status" style="flex:1 1 150px;padding:0.5rem;border:1px solid #ccc;border-radius:4px;">
          <option value="">All Status</option>
          <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>Active
          </option>
          <option value="inactive" <?= (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : '' ?>>
            Inactive</option>
        </select>

        <!-- Apply Filters -->
        <button type="submit"
          style="flex:1 1 48%;padding:0.5rem;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;">
          <i class="fas fa-search"></i> Apply Filters
        </button>

        <!-- Reset Filters -->
        <button type="button" id="resetFiltersBtn"
          style="flex:1 1 48%;padding:0.5rem;background:#6c757d;color:#fff;border:none;border-radius:4px;cursor:pointer;">
          <i class="fas fa-redo"></i> Reset Filters
        </button>

      </form>
    </div>




    <?php
    $current_cat = '';
    if (empty($habits)) {
      echo '<p>No habits yet. Add one!</p>';
    } else {
      foreach ($habits as $h) {
        $cat = $h['category'] ?: 'Uncategorized';
        if ($cat !== $current_cat) {
          if ($current_cat !== '')
            echo '</div>'; // close previous category container
          echo "<h3 style='margin-top:1.5rem;'>" . htmlspecialchars($cat) . "</h3>";
          echo "<div class='category-group' style='display:flex;flex-wrap:wrap;gap:1rem;'>";
          $current_cat = $cat;
        }
        // card
        ?>
        <div class="entry-card" data-habit-id="<?= (int) $h['habit_id'] ?>"
          style="width:500px;padding:1.5rem;border:1px solid #eee;border-radius:8px;">
          <div class="entry-header">
            <h4><?= htmlspecialchars($h['habit_name']) ?></h4>
            <div class="entry-meta">
              <small><?= htmlspecialchars($h['frequency']) ?> • start
                <?= htmlspecialchars(date('M d, Y', strtotime($h['start_date']))) ?></small>
              <?php if ((int) $h['is_active'] === 0): ?><span
                  style="color:#999;margin-left:.5rem;">(inactive)</span><?php endif; ?>
            </div>
          </div>
          <div style="margin-top:.4rem;color:#555;font-size:0.9rem;">
            <strong>Streak:</strong> <?= (int) ($h['streak_count'] ?? 0) ?> days<br>
            <strong>Last 7 days:</strong>
            <div
              style="background:#eee;border-radius:4px;width:100%;height:8px;overflow:hidden;display:inline-block;vertical-align:middle;">
              <div style="background:#28a745;height:8px;width:<?= (int) ($h['week_progress'] ?? 0) ?>%;"></div>
            </div>
            <span style="font-size:0.8rem;"><?= (int) ($h['week_progress'] ?? 0) ?>%</span>
          </div>


          <?php if (!empty($h['description'])): ?>
            <div class="entry-content" style="margin-top:.6rem;"><?= nl2br(htmlspecialchars($h['description'])) ?></div>
          <?php endif; ?>

          <div class="habit-actions" style="display:flex;gap:.5rem;margin-top:.75rem;">
            <?php if ((int) $h['is_active'] === 0): ?>
              <!-- Inactive habit: show disabled button -->
              <button class="btn btn-secondary btn-small" disabled>
                <i class="fas fa-ban"></i> Inactive
              </button>
            <?php else: ?>
              <?php if ((int) $h['done_today'] === 1): ?>
                <form method="post" action="toggle_today.php" style="display:inline;">
                  <input type="hidden" name="habit_id" value="<?= (int) $h['habit_id'] ?>">
                  <input type="hidden" name="action" value="unmark">
                  <button class="btn btn-secondary btn-small" type="submit"><i class="fas fa-undo"></i> Unmark</button>
                </form>
              <?php else: ?>
                <?php if ($h['habit_type'] === 'timer'): ?>
                  <button class="btn btn-success btn-small"
                    onclick="openTimer(<?= (int) $h['habit_id'] ?>, <?= (int) ($h['timer_duration'] ?? 0) ?>)">
                    <i class="fas fa-play"></i> Start (<?= (int) ($h['timer_duration'] ?? 0) ?> min)
                  </button>
                <?php else: ?>
                  <form method="post" action="toggle_today.php" style="display:inline;">
                    <input type="hidden" name="habit_id" value="<?= (int) $h['habit_id'] ?>">
                    <input type="hidden" name="action" value="mark">
                    <input type="hidden" name="status" value="done">
                    <button class="btn btn-success btn-small" type="submit"><i class="fas fa-check"></i> Mark Done</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>


            <a class="btn btn-warning btn-small" href="edit_habit.php?id=<?= (int) $h['habit_id'] ?>"><i
                class="fas fa-edit"></i> Edit</a>



            <!-- delete uses a small POST form -->
            <form method="post" action="delete_habit.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= (int) $h['habit_id'] ?>">
              <button class="btn btn-danger btn-small" onclick="return confirm('Delete? This removes logs too.')"><i
                  class="fas fa-trash"></i> Delete</button>
            </form>

            <a class="btn btn-info btn-small" href="habits_detail.php?id=<?= (int) $h['habit_id'] ?>"><i
                class="fas fa-history"></i> History</a>

          </div>
        </div>
        <?php
      } // foreach
      if ($current_cat !== '')
        echo '</div>'; // close last category group
    }
    ?>

  </div>

  <!-- TIMER UI: modal / floating panel -->
  <div id="timerModal"
    style="display:none;position:fixed;right:20px;bottom:20px;background:#fff;border:1px solid #ddd;padding:1rem;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.12);z-index:9999;">
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
    <small style="display:block;margin-top:.5rem;color:#666;text-align:center;">When you stop the timer the duration
      will be logged for today.</small>
  </div>

  <script>
    let timerInterval = null;
    let remainingSeconds = 0;
    let timerHabitId = null;
    let _timerDefaultSeconds = 0; // store initial total for elapsed calculation

    function openTimer(habitId, defaultMinutes) {
      const habitCard = document.querySelector(`.entry-card[data-habit-id='${habitId}']`);
      if (habitCard && habitCard.querySelector('.btn-secondary[disabled]')) {
        alert('This habit is inactive. Reactivate to start timer.');
        return;
      }
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
  <style>
    /* Force all habit action buttons same style/size */
    .habit-actions .btn,
    .habit-actions form button {
      width: 100%;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 0.9rem;
      line-height: 1.1rem;
      border-radius: 6px;
      white-space: nowrap;
    }

    /* ensure icons have spacing */
    .habit-actions .btn i {
      margin-right: 6px;
    }



    /* explicitly style the history/info button */
    .habit-actions .btn-info {
      background-color: #474747ff !important;
      color: #fff !important;
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggleBtn = document.getElementById('toggleFilterBtn');
      const panel = document.getElementById('filterPanel');

      if (toggleBtn && panel) {
        toggleBtn.addEventListener('click', function () {
          if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
            this.querySelector('span').innerHTML = '&#9650;'; // arrow up
          } else {
            panel.style.display = 'none';
            this.querySelector('span').innerHTML = '&#9660;'; // arrow down
          }
        });
      }
    });
  </script>
  <script>
    document.getElementById('resetFiltersBtn').addEventListener('click', function () {
      const form = document.getElementById('habitFilterForm');

      // Clear all input/select fields
      form.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
      form.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

      // Submit the form automatically
      form.submit();
    });
  </script>