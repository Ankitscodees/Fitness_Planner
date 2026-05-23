<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM workouts WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    setFlash('Workout deleted successfully.', 'success');
    redirect('/fitness_planner/admin/workouts.php');
}

// Toggle featured
if (isset($_GET['toggle_featured'])) {
    $stmt = $db->prepare("UPDATE workouts SET is_featured = NOT is_featured WHERE id = ?");
    $stmt->execute([(int)$_GET['toggle_featured']]);
    redirect('/fitness_planner/admin/workouts.php');
}

// Fetch all workouts with category
$workouts = $db->query("
    SELECT w.*, c.name AS category_name
    FROM workouts w
    LEFT JOIN categories c ON c.id = w.category_id
    ORDER BY w.created_at DESC
")->fetchAll();

$pageTitle = 'Manage Workouts';
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
  <aside class="admin-sidebar">
    <div class="admin-logo"><span class="logo-icon">⚡</span><span>FitPro <em>Admin</em></span></div>
    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-link">📊 Dashboard</a>
      <a href="workouts.php" class="admin-nav-link active">🏋️ Workouts</a>
      <a href="meals.php" class="admin-nav-link">🥗 Meals</a>
      <a href="users.php" class="admin-nav-link">👥 Users</a>
      <a href="categories.php" class="admin-nav-link">🏷️ Categories</a>
      <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
      <a href="../index.php" class="admin-nav-link">← Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Workouts</h1>
      <a href="workout_form.php" class="btn btn-primary btn-sm">+ Add Workout</a>
    </div>

    <?= getFlash() ?>

    <div class="admin-card">
      <div class="admin-card-head">
        <h2><?= count($workouts) ?> Workouts</h2>
        <input type="text" id="adminSearch" placeholder="Search workouts…" class="admin-search-input">
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table" id="adminTable">
          <thead>
            <tr>
              <th data-sort="name">Name</th>
              <th>Category</th>
              <th>Difficulty</th>
              <th>Goal</th>
              <th>Duration</th>
              <th>Featured</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($workouts as $w): ?>
            <tr>
              <td><strong><?= e($w['name']) ?></strong></td>
              <td><?= e($w['category_name'] ?? '—') ?></td>
              <td>
                <span class="badge" style="background:<?= difficultyColor($w['difficulty']) ?>20;color:<?= difficultyColor($w['difficulty']) ?>">
                  <?= ucfirst(e($w['difficulty'])) ?>
                </span>
              </td>
              <td><?= goalLabel($w['goal_tag']) ?></td>
              <td><?= $w['duration_minutes'] ?> min</td>
              <td>
                <a href="workouts.php?toggle_featured=<?= $w['id'] ?>" title="Toggle featured">
                  <?= $w['is_featured'] ? '⭐' : '☆' ?>
                </a>
              </td>
              <td>
                <div class="action-btns">
                  <a href="workout_form.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="delete_id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                      data-confirm="Delete '<?= e($w['name']) ?>'?">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$workouts): ?>
            <tr><td colspan="7" style="text-align:center;opacity:.5;padding:2rem">No workouts yet. <a href="workout_form.php">Add one</a></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
