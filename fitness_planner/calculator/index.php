<?php
// ============================================================
// calculator/index.php — BMI + BMR + TDEE + AI Recommendation
// ============================================================
require_once __DIR__ . '/../includes/header.php';

$user       = isLoggedIn() ? getCurrentUser() : null;
$aiRec      = null;
$calcDone   = false;
$bmi = $bmr = $tdee = 0;

// Handle server-side AI recommendation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_ai'])) {
    $weight   = (float)($_POST['weight'] ?? 0);
    $height   = (float)($_POST['height'] ?? 0);
    $age      = (int)($_POST['age'] ?? 0);
    $gender   = $_POST['gender'] ?? '';
    $activity = $_POST['activity'] ?? 'moderately_active';
    $goal     = $_POST['goal'] ?? 'fitness';

    if ($weight > 0 && $height > 0 && $age > 0 && $gender) {
        $bmi  = calculateBMI($weight, $height);
        $bmr  = calculateBMR($weight, $height, $age, $gender);
        $tdee = calculateTDEE($bmr, $activity);
        $aiRec = getAIRecommendation($goal, $bmi, $tdee);
        $calcDone = true;

        // Fetch recommended workouts and meals
        $pdo = getDB();
        $wStmt = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.goal_tag = ? OR w.goal_tag = 'all' ORDER BY w.is_featured DESC LIMIT 3");
        $wStmt->execute([$aiRec['workout_tag']]);
        $aiWorkouts = $wStmt->fetchAll();

        $mStmt = $pdo->prepare("SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE m.goal_tag = ? OR m.goal_tag = 'all' ORDER BY m.is_featured DESC LIMIT 3");
        $mStmt->execute([$aiRec['meal_tag']]);
        $aiMeals = $mStmt->fetchAll();
    }
}

$pageTitle = 'BMI & TDEE Calculator';
?>

<div class="page-header">
    <div class="section-label">📊 Tools</div>
    <h1 class="page-header-title">BMI + TDEE Calculator</h1>
    <p class="page-header-subtitle">Calculate your health metrics and get AI-powered recommendations</p>
</div>

