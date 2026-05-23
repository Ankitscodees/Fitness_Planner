<?php
// ============================================================
// login.php
// ============================================================
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect('/fitness_planner/dashboard.php');

$error = '';
$old   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $old      = ['email' => $email];

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($user['role'] === 'admin' ? '/fitness_planner/admin/index.php' : '/fitness_planner/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-logo">
            <a href="/fitness_planner/index.php" class="nav-logo" style="justify-content:center">
                <span class="logo-icon">💪</span>
                <span class="logo-text">FIT<span class="logo-accent">PRO</span></span>
            </a>
            <p>Welcome back, champion</p>
        </div>

        <h2 class="form-title">Sign In</h2>
        <p class="form-subtitle">Continue your fitness journey</p>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:20px;animation:none">
                <span class="flash-icon">❌</span> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" value="<?= e($old['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Your password" required>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top:8px">Sign In →</button>
        </form>

        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px;margin-top:24px;font-size:0.82rem;color:var(--text-muted)">
            <strong style="color:var(--text-secondary)">Demo Credentials:</strong><br>
            Admin: <code style="color:var(--accent)">admin@fitpro.com</code> / <code style="color:var(--accent)">password</code><br>
            User:  <code style="color:var(--accent)">john@example.com</code> / <code style="color:var(--accent)">password</code>
        </div>

        <div class="form-divider">or</div>
        <p style="text-align:center;font-size:0.88rem;color:var(--text-secondary)">
            New here? <a href="/fitness_planner/register.php" class="form-link">Create an account</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
