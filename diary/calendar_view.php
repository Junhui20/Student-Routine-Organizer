<?php
require_once '../includes/SessionManager.php';
require_once '../config/database.php';

try {
    SessionManager::initializeSession();
} catch (Exception $e) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
if(!SessionManager::isAuthenticated()) {
    header("Location: ../auth/login.php");
    exit();
}

// Get current month and year from URL parameters or use current date
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Validate date parameters
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}
if ($currentYear < 2000 || $currentYear > 2100) {
    $currentYear = date('Y');
}

// Get entries for the current month
$entriesData = [];
$moodStats = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all entries for the current month
    $stmt = $conn->prepare("
        SELECT entry_id, title, content, mood, entry_date, created_at 
        FROM diary_entries 
        WHERE user_id = ? 
        AND YEAR(entry_date) = ? 
        AND MONTH(entry_date) = ?
        ORDER BY entry_date ASC, created_at ASC
    ");
    
    $stmt->execute([SessionManager::getCurrentUserId(), $currentYear, $currentMonth]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize entries by date
    foreach ($entries as $entry) {
        $day = date('j', strtotime($entry['entry_date']));
        if (!isset($entriesData[$day])) {
            $entriesData[$day] = [];
        }
        $entriesData[$day][] = $entry;
        
        // Count mood statistics
        $mood = $entry['mood'];
        if (!isset($moodStats[$mood])) {
            $moodStats[$mood] = 0;
        }
        $moodStats[$mood]++;
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calendar helper functions
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month];
}

function getMoodColor($mood) {
    $colors = [
        'Happy' => '#fffacd',
        'Sad' => '#e6f3ff',
        'Excited' => '#fff0f5',
        'Stressed' => '#ffe4e1',
        'Calm' => '#f0fff0',
        'Angry' => '#ffe4e1',
        'Grateful' => '#f0fff0',
        'Tired' => '#f5f5dc'
    ];
    return $colors[$mood] ?? '#f8f9fa';
}

function getMoodEmoji($mood) {
    $emojis = [
        'Happy' => '😊', 'Sad' => '😢', 'Excited' => '🎉', 'Stressed' => '😰',
        'Calm' => '😌', 'Angry' => '😠', 'Grateful' => '🙏', 'Tired' => '😴'
    ];
    return $emojis[$mood] ?? '😊';
}

// Navigation calculations
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Calendar calculations
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$lastDayOfMonth = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('w', $firstDayOfMonth); // 0 = Sunday
$weeksInMonth = ceil(($daysInMonth + $startDayOfWeek) / 7);
?>

<?php include '../includes/header.php'; ?>

<style>
.calendar-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.calendar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.calendar-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.nav-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    padding: 8px 16px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.month-year-title {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.quick-navigation {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1rem;
}

.quick-nav-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.quick-nav-btn:hover,
.quick-nav-btn.active {
    background: rgba(255,255,255,0.4);
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: white;
}

.calendar-day-header {
    background: #f8f9fa;
    padding: 1rem;
    text-align: center;
    font-weight: bold;
    color: #495057;
    border-bottom: 1px solid #e9ecef;
}

.calendar-day {
    min-height: 120px;
    border-right: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    color: #adb5bd;
}

.calendar-day.today {
    background: linear-gradient(135deg, #667eea20, #764ba240);
    border: 2px solid #667eea;
}

.calendar-day.has-entries {
    background: linear-gradient(135deg, #f0fff0, #e8f5e8);
}

.day-number {
    position: absolute;
    top: 8px;
    left: 8px;
    font-weight: bold;
    font-size: 1.1rem;
    color: #333;
}

.calendar-day.other-month .day-number {
    color: #adb5bd;
}

.calendar-day.today .day-number {
    color: #667eea;
    font-size: 1.3rem;
}

.entry-indicators {
    position: absolute;
    bottom: 8px;
    left: 8px;
    right: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
}

.entry-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.1);
}

.mood-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.entries-count {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #667eea;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.8rem;
    font-weight: bold;
}

/* Entry Preview Tooltip */
.entry-preview {
    position: absolute;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    z-index: 1000;
    max-width: 300px;
    display: none;
    pointer-events: none;
}

.preview-title {
    font-weight: bold;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.preview-content {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.preview-mood {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #6c757d;
}

/* Statistics Panel */
.calendar-stats {
    background: #f8f9fa;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.stats-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #495057;
    margin-bottom: 1rem;
    text-align: center;
}

.mood-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
}

.mood-stat-item {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    border: 1px solid #dee2e6;
    transition: transform 0.2s;
}

.mood-stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.mood-stat-emoji {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.mood-stat-count {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.mood-stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

/* View Toggle */
.view-toggle {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 100;
}

.toggle-btn {
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 1rem 1.5rem;
    font-size: 1rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.2s;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
}

@media (max-width: 768px) {
    .calendar-container {
        margin: 1rem 0;
        border-radius: 10px;
    }
    
    .calendar-header {
        padding: 1.5rem 1rem;
    }
    
    .month-year-title {
        font-size: 1.5rem;
    }
    
    .calendar-navigation {
        flex-direction: column;
        gap: 1rem;
    }
    
    .quick-navigation {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .calendar-day {
        min-height: 80px;
    }
    
    .day-number {
        font-size: 1rem;
    }
    
    .mood-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .entry-preview {
        max-width: 250px;
        font-size: 0.8rem;
    }
}
</style>

<div class="container" style="padding: 2rem 1rem;">
    <!-- Calendar Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-calendar-alt"></i> Calendar View</h2>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-list"></i> List View
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="calendar-container">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="calendar-navigation">
                <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn">
                    <i class="fas fa-chevron-left"></i>
                    <?php echo getMonthName($prevMonth) . ' ' . $prevYear; ?>
                </a>
                
                <h3 class="month-year-title">
                    <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?>
                </h3>
                
                <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn">
                    <?php echo getMonthName($nextMonth) . ' ' . $nextYear; ?>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <div class="quick-navigation">
                <?php
                $today = new DateTime();
                $currentYearForNav = date('Y');
                $currentMonthForNav = date('n');
                ?>
                <a href="?year=<?php echo $currentYearForNav; ?>&month=<?php echo $currentMonthForNav; ?>" 
                   class="quick-nav-btn <?php echo ($currentYear == $currentYearForNav && $currentMonth == $currentMonthForNav) ? 'active' : ''; ?>">
                    Today
                </a>
                <a href="?year=<?php echo $currentYear; ?>&month=<?php echo $currentMonth-1 < 1 ? 12 : $currentMonth-1; ?>" class="quick-nav-btn">
                    Last Month
                </a>
                <a href="?year=<?php echo $currentYear; ?>&month=<?php echo $currentMonth+1 > 12 ? 1 : $currentMonth+1; ?>" class="quick-nav-btn">
                    Next Month
                </a>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <!-- Day Headers -->
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>

            <?php
            $today = date('Y-m-d');
            $currentDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
            
            // Calculate days to show from previous month
            $prevMonthDays = date('t', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
            
            // Add days from previous month
            for ($i = $startDayOfWeek - 1; $i >= 0; $i--) {
                $day = $prevMonthDays - $i;
                echo '<div class="calendar-day other-month">';
                echo '<div class="day-number">' . $day . '</div>';
                echo '</div>';
            }
            
            // Add days of current month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateString = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $isToday = ($dateString === $today);
                $hasEntries = isset($entriesData[$day]);
                
                $dayClasses = ['calendar-day'];
                if ($isToday) $dayClasses[] = 'today';
                if ($hasEntries) $dayClasses[] = 'has-entries';
                
                echo '<div class="' . implode(' ', $dayClasses) . '" data-date="' . $dateString . '">';
                echo '<div class="day-number">' . $day . '</div>';
                
                if ($hasEntries) {
                    $dayEntries = $entriesData[$day];
                    echo '<div class="entries-count">' . count($dayEntries) . '</div>';
                    
                    // Show mood indicators
                    echo '<div class="entry-indicators">';
                    foreach ($dayEntries as $entry) {
                        $moodColor = getMoodColor($entry['mood']);
                        $moodEmoji = getMoodEmoji($entry['mood']);
                        echo '<div class="mood-indicator" style="background-color: ' . $moodColor . ';" title="' . htmlspecialchars($entry['mood']) . '">';
                        echo $moodEmoji;
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    // Hidden entry data for preview
                    echo '<div class="entry-data" style="display: none;">';
                    echo htmlspecialchars(json_encode($dayEntries));
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            // Calculate remaining cells and fill with next month days
            $totalCells = $weeksInMonth * 7;
            $filledCells = $startDayOfWeek + $daysInMonth;
            $remainingCells = $totalCells - $filledCells;
            
            for ($day = 1; $day <= $remainingCells; $day++) {
                echo '<div class="calendar-day other-month">';
                echo '<div class="day-number">' . $day . '</div>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Statistics Panel -->
        <?php if (!empty($moodStats)): ?>
        <div class="calendar-stats">
            <h4 class="stats-title">
                <i class="fas fa-chart-pie"></i>
                Mood Statistics for <?php echo getMonthName($currentMonth); ?>
            </h4>
            <div class="mood-stats">
                <?php foreach ($moodStats as $mood => $count): ?>
                <div class="mood-stat-item">
                    <div class="mood-stat-emoji"><?php echo getMoodEmoji($mood); ?></div>
                    <div class="mood-stat-count"><?php echo $count; ?></div>
                    <div class="mood-stat-label"><?php echo $mood; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Entry Preview Tooltip -->
<div id="entry-preview" class="entry-preview"></div>

<!-- View Toggle Button -->
<div class="view-toggle">
    <a href="add_entry.php" class="toggle-btn">
        <i class="fas fa-plus"></i>
        New Entry
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.querySelectorAll('.calendar-day.has-entries');
    const preview = document.getElementById('entry-preview');
    let previewTimeout;

    calendarDays.forEach(day => {
        day.addEventListener('mouseenter', function(e) {
            clearTimeout(previewTimeout);
            
            const entryDataElement = this.querySelector('.entry-data');
            if (!entryDataElement) return;
            
            try {
                const entries = JSON.parse(entryDataElement.textContent);
                showPreview(entries, e);
            } catch (error) {
                console.error('Error parsing entry data:', error);
            }
        });

        day.addEventListener('mouseleave', function() {
            previewTimeout = setTimeout(() => {
                hidePreview();
            }, 100);
        });

        day.addEventListener('click', function() {
            const entryDataElement = this.querySelector('.entry-data');
            if (!entryDataElement) return;
            
            try {
                const entries = JSON.parse(entryDataElement.textContent);
                if (entries.length === 1) {
                    // Single entry - go directly to view
                    window.location.href = `view_entry.php?id=${entries[0].entry_id}`;
                } else {
                    // Multiple entries - show selection
                    showEntrySelection(entries);
                }
            } catch (error) {
                console.error('Error parsing entry data:', error);
            }
        });
    });

    preview.addEventListener('mouseenter', function() {
        clearTimeout(previewTimeout);
    });

    preview.addEventListener('mouseleave', function() {
        hidePreview();
    });

    function showPreview(entries, event) {
        if (entries.length === 0) return;

        let previewHTML = '';
        
        entries.forEach((entry, index) => {
            if (index > 0) previewHTML += '<hr style="margin: 0.5rem 0; border: 1px solid #eee;">';
            
            const contentPreview = stripHtml(entry.content).substring(0, 100);
            const shortContent = contentPreview.length < entry.content.length ? contentPreview + '...' : contentPreview;
            
            previewHTML += `
                <div class="preview-entry">
                    <div class="preview-title">${escapeHtml(entry.title)}</div>
                    <div class="preview-content">${escapeHtml(shortContent)}</div>
                    <div class="preview-mood">
                        ${getMoodEmoji(entry.mood)} ${escapeHtml(entry.mood)}
                        <span style="margin-left: auto; font-size: 0.7rem;">
                            ${formatTime(entry.created_at)}
                        </span>
                    </div>
                </div>
            `;
        });

        preview.innerHTML = previewHTML;
        
        // Position the preview
        const rect = event.target.closest('.calendar-day').getBoundingClientRect();
        const previewRect = preview.getBoundingClientRect();
        
        let left = rect.left + (rect.width / 2) - (300 / 2);
        let top = rect.top - 10;
        
        // Adjust if preview goes off screen
        if (left < 10) left = 10;
        if (left + 300 > window.innerWidth - 10) left = window.innerWidth - 310;
        if (top < 10) top = rect.bottom + 10;
        
        preview.style.left = left + 'px';
        preview.style.top = top + 'px';
        preview.style.display = 'block';
    }

    function hidePreview() {
        preview.style.display = 'none';
    }

    function showEntrySelection(entries) {
        let selectionHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: flex; align-items: center; justify-content: center;" onclick="this.remove()">
                <div style="background: white; border-radius: 15px; padding: 2rem; max-width: 500px; max-height: 80vh; overflow-y: auto;" onclick="event.stopPropagation()">
                    <h3 style="margin: 0 0 1rem 0; text-align: center;">Select Entry to View</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        `;
        
        entries.forEach(entry => {
            const contentPreview = stripHtml(entry.content).substring(0, 80);
            selectionHTML += `
                <a href="view_entry.php?id=${entry.entry_id}" style="text-decoration: none; color: inherit; padding: 1rem; border: 1px solid #dee2e6; border-radius: 8px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='white'">
                    <div style="font-weight: bold; margin-bottom: 0.25rem;">${escapeHtml(entry.title)}</div>
                    <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">${escapeHtml(contentPreview)}...</div>
                    <div style="font-size: 0.8rem; color: #999;">
                        ${getMoodEmoji(entry.mood)} ${escapeHtml(entry.mood)} • ${formatTime(entry.created_at)}
                    </div>
                </a>
            `;
        });
        
        selectionHTML += `
                    </div>
                    <button onclick="this.closest('[style*=\"position: fixed\"]').remove()" style="width: 100%; margin-top: 1rem; padding: 0.5rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', selectionHTML);
    }

    function stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getMoodEmoji(mood) {
        const emojis = {
            'Happy': '😊', 'Sad': '😢', 'Excited': '🎉', 'Stressed': '😰',
            'Calm': '😌', 'Angry': '😠', 'Grateful': '🙏', 'Tired': '😴'
        };
        return emojis[mood] || '😊';
    }

    function formatTime(datetime) {
        const date = new Date(datetime);
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }
});
</script>

 