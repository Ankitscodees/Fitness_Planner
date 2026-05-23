<?php
// ============================================================
// dashboard.php — User Dashboard
// ============================================================
require_once __DIR__ . '/includes/header.php';
requireLogin();

$pdo  = getDB();
$user = getCurrentUser();

// Progress logs
$progress = $pdo->prepare("
    SELECT * FROM user_progress 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT 10
");
$progress->execute([$user['id']]);
$logs = $progress->fetchAll();

// Chart data (last 7 entries)
$chartLogs    = array_reverse(array_slice($logs, 0, 7));
$chartLabels  = array_column($chartLogs, 'log_date');
$chartWeights = array_column($chartLogs, 'weight');

// Filter out nulls
$validWeights = array_filter($chartWeights);

// Recent workouts/meals suggestions
$suggestedWorkouts = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.goal_tag = ? OR w.goal_tag = 'all' ORDER BY RAND() LIMIT 3");
$suggestedWorkouts->execute([$user['goal']]);
$suggestions = $suggestedWorkouts->fetchAll();

// Log progress form
$logError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_progress'])) {
    $logWeight = (float)($_POST['log_weight'] ?? 0);
    $logNotes  = trim($_POST['log_notes'] ?? '');
    $logDate   = $_POST['log_date'] ?? date('Y-m-d');

    if ($logWeight > 0) {
        $bmi  = ($user['height'] > 0) ? calculateBMI($logWeight, $user['height']) : null;
        $bmr  = ($user['height'] > 0 && $user['age'] > 0 && $user['gender'])
                    ? calculateBMR($logWeight, $user['height'], $user['age'], $user['gender']) : null;
        $tdee = ($bmr && $user['activity_level'])
                    ? calculateTDEE($bmr, $user['activity_level']) : null;

        $stmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, log_date, weight, bmi, bmr, tdee, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE weight=VALUES(weight), bmi=VALUES(bmi), bmr=VALUES(bmr), tdee=VALUES(tdee), notes=VALUES(notes)
        ");
        $stmt->execute([$user['id'], $logDate, $logWeight, $bmi, $bmr, $tdee, $logNotes ?: null]);
        setFlash('success', 'Progress logged successfully!');
        redirect('/fitness_planner/dashboard.php');
    } else {
        $logError = 'Please enter a valid weight.';
    }
}

// Compute stats
$currentWeight  = $logs[0]['weight']  ?? $user['weight'];
$currentBmi     = $logs[0]['bmi']     ?? ($user['weight'] && $user['height'] ? calculateBMI($user['weight'], $user['height']) : null);
$currentBmr     = $logs[0]['bmr']     ?? null;
$currentTdee    = $logs[0]['tdee']    ?? null;
$bmiCat         = $currentBmi ? getBMICategory($currentBmi) : null;

$pageTitle = 'Dashboard';

$workoutEmojis = ['💪','🏋️','🔥','⚡','🚀'];
?>

<div class="dashboard-layout">
    <div class="d-flex justify-between align-center flex-wrap gap-16" style="margin-bottom:8px">
        <div>
            <h1 class="dashboard-greeting">
                <?= date('H') < 12 ? '🌅 Good Morning' : (date('H') < 17 ? '☀️ Good Afternoon' : '🌙 Good Evening') ?>, <?= e(explode(' ', $user['name'])[0]) ?>
            </h1>
            <p class="dashboard-meta">
                Goal: <strong><?= goalLabel($user['goal']) ?></strong> &nbsp;·&nbsp; 
                Activity: <strong><?= formatActivity($user['activity_level']) ?></strong> &nbsp;·&nbsp;
                Today: <?= date('l, F j, Y') ?>
            </p>
        </div>
        <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary">📊 Calculator</a>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-card-icon">⚖️</div>
            <div class="stat-card-value"><?= $currentWeight ? number_format($currentWeight, 1) . ' kg' : '—' ?></div>
            <div class="stat-card-label">Current Weight</div>
            <?php if (count($logs) >= 2 && $logs[0]['weight'] && $logs[1]['weight']): 
                $diff = round($logs[0]['weight'] - $logs[1]['weight'], 1);
            ?>
                <div class="stat-card-change <?= $diff < 0 ? 'change-positive' : 'change-negative' ?>">
                    <?= $diff > 0 ? '+' : '' ?><?= $diff ?> kg vs yesterday
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">📏</div>
            <div class="stat-card-value" style="color:<?= $bmiCat ? $bmiCat['color'] : 'var(--text-primary)' ?>">
                <?= $currentBmi ? number_format($currentBmi, 1) : '—' ?>
            </div>
            <div class="stat-card-label">BMI <?= $bmiCat ? '(' . $bmiCat['label'] . ')' : '' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">🔥</div>
            <div class="stat-card-value"><?= $currentBmr ? number_format($currentBmr) : '—' ?></div>
            <div class="stat-card-label">BMR (kcal/day)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">⚡</div>
            <div class="stat-card-value"><?= $currentTdee ? number_format($currentTdee) : '—' ?></div>
            <div class="stat-card-label">TDEE (kcal/day)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">📅</div>
            <div class="stat-card-value"><?= count($logs) ?></div>
            <div class="stat-card-label">Days Logged</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div>
            <!-- Weight Chart -->
            <?php if (count($validWeights) >= 2): ?>
            <div class="progress-chart">
                <div class="chart-title">📈 Weight Progress</div>
                <canvas id="weightChart" style="width:100%;display:block"
                    data-labels='<?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), $chartLabels)) ?>'
                    data-values='<?= json_encode(array_values($validWeights)) ?>'>
                </canvas>
            </div>
            <?php endif; ?>

            <!-- Progress Log Form -->
            <div class="progress-chart" style="margin-bottom:24px">
                <div class="chart-title">📝 Log Today's Progress</div>
                <?php if ($logError): ?>
                    <div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:16px;animation:none">❌ <?= e($logError) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="log_progress" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="log_weight">Weight (kg)</label>
                            <input type="number" id="log_weight" name="log_weight" class="form-control" placeholder="e.g. 75.5" step="0.1" min="20" max="500" value="<?= e($currentWeight ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="log_date">Date</label>
                            <input type="date" id="log_date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="log_notes">Notes (optional)</label>
                        <textarea id="log_notes" name="log_notes" class="form-control" placeholder="How was your day? Any achievements?" style="min-height:70px"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Log Progress</button>
                </form>
            </div>

            <!-- Progress Table -->
            <?php if ($logs): ?>
            <div class="admin-table-wrap">
                <div class="admin-table-header">
                    <h3 style="font-family:var(--font-display);font-size:1.1rem">Progress History</h3>
                </div>
                <div class="progress-table-wrap">
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>BMI</th>
                                <th>BMR</th>
                                <th>TDEE</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= e(date('M j, Y', strtotime($log['log_date']))) ?></td>
                                    <td><?= $log['weight'] ? number_format($log['weight'], 1) . ' kg' : '—' ?></td>
                                    <td><?= $log['bmi']   ? number_format($log['bmi'], 1)   : '—' ?></td>
                                    <td><?= $log['bmr']   ? number_format($log['bmr'])      : '—' ?></td>
                                    <td><?= $log['tdee']  ? number_format($log['tdee'])     : '—' ?></td>
                                    <td><?= e(truncate($log['notes'] ?? '', 60)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <div class="empty-title">No Progress Yet</div>
                    <p>Start logging your weight above to track your journey!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Profile Card -->
            <div class="sidebar-card">
                <div class="sidebar-title">👤 Your Profile</div>
                <div class="sidebar-stat"><span class="sidebar-stat-label">Name</span><span class="sidebar-stat-value"><?= e($user['name']) ?></span></div>
                <div class="sidebar-stat"><span class="sidebar-stat-label">Goal</span><span class="sidebar-stat-value"><?= goalLabel($user['goal']) ?></span></div>
                <?php if ($user['age']): ?><div class="sidebar-stat"><span class="sidebar-stat-label">Age</span><span class="sidebar-stat-value"><?= e($user['age']) ?> years</span></div><?php endif; ?>
                <?php if ($user['height']): ?><div class="sidebar-stat"><span class="sidebar-stat-label">Height</span><span class="sidebar-stat-value"><?= e($user['height']) ?> cm</span></div><?php endif; ?>
                <?php if ($user['gender']): ?><div class="sidebar-stat"><span class="sidebar-stat-label">Gender</span><span class="sidebar-stat-value"><?= ucfirst(e($user['gender'])) ?></span></div><?php endif; ?>
                <div class="sidebar-stat"><span class="sidebar-stat-label">Activity</span><span class="sidebar-stat-value" style="font-size:0.78rem"><?= ucwords(str_replace('_', ' ', e($user['activity_level']))) ?></span></div>
                <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-sm w-full" style="margin-top:16px;text-align:center;display:block">Update via Calculator</a>
            </div>

            <!-- Suggested Workouts -->
            <?php if ($suggestions): ?>
            <div class="sidebar-card" style="position:static">
                <div class="sidebar-title">⚡ Suggested For You</div>
                <?php foreach ($suggestions as $i => $sw): ?>
                    <div style="padding:12px 0;border-bottom:1px solid var(--border);<?= $i === count($suggestions)-1 ? 'border-bottom:none' : '' ?>">
                        <div style="font-size:0.88rem;font-weight:600;color:var(--text-primary);margin-bottom:4px"><?= e($sw['title']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted)">⏱ <?= e($sw['duration_minutes']) ?> min &nbsp;·&nbsp; 🔥 <?= e($sw['calories_burned']) ?> kcal</div>
                        <a href="/fitness_planner/workouts/detail.php?id=<?= $sw['id'] ?>" style="font-size:0.78rem;color:var(--accent);display:block;margin-top:4px">View workout →</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Quick Links -->
            <div class="sidebar-card" style="position:static">
                <div class="sidebar-title">🔗 Quick Access</div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="/fitness_planner/workouts/index.php" class="btn btn-secondary btn-sm">💪 Browse Workouts</a>
                    <a href="/fitness_planner/meals/index.php" class="btn btn-secondary btn-sm">🥗 Meal Plans</a>
                    <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-sm">📊 Calculator + AI</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
