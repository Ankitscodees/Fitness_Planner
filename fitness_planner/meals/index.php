<?php
// ============================================================
// meals/index.php — Meals Listing
// ============================================================
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

$goalFilter = $_GET['goal']      ?? 'all';
$typeFilter = $_GET['meal_type'] ?? 'all';
$catFilter  = (int)($_GET['category'] ?? 0);
$search     = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($goalFilter !== 'all') { $where[] = "(m.goal_tag = ? OR m.goal_tag = 'all')"; $params[] = $goalFilter; }
if ($typeFilter !== 'all') { $where[] = 'm.meal_type = ?'; $params[] = $typeFilter; }
if ($catFilter > 0)        { $where[] = 'm.category_id = ?'; $params[] = $catFilter; }
if ($search !== '')        { $where[] = '(m.title LIKE ? OR m.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql = "SELECT m.*, c.name AS category_name FROM meals m LEFT JOIN categories c ON m.category_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY m.is_featured DESC, m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$meals = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE type='meal' ORDER BY name")->fetchAll();

$pageTitle = 'Meals';
$mealEmojis = ['🥗','🍗','🥦','🍎','🥑','🥩','🍳','🥝','🫙','🥕'];
?>

<div class="page-header">
    <div class="section-label">🥗 Nutrition</div>
    <h1 class="page-header-title">Meal Library</h1>
    <p class="page-header-subtitle">Nutritionist-crafted meals with detailed macros and instructions</p>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;align-items:center">
        <input type="text" name="search" class="filter-select" placeholder="🔍 Search meals..." value="<?= e($search) ?>" style="border-radius:20px">
        <?php if ($goalFilter !== 'all'): ?><input type="hidden" name="goal" value="<?= e($goalFilter) ?>"><?php endif; ?>
        <?php if ($typeFilter !== 'all'): ?><input type="hidden" name="meal_type" value="<?= e($typeFilter) ?>"><?php endif; ?>
        <button type="submit" class="filter-btn" style="background:var(--accent);color:#000;border-color:var(--accent)">Go</button>
        <?php if ($search): ?><a href="/fitness_planner/meals/index.php" class="filter-btn">✕ Clear</a><?php endif; ?>
    </form>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php foreach (['all'=>'All Goals','fat_loss'=>'🔥 Fat Loss','weight_gain'=>'💪 Gain','fitness'=>'⚡ Fitness'] as $val => $label): ?>
            <a href="?goal=<?= $val ?>&meal_type=<?= $typeFilter ?>&category=<?= $catFilter ?>"
               class="filter-btn <?= $goalFilter === $val ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <select class="filter-select" onchange="location.href='?goal=<?= $goalFilter ?>&meal_type='+this.value+'&category=<?= $catFilter ?>'">
        <option value="all" <?= $typeFilter==='all' ? 'selected':'' ?>>All Meal Types</option>
        <?php foreach (['breakfast','lunch','dinner','snack','pre_workout','post_workout'] as $type): ?>
            <option value="<?= $type ?>" <?= $typeFilter===$type ? 'selected':'' ?>><?= ucwords(str_replace('_',' ',$type)) ?></option>
        <?php endforeach; ?>
    </select>

    <select class="filter-select" onchange="location.href='?goal=<?= $goalFilter ?>&meal_type=<?= $typeFilter ?>&category='+this.value">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter===$cat['id'] ? 'selected':'' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div style="padding:16px 24px;color:var(--text-muted);font-size:0.85rem">
    Showing <strong style="color:var(--text-primary)"><?= count($meals) ?></strong> meal<?= count($meals) !== 1 ? 's' : '' ?>
</div>

<div style="padding:24px;max-width:1280px;margin:0 auto">
    <?php if ($meals): ?>
        <div class="wi-grid">
            <?php
            $mealPhotos = [
                'breakfast' => [
                    'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=600&q=80',
                    'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=600&q=80',
                    'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=600&q=80',
                ],
                'lunch' => [
                    'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&q=80',
                    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
                    'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&q=80',
                ],
                'dinner' => [
                    'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=600&q=80',
                    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
                    'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&q=80',
                ],
                'snack' => [
                    'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=600&q=80',
                    'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&q=80',
                    'https://images.unsplash.com/photo-1559181567-c3190bfbf4ea?w=600&q=80',
                ],
                'pre_workout' => [
                    'https://images.unsplash.com/photo-1622484211148-b9a3be3c4c47?w=600&q=80',
                    'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?w=600&q=80',
                ],
                'post_workout' => [
                    'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=600&q=80',
                    'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=600&q=80',
                ],
            ];
            $fallbackPhotos = [
                'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&q=80',
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
                'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&q=80',
                'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=600&q=80',
                'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
                'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=600&q=80',
            ];
            foreach ($meals as $i => $m):
                $type = $m['meal_type'] ?? 'lunch';
                $pool = $mealPhotos[$type] ?? $fallbackPhotos;
                $photo = ($m['image_url'] && strlen($m['image_url']) > 5)
                    ? $m['image_url']
                    : $pool[$i % count($pool)];
                $totalMacros = max(1, ($m['protein_g'] ?? 0) + ($m['carbs_g'] ?? 0) + ($m['fat_g'] ?? 0));
            ?>
                <a href="/fitness_planner/meals/detail.php?id=<?= $m['id'] ?>" class="wi-card">
                    <div class="wi-img">
                        <img src="<?= $photo ?>"
                             alt="<?= e($m['title'] ?? $m['name'] ?? '') ?>"
                             loading="lazy"
                             onerror="this.src='<?= $fallbackPhotos[$i % count($fallbackPhotos)] ?>'">
                    </div>
                    <div class="wi-body">
                        <h3 class="wi-title"><?= e($m['title'] ?? $m['name'] ?? '') ?></h3>
                        <div class="wi-tags">
                            <?php if (!empty($m['category_name'])): ?>
                                <span class="wi-tag"><?= e($m['category_name']) ?></span>
                            <?php endif; ?>
                            <span class="wi-tag"><?= ucfirst(str_replace('_', ' ', $type)) ?></span>
                        </div>
                        <p class="wi-desc"><?= e(truncate($m['description'], 80)) ?></p>
                        <!-- Macro mini bars -->
                        <?php
                        $macros = [
                            ['P', $m['protein_g'] ?? 0, '#a78bfa'],
                            ['C', $m['carbs_g']   ?? 0, '#818cf8'],
                            ['F', $m['fat_g']     ?? 0, '#c084fc'],
                        ];
                        ?>
                        <div style="display:flex;gap:6px;margin-top:10px">
                            <?php foreach ($macros as [$lbl, $val, $clr]):
                                $pct = min(100, round(($val / $totalMacros) * 100));
                            ?>
                            <div style="flex:1">
                                <div style="font-size:.65rem;color:#6060a0;margin-bottom:3px"><?= $lbl ?> <?= $pct ?>%</div>
                                <div style="height:3px;background:#2e2e48;border-radius:2px">
                                    <div style="height:3px;width:<?= $pct ?>%;background:<?= $clr ?>;border-radius:2px"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="wi-foot">
                        <span class="wi-meta">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $m['prep_time_minutes'] ?? '—' ?> min
                        </span>
                        <span class="wi-meta">🔥 <?= $m['calories'] ?? 0 ?> kcal</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🥗</div>
            <div class="empty-title">No Meals Found</div>
            <p>Try adjusting your filters or <a href="/fitness_planner/meals/index.php" style="color:var(--accent)">clear all</a></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
