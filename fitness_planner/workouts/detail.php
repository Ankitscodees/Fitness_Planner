<?php
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/fitness_planner/workouts/index.php');

$stmt = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.id = ?");
$stmt->execute([$id]);
$w = $stmt->fetch();
if (!$w) { setFlash('Workout not found.','error'); redirect('/fitness_planner/workouts/index.php'); }

$related = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.id != ? AND (w.category_id = ? OR w.difficulty = ?) LIMIT 3");
$related->execute([$id, $w['category_id'], $w['difficulty']]);
$relatedWorkouts = $related->fetchAll();

$dColor = difficultyColor($w['difficulty']);
$steps  = array_filter(array_map('trim', explode("\n", $w['instructions'] ?? '')));

// Real workout photo fallback pool
$photoPool = [
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=85',
    'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=900&q=85',
    'https://images.unsplash.com/photo-1549060279-7e168fcee0c2?w=900&q=85',
    'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&q=85',
    'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=900&q=85',
    'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=900&q=85',
    'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=900&q=85',
    'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=900&q=85',
];
$heroPhoto = ($w['image_url'] && strlen($w['image_url']) > 5)
    ? $w['image_url']
    : $photoPool[$id % count($photoPool)];

// YouTube search URL for this workout
$ytSearch = 'https://www.youtube.com/results?search_query=' . urlencode(($w['title'] ?? '') . ' workout tutorial how to');

// Convert stored YouTube URL to embed if exists
$videoEmbed = null;
if (!empty($w['video_url'])) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $w['video_url'], $m2)) {
        $videoEmbed = 'https://www.youtube.com/embed/' . $m2[1] . '?rel=0&modestbranding=1';
    }
}
?>

<div style="padding:80px 24px 0;max-width:1280px;margin:0 auto">
    <a href="/fitness_planner/workouts/index.php" style="color:var(--text-muted);font-size:.88rem;text-decoration:none">← Back to Workouts</a>
    <div style="color:var(--accent-light);font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-top:10px"><?= e($w['category_name'] ?? 'Workout') ?></div>
</div>

