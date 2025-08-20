<?php
require_once __DIR__ . '/habits_functions.php';
require_login();

$habit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($habit_id <= 0 || !habit_belongs_to_user($habit_id)) {
    header("Location: index.php");
    exit();
}

$c = db();
$uid = current_user_id();
$error = '';

// Fetch habit details
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

// Fetch logs including duration
$log_query = $c->prepare("SELECT log_date, duration FROM habit_logs WHERE habit_id = ? ORDER BY log_date DESC");
$log_query->bind_param("i", $habit_id);
$log_query->execute();
$logs = $log_query->get_result()->fetch_all(MYSQLI_ASSOC);
$log_query->close();

// Calculate streak
$streak = 0;
$today = date("Y-m-d");
$prev_date = $today;
foreach ($logs as $log) {
    if ($log['log_date'] == $prev_date) {
        $streak++;
        $prev_date = date("Y-m-d", strtotime("$prev_date -1 day"));
    } else {
        break;
    }
}
// Check if habit is already marked done today
$stmt_done = $c->prepare("SELECT 1 FROM habit_logs WHERE habit_id = ? AND log_date = CURDATE() AND status = 'done' LIMIT 1");
$stmt_done->bind_param("i", $habit_id);
$stmt_done->execute();
$stmt_done->store_result();
$done_today = $stmt_done->num_rows > 0;
$stmt_done->close();

include '../includes/header.php';
?>

<div class="container">
    <div class="habit-card-detail" style="padding:1.5rem; border:1px solid #eee; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); max-width:700px; margin:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2><?= htmlspecialchars($habit['habit_name']) ?></h2>
            <span style="font-size:.9rem; color:<?= $habit['is_active'] ? '#28a745' : '#999' ?>;">
                <?= $habit['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>

        <?php if (!empty($habit['description'])): ?>
            <p style="margin:0.5rem 0 1rem 0; color:#555; font-size:0.95rem;"><?= nl2br(htmlspecialchars($habit['description'])) ?></p>
        <?php endif; ?>

        <div style="display:flex;gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <div><strong>Frequency:</strong> <?= htmlspecialchars($habit['frequency']) ?></div>
            <div><strong>Start:</strong> <?= htmlspecialchars($habit['start_date']) ?></div>
            <div><strong>End:</strong> <?= htmlspecialchars($habit['end_date'] ?: 'N/A') ?></div>
            <div><strong>Category:</strong> <?= htmlspecialchars($habit['category']) ?></div>
        </div>

        <div style="display:flex;gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <div style="flex:1; min-width:120px;">
                <strong>Streak:</strong>
                <div style="margin-top:.25rem; background:#eee; border-radius:4px; width:100%; height:12px; overflow:hidden;">
                    <div style="background:#28a745; width:<?= min($streak*10,100) ?>%; height:12px;"></div>
                </div>
                <small><?= $streak ?> days</small>
            </div>
        </div>

        <h3 style="margin-top:1.5rem;">History</h3>
        <div style="max-height:300px; overflow-y:auto;">
            <?php foreach ($logs as $log): ?>
            <div style="display:flex;justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #eee;">
                <span><?= htmlspecialchars($log['log_date']) ?></span>
                <span><?= htmlspecialchars($log['duration']) ?> min</span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($logs)) echo "<p>No history yet.</p>"; ?>
        </div>

        <div style="display:flex;gap:.5rem; margin-top:1rem; flex-wrap:wrap;">
            <form method="post" action="toggle_today.php" style="flex:1;">
                <input type="hidden" name="habit_id" value="<?= $habit_id ?>">
                <input type="hidden" name="action" value="<?= $done_today ? 'unmark' : 'mark' ?>">
                <input type="hidden" name="status" value="done">
                <button class="btn <?= $done_today ? 'btn-secondary' : 'btn-success' ?>" style="width:100%; padding:.6rem;"><?= $done_today ? 'Unmark' : 'Mark Done' ?></button>
            </form>
            <a href="edit_habit.php?id=<?= $habit_id ?>" class="btn btn-warning" style="flex:1; text-align:center; padding:.6rem;">Edit</a>
            <form method="post" action="delete_habit.php" style="flex:1;" onsubmit="return confirm('Delete this habit? This removes logs too.')">
                <input type="hidden" name="id" value="<?= $habit_id ?>">
                <button class="btn btn-danger" style="width:100%; padding:.6rem;">Delete</button>
            </form>
        </div>
    </div>
</div>
