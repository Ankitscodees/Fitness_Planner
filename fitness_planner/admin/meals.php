<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    setFlash('Meal deleted.', 'success');
    redirect('/fitness_planner/admin/meals.php');
}

// Toggle featured
if (isset($_GET['toggle_featured'])) {
    $stmt = $db->prepare("UPDATE meals SET is_featured = NOT is_featured WHERE id = ?");
    $stmt->execute([(int)$_GET['toggle_featured']]);
    redirect('/fitness_planner/admin/meals.php');
}

$meals = $db->query("
    SELECT m.*, c.name AS category_name
    FROM meals m
    LEFT JOIN categories c ON c.id = m.category_id
    ORDER BY m.created_at DESC
")->fetchAll();

$pageTitle = 'Manage Meals';
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
      <a href="workouts.php" class="admin-nav-link">🏋️ Workouts</a>
      <a href="meals.php" class="admin-nav-link active">🥗 Meals</a>
      <a href="users.php" class="admin-nav-link">👥 Users</a>
      <a href="categories.php" class="admin-nav-link">🏷️ Categories</a>
      <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
      <a href="../index.php" class="admin-nav-link">← Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Meals</h1>
      <a href="meal_form.php" class="btn btn-primary btn-sm">+ Add Meal</a>
    </div>

    <?= getFlash() ?>

    <div class="admin-card">
      <div class="admin-card-head">
        <h2><?= count($meals) ?> Meals</h2>
        <input type="text" id="adminSearch" placeholder="Search meals…" class="admin-search-input">
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table" id="adminTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Category</th>
              <th>Calories</th>
              <th>Protein</th>
              <th>Carbs</th>
              <th>Fat</th>
              <th>Goal</th>
              <th>Featured</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($meals as $m): ?>
            <tr>
              <td><strong><?= e($m['name']) ?></strong></td>
              <td><?= e($m['category_name'] ?? '—') ?></td>
              <td><?= $m['calories'] ?> kcal</td>
              <td><?= $m['protein_g'] ?>g</td>
              <td><?= $m['carbs_g'] ?>g</td>
              <td><?= $m['fat_g'] ?>g</td>
              <td><?= goalLabel($m['goal_tag']) ?></td>
              <td>
                <a href="meals.php?toggle_featured=<?= $m['id'] ?>">
                  <?= $m['is_featured'] ? '⭐' : '☆' ?>
                </a>
              </td>
              <td>
                <div class="action-btns">
                  <a href="meal_form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                      data-confirm="Delete '<?= e($m['name']) ?>'?">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$meals): ?>
            <tr><td colspan="9" style="text-align:center;opacity:.5;padding:2rem">No meals yet. <a href="meal_form.php">Add one</a></td></tr>
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
