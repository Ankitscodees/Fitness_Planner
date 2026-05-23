<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

$editMode = false;
$meal = [
    'id' => '', 'name' => '', 'category_id' => '', 'description' => '',
    'calories' => '', 'protein_g' => '', 'carbs_g' => '', 'fat_g' => '',
    'fiber_g' => '', 'prep_time_minutes' => 15, 'goal_tag' => 'fitness',
    'meal_type' => 'lunch', 'image_url' => '', 'ingredients' => '',
    'instructions' => '', 'is_featured' => 0
];

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM meals WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $found = $stmt->fetch();
    if ($found) { $meal = $found; $editMode = true; }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = $_POST;
    $name         = trim($f['name'] ?? '');
    $category_id  = (int)($f['category_id'] ?? 0);
    $description  = trim($f['description'] ?? '');
    $calories     = (int)($f['calories'] ?? 0);
    $protein_g    = (float)($f['protein_g'] ?? 0);
    $carbs_g      = (float)($f['carbs_g'] ?? 0);
    $fat_g        = (float)($f['fat_g'] ?? 0);
    $fiber_g      = (float)($f['fiber_g'] ?? 0);
    $prep_time    = (int)($f['prep_time_minutes'] ?? 15);
    $goal_tag     = $f['goal_tag'] ?? 'fitness';
    $meal_type    = $f['meal_type'] ?? 'lunch';
    $image_url    = trim($f['image_url'] ?? '');
    $ingredients  = trim($f['ingredients'] ?? '');
    $instructions = trim($f['instructions'] ?? '');
    $is_featured  = isset($f['is_featured']) ? 1 : 0;
    $edit_id      = (int)($f['edit_id'] ?? 0);

    if (!$name)        $errors[] = 'Meal name is required.';
    if (!$description) $errors[] = 'Description is required.';
    if ($calories < 1) $errors[] = 'Calories must be provided.';

    if (!$errors) {
        if ($edit_id) {
            $stmt = $db->prepare("
                UPDATE meals SET
                  name=?, category_id=?, description=?, calories=?,
                  protein_g=?, carbs_g=?, fat_g=?, fiber_g=?,
                  prep_time_minutes=?, goal_tag=?, meal_type=?,
                  image_url=?, ingredients=?, instructions=?, is_featured=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $category_id ?: null, $description, $calories,
                $protein_g, $carbs_g, $fat_g, $fiber_g,
                $prep_time, $goal_tag, $meal_type,
                $image_url ?: null, $ingredients ?: null, $instructions ?: null,
                $is_featured, $edit_id
            ]);
            setFlash('Meal updated!', 'success');
        } else {
            $stmt = $db->prepare("
                INSERT INTO meals
                  (name, category_id, description, calories, protein_g, carbs_g,
                   fat_g, fiber_g, prep_time_minutes, goal_tag, meal_type,
                   image_url, ingredients, instructions, is_featured)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $name, $category_id ?: null, $description, $calories,
                $protein_g, $carbs_g, $fat_g, $fiber_g,
                $prep_time, $goal_tag, $meal_type,
                $image_url ?: null, $ingredients ?: null, $instructions ?: null,
                $is_featured
            ]);
            setFlash('Meal added!', 'success');
        }
        redirect('/fitness_planner/admin/meals.php');
    }
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$pageTitle   = $editMode ? 'Edit Meal' : 'Add Meal';
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
      <h1 class="admin-page-title"><?= e($pageTitle) ?></h1>
      <a href="meals.php" class="btn btn-sm btn-secondary">← Back</a>
    </div>

    <?php if ($errors): ?>
    <div class="flash-message flash-error">
      <?php foreach ($errors as $err): ?><div>⚠ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:800px">
      <form method="POST" class="admin-form">
        <?php if ($editMode): ?>
        <input type="hidden" name="edit_id" value="<?= $meal['id'] ?>">
        <?php endif; ?>

        <div class="form-row-2">
          <div class="form-group">
            <label>Meal Name *</label>
            <input type="text" name="name" value="<?= e($meal['name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $meal['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Description *</label>
          <textarea name="description" rows="3" required><?= e($meal['description']) ?></textarea>
        </div>

        <!-- Macros -->
        <div class="form-section-label">Nutrition (per serving)</div>
        <div class="form-row-4">
          <div class="form-group">
            <label>Calories *</label>
            <input type="number" name="calories" value="<?= (int)$meal['calories'] ?>" min="1" required>
          </div>
          <div class="form-group">
            <label>Protein (g)</label>
            <input type="number" name="protein_g" value="<?= (float)$meal['protein_g'] ?>" min="0" step="0.1">
          </div>
          <div class="form-group">
            <label>Carbs (g)</label>
            <input type="number" name="carbs_g" value="<?= (float)$meal['carbs_g'] ?>" min="0" step="0.1">
          </div>
          <div class="form-group">
            <label>Fat (g)</label>
            <input type="number" name="fat_g" value="<?= (float)$meal['fat_g'] ?>" min="0" step="0.1">
          </div>
        </div>

        <div class="form-row-3">
          <div class="form-group">
            <label>Fiber (g)</label>
            <input type="number" name="fiber_g" value="<?= (float)$meal['fiber_g'] ?>" min="0" step="0.1">
          </div>
          <div class="form-group">
            <label>Prep Time (min)</label>
            <input type="number" name="prep_time_minutes" value="<?= (int)$meal['prep_time_minutes'] ?>" min="1">
          </div>
          <div class="form-group">
            <label>Meal Type</label>
            <select name="meal_type">
              <?php foreach (['breakfast','lunch','dinner','snack'] as $mt): ?>
              <option value="<?= $mt ?>" <?= ($meal['meal_type'] ?? '') === $mt ? 'selected' : '' ?>><?= ucfirst($mt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Goal Tag</label>
            <select name="goal_tag">
              <option value="fitness"     <?= ($meal['goal_tag'] ?? '') === 'fitness'     ? 'selected' : '' ?>>General Fitness</option>
              <option value="fat_loss"    <?= ($meal['goal_tag'] ?? '') === 'fat_loss'    ? 'selected' : '' ?>>Fat Loss</option>
              <option value="weight_gain" <?= ($meal['goal_tag'] ?? '') === 'weight_gain' ? 'selected' : '' ?>>Weight Gain / Muscle</option>
            </select>
          </div>
          <div class="form-group">
            <label>Image URL</label>
            <input type="url" name="image_url" value="<?= e($meal['image_url']) ?>" placeholder="https://...">
          </div>
        </div>

        <div class="form-group">
          <label>Ingredients</label>
          <textarea name="ingredients" rows="4" placeholder="1 cup oats&#10;2 eggs&#10;…"><?= e($meal['ingredients']) ?></textarea>
        </div>

        <div class="form-group">
          <label>Cooking Instructions</label>
          <textarea name="instructions" rows="4" placeholder="1. Preheat…&#10;2. Mix…"><?= e($meal['instructions']) ?></textarea>
        </div>

        <div class="form-group form-checkbox">
          <label>
            <input type="checkbox" name="is_featured" value="1" <?= $meal['is_featured'] ? 'checked' : '' ?>>
            Feature this meal on the homepage
          </label>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><?= $editMode ? 'Update Meal' : 'Add Meal' ?></button>
          <a href="meals.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
