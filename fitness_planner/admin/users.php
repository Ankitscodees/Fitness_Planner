<?php
require_once '../includes/functions.php';
requireAdmin();
$db = getDB();

// Toggle active status
if (isset($_GET['toggle_active'])) {
    $uid = (int)$_GET['toggle_active'];
    // Don't let admin deactivate themselves
    if ($uid !== (int)$_SESSION['user_id']) {
        $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$uid]);
    }
    redirect('/fitness_planner/admin/users.php');
}

// Toggle role
if (isset($_GET['toggle_role'])) {
    $uid = (int)$_GET['toggle_role'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $db->prepare("UPDATE users SET role = IF(role='admin','user','admin') WHERE id = ?")->execute([$uid]);
    }
    redirect('/fitness_planner/admin/users.php');
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $uid = (int)$_POST['delete_id'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $db->prepare("DELETE FROM user_progress WHERE user_id = ?")->execute([$uid]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        setFlash('User deleted.', 'success');
    } else {
        setFlash('You cannot delete your own account.', 'error');
    }
    redirect('/fitness_planner/admin/users.php');
}

$users = $db->query("
    SELECT u.*,
      (SELECT COUNT(*) FROM user_progress WHERE user_id = u.id) AS log_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Manage Users';
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
      <a href="users.php" class="admin-nav-link active">👥 Users</a>
      <a href="categories.php" class="admin-nav-link">🏷️ Categories</a>
      <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
      <a href="../index.php" class="admin-nav-link">← Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Users</h1>
    </div>

    <?= getFlash() ?>

    <div class="admin-card">
      <div class="admin-card-head">
        <h2><?= count($users) ?> Accounts</h2>
        <input type="text" id="adminSearch" placeholder="Search users…" class="admin-search-input">
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table" id="adminTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Progress Logs</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u):
              $isSelf = $u['id'] == $_SESSION['user_id'];
            ?>
            <tr>
              <td>
                <strong><?= e($u['name']) ?></strong>
                <?php if ($isSelf): ?> <span class="badge" style="background:#e8ff0020;color:#e8ff00;font-size:.65rem">You</span><?php endif; ?>
              </td>
              <td><?= e($u['email']) ?></td>
              <td>
                <span class="badge" style="background:<?= $u['role']==='admin' ? '#e8ff0020' : '#ffffff10' ?>;color:<?= $u['role']==='admin' ? '#e8ff00' : '#ccc' ?>">
                  <?= ucfirst(e($u['role'])) ?>
                </span>
              </td>
              <td>
                <span class="badge" style="background:<?= $u['is_active'] ? '#00ff8820' : '#ff444420' ?>;color:<?= $u['is_active'] ? '#00ff88' : '#ff4444' ?>">
                  <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td><?= $u['log_count'] ?></td>
              <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div class="action-btns">
                  <?php if (!$isSelf): ?>
                  <a href="users.php?toggle_active=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">
                    <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </a>
                  <a href="users.php?toggle_role=<?= $u['id'] ?>"
                     class="btn btn-sm btn-secondary"
                     data-confirm="Change role of '<?= e($u['name']) ?>' to <?= $u['role']==='admin'?'user':'admin' ?>?">
                    <?= $u['role'] === 'admin' ? 'Revoke Admin' : 'Make Admin' ?>
                  </a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                      data-confirm="Delete user '<?= e($u['name']) ?>'? This cannot be undone.">Delete</button>
                  </form>
                  <?php else: ?>
                  <span style="opacity:.4;font-size:.8rem">Protected</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
