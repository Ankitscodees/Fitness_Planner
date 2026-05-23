<?php
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

$diffFilter = $_GET['difficulty'] ?? 'all';
$catFilter  = (int)($_GET['category'] ?? 0);
$search     = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($diffFilter !== 'all') { $where[] = 'w.difficulty = ?'; $params[] = $diffFilter; }
if ($catFilter > 0)        { $where[] = 'w.category_id = ?'; $params[] = $catFilter; }
if ($search !== '')        { $where[] = '(w.title LIKE ? OR w.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql      = "SELECT w.*, c.name AS category_name, w.title AS name FROM workouts w LEFT JOIN categories c ON w.category_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY w.is_featured DESC, w.created_at DESC";
$stmt     = $pdo->prepare($sql);
$stmt->execute($params);
$workouts = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Real Unsplash workout photos (static pool — no API key needed)
$photos = [
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&q=80',
    'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'https://images.unsplash.com/photo-1549060279-7e168fcee0c2?w=600&q=80',
    'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80',
    'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80',
    'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'https://images.unsplash.com/photo-1600618528240-fb9fc964b853?w=600&q=80',
    'https://images.unsplash.com/photo-1544033527-b192daee1f5b?w=600&q=80',
    'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
];
?>

<!-- Page Header — plain white like screenshot -->
<div style="padding:48px 24px 28px;background:var(--bg-primary)">
    <div style="max-width:1280px;margin:0 auto">
        <h1 style="font-size:2rem;font-weight:700;color:var(--text-primary);margin-bottom:6px">All Workouts</h1>
        <p style="color:var(--text-muted);font-size:.95rem">Find the perfect routine for your goals</p>
    </div>
</div>

<!-- Filter Bar — single contained box exactly like screenshot -->
<div style="padding:0 24px 32px;background:var(--bg-primary)">
    <div style="max-width:1280px;margin:0 auto">
        <form method="GET">
            <div class="fi-bar">
                <div class="fi-search-wrap">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text-muted);flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" name="search" class="fi-search-input" placeholder="Search workouts..." value="<?= e($search) ?>">
                </div>
                <div class="fi-divider"></div>
                <select name="category" class="fi-select" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter===$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="fi-divider"></div>
                <select name="difficulty" class="fi-select" onchange="this.form.submit()">
                    <option value="all"         <?= $diffFilter==='all'          ? 'selected':'' ?>>All Levels</option>
                    <option value="beginner"    <?= $diffFilter==='beginner'     ? 'selected':'' ?>>Beginner</option>
                    <option value="intermediate"<?= $diffFilter==='intermediate' ? 'selected':'' ?>>Intermediate</option>
                    <option value="advanced"    <?= $diffFilter==='advanced'     ? 'selected':'' ?>>Advanced</option>
                </select>
                <div class="fi-divider"></div>
                <?php if ($search || $diffFilter !== 'all' || $catFilter): ?>
                    <a href="/fitness_planner/workouts/index.php" class="fi-reset">Reset</a>
                <?php else: ?>
                    <button type="submit" class="fi-reset">Search</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Cards Grid -->
<div style="padding:0 24px 48px;background:var(--bg-primary)">
    <div style="max-width:1280px;margin:0 auto">
    <?php if ($workouts): ?>
        <div class="wi-grid">
            <?php foreach ($workouts as $i => $w): ?>
                <a href="/fitness_planner/workouts/detail.php?id=<?= $w['id'] ?>" class="wi-card">
                    <div class="wi-img">
                        <img src="<?= ($w['image_url'] && strlen($w['image_url']) > 5) ? $w['image_url'] : $photos[$i % count($photos)] ?>"
                             alt="<?= e($w['name']) ?>"
                             loading="lazy"
                             onerror="this.src='<?= $photos[$i % count($photos)] ?>'">
                    </div>
                    <div class="wi-body">
                        <h3 class="wi-title"><?= e($w['name']) ?></h3>
                        <div class="wi-tags">
                            <?php if ($w['category_name']): ?>
                                <span class="wi-tag"><?= e($w['category_name']) ?></span>
                            <?php endif; ?>
                            <span class="wi-tag"><?= ucfirst(e($w['difficulty'])) ?></span>
                        </div>
                        <p class="wi-desc"><?= e(truncate($w['description'], 80)) ?></p>
                    </div>
                    <div class="wi-foot">
                        <span class="wi-meta"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?= $w['duration_minutes'] ?> min</span>
                        <span class="wi-meta wi-meta-fire">🔥 <?= $w['calories_burned'] ?> kcal</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🏋️</div>
            <div class="empty-title">No Workouts Found</div>
            <p>Try adjusting your filters or <a href="/fitness_planner/workouts/index.php" style="color:var(--accent)">clear all</a></p>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
