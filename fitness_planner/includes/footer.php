<?php
// ============================================================
// includes/footer.php — Global Footer
// ============================================================
$baseUrl = '/fitness_planner';
?>
</main>

<!-- ====== FOOTER ====== -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="<?= $baseUrl ?>/index.php" class="footer-logo">
                <span class="logo-icon">💪</span>
                <span class="logo-text">FIT<span class="logo-accent">PRO</span></span>
            </a>
            <p class="footer-tagline">Transform your body. Elevate your life.</p>
        </div>

        <div class="footer-links">
            <div class="footer-col">
                <h4>Explore</h4>
                <ul>
                    <li><a href="<?= $baseUrl ?>/workouts/index.php">Workouts</a></li>
                    <li><a href="<?= $baseUrl ?>/meals/index.php">Meal Plans</a></li>
                    <li><a href="<?= $baseUrl ?>/calculator/index.php">BMI Calculator</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
                        <li><a href="<?= $baseUrl ?>/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= $baseUrl ?>/login.php">Login</a></li>
                        <li><a href="<?= $baseUrl ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Goals</h4>
                <ul>
                    <li><a href="<?= $baseUrl ?>/workouts/index.php?goal=fat_loss">Fat Loss</a></li>
                    <li><a href="<?= $baseUrl ?>/workouts/index.php?goal=weight_gain">Weight Gain</a></li>
                    <li><a href="<?= $baseUrl ?>/workouts/index.php?goal=fitness">General Fitness</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> FitPro. Built with ❤️ for fitness enthusiasts.</p>
    </div>
</footer>

<script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
