<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

$editMode = false;
$workout  = [
    'id' => '', 'name' => '', 'category_id' => '', 'description' => '',
    'difficulty' => 'beginner', 'duration_minutes' => 30, 'calories_burned' => '',
    'goal_tag' => 'fitness', 'image_url' => '', 'video_url' => '',
    'instructions' => '', 'equipment' => '', 'is_featured' => 0
];

// Load for edit
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM workouts WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $found = $stmt->fetch();
    if ($found) {
        $workout  = $found;
        $editMode = true;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $description  = trim($_POST['description'] ?? '');
    $difficulty   = $_POST['difficulty'] ?? 'beginner';
    $duration     = (int)($_POST['duration_minutes'] ?? 30);
    $calories     = (int)($_POST['calories_burned'] ?? 0);
    $goal_tag     = $_POST['goal_tag'] ?? 'fitness';
    $image_url    = trim($_POST['image_url'] ?? '');
    $video_url    = trim($_POST['video_url'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $equipment    = trim($_POST['equipment'] ?? '');
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
    $edit_id      = (int)($_POST['edit_id'] ?? 0);

    if (!$name)        $errors[] = 'Workout name is required.';
    if (!$description) $errors[] = 'Description is required.';
    if ($duration < 1) $errors[] = 'Duration must be at least 1 minute.';

    if (!$errors) {
        if ($edit_id) {
            $stmt = $db->prepare("
                UPDATE workouts SET
                  name=?, category_id=?, description=?, difficulty=?,
                  duration_minutes=?, calories_burned=?, goal_tag=?,
                  image_url=?, video_url=?, instructions=?, equipment=?, is_featured=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $category_id ?: null, $description, $difficulty,
                $duration, $calories ?: null, $goal_tag,
                $image_url ?: null, $video_url ?: null, $instructions ?: null,
                $equipment ?: null, $is_featured, $edit_id
            ]);
            setFlash('Workout updated successfully!', 'success');
        } else {
            $stmt = $db->prepare("
                INSERT INTO workouts
                  (name, category_id, description, difficulty, duration_minutes,
                   calories_burned, goal_tag, image_url, video_url, instructions,
                   equipment, is_featured)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $name, $category_id ?: null, $description, $difficulty,
                $duration, $calories ?: null, $goal_tag,
                $image_url ?: null, $video_url ?: null, $instructions ?: null,
                $equipment ?: null, $is_featured
            ]);
            setFlash('Workout added successfully!', 'success');
        }
        redirect('/fitness_planner/admin/workouts.php');
    }

    // Re-populate on error
    $workout = array_merge($workout, compact(
        'name','category_id','description','difficulty','duration',
        'calories','goal_tag','image_url','video_url','instructions','equipment','is_featured'
    ));
    $workout['duration_minutes'] = $duration;
    $workout['calories_burned']  = $calories;
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$pageTitle   = $editMode ? 'Edit Workout' : 'Add Workout';
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
      <h1 class="admin-page-title"><?= e($pageTitle) ?></h1>
      <a href="workouts.php" class="btn btn-sm btn-secondary">← Back</a>
    </div>

    <?php if ($errors): ?>
    <div class="flash-message flash-error">
      <?php foreach ($errors as $err): ?><div>⚠ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:800px">
      <form method="POST" class="admin-form">
        <?php if ($editMode): ?>
        <input type="hidden" name="edit_id" value="<?= $workout['id'] ?>">
        <?php endif; ?>

        <div class="form-row-2">
          <div class="form-group">
            <label>Workout Name *</label>
            <input type="text" name="name" value="<?= e($workout['name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $workout['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Description *</label>
          <textarea name="description" rows="3" required><?= e($workout['description']) ?></textarea>
        </div>

        <div class="form-row-3">
          <div class="form-group">
            <label>Difficulty</label>
            <select name="difficulty">
              <?php foreach (['beginner','intermediate','advanced'] as $d): ?>
              <option value="<?= $d ?>" <?= $workout['difficulty'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Duration (min)</label>
            <input type="number" name="duration_minutes" value="<?= (int)$workout['duration_minutes'] ?>" min="1" required>
          </div>
          <div class="form-group">
            <label>Calories Burned</label>
            <input type="number" name="calories_burned" value="<?= (int)$workout['calories_burned'] ?>" min="0">
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Goal Tag</label>
            <select name="goal_tag">
              <option value="fitness"     <?= $workout['goal_tag'] === 'fitness'     ? 'selected' : '' ?>>General Fitness</option>
              <option value="fat_loss"    <?= $workout['goal_tag'] === 'fat_loss'    ? 'selected' : '' ?>>Fat Loss</option>
              <option value="weight_gain" <?= $workout['goal_tag'] === 'weight_gain' ? 'selected' : '' ?>>Weight Gain / Muscle</option>
            </select>
          </div>
          <div class="form-group">
            <label>Equipment (comma-separated)</label>
            <input type="text" name="equipment" value="<?= e($workout['equipment']) ?>" placeholder="e.g. Dumbbells, Barbell">
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Image URL</label>
            <input type="url" name="image_url" value="<?= e($workout['image_url']) ?>" placeholder="https://...">
          </div>
          <div class="form-group">
            <label>Video URL</label>
            <input type="url" name="video_url" value="<?= e($workout['video_url']) ?>" placeholder="https://youtube.com/...">
          </div>
        </div>

        <div class="form-group">
          <label>Step-by-step Instructions</label>
          <textarea name="instructions" rows="5" placeholder="1. Warm up for 5 min&#10;2. …"><?= e($workout['instructions']) ?></textarea>
        </div>

        <div class="form-group form-checkbox">
          <label>
            <input type="checkbox" name="is_featured" value="1" <?= $workout['is_featured'] ? 'checked' : '' ?>>
            Feature this workout on the homepage
          </label>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><?= $editMode ? 'Update Workout' : 'Add Workout' ?></button>
          <a href="workouts.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
