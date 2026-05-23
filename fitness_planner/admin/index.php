<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

// Stats
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalWorkouts = $db->query("SELECT COUNT(*) FROM workouts")->fetchColumn();
$totalMeals    = $db->query("SELECT COUNT(*) FROM meals")->fetchColumn();
$totalCats     = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Recent users
$recentUsers = $db->query("SELECT id, name, email, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent progress logs
$recentProgress = $db->query("
    SELECT up.*, u.name AS user_name
    FROM user_progress up
    JOIN users u ON u.id = up.user_id
    ORDER BY up.logged_at DESC LIMIT 8
")->fetchAll();

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — FitPro Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      <span class="logo-icon">⚡</span>
      <span>FitPro <em>Admin</em></span>
    </div>
    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-link active">📊 Dashboard</a>
      <a href="workouts.php" class="admin-nav-link">🏋️ Workouts</a>
      <a href="meals.php" class="admin-nav-link">🥗 Meals</a>
      <a href="users.php" class="admin-nav-link">👥 Users</a>
      <a href="categories.php" class="admin-nav-link">🏷️ Categories</a>
      <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
      <a href="../index.php" class="admin-nav-link">← Back to Site</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Dashboard</h1>
      <span class="admin-user-badge">👤 <?= e($_SESSION['user_name']) ?></span>
    </div>

    <?= getFlash() ?>

    <!-- Stat Cards -->
    <div class="admin-stats">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalUsers ?></div>
          <div class="stat-label">Members</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏋️</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalWorkouts ?></div>
          <div class="stat-label">Workouts</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🥗</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalMeals ?></div>
          <div class="stat-label">Meals</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏷️</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalCats ?></div>
          <div class="stat-label">Categories</div>
        </div>
      </div>
    </div>

    <!-- Two Column -->
    <div class="admin-two-col">
      <!-- Recent Users -->
      <div class="admin-card">
        <div class="admin-card-head">
          <h2>Recent Members</h2>
          <a href="users.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
            <tbody>
              <?php foreach ($recentUsers as $u): ?>
              <tr>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$recentUsers): ?><tr><td colspan="3" style="text-align:center;opacity:.5">No members yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Progress -->
      <div class="admin-card">
        <div class="admin-card-head">
          <h2>Recent Progress Logs</h2>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>User</th><th>Weight</th><th>BMI</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentProgress as $p): ?>
              <tr>
                <td><?= e($p['user_name']) ?></td>
                <td><?= $p['weight_kg'] ?> kg</td>
                <td><?= number_format($p['bmi'], 1) ?></td>
                <td><?= date('M j', strtotime($p['logged_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$recentProgress): ?><tr><td colspan="4" style="text-align:center;opacity:.5">No logs yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="admin-card" style="margin-top:1.5rem;">
      <div class="admin-card-head"><h2>Quick Actions</h2></div>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;padding:1rem 1.25rem">
        <a href="workout_form.php" class="btn btn-primary">+ Add Workout</a>
        <a href="meal_form.php" class="btn btn-primary">+ Add Meal</a>
        <a href="categories.php?action=add" class="btn btn-secondary">+ Add Category</a>
      </div>
    </div>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
