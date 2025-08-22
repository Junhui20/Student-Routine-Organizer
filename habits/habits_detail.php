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

// Fetch habit details
$stmt = $c->prepare("SELECT habit_id, habit_name, description, frequency, start_date, end_date, category, habit_type, timer_duration, is_active 
                     FROM habits WHERE habit_id = ? AND user_id = ?");
$stmt->bind_param("ii", $habit_id, $uid);
$stmt->execute();
$res = $stmt->get_result();
$habit = $res->fetch_assoc();
$stmt->close();

if (!$habit) {
    header("Location: index.php");
    exit();
}

// Fetch logs
$log_query = $c->prepare("SELECT log_date, duration, status 
                          FROM habit_logs 
                          WHERE habit_id = ? ORDER BY log_date DESC");
$log_query->bind_param("i", $habit_id);
$log_query->execute();
$logs = $log_query->get_result()->fetch_all(MYSQLI_ASSOC);
$log_query->close();

// Organize logs by date
$done_days = [];
foreach ($logs as $log) {
    if ($log['status'] === 'done') {
        $done_days[$log['log_date']] = true;
    }
}

// Calculate streaks
$today = date("Y-m-d");
$streak = 0;
$cur_date = $today;
while (isset($done_days[$cur_date])) {
    $streak++;
    $cur_date = date("Y-m-d", strtotime($cur_date . " -1 day"));
}

$best_streak = 0;
$temp = 0;
$prev = null;
foreach (array_reverse($logs) as $log) {
    if ($log['status'] === 'done') {
        if ($prev && date("Y-m-d", strtotime($prev . " +1 day")) === $log['log_date']) {
            $temp++;
        } else {
            $temp = 1;
        }
        $best_streak = max($best_streak, $temp);
        $prev = $log['log_date'];
    } else {
        $temp = 0;
    }
}

// Weekly calendar (Mon–Sun of this week)
$week_days = [];
$start_of_week = strtotime("monday this week");
for ($i = 0; $i < 7; $i++) {
    $date = date("Y-m-d", strtotime("+$i day", $start_of_week));
    $week_days[] = [
        'date' => $date,
        'label' => date("D", strtotime($date)),
        'done' => isset($done_days[$date]),
    ];
}

// Check if already marked today
$stmt_done = $c->prepare("SELECT 1 FROM habit_logs WHERE habit_id = ? AND log_date = CURDATE() AND status = 'done' LIMIT 1");
$stmt_done->bind_param("i", $habit_id);
$stmt_done->execute();
$stmt_done->store_result();
$done_today = $stmt_done->num_rows > 0;
$stmt_done->close();

include '../includes/header.php';
?>

<style>
    .week-calendar {
        display: flex;
        justify-content: space-between;
        margin: 1rem 0;
        gap: 5px;
    }

    .week-day {
        flex: 1;
        text-align: center;
        padding: 0.5rem;
        border-radius: 8px;
        background: #f1f1f1;
        font-size: 0.9rem;
    }

    .week-day.done {
        background: #28a745;
        color: #fff;
        font-weight: bold;
    }

    .stats-box {
        display: flex;
        justify-content: space-around;
        margin: 1rem 0;
        background: #f9f9f9;
        border-radius: 8px;
        padding: 1rem;
    }

    .stats-box div {
        text-align: center;
    }

    /* ✅ Uniform Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .action-buttons form,
    .action-buttons a {
        flex: 1;
    }

    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 60px;
        /* all same height */
        font-weight: 500;
        text-align: center;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .action-btn i {
        margin-right: 6px;
    }
</style>

<div class="container">
    <div class="habit-card-detail"
        style="padding:1.5rem; border:1px solid #eee; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); max-width:700px; margin:auto;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <!-- Left: Title -->
            <div>
                <h2 style="margin:0;"><?= htmlspecialchars($habit['habit_name']) ?></h2>
                <span style="font-size:.9rem; color:<?= $habit['is_active'] ? '#28a745' : '#999' ?>;">
                    <?= $habit['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>

            <!-- Right: Back Button -->
            <a href="index.php" class="btn btn-primary" style="height:40px; display:flex; align-items:center;">
                <i class="bi bi-arrow-left"></i>&nbsp; Back
            </a>
        </div>

        <!-- Description -->
        <?php if (!empty($habit['description'])): ?>
            <p style="margin:0.5rem 0 1rem 0; color:#555; font-size:0.95rem;">
                <?= nl2br(htmlspecialchars($habit['description'])) ?>
            </p>
        <?php endif; ?>

        <!-- Meta Info -->
        <div style="display:flex;gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <div><strong>Frequency:</strong> <?= htmlspecialchars($habit['frequency']) ?></div>
            <div><strong>Start:</strong> <?= htmlspecialchars($habit['start_date']) ?></div>
            <div><strong>End:</strong> <?= htmlspecialchars($habit['end_date'] ?: 'N/A') ?></div>
            <div><strong>Category:</strong> <?= htmlspecialchars($habit['category']) ?></div>
        </div>

        <!-- Weekly Calendar -->
        <div class="week-calendar">
            <?php foreach ($week_days as $day): ?>
                <div class="week-day <?= $day['done'] ? 'done' : '' ?>">
                    <?= $day['label'] ?><br>
                    <?= $day['done'] ? '✔' : '✖' ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Stats Box (without Accuracy) -->
        <div class="stats-box">
            <div>
                <strong><?= $streak ?></strong><br><small>Current Streak</small>
            </div>
            <div>
                <strong><?= $best_streak ?></strong><br><small>Best Streak</small>
            </div>
        </div>

        <!-- History -->
        <h3 style="margin-top:1.5rem;">History</h3>
        <div style="max-height:300px; overflow-y:auto;">
            <?php foreach ($logs as $log): ?>
                <div style="display:flex;justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #eee;">
                    <span><?= htmlspecialchars($log['log_date']) ?></span>
                    <span><?= $log['status'] === 'done' ? '✔' : '✖' ?>
                        <?= $log['duration'] ? '(' . htmlspecialchars($log['duration']) . ' min)' : '' ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($logs))
                echo "<p>No history yet.</p>"; ?>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <form method="post" action="toggle_today.php">
                <input type="hidden" name="habit_id" value="<?= $habit_id ?>">
                <input type="hidden" name="action" value="<?= $done_today ? 'unmark' : 'mark' ?>">
                <input type="hidden" name="status" value="done">
                <button class="action-btn btn <?= $done_today ? 'btn-secondary' : 'btn-success' ?>">
                    <i class="bi <?= $done_today ? 'bi-x-circle' : 'bi-check-circle' ?>"></i>
                    <?= $done_today ? 'Unmark' : 'Mark Done' ?>
                </button>
            </form>
            <a href="edit_habit.php?id=<?= $habit_id ?>" class="action-btn btn btn-warning">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <form method="post" action="delete_habit.php"
                onsubmit="return confirm('Delete this habit? This removes logs too.')">
                <input type="hidden" name="id" value="<?= $habit_id ?>">
                <button class="action-btn btn btn-danger">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>

    </div>
</div>