<?php
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/fitness_planner/meals/index.php');

$stmt = $pdo->prepare("SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { setFlash('Meal not found.','error'); redirect('/fitness_planner/meals/index.php'); }

$related = $pdo->prepare("SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id != ? AND (m.goal_tag = ? OR m.meal_type = ?) LIMIT 3");
$related->execute([$id, $m['goal_tag'], $m['meal_type']]);
$relatedMeals = $related->fetchAll();

// Use correct column names (protein_g, carbs_g, fat_g, fiber_g)
$protein = (float)($m['protein_g'] ?? $m['protein'] ?? 0);
$carbs   = (float)($m['carbs_g']   ?? $m['carbs']   ?? 0);
$fat     = (float)($m['fat_g']     ?? $m['fat']      ?? 0);
$fiber   = (float)($m['fiber_g']   ?? $m['fiber']    ?? 0);
$totalMacros = max(1, $protein + $carbs + $fat);

$steps          = array_filter(array_map('trim', explode("\n", $m['instructions'] ?? '')));
$ingredientList = array_filter(array_map('trim', explode("\n", $m['ingredients']  ?? '')));

// Photo pool by meal type — real food photos
$mealPhotos = [
    'breakfast'    => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=900&q=85',
    'lunch'        => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=900&q=85',
    'dinner'       => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=900&q=85',
    'snack'        => 'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=900&q=85',
    'pre_workout'  => 'https://images.unsplash.com/photo-1622484211148-b9a3be3c4c47?w=900&q=85',
    'post_workout' => 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=900&q=85',
];
$heroPhoto = ($m['image_url'] && strlen($m['image_url']) > 5)
    ? $m['image_url']
    : ($mealPhotos[$m['meal_type']] ?? 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=900&q=85');

// Related card photos
$relatedPool = [
    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80',
    'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&q=80',
];
?>

<div style="padding:80px 24px 0;max-width:1280px;margin:0 auto">
    <a href="/fitness_planner/meals/index.php" style="color:var(--text-muted);font-size:0.88rem;text-decoration:none">← Back to Meals</a>
    <div style="color:var(--accent);font-size:0.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-top:10px"><?= e($m['category_name'] ?? 'Meal') ?></div>
</div>

<div class="detail-layout">
    <div>
        <!-- REAL HERO IMAGE -->
        <div style="position:relative;border-radius:18px;overflow:hidden;margin-bottom:28px;height:360px">
            <img src="<?= $heroPhoto ?>"
                 alt="<?= e($m['title']) ?>"
                 style="width:100%;height:100%;object-fit:cover;display:block"
                 onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=900&q=85'">
            <div style="position:absolute;top:16px;left:16px;display:flex;gap:8px">
                <span style="padding:6px 14px;border-radius:999px;background:rgba(0,0,0,0.65);backdrop-filter:blur(10px);font-size:.8rem;color:#fff;text-transform:capitalize">
                    <?= str_replace('_',' ', e($m['meal_type'])) ?>
                </span>
                <?php if ($m['is_featured']): ?>
                <span style="padding:6px 14px;border-radius:999px;background:rgba(139,92,246,0.3);backdrop-filter:blur(10px);font-size:.8rem;color:#a78bfa;border:1px solid rgba(139,92,246,.4)">
                    ⭐ Featured
                </span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="detail-title"><?= e($m['title']) ?></h1>

        <div class="detail-meta">
            <div class="detail-meta-item">🔥 <span><?= e($m['calories']) ?> kcal</span></div>
            <div class="detail-meta-item">⏱ <span><?= e($m['prep_time_minutes']) ?> min prep</span></div>
            <div class="detail-meta-item">🍽️ <span><?= e($m['servings'] ?? 1) ?> serving<?= ($m['servings'] ?? 1) > 1 ? 's':'' ?></span></div>
            <div class="detail-meta-item" style="color:var(--accent-light)"><?= goalLabel($m['goal_tag']) ?></div>
        </div>

        <!-- Description -->
        <div class="detail-section">
            <h2 class="detail-section-title">About This Meal</h2>
            <p style="color:var(--text-secondary);line-height:1.8"><?= nl2br(e($m['description'] ?? '')) ?></p>
        </div>

        <!-- Macro Bars — fixed column names -->
        <div class="detail-section">
            <h2 class="detail-section-title">Nutrition Breakdown</h2>
            <div class="results-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr));margin-bottom:20px">
                <div class="result-item highlighted">
                    <div class="result-label">Calories</div>
                    <div class="result-value" style="color:var(--accent-light)"><?= e($m['calories']) ?></div>
                    <div class="result-unit">kcal</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Protein</div>
                    <div class="result-value" style="color:#4ade80"><?= $protein ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Carbs</div>
                    <div class="result-value" style="color:#60a5fa"><?= $carbs ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Fat</div>
                    <div class="result-value" style="color:#fbbf24"><?= $fat ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <?php if ($fiber > 0): ?>
                <div class="result-item">
                    <div class="result-label">Fiber</div>
                    <div class="result-value" style="color:#a78bfa"><?= $fiber ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $macros = [
                ['💪 Protein',       $protein, '#4ade80'],
                ['🌾 Carbohydrates', $carbs,   '#60a5fa'],
                ['💧 Fat',           $fat,     '#fbbf24'],
            ];
            foreach ($macros as [$lbl, $val, $clr]):
                $pct = min(100, round(($val / $totalMacros) * 100));
            ?>
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:6px">
                    <span style="color:var(--text-secondary)"><?= $lbl ?></span>
                    <span style="color:var(--text-primary)"><?= $val ?>g (<?= $pct ?>%)</span>
                </div>
                <div class="nutrition-bar" style="height:8px">
                    <div class="nutrition-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>" data-width="<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Ingredients -->
        <?php if ($ingredientList): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">🛒 Ingredients</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">
                <?php foreach ($ingredientList as $ing): ?>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;font-size:.88rem;color:var(--text-secondary);display:flex;gap:8px;align-items:center">
                    <span style="color:var(--accent)">•</span> <?= e($ing) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <?php if ($steps): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">👩‍🍳 Instructions</h2>
            <div class="instructions-list">
                <?php $idx=0; foreach ($steps as $step):
                    $clean = preg_replace('/^\d+\.\s*/', '', $step);
                    if (!$clean) continue; $idx++;
                ?>
                <div class="instruction-item">
                    <span class="step-num"><?= $idx ?></span>
                    <span style="color:var(--text-secondary);line-height:1.6"><?= e($clean) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Related Meals — with real photos -->
        <?php if ($relatedMeals): ?>
        <div class="detail-section" style="margin-top:40px">
            <h2 class="detail-section-title">Similar Meals</h2>
            <div class="wi-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                <?php foreach ($relatedMeals as $i => $r):
                    $rType  = $r['meal_type'] ?? 'lunch';
                    $rPhoto = ($r['image_url'] && strlen($r['image_url'])>5) ? $r['image_url'] : ($mealPhotos[$rType] ?? $relatedPool[$i % 3]);
                    $rProt  = (float)($r['protein_g'] ?? $r['protein'] ?? 0);
                ?>
                <a href="/fitness_planner/meals/detail.php?id=<?= $r['id'] ?>" class="wi-card">
                    <div class="wi-img" style="height:150px">
                        <img src="<?= $rPhoto ?>" alt="<?= e($r['title']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <div class="wi-body" style="padding:14px">
                        <h3 class="wi-title" style="font-size:.95rem"><?= e($r['title']) ?></h3>
                        <div class="wi-foot" style="border:none;padding:8px 0 0">
                            <span class="wi-meta">🔥 <?= $r['calories'] ?> kcal</span>
                            <span class="wi-meta">💪 <?= $rProt ?>g</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="sidebar-card">
            <div class="sidebar-title">📊 Nutrition Facts</div>
            <div style="text-align:center;margin-bottom:16px">
                <div style="font-family:var(--font-display);font-size:3rem;color:var(--accent-light)"><?= e($m['calories']) ?></div>
                <div style="font-size:.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.1em">Calories per serving</div>
            </div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Protein</span><span class="sidebar-stat-value" style="color:#4ade80"><?= $protein ?>g</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Carbs</span><span class="sidebar-stat-value" style="color:#60a5fa"><?= $carbs ?>g</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Fat</span><span class="sidebar-stat-value" style="color:#fbbf24"><?= $fat ?>g</span></div>
            <?php if ($fiber > 0): ?>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Fiber</span><span class="sidebar-stat-value" style="color:#a78bfa"><?= $fiber ?>g</span></div>
            <?php endif; ?>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Prep Time</span><span class="sidebar-stat-value"><?= e($m['prep_time_minutes']) ?> min</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Servings</span><span class="sidebar-stat-value"><?= e($m['servings'] ?? 1) ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Meal Type</span><span class="sidebar-stat-value" style="text-transform:capitalize"><?= str_replace('_',' ',e($m['meal_type'])) ?></span></div>
        </div>

        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(99,102,241,.04));border-color:rgba(139,92,246,.25)">
            <div class="sidebar-title">🤖 AI Tip</div>
            <?php $tips = [
                'fat_loss'    => 'High protein keeps you full while staying low calorie — perfect for a deficit.',
                'weight_gain' => 'High calorie and protein content to fuel muscle growth and recovery.',
                'fitness'     => 'A balanced meal that supports overall performance and daily energy.',
                'all'         => 'Versatile meal that fits any fitness goal. Adjust portion sizes as needed.',
            ]; ?>
            <p style="font-size:.85rem;color:var(--text-secondary);line-height:1.6"><?= $tips[$m['goal_tag']] ?? $tips['all'] ?></p>
            <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-sm w-full" style="text-align:center;display:block;margin-top:14px">Get AI Meal Plan →</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/fitness_planner/meals/index.php');

$stmt = $pdo->prepare("SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { setFlash('error', 'Meal not found.'); redirect('/fitness_planner/meals/index.php'); }

$related = $pdo->prepare("SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id != ? AND (m.goal_tag = ? OR m.meal_type = ?) LIMIT 3");
$related->execute([$id, $m['goal_tag'], $m['meal_type']]);
$relatedMeals = $related->fetchAll();

$pageTitle = $m['title'];
$mealEmojis = ['🥗','🍗','🥦','🍎','🥑','🥩','🍳','🥝','🫙','🥕'];
$emoji = $mealEmojis[$id % count($mealEmojis)];

$totalMacros = (($m['protein'] + $m['carbs'] + $m['fat']) ?: 1);
$steps = array_filter(array_map('trim', explode("\n", $m['instructions'] ?? '')));
$ingredientList = array_filter(array_map('trim', explode("\n", $m['ingredients'] ?? '')));
?>

<div class="page-header" style="text-align:left;padding-left:max(24px,calc((100% - 1200px)/2))">
    <div style="margin-bottom:8px">
        <a href="/fitness_planner/meals/index.php" style="color:var(--text-muted);font-size:0.88rem">← Back to Meals</a>
    </div>
    <div class="section-label"><?= e($m['category_name'] ?? 'Meal') ?></div>
</div>

<div class="detail-layout">
    <div>
        <div class="detail-hero-image" style="font-size:7rem">
            <?= $emoji ?>
            <div style="position:absolute;top:16px;left:16px;display:flex;gap:8px">
                <span class="badge" style="font-size:0.8rem;padding:6px 14px;background:rgba(0,0,0,0.6);backdrop-filter:blur(10px);text-transform:capitalize">
                    <?= str_replace('_',' ',e($m['meal_type'])) ?>
                </span>
                <?php if ($m['is_featured']): ?>
                    <span class="badge badge-featured" style="font-size:0.8rem;padding:6px 14px">⭐ Featured</span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="detail-title"><?= e($m['title']) ?></h1>

        <div class="detail-meta">
            <div class="detail-meta-item">🔥 <span><?= e($m['calories']) ?> kcal</span></div>
            <div class="detail-meta-item">⏱ <span><?= e($m['prep_time_minutes']) ?> min prep</span></div>
            <div class="detail-meta-item">🍽️ <span><?= e($m['servings']) ?> serving<?= $m['servings'] > 1 ? 's':'' ?></span></div>
            <div class="detail-meta-item" style="color:var(--accent);border-color:rgba(232,255,0,0.3)"><?= goalLabel($m['goal_tag']) ?></div>
        </div>

        <!-- Description -->
        <div class="detail-section">
            <h2 class="detail-section-title">About This Meal</h2>
            <p style="color:var(--text-secondary);line-height:1.8"><?= nl2br(e($m['description'] ?? '')) ?></p>
        </div>

        <!-- Macros Visual -->
        <div class="detail-section">
            <h2 class="detail-section-title">Nutrition Breakdown</h2>
            <div class="results-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
                <div class="result-item highlighted">
                    <div class="result-label">Calories</div>
                    <div class="result-value text-accent"><?= e($m['calories']) ?></div>
                    <div class="result-unit">kcal</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Protein</div>
                    <div class="result-value" style="color:var(--green)"><?= e($m['protein']) ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Carbs</div>
                    <div class="result-value" style="color:var(--blue)"><?= e($m['carbs']) ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Fat</div>
                    <div class="result-value" style="color:var(--orange)"><?= e($m['fat']) ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <?php if ($m['fiber']): ?>
                <div class="result-item">
                    <div class="result-label">Fiber</div>
                    <div class="result-value" style="color:var(--purple)"><?= e($m['fiber']) ?></div>
                    <div class="result-unit">grams</div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Macro Bars -->
            <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
                <?php
                $macros = [
                    ['💪 Protein', $m['protein'], $totalMacros, '#4ade80'],
                    ['🌾 Carbohydrates', $m['carbs'], $totalMacros, '#60a5fa'],
                    ['💧 Fat', $m['fat'], $totalMacros, '#fbbf24'],
                ];
                foreach ($macros as [$lbl, $val, $tot, $clr]):
                    $pct = min(100, round(($val / $tot) * 100));
                ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:0.83rem;margin-bottom:6px">
                            <span style="color:var(--text-secondary)"><?= $lbl ?></span>
                            <span style="color:var(--text-primary)"><?= $val ?>g (<?= $pct ?>%)</span>
                        </div>
                        <div class="nutrition-bar" style="height:8px">
                            <div class="nutrition-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>" data-width="<?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ingredients -->
        <?php if ($ingredientList): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">🛒 Ingredients</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">
                <?php foreach ($ingredientList as $ing): ?>
                    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;font-size:0.88rem;color:var(--text-secondary);display:flex;gap:8px;align-items:center">
                        <span style="color:var(--accent)">•</span> <?= e($ing) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <?php if ($steps): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">👩‍🍳 Instructions</h2>
            <div class="instructions-list">
                <?php foreach ($steps as $idx => $step):
                    $clean = preg_replace('/^\d+\.\s*/', '', $step);
                    if (empty($clean)) continue;
                ?>
                    <div class="instruction-item">
                        <span class="step-num"><?= $idx + 1 ?></span>
                        <span style="color:var(--text-secondary);line-height:1.6"><?= e($clean) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Related -->
        <?php if ($relatedMeals): ?>
        <div class="detail-section" style="margin-top:40px">
            <h2 class="detail-section-title">Similar Meals</h2>
            <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                <?php foreach ($relatedMeals as $i => $r): ?>
                    <div class="card">
                        <div class="card-image" style="height:100px;font-size:2rem">
                            <?= ['🥗','🍗','🥦'][$i] ?>
                        </div>
                        <div class="card-body" style="padding:14px">
                            <h3 class="card-title" style="font-size:0.95rem;margin-bottom:6px"><?= e($r['title']) ?></h3>
                            <div class="card-meta">
                                <span class="meta-item">🔥 <?= e($r['calories']) ?> kcal</span>
                                <span class="meta-item">💪 <?= e($r['protein']) ?>g</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="/fitness_planner/meals/detail.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">View →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="sidebar-card">
            <div class="sidebar-title">📊 Nutrition Facts</div>
            <div style="text-align:center;margin-bottom:16px">
                <div style="font-family:var(--font-display);font-size:3rem;color:var(--accent)"><?= e($m['calories']) ?></div>
                <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em">Calories per serving</div>
            </div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Protein</span><span class="sidebar-stat-value text-green"><?= e($m['protein']) ?>g</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Carbs</span><span class="sidebar-stat-value" style="color:var(--blue)"><?= e($m['carbs']) ?>g</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Fat</span><span class="sidebar-stat-value" style="color:var(--orange)"><?= e($m['fat']) ?>g</span></div>
            <?php if ($m['fiber']): ?><div class="sidebar-stat"><span class="sidebar-stat-label">Fiber</span><span class="sidebar-stat-value" style="color:var(--purple)"><?= e($m['fiber']) ?>g</span></div><?php endif; ?>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Prep Time</span><span class="sidebar-stat-value"><?= e($m['prep_time_minutes']) ?> min</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Servings</span><span class="sidebar-stat-value"><?= e($m['servings']) ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Meal Type</span><span class="sidebar-stat-value" style="text-transform:capitalize"><?= str_replace('_',' ',e($m['meal_type'])) ?></span></div>
        </div>

        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(232,255,0,0.06),rgba(0,255,136,0.03));border-color:rgba(232,255,0,0.2)">
            <div class="sidebar-title">🤖 AI Tip</div>
            <?php
            $tips = [
                'fat_loss'    => 'This meal is great for fat loss — high protein keeps you full while staying low calorie.',
                'weight_gain' => 'Perfect for bulking! High calorie and protein content to support muscle growth.',
                'fitness'     => 'A balanced meal that supports overall performance and recovery.',
                'all'         => 'Versatile meal that fits any fitness goal. Adjust portion sizes as needed.',
            ];
            ?>
            <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.6"><?= $tips[$m['goal_tag']] ?? $tips['all'] ?></p>
            <a href="/fitness_planner/calculator/index.php" class="btn btn-secondary btn-sm w-full" style="text-align:center;display:block;margin-top:14px">Get AI Meal Plan →</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
