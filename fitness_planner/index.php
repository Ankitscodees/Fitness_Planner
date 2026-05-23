<?php
// ============================================================
// index.php — Homepage
// ============================================================
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Featured workouts
$featuredWorkouts = $pdo->query("
    SELECT w.*, c.name AS category_name 
    FROM workouts w 
    LEFT JOIN categories c ON w.category_id = c.id 
    WHERE w.is_featured = 1 
    ORDER BY w.created_at DESC 
    LIMIT 3
")->fetchAll();

// Featured meals
$featuredMeals = $pdo->query("
    SELECT m.*, c.name AS category_name 
    FROM meals m 
    LEFT JOIN categories c ON m.category_id = c.id 
    WHERE m.is_featured = 1 
    ORDER BY m.created_at DESC 
    LIMIT 3
")->fetchAll();

// Stats
$totalWorkouts = $pdo->query("SELECT COUNT(*) FROM workouts")->fetchColumn();
$totalMeals    = $pdo->query("SELECT COUNT(*) FROM meals")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

$pageTitle = 'Home';

$workoutEmojis = ['💪', '🏋️', '🔥', '⚡', '🚀', '🎯', '🏃', '🧘'];
$mealEmojis    = ['🥗', '🍗', '🥦', '🍎', '🥑', '🥩', '🍳', '🥝'];
?>

<!-- ====== HERO ====== -->
<section class="hero">
    <div class="hero-grid">
        <div class="hero-content">
            <div class="hero-label">🚀 Your Ultimate Fitness Companion</div>
            <h1 class="hero-title">
                UNLEASH YOUR
                <span class="line-accent">POTENTIAL</span>
            </h1>
            <p class="hero-subtitle">
                Science-backed workouts, personalized meal plans, and AI-powered recommendations — 
                all designed to help you crush your fitness goals.
            </p>
            <div class="hero-cta">
                <?php if (!isLoggedIn()): ?>
                    <a href="/fitness_planner/register.php" class="btn btn-primary btn-lg">Get Started Free →</a>
                    <a href="/fitness_planner/workouts/index.php" class="btn btn-secondary btn-lg">Browse Workouts</a>
                <?php else: ?>
                    <a href="/fitness_planner/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard →</a>
                    <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-lg">BMI Calculator</a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalWorkouts ?>+</div>
                    <div class="stat-label">Workouts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $totalMeals ?>+</div>
                    <div class="stat-label">Meal Plans</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $totalUsers ?>+</div>
                    <div class="stat-label">Members</div>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <?php foreach (array_slice($featuredWorkouts, 0, 2) as $w): ?>
                <div class="hero-card">
                    <div class="hero-card-label">Featured Workout</div>
                    <div class="hero-card-title"><?= e($w['title']) ?></div>
                    <div class="hero-card-meta">
                        <span class="hero-card-badge">⏱ <?= e($w['duration_minutes']) ?> min</span>
                        <span class="hero-card-badge">🔥 <?= e($w['calories_burned']) ?> kcal</span>
                        <span class="hero-card-badge" style="text-transform:capitalize"><?= e($w['difficulty']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="hero-card" style="background:linear-gradient(135deg,rgba(232,255,0,0.08),rgba(0,255,136,0.05));border-color:rgba(232,255,0,0.2)">
                <div class="hero-card-label">AI Recommendation</div>
                <div class="hero-card-title">Smart Goal Tracking</div>
                <div class="hero-card-meta">
                    <span class="hero-card-badge">🤖 Rule-Based AI</span>
                    <span class="hero-card-badge">📊 BMI + TDEE</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== FEATURED WORKOUTS ====== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-label">💪 Training</div>
            <h2 class="section-title">Featured Workouts</h2>
            <p class="section-subtitle">Expert-designed programs for every fitness level and goal</p>
        </div>
        <div class="cards-grid">
            <?php foreach ($featuredWorkouts as $i => $w):
                $emoji = $workoutEmojis[$i % count($workoutEmojis)];
                $dColor = difficultyColor($w['difficulty']);
            ?>
                <div class="card animate-in" style="animation-delay:<?= $i * 0.1 ?>s">
                    <div class="card-image">
                        <?= $emoji ?>
                        <div class="card-badges">
                            <span class="badge badge-difficulty" style="color:<?= $dColor ?>;border:1px solid <?= $dColor ?>44"><?= ucfirst(e($w['difficulty'])) ?></span>
                            <span class="badge badge-featured">⭐ Featured</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-category"><?= e($w['category_name'] ?? 'General') ?></div>
                        <h3 class="card-title"><?= e($w['title']) ?></h3>
                        <p class="card-desc"><?= e(truncate($w['description'])) ?></p>
                        <div class="card-meta">
                            <span class="meta-item"><span class="meta-icon">⏱</span> <?= e($w['duration_minutes']) ?> min</span>
                            <span class="meta-item"><span class="meta-icon">🔥</span> <?= e($w['calories_burned']) ?> kcal</span>
                            <?php if ($w['muscle_group']): ?>
                                <span class="meta-item"><span class="meta-icon">🎯</span> <?= e(truncate($w['muscle_group'], 30)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span style="font-size:0.8rem;color:var(--text-muted)"><?= goalLabel($w['goal_tag']) ?></span>
                        <a href="/fitness_planner/workouts/detail.php?id=<?= $w['id'] ?>" class="btn btn-primary btn-sm">View →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:40px">
            <a href="/fitness_planner/workouts/index.php" class="btn btn-secondary">View All Workouts →</a>
        </div>
    </div>
</section>

<!-- ====== FEATURES ====== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <div class="section-label">✨ What We Offer</div>
            <h2 class="section-title">Everything You Need</h2>
        </div>
        <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
            <?php
            $features = [
                ['🏋️', 'Workout Library', 'Dozens of expert-designed workouts for every goal — fat loss, muscle gain, and general fitness.'],
                ['🥗', 'Meal Planning', 'Nutritionist-crafted meal plans with detailed macros, ingredients, and step-by-step instructions.'],
                ['📊', 'BMI & TDEE Calc', 'Calculate your BMI, BMR, and daily calorie needs with personalized insights based on your goals.'],
                ['🤖', 'AI Recommendations', 'Get smart, rule-based workout and meal recommendations tailored to your specific fitness goal.'],
                ['📈', 'Progress Tracking', 'Log your daily weight and activity to visualize your transformation over time on your dashboard.'],
                ['🔒', 'Secure Accounts', 'Your data is protected with bcrypt password hashing and secure session management.'],
            ];
            foreach ($features as $i => $f):
            ?>
                <div class="card animate-in" style="animation-delay:<?= $i * 0.08 ?>s">
                    <div class="card-body" style="text-align:center;padding:32px 24px">
                        <div style="font-size:2.5rem;margin-bottom:16px"><?= $f[0] ?></div>
                        <h3 class="card-title" style="font-size:1.1rem;margin-bottom:10px"><?= $f[1] ?></h3>
                        <p class="card-desc" style="font-size:0.85rem;text-align:center"><?= $f[2] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====== FEATURED MEALS ====== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-label">🥗 Nutrition</div>
            <h2 class="section-title">Fuel Your Goals</h2>
            <p class="section-subtitle">Delicious, macro-balanced meals for every dietary preference</p>
        </div>
        <div class="cards-grid">
            <?php foreach ($featuredMeals as $i => $m):
                $emoji = $mealEmojis[$i % count($mealEmojis)];
            ?>
                <div class="card animate-in" style="animation-delay:<?= $i * 0.1 ?>s">
                    <div class="card-image">
                        <?= $emoji ?>
                        <div class="card-badges">
                            <span class="badge badge-featured">⭐ Featured</span>
                            <span class="badge" style="background:rgba(0,0,0,0.5);text-transform:capitalize"><?= str_replace('_', ' ', e($m['meal_type'])) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-category"><?= e($m['category_name'] ?? 'General') ?></div>
                        <h3 class="card-title"><?= e($m['title']) ?></h3>
                        <p class="card-desc"><?= e(truncate($m['description'])) ?></p>
                        <div class="card-meta">
                            <span class="meta-item"><span class="meta-icon">🔥</span> <?= e($m['calories']) ?> kcal</span>
                            <span class="meta-item"><span class="meta-icon">💪</span> <?= e($m['protein']) ?>g protein</span>
                            <span class="meta-item"><span class="meta-icon">⏱</span> <?= e($m['prep_time_minutes']) ?> min</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span style="font-size:0.8rem;color:var(--text-muted)"><?= goalLabel($m['goal_tag']) ?></span>
                        <a href="/fitness_planner/meals/detail.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">View →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:40px">
            <a href="/fitness_planner/meals/index.php" class="btn btn-secondary">View All Meals →</a>
        </div>
    </div>
</section>

<!-- ====== CTA ====== -->
<?php if (!isLoggedIn()): ?>
<section class="section section-alt">
    <div class="container" style="text-align:center">
        <div class="section-label">🚀 Get Started Today</div>
        <h2 class="section-title">Ready to Transform?</h2>
        <p class="section-subtitle" style="margin-bottom:40px">Join thousands of members who are crushing their fitness goals with FitPro.</p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
            <a href="/fitness_planner/register.php" class="btn btn-primary btn-lg">Create Free Account →</a>
            <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-lg">Try BMI Calculator</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