<div class="detail-layout">
    <div>
        <!-- REAL HERO IMAGE -->
        <div style="position:relative;border-radius:18px;overflow:hidden;margin-bottom:28px;height:360px">
            <img src="<?= $heroPhoto ?>"
                 alt="<?= e($w['title']) ?>"
                 style="width:100%;height:100%;object-fit:cover;display:block"
                 onerror="this.src='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=85'">
            <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 50%,rgba(10,10,20,.85))"></div>
            <div style="position:absolute;top:16px;left:16px;display:flex;gap:8px">
                <span style="padding:6px 14px;border-radius:999px;background:rgba(0,0,0,.65);backdrop-filter:blur(10px);font-size:.8rem;color:<?= $dColor ?>;border:1px solid <?= $dColor ?>44">
                    <?= ucfirst(e($w['difficulty'])) ?>
                </span>
                <?php if ($w['is_featured']): ?>
                <span style="padding:6px 14px;border-radius:999px;background:rgba(139,92,246,.3);backdrop-filter:blur(10px);font-size:.8rem;color:#a78bfa;border:1px solid rgba(139,92,246,.4)">⭐ Featured</span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="detail-title"><?= e($w['title']) ?></h1>

        <div class="detail-meta">
            <div class="detail-meta-item">⏱ <span><?= e($w['duration_minutes']) ?> min</span></div>
            <div class="detail-meta-item">🔥 <span><?= e($w['calories_burned']) ?> kcal</span></div>
            <div class="detail-meta-item">🎯 <span><?= e($w['muscle_group'] ?? 'Full Body') ?></span></div>
            <?php if ($w['equipment'] && $w['equipment'] !== 'None'): ?>
            <div class="detail-meta-item">🏋️ <span><?= e($w['equipment']) ?></span></div>
            <?php endif; ?>
            <div class="detail-meta-item" style="color:var(--accent-light)"><?= goalLabel($w['goal_tag']) ?></div>
        </div>

        <!-- Description -->
        <div class="detail-section">
            <h2 class="detail-section-title">About This Workout</h2>
            <p style="color:var(--text-secondary);line-height:1.8"><?= nl2br(e($w['description'] ?? '')) ?></p>
        </div>

        <!-- ═══ VIDEO SECTION ═══ -->
        <div class="detail-section">
            <h2 class="detail-section-title">🎬 How To Perform</h2>
            <?php if ($videoEmbed): ?>
                <!-- Embedded YouTube video from DB -->
                <div style="position:relative;padding-bottom:56.25%;border-radius:14px;overflow:hidden;background:#000">
                    <iframe src="<?= $videoEmbed ?>"
                            style="position:absolute;inset:0;width:100%;height:100%;border:none"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen loading="lazy"></iframe>
                </div>
            <?php else: ?>
                <!-- No video stored — show YouTube search card -->
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:28px;display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center">
                    <div style="width:64px;height:64px;background:#ff0000;border-radius:14px;display:flex;align-items:center;justify-content:center">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M21.593 7.203a2.506 2.506 0 0 0-1.762-1.766C18.265 5.007 12 5 12 5s-6.264-.007-7.831.404a2.56 2.56 0 0 0-1.766 1.778c-.413 1.566-.417 4.814-.417 4.814s-.004 3.264.413 4.814c.23.857.905 1.534 1.762 1.765 1.582.43 7.83.437 7.83.437s6.265.007 7.831-.403a2.515 2.515 0 0 0 1.767-1.763c.414-1.565.417-4.812.417-4.812s.02-3.265-.413-4.831zM9.996 15.005l.005-6 5.207 3.005-5.212 2.995z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:6px">Watch Tutorial on YouTube</div>
                        <div style="font-size:.85rem;color:var(--text-muted)">See proper form and technique for <strong style="color:var(--text-secondary)"><?= e($w['title']) ?></strong></div>
                    </div>
                    <a href="<?= $ytSearch ?>" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:8px;background:#ff0000;color:#fff;font-weight:600;font-size:.9rem;padding:12px 24px;border-radius:10px;text-decoration:none;transition:opacity .15s"
                       onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M21.593 7.203a2.506 2.506 0 0 0-1.762-1.766C18.265 5.007 12 5 12 5s-6.264-.007-7.831.404a2.56 2.56 0 0 0-1.766 1.778c-.413 1.566-.417 4.814-.417 4.814s-.004 3.264.413 4.814c.23.857.905 1.534 1.762 1.765 1.582.43 7.83.437 7.83.437s6.265.007 7.831-.403a2.515 2.515 0 0 0 1.767-1.763c.414-1.565.417-4.812.417-4.812s.02-3.265-.413-4.831zM9.996 15.005l.005-6 5.207 3.005-5.212 2.995z"/></svg>
                        Search "<?= e($w['title']) ?>" on YouTube
                    </a>
                    <p style="font-size:.75rem;color:var(--text-muted);margin:0">Opens YouTube in a new tab</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <?php if ($steps): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">📋 Step-by-Step Instructions</h2>
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

        <!-- Related Workouts — with real photos -->
        <?php if ($relatedWorkouts): ?>
        <div class="detail-section" style="margin-top:40px">
            <h2 class="detail-section-title">Related Workouts</h2>
            <div class="wi-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                <?php foreach ($relatedWorkouts as $i => $r):
                    $rPhoto = ($r['image_url'] && strlen($r['image_url'])>5) ? $r['image_url'] : $photoPool[$r['id'] % count($photoPool)];
                ?>
                <a href="/fitness_planner/workouts/detail.php?id=<?= $r['id'] ?>" class="wi-card">
                    <div class="wi-img" style="height:150px">
                        <img src="<?= $rPhoto ?>" alt="<?= e($r['title']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <div class="wi-body" style="padding:14px">
                        <h3 class="wi-title" style="font-size:.95rem"><?= e($r['title']) ?></h3>
                        <div class="wi-foot" style="border:none;padding:8px 0 0">
                            <span class="wi-meta">⏱ <?= $r['duration_minutes'] ?> min</span>
                            <span class="wi-meta">🔥 <?= $r['calories_burned'] ?> kcal</span>
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
            <div class="sidebar-title">📋 Quick Stats</div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Duration</span><span class="sidebar-stat-value" style="color:var(--accent-light)"><?= e($w['duration_minutes']) ?> min</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Calories</span><span class="sidebar-stat-value">~<?= e($w['calories_burned']) ?> kcal</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Difficulty</span><span class="sidebar-stat-value" style="color:<?= $dColor ?>"><?= ucfirst(e($w['difficulty'])) ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Category</span><span class="sidebar-stat-value"><?= e($w['category_name'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Muscle Group</span><span class="sidebar-stat-value" style="font-size:.8rem"><?= e($w['muscle_group'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Equipment</span><span class="sidebar-stat-value" style="font-size:.8rem"><?= e($w['equipment'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Goal</span><span class="sidebar-stat-value"><?= goalLabel($w['goal_tag']) ?></span></div>
        </div>

        <!-- YouTube Search Shortcut in sidebar -->
        <div class="sidebar-card" style="position:static">
            <div class="sidebar-title">🎬 Watch & Learn</div>
            <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:14px;line-height:1.5">Find video tutorials, form guides, and tips for this exercise.</p>
            <a href="<?= $ytSearch ?>" target="_blank" rel="noopener"
               style="display:flex;align-items:center;justify-content:center;gap:8px;background:#ff0000;color:#fff;font-weight:600;font-size:.88rem;padding:11px 16px;border-radius:10px;text-decoration:none;margin-bottom:8px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M21.593 7.203a2.506 2.506 0 0 0-1.762-1.766C18.265 5.007 12 5 12 5s-6.264-.007-7.831.404a2.56 2.56 0 0 0-1.766 1.778c-.413 1.566-.417 4.814-.417 4.814s-.004 3.264.413 4.814c.23.857.905 1.534 1.762 1.765 1.582.43 7.83.437 7.83.437s6.265.007 7.831-.403a2.515 2.515 0 0 0 1.767-1.763c.414-1.565.417-4.812.417-4.812s.02-3.265-.413-4.831zM9.996 15.005l.005-6 5.207 3.005-5.212 2.995z"/></svg>
                YouTube Tutorial
            </a>
            <a href="https://www.google.com/search?q=<?= urlencode(($w['title'] ?? '') . ' exercise proper form') ?>"
               target="_blank" rel="noopener"
               style="display:flex;align-items:center;justify-content:center;gap:8px;background:var(--bg-input);border:1px solid var(--border);color:var(--text-secondary);font-size:.88rem;padding:10px 16px;border-radius:10px;text-decoration:none">
                🔍 Search Form Guide
            </a>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(99,102,241,.04));border-color:rgba(139,92,246,.25)">
            <div class="sidebar-title">📈 Log This Workout</div>
            <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:16px">Track your progress and build consistency.</p>
            <a href="/fitness_planner/dashboard.php" class="btn btn-primary btn-sm w-full" style="text-align:center;display:block">Log to Dashboard →</a>
        </div>
        <?php else: ?>
        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(99,102,241,.04));border-color:rgba(139,92,246,.25)">
            <div class="sidebar-title">🚀 Track Progress</div>
            <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:16px">Sign up to log workouts and track your transformation.</p>
            <a href="/fitness_planner/register.php" class="btn btn-primary btn-sm w-full" style="text-align:center;display:block">Get Started Free →</a>
        </div>
        <?php endif; ?>

        <div class="sidebar-card" style="position:static">
            <div class="sidebar-title">🎯 Goal Tips</div>
            <?php $tips = [
                'fat_loss'    => ['Stay in a calorie deficit','Keep rest periods short (30-60s)','Combine with cardio'],
                'weight_gain' => ['Eat at a calorie surplus','Progressive overload each week','Get 8+ hours of sleep'],
                'fitness'     => ['Stay consistent every week','Pair with balanced nutrition','Focus on proper form'],
                'all'         => ['Suitable for any goal','Adjust intensity as needed','Focus on proper form'],
            ];
            foreach ($tips[$w['goal_tag']] ?? $tips['all'] as $tip): ?>
            <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-size:.83rem;color:var(--text-secondary)">
                <span style="color:var(--accent-light)">✓</span> <?= e($tip) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/fitness_planner/workouts/index.php');

$stmt = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.id = ?");
$stmt->execute([$id]);
$w = $stmt->fetch();
if (!$w) { setFlash('error', 'Workout not found.'); redirect('/fitness_planner/workouts/index.php'); }

// Related workouts
$related = $pdo->prepare("SELECT w.*, c.name AS category_name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE w.id != ? AND (w.category_id = ? OR w.difficulty = ?) LIMIT 3");
$related->execute([$id, $w['category_id'], $w['difficulty']]);
$relatedWorkouts = $related->fetchAll();

$pageTitle = $w['title'];
$dColor = difficultyColor($w['difficulty']);

$workoutEmojis = ['💪','🏋️','🔥','⚡','🚀','🎯','🏃','🧘'];
$emoji = $workoutEmojis[$id % count($workoutEmojis)];

// Parse instructions into steps
$steps = array_filter(array_map('trim', explode("\n", $w['instructions'] ?? '')));
?>

<div class="page-header" style="text-align:left;padding-left:max(24px, calc((100% - 1200px)/2))">
    <div style="margin-bottom:8px">
        <a href="/fitness_planner/workouts/index.php" style="color:var(--text-muted);font-size:0.88rem">← Back to Workouts</a>
    </div>
    <div class="section-label"><?= e($w['category_name'] ?? 'Workout') ?></div>
</div>

<div class="detail-layout">
    <!-- Main Content -->
    <div>
        <div class="detail-hero-image" style="font-size:7rem">
            <?= $emoji ?>
            <div style="position:absolute;top:16px;left:16px;display:flex;gap:8px">
                <span class="badge" style="font-size:0.8rem;padding:6px 14px;background:rgba(0,0,0,0.6);color:<?= $dColor ?>;border:1px solid <?= $dColor ?>44;backdrop-filter:blur(10px)">
                    <?= ucfirst(e($w['difficulty'])) ?>
                </span>
                <?php if ($w['is_featured']): ?>
                    <span class="badge badge-featured" style="font-size:0.8rem;padding:6px 14px">⭐ Featured</span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="detail-title"><?= e($w['title']) ?></h1>

        <div class="detail-meta">
            <div class="detail-meta-item">⏱ <span><?= e($w['duration_minutes']) ?> min</span></div>
            <div class="detail-meta-item">🔥 <span><?= e($w['calories_burned']) ?> kcal</span></div>
            <div class="detail-meta-item">🎯 <span><?= e($w['muscle_group'] ?? 'Full Body') ?></span></div>
            <?php if ($w['equipment'] && $w['equipment'] !== 'None'): ?>
                <div class="detail-meta-item">🏋️ <span><?= e($w['equipment']) ?></span></div>
            <?php endif; ?>
            <div class="detail-meta-item" style="color:var(--accent);border-color:rgba(232,255,0,0.3)"><?= goalLabel($w['goal_tag']) ?></div>
        </div>

        <!-- Description -->
        <div class="detail-section">
            <h2 class="detail-section-title">About This Workout</h2>
            <p style="color:var(--text-secondary);line-height:1.8"><?= nl2br(e($w['description'] ?? '')) ?></p>
        </div>

        <!-- Instructions -->
        <?php if ($steps): ?>
        <div class="detail-section">
            <h2 class="detail-section-title">Step-by-Step Instructions</h2>
            <div class="instructions-list">
                <?php foreach ($steps as $idx => $step):
                    // Remove leading number like "1. "
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

        <!-- Related Workouts -->
        <?php if ($relatedWorkouts): ?>
        <div class="detail-section" style="margin-top:40px">
            <h2 class="detail-section-title">Related Workouts</h2>
            <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                <?php foreach ($relatedWorkouts as $i => $r): ?>
                    <div class="card">
                        <div class="card-image" style="height:120px;font-size:2.5rem">
                            <?= ['💪','🏋️','🔥'][$i] ?>
                        </div>
                        <div class="card-body" style="padding:16px">
                            <div class="card-category"><?= e($r['category_name'] ?? 'General') ?></div>
                            <h3 class="card-title" style="font-size:1rem"><?= e($r['title']) ?></h3>
                            <div class="card-meta">
                                <span class="meta-item">⏱ <?= e($r['duration_minutes']) ?> min</span>
                                <span class="meta-item">🔥 <?= e($r['calories_burned']) ?> kcal</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="/fitness_planner/workouts/detail.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">View →</a>
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
            <div class="sidebar-title">📋 Quick Stats</div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Duration</span><span class="sidebar-stat-value text-accent"><?= e($w['duration_minutes']) ?> minutes</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Calories</span><span class="sidebar-stat-value">~<?= e($w['calories_burned']) ?> kcal</span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Difficulty</span><span class="sidebar-stat-value" style="color:<?= $dColor ?>"><?= ucfirst(e($w['difficulty'])) ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Category</span><span class="sidebar-stat-value"><?= e($w['category_name'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Muscle Group</span><span class="sidebar-stat-value" style="font-size:0.8rem"><?= e($w['muscle_group'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Equipment</span><span class="sidebar-stat-value" style="font-size:0.8rem"><?= e($w['equipment'] ?? '—') ?></span></div>
            <div class="sidebar-stat"><span class="sidebar-stat-label">Goal</span><span class="sidebar-stat-value"><?= goalLabel($w['goal_tag']) ?></span></div>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(232,255,0,0.06),rgba(0,255,136,0.03));border-color:rgba(232,255,0,0.2)">
            <div class="sidebar-title">📈 Log This Workout</div>
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px">Track your progress and build consistency.</p>
            <a href="/fitness_planner/dashboard.php" class="btn btn-primary btn-sm w-full" style="text-align:center;display:block">Log to Dashboard →</a>
        </div>
        <?php else: ?>
        <div class="sidebar-card" style="position:static;background:linear-gradient(135deg,rgba(232,255,0,0.06),rgba(0,255,136,0.03));border-color:rgba(232,255,0,0.2)">
            <div class="sidebar-title">🚀 Track Progress</div>
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px">Sign up to log workouts and track your transformation.</p>
            <a href="/fitness_planner/register.php" class="btn btn-primary btn-sm w-full" style="text-align:center;display:block">Get Started Free →</a>
        </div>
        <?php endif; ?>

        <div class="sidebar-card" style="position:static">
            <div class="sidebar-title">🎯 Goal Match</div>
            <?php
            $tips = [
                'fat_loss'    => ['Stay in a calorie deficit', 'Use this with cardio', 'Keep rest periods short'],
                'weight_gain' => ['Eat at a calorie surplus', 'Focus on progressive overload', 'Get 8+ hours of sleep'],
                'fitness'     => ['Stay consistent', 'Pair with balanced nutrition', 'Listen to your body'],
                'all'         => ['Suitable for any goal', 'Adjust intensity as needed', 'Focus on proper form'],
            ];
            foreach ($tips[$w['goal_tag']] ?? $tips['all'] as $tip): ?>
                <div style="display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-size:0.83rem;color:var(--text-secondary)">
                    <span style="color:var(--accent)">✓</span> <?= e($tip) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
