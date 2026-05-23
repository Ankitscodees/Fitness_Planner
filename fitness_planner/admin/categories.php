<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

$editCat = null;
$errors  = [];

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_POST['delete_id']]);
    setFlash('Category deleted.', 'success');
    redirect('/fitness_planner/admin/categories.php');
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_name'])) {
    $cat_name = trim($_POST['cat_name']);
    $cat_type = $_POST['cat_type'] ?? 'both';
    $edit_id  = (int)($_POST['edit_id'] ?? 0);

    if (!$cat_name) $errors[] = 'Category name is required.';

    if (!$errors) {
        if ($edit_id) {
            $db->prepare("UPDATE categories SET name=?, type=? WHERE id=?")
               ->execute([$cat_name, $cat_type, $edit_id]);
            setFlash('Category updated.', 'success');
        } else {
            $db->prepare("INSERT INTO categories (name, type) VALUES (?,?)")
               ->execute([$cat_name, $cat_type]);
            setFlash('Category added.', 'success');
        }
        redirect('/fitness_planner/admin/categories.php');
    }
}

// Load for edit
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}

// Fetch all + usage counts
$categories = $db->query("
    SELECT c.*,
      (SELECT COUNT(*) FROM workouts WHERE category_id = c.id) AS workout_count,
      (SELECT COUNT(*) FROM meals WHERE category_id = c.id) AS meal_count
    FROM categories c
    ORDER BY c.name
")->fetchAll();

$pageTitle = 'Manage Categories';
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
      <a href="meals.php" class="admin-nav-link">🥗 Meals</a>
      <a href="users.php" class="admin-nav-link">👥 Users</a>
      <a href="categories.php" class="admin-nav-link active">🏷️ Categories</a>
      <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
      <a href="../index.php" class="admin-nav-link">← Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Categories</h1>
    </div>

    <?= getFlash() ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

      <!-- List -->
      <div class="admin-card">
        <div class="admin-card-head">
          <h2><?= count($categories) ?> Categories</h2>
          <input type="text" id="adminSearch" placeholder="Search…" class="admin-search-input">
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table" id="adminTable">
            <thead>
              <tr><th>Name</th><th>Type</th><th>Workouts</th><th>Meals</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
              <tr>
                <td><strong><?= e($cat['name']) ?></strong></td>
                <td><?= ucfirst(e($cat['type'] ?? 'both')) ?></td>
                <td><?= $cat['workout_count'] ?></td>
                <td><?= $cat['meal_count'] ?></td>
                <td>
                  <div class="action-btns">
                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="delete_id" value="<?= $cat['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="Delete category '<?= e($cat['name']) ?>'? Linked items will lose this category.">
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$categories): ?>
              <tr><td colspan="5" style="text-align:center;opacity:.5;padding:2rem">No categories yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-head">
          <h2><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
          <?php if ($editCat): ?>
          <a href="categories.php" class="btn btn-sm btn-secondary">+ New</a>
          <?php endif; ?>
        </div>

        <?php if ($errors): ?>
        <div class="flash-message flash-error" style="margin:0 1.25rem .5rem">
          <?php foreach ($errors as $err): ?><div>⚠ <?= e($err) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="admin-form" style="padding:1.25rem">
          <?php if ($editCat): ?>
          <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>">
          <?php endif; ?>

          <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="cat_name"
              value="<?= e($editCat['name'] ?? ($_POST['cat_name'] ?? '')) ?>"
              placeholder="e.g. Strength Training" required>
          </div>

          <div class="form-group">
            <label>Type</label>
            <select name="cat_type">
              <?php
              $ct = $editCat['type'] ?? ($_POST['cat_type'] ?? 'both');
              foreach (['both' => 'Workouts & Meals', 'workout' => 'Workouts only', 'meal' => 'Meals only'] as $v => $l):
              ?>
              <option value="<?= $v ?>" <?= $ct === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editCat ? 'Update' : 'Add Category' ?></button>
            <?php if ($editCat): ?><a href="categories.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