<div class="calc-container">

    <!-- Calculator Form -->
    <div class="calc-card">
        <div class="calc-title">🧮 Your Health Metrics</div>
        <p class="calc-subtitle">Fill in your details to calculate BMI, BMR, TDEE, and get personalized recommendations</p>

        <div id="calcError" class="flash-message flash-error hidden" style="position:static;transform:none;margin-bottom:16px;animation:none"></div>

        <form id="calcForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_weight">Weight (kg) *</label>
                    <input type="number" id="c_weight" name="weight" class="form-control" placeholder="e.g. 75" min="20" max="500" step="0.1" value="<?= e($user['weight'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_height">Height (cm) *</label>
                    <input type="number" id="c_height" name="height" class="form-control" placeholder="e.g. 175" min="100" max="250" step="0.1" value="<?= e($user['height'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_age">Age *</label>
                    <input type="number" id="c_age" name="age" class="form-control" placeholder="e.g. 28" min="10" max="120" value="<?= e($user['age'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_gender">Gender *</label>
                    <select id="c_gender" name="gender" class="form-control" required>
                        <option value="">Select...</option>
                        <option value="male"   <?= ($user['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other"                                                             >Other (uses female formula)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_activity">Activity Level *</label>
                    <select id="c_activity" name="activity" class="form-control" required>
                        <option value="sedentary"         <?= ($user['activity_level'] ?? '') === 'sedentary'         ? 'selected':'' ?>>🪑 Sedentary (little/no exercise)</option>
                        <option value="lightly_active"    <?= ($user['activity_level'] ?? '') === 'lightly_active'    ? 'selected':'' ?>>🚶 Lightly Active (1-3 days/week)</option>
                        <option value="moderately_active" <?= ($user['activity_level'] ?? 'moderately_active') === 'moderately_active' ? 'selected':'' ?>>🏃 Moderately Active (3-5 days/week)</option>
                        <option value="very_active"       <?= ($user['activity_level'] ?? '') === 'very_active'       ? 'selected':'' ?>>🏋️ Very Active (6-7 days/week)</option>
                        <option value="extra_active"      <?= ($user['activity_level'] ?? '') === 'extra_active'      ? 'selected':'' ?>>⚡ Extra Active (physical job + exercise)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_goal_js">Fitness Goal</label>
                    <select id="c_goal_js" class="form-control">
                        <option value="fitness"     <?= ($user['goal'] ?? 'fitness') === 'fitness'     ? 'selected':'' ?>>⚡ General Fitness</option>
                        <option value="fat_loss"    <?= ($user['goal'] ?? '') === 'fat_loss'    ? 'selected':'' ?>>🔥 Fat Loss</option>
                        <option value="weight_gain" <?= ($user['goal'] ?? '') === 'weight_gain' ? 'selected':'' ?>>💪 Weight Gain</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Calculate My Metrics →</button>
        </form>

        <!-- JS Results -->
        <div id="calcResults" class="hidden" style="margin-top:32px;border-top:1px solid var(--border);padding-top:28px">
            <h3 style="font-family:var(--font-display);font-size:1.4rem;margin-bottom:20px">Your Results</h3>
            <div class="results-grid">
                <div class="result-item highlighted">
                    <div class="result-label">BMI</div>
                    <div class="result-value" id="res_bmi">—</div>
                    <div class="result-unit" id="bmi_cat">—</div>
                </div>
                <div class="result-item">
                    <div class="result-label">BMR</div>
                    <div class="result-value" id="res_bmr">—</div>
                    <div class="result-unit">kcal/day</div>
                </div>
                <div class="result-item highlighted">
                    <div class="result-label">TDEE</div>
                    <div class="result-value" id="res_tdee">—</div>
                    <div class="result-unit">kcal/day</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Fat Loss</div>
                    <div class="result-value" style="color:var(--red)" id="res_fat">—</div>
                    <div class="result-unit">kcal/day</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Weight Gain</div>
                    <div class="result-value" style="color:var(--green)" id="res_gain">—</div>
                    <div class="result-unit">kcal/day</div>
                </div>
            </div>

            <div style="margin:20px 0">
                <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:6px">
                    <span style="color:#60a5fa">Underweight (&lt;18.5)</span>
                    <span style="color:#4ade80">Normal (18.5–24.9)</span>
                    <span style="color:#fbbf24">Overweight (25–29.9)</span>
                    <span style="color:#f87171">Obese (30+)</span>
                </div>
                <div class="bmi-bar">
                    <div class="bmi-indicator" id="bmiIndicator" style="left:2%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields for server-side AI request -->
    <form method="POST" id="aiForm">
        <input type="hidden" name="get_ai" value="1">
        <input type="hidden" name="weight"   id="c_bmi_val">
        <input type="hidden" name="height"   id="c_bmr_val">
        <input type="hidden" name="age"      id="c_tdee_val">
        <!-- Note: For full server-side AI, user fills the form above and a separate dedicated section is shown -->
    </form>

    <!-- AI Recommendation Section (PHP/Server-side) -->
    <div class="calc-card">
        <div class="calc-title">🤖 AI Recommendation Engine</div>
        <p class="calc-subtitle">Get a personalized plan based on your goal and calculated metrics</p>

        <form method="POST">
            <input type="hidden" name="get_ai" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Weight (kg) *</label>
                    <input type="number" name="weight" class="form-control" placeholder="75" step="0.1" min="20" max="500" value="<?= e($user['weight'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm) *</label>
                    <input type="number" name="height" class="form-control" placeholder="175" step="0.1" min="100" max="250" value="<?= e($user['height'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Age *</label>
                    <input type="number" name="age" class="form-control" placeholder="28" min="10" max="120" value="<?= e($user['age'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-control" required>
                        <option value="">Select...</option>
                        <option value="male"   <?= ($user['gender'] ?? '') === 'male'   ? 'selected':'' ?>>Male</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected':'' ?>>Female</option>
                        <option value="other"  >Other</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Activity Level *</label>
                    <select name="activity" class="form-control" required>
                        <option value="sedentary">Sedentary</option>
                        <option value="lightly_active">Lightly Active</option>
                        <option value="moderately_active" selected>Moderately Active</option>
                        <option value="very_active">Very Active</option>
                        <option value="extra_active">Extra Active</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Your Goal *</label>
                    <select name="goal" class="form-control">
                        <option value="fitness"     <?= ($user['goal'] ?? 'fitness') === 'fitness'     ? 'selected':'' ?>>⚡ General Fitness</option>
                        <option value="fat_loss"    <?= ($user['goal'] ?? '') === 'fat_loss'    ? 'selected':'' ?>>🔥 Fat Loss</option>
                        <option value="weight_gain" <?= ($user['goal'] ?? '') === 'weight_gain' ? 'selected':'' ?>>💪 Weight Gain</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">🤖 Generate AI Plan →</button>
        </form>
    </div>

    <!-- AI Results -->
    <?php if ($calcDone && $aiRec): ?>
    <div class="ai-rec-card">
        <div class="ai-rec-header">
            <div class="ai-rec-icon">🤖</div>
            <div>
                <div class="ai-rec-title">Your Personalized Plan</div>
                <div class="ai-rec-subtitle">
                    Goal: <strong style="color:var(--accent)"><?= goalLabel($aiRec['goal']) ?></strong> &nbsp;·&nbsp;
                    BMI: <strong><?= number_format($bmi, 1) ?></strong> &nbsp;·&nbsp;
                    TDEE: <strong><?= number_format($tdee) ?> kcal/day</strong>
                </div>
            </div>
        </div>

        <!-- Daily Calorie Target -->
        <div style="background:var(--bg-card);border:1px solid rgba(232,255,0,0.2);border-radius:var(--radius-md);padding:20px;margin-bottom:24px;text-align:center">
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:8px">Daily Calorie Target</div>
            <div style="font-family:var(--font-display);font-size:3.5rem;color:var(--accent);line-height:1"><?= number_format($aiRec['calories']) ?></div>
            <div style="font-size:0.88rem;color:var(--text-secondary);margin-top:4px">kcal per day</div>
        </div>

        <!-- Macros -->
        <h3 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:16px">Daily Macros</h3>
        <div class="macro-grid">
            <div class="macro-item">
                <div class="macro-value"><?= $aiRec['calories'] ?></div>
                <div class="macro-label">Calories</div>
            </div>
            <div class="macro-item">
                <div class="macro-value" style="color:var(--green)"><?= $aiRec['protein'] ?>g</div>
                <div class="macro-label">Protein</div>
            </div>
            <div class="macro-item">
                <div class="macro-value" style="color:var(--blue)"><?= $aiRec['carbs'] ?>g</div>
                <div class="macro-label">Carbs</div>
            </div>
            <div class="macro-item">
                <div class="macro-value" style="color:var(--orange)"><?= $aiRec['fat'] ?>g</div>
                <div class="macro-label">Fat</div>
            </div>
        </div>

        <!-- Tips -->
        <h3 style="font-family:var(--font-display);font-size:1.2rem;margin:24px 0 16px">💡 Expert Tips for <?= goalLabel($aiRec['goal']) ?></h3>
        <div class="tips-list">
            <?php foreach ($aiRec['tips'] as $tip): ?>
                <div class="tip-item">
                    <span class="tip-bullet">→</span>
                    <span><?= e($tip) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recommended Workouts -->
        <?php if (!empty($aiWorkouts)): ?>
        <div style="margin-top:32px">
            <h3 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:16px">💪 Recommended Workouts</h3>
            <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                <?php $wEmojis = ['💪','🏋️','🔥']; ?>
                <?php foreach ($aiWorkouts as $i => $w): ?>
                    <div class="card">
                        <div class="card-image" style="height:100px;font-size:2.5rem"><?= $wEmojis[$i] ?></div>
                        <div class="card-body" style="padding:14px">
                            <div class="card-category"><?= e($w['category_name'] ?? '') ?></div>
                            <h4 class="card-title" style="font-size:0.95rem"><?= e($w['title']) ?></h4>
                            <div class="card-meta"><span class="meta-item">⏱ <?= e($w['duration_minutes']) ?> min</span><span class="meta-item">🔥 <?= e($w['calories_burned']) ?> kcal</span></div>
                        </div>
                        <div class="card-footer">
                            <a href="/fitness_planner/workouts/detail.php?id=<?= $w['id'] ?>" class="btn btn-primary btn-sm">View →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommended Meals -->
        <?php if (!empty($aiMeals)): ?>
        <div style="margin-top:32px">
            <h3 style="font-family:var(--font-display);font-size:1.2rem;margin-bottom:16px">🥗 Recommended Meals</h3>
            <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                <?php $mEmojis = ['🥗','🍗','🥦']; ?>
                <?php foreach ($aiMeals as $i => $m): ?>
                    <div class="card">
                        <div class="card-image" style="height:100px;font-size:2.5rem"><?= $mEmojis[$i] ?></div>
                        <div class="card-body" style="padding:14px">
                            <div class="card-category"><?= e($m['category_name'] ?? '') ?></div>
                            <h4 class="card-title" style="font-size:0.95rem"><?= e($m['title']) ?></h4>
                            <div class="card-meta"><span class="meta-item">🔥 <?= e($m['calories']) ?> kcal</span><span class="meta-item">💪 <?= e($m['protein']) ?>g P</span></div>
                        </div>
                        <div class="card-footer">
                            <a href="/fitness_planner/meals/detail.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">View →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
        <div style="margin-top:32px;padding:24px;background:rgba(232,255,0,0.06);border:1px solid rgba(232,255,0,0.2);border-radius:var(--radius-md);text-align:center">
            <h3 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:8px">💾 Save Your Results</h3>
            <p style="color:var(--text-secondary);margin-bottom:16px">Create a free account to save your metrics and track progress over time.</p>
            <a href="/fitness_planner/register.php" class="btn btn-primary">Create Free Account →</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Info Cards -->
    <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));margin-top:8px">
        <?php
        $infoCards = [
            ['📏', 'BMI', 'Body Mass Index', 'Measures body fat based on weight and height. Ranges: Underweight (<18.5), Normal (18.5-24.9), Overweight (25-29.9), Obese (30+).'],
            ['🔥', 'BMR', 'Basal Metabolic Rate', 'Calories your body burns at rest just to maintain vital functions. Calculated using the Mifflin-St Jeor equation.'],
            ['⚡', 'TDEE', 'Total Daily Energy Expenditure', 'Total calories you burn per day including all activities. Your maintenance calories for weight stability.'],
        ];
        foreach ($infoCards as $c):
        ?>
            <div class="card" style="padding:24px">
                <div style="font-size:2rem;margin-bottom:12px"><?= $c[0] ?></div>
                <div style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:4px"><?= $c[1] ?><span style="color:var(--accent);margin-left:8px;font-size:0.7em"><?= $c[2] ?></span></div>
                <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.6"><?= $c[3] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
