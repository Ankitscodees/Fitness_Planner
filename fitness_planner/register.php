<?php
// ============================================================
// register.php
// ============================================================
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect('/fitness_planner/dashboard.php');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $gender   = $_POST['gender'] ?? '';
    $age      = (int)($_POST['age'] ?? 0);
    $weight   = (float)($_POST['weight'] ?? 0);
    $height   = (float)($_POST['height'] ?? 0);
    $goal     = $_POST['goal'] ?? 'fitness';
    $activity = $_POST['activity_level'] ?? 'moderately_active';

    $old = compact('name','email','gender','age','weight','height','goal','activity');

    // Validate
    if (empty($name))                          $errors['name']     = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';
    if (strlen($password) < 6)                 $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                $errors['confirm']  = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = getDB();
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, gender, age, weight, height, goal, activity_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $hash, $gender ?: null, $age ?: null, $weight ?: null, $height ?: null, $goal, $activity]);
            setFlash('success', 'Account created! Please log in.');
            redirect('/fitness_planner/login.php');
        }
    }
}

$pageTitle = 'Register';
?>

<div class="form-page">
    <div class="form-card" style="max-width:520px">
        <div class="form-logo">
            <a href="/fitness_planner/index.php" class="nav-logo" style="justify-content:center">
                <span class="logo-icon">💪</span>
                <span class="logo-text">FIT<span class="logo-accent">PRO</span></span>
            </a>
            <p>Start your transformation today</p>
        </div>

        <h2 class="form-title">Create Account</h2>
        <p class="form-subtitle">Join thousands reaching their fitness goals</p>

        <form method="POST" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" value="<?= e($old['name'] ?? '') ?>" required>
                    <?php if (isset($errors['name'])): ?><div class="form-error"><?= e($errors['name']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" value="<?= e($old['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                    <div style="background:var(--border);border-radius:4px;height:4px;margin-top:8px;overflow:hidden">
                        <div class="strength-fill" style="height:100%;width:0%;transition:0.3s;border-radius:4px"></div>
                    </div>
                    <span id="strengthLabel" style="font-size:0.75rem;margin-top:4px;display:block"></span>
                    <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                    <?php if (isset($errors['confirm'])): ?><div class="form-error"><?= e($errors['confirm']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="">Select...</option>
                        <option value="male"   <?= ($old['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other"  <?= ($old['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="age">Age</label>
                    <input type="number" id="age" name="age" class="form-control" placeholder="25" min="10" max="120" value="<?= e($old['age'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" class="form-control" placeholder="70" step="0.1" min="20" max="500" value="<?= e($old['weight'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="height">Height (cm)</label>
                    <input type="number" id="height" name="height" class="form-control" placeholder="175" step="0.1" min="100" max="250" value="<?= e($old['height'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="goal">Fitness Goal</label>
                <select id="goal" name="goal" class="form-control">
                    <option value="fitness"     <?= ($old['goal'] ?? 'fitness') === 'fitness'     ? 'selected' : '' ?>>⚡ General Fitness</option>
                    <option value="fat_loss"    <?= ($old['goal'] ?? '') === 'fat_loss'    ? 'selected' : '' ?>>🔥 Fat Loss</option>
                    <option value="weight_gain" <?= ($old['goal'] ?? '') === 'weight_gain' ? 'selected' : '' ?>>💪 Weight Gain</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="activity_level">Activity Level</label>
                <select id="activity_level" name="activity_level" class="form-control">
                    <option value="sedentary"         <?= ($old['activity_level'] ?? '') === 'sedentary'         ? 'selected' : '' ?>>🪑 Sedentary (little/no exercise)</option>
                    <option value="lightly_active"    <?= ($old['activity_level'] ?? '') === 'lightly_active'    ? 'selected' : '' ?>>🚶 Lightly Active (1-3 days/week)</option>
                    <option value="moderately_active" <?= ($old['activity_level'] ?? 'moderately_active') === 'moderately_active' ? 'selected' : '' ?>>🏃 Moderately Active (3-5 days/week)</option>
                    <option value="very_active"       <?= ($old['activity_level'] ?? '') === 'very_active'       ? 'selected' : '' ?>>🏋️ Very Active (6-7 days/week)</option>
                    <option value="extra_active"      <?= ($old['activity_level'] ?? '') === 'extra_active'      ? 'selected' : '' ?>>⚡ Extra Active (physical job)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top:8px">Create Account →</button>
        </form>

        <div class="form-divider">or</div>
        <p style="text-align:center;font-size:0.88rem;color:var(--text-secondary)">
            Already have an account? <a href="/fitness_planner/login.php" class="form-link">Log in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
