<?php
// ============================================================
// includes/functions.php — Helper Functions
// ============================================================

/**
 * Sanitize output for HTML display
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if logged-in user is admin
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login — redirect if not logged in
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/fitness_planner/login.php');
    }
}

/**
 * Require admin — redirect if not admin
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect('/fitness_planner/index.php');
    }
}

/**
 * Get current logged-in user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Set a flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Calculate BMI
 */
function calculateBMI(float $weight, float $height): float {
    // weight in kg, height in cm
    $heightM = $height / 100;
    return round($weight / ($heightM * $heightM), 2);
}

/**
 * Get BMI category
 */
function getBMICategory(float $bmi): array {
    if ($bmi < 18.5) return ['label' => 'Underweight', 'color' => '#60a5fa'];
    if ($bmi < 25.0) return ['label' => 'Normal Weight', 'color' => '#4ade80'];
    if ($bmi < 30.0) return ['label' => 'Overweight', 'color' => '#fbbf24'];
    return ['label' => 'Obese', 'color' => '#f87171'];
}

/**
 * Calculate BMR using Mifflin-St Jeor formula
 */
function calculateBMR(float $weight, float $height, int $age, string $gender): float {
    // weight kg, height cm
    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
    return $gender === 'female' ? round($bmr - 161, 2) : round($bmr + 5, 2);
}

/**
 * Calculate TDEE
 */
function calculateTDEE(float $bmr, string $activityLevel): float {
    $multipliers = [
        'sedentary'          => 1.2,
        'lightly_active'     => 1.375,
        'moderately_active'  => 1.55,
        'very_active'        => 1.725,
        'extra_active'       => 1.9,
    ];
    $mult = $multipliers[$activityLevel] ?? 1.55;
    return round($bmr * $mult, 2);
}

/**
 * AI Rule-Based Recommendation
 */
function getAIRecommendation(string $goal, float $bmi, float $tdee): array {
    $rec = [
        'goal'     => $goal,
        'calories' => 0,
        'protein'  => 0,
        'carbs'    => 0,
        'fat'      => 0,
        'tips'     => [],
        'workouts' => [],
        'meals'    => [],
    ];

    if ($goal === 'fat_loss') {
        $rec['calories'] = round($tdee - 500);
        $rec['protein']  = round($rec['calories'] * 0.35 / 4);
        $rec['carbs']    = round($rec['calories'] * 0.35 / 4);
        $rec['fat']      = round($rec['calories'] * 0.30 / 9);
        $rec['tips'] = [
            'Create a 500 calorie daily deficit for ~0.5kg/week loss',
            'Prioritize high-protein foods to preserve muscle mass',
            'Include 3-4 cardio sessions per week (30-45 min)',
            'Stay hydrated: aim for 2.5–3 liters of water daily',
            'Get 7-9 hours of sleep to support fat metabolism',
        ];
        $rec['workout_tag'] = 'fat_loss';
        $rec['meal_tag']    = 'fat_loss';
    } elseif ($goal === 'weight_gain') {
        $rec['calories'] = round($tdee + 400);
        $rec['protein']  = round($rec['calories'] * 0.30 / 4);
        $rec['carbs']    = round($rec['calories'] * 0.45 / 4);
        $rec['fat']      = round($rec['calories'] * 0.25 / 9);
        $rec['tips'] = [
            'Eat 300-500 calories above TDEE for lean muscle gain',
            'Aim for 1.6-2.2g of protein per kg of body weight',
            'Focus on compound lifts: squats, deadlifts, bench press',
            'Eat every 3-4 hours to maintain positive nitrogen balance',
            'Track progress with weekly weight measurements',
        ];
        $rec['workout_tag'] = 'weight_gain';
        $rec['meal_tag']    = 'weight_gain';
    } else {
        // fitness / maintenance
        $rec['calories'] = round($tdee);
        $rec['protein']  = round($rec['calories'] * 0.30 / 4);
        $rec['carbs']    = round($rec['calories'] * 0.40 / 4);
        $rec['fat']      = round($rec['calories'] * 0.30 / 9);
        $rec['tips'] = [
            'Eat at maintenance calories to sustain performance',
            'Balance strength training with cardio 3-4x per week',
            'Include flexibility and mobility work on rest days',
            'Focus on whole, minimally processed foods',
            'Monitor energy levels and adjust calories as needed',
        ];
        $rec['workout_tag'] = 'fitness';
        $rec['meal_tag']    = 'fitness';
    }

    return $rec;
}

/**
 * Format activity level for display
 */
function formatActivity(string $level): string {
    $labels = [
        'sedentary'         => 'Sedentary (little/no exercise)',
        'lightly_active'    => 'Lightly Active (1-3 days/week)',
        'moderately_active' => 'Moderately Active (3-5 days/week)',
        'very_active'       => 'Very Active (6-7 days/week)',
        'extra_active'      => 'Extra Active (physical job)',
    ];
    return $labels[$level] ?? ucfirst(str_replace('_', ' ', $level));
}

/**
 * Truncate text
 */
function truncate(string $text, int $limit = 120): string {
    $text = strip_tags($text);
    if (strlen($text) <= $limit) return $text;
    return substr($text, 0, $limit) . '...';
}

/**
 * Difficulty badge color
 */
function difficultyColor(string $difficulty): string {
    return match($difficulty) {
        'beginner'     => '#4ade80',
        'intermediate' => '#fbbf24',
        'advanced'     => '#f87171',
        default        => '#94a3b8',
    };
}

/**
 * Goal tag label
 */
function goalLabel(string $goal): string {
    return match($goal) {
        'fat_loss'    => '🔥 Fat Loss',
        'weight_gain' => '💪 Weight Gain',
        'fitness'     => '⚡ Fitness',
        'all'         => '✨ All Goals',
        default       => ucfirst($goal),
    };
}
