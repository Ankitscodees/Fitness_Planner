<?php
// ============================================================
// includes/header.php — Global Header
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$baseUrl = '/fitness_planner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — FitPro' : 'FitPro — Your Ultimate Fitness Planner' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💪</text></svg>">
</head>
<body>

<!-- ====== NAVBAR ====== -->
<nav class="navbar">
    <div class="nav-container">
        <!-- Logo — ⚡ icon + purple gradient text like screenshot -->
        <a href="<?= $baseUrl ?>/index.php" class="nav-logo">
            <span style="font-size:1.3rem">⚡</span>
            <span class="nav-logo-text">FitnessPro</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= $baseUrl ?>/index.php"            class="nav-link <?= $currentPage==='index' && !strpos($_SERVER['PHP_SELF'],'workouts') && !strpos($_SERVER['PHP_SELF'],'meals') && !strpos($_SERVER['PHP_SELF'],'calculator') ? 'nav-link-active':'' ?>">Home</a></li>
            <li><a href="<?= $baseUrl ?>/workouts/index.php"   class="nav-link <?= strpos($_SERVER['PHP_SELF'],'workouts')   ? 'nav-link-active':'' ?>">Workouts</a></li>
            <li><a href="<?= $baseUrl ?>/meals/index.php"      class="nav-link <?= strpos($_SERVER['PHP_SELF'],'meals')      ? 'nav-link-active':'' ?>">Meals</a></li>
            <li><a href="<?= $baseUrl ?>/calculator/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'],'calculator') ? 'nav-link-active':'' ?>">Calculator</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= $baseUrl ?>/dashboard.php" class="nav-link <?= $currentPage==='dashboard' ? 'nav-link-active':'' ?>">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= $baseUrl ?>/admin/index.php" class="nav-link nav-link-admin">⚙ Admin</a></li>
                <?php endif; ?>
                <li style="display:flex;align-items:center;gap:8px;margin-left:8px">
                    <span class="nav-username">👤 <?= e($_SESSION['user_name'] ?? 'User') ?></span>
                    <a href="<?= $baseUrl ?>/logout.php" class="nav-logout">Logout</a>
                </li>
            <?php else: ?>
                <li><a href="<?= $baseUrl ?>/login.php"    class="nav-link">Login</a></li>
                <li><a href="<?= $baseUrl ?>/register.php" class="nav-signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- ====== FLASH MESSAGE ====== -->
<?php if ($flash): ?>
    <div class="flash-message flash-<?= e($flash['type']) ?>" id="flashMsg">
        <span class="flash-icon"><?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?></span>
        <?= e($flash['message']) ?>
        <button class="flash-close" onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>

<main class="main-content">
