-- ============================================================
-- FitPro Database - fitpro.sql
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS fitpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitpro;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('workout','meal') NOT NULL DEFAULT 'workout',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    age INT DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT NULL,
    height DECIMAL(5,2) DEFAULT NULL,
    gender ENUM('male','female','other') DEFAULT NULL,
    goal ENUM('fat_loss','weight_gain','fitness') DEFAULT 'fitness',
    activity_level ENUM('sedentary','lightly_active','moderately_active','very_active','extra_active') DEFAULT 'moderately_active',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: workouts
-- ============================================================
CREATE TABLE IF NOT EXISTS workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    difficulty ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    duration_minutes INT NOT NULL DEFAULT 30,
    calories_burned INT DEFAULT NULL,
    equipment VARCHAR(255) DEFAULT NULL,
    muscle_group VARCHAR(255) DEFAULT NULL,
    instructions TEXT,
    video_url VARCHAR(500) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    goal_tag ENUM('fat_loss','weight_gain','fitness','all') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: meals
-- ============================================================
CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    calories INT NOT NULL DEFAULT 0,
    protein DECIMAL(6,2) DEFAULT NULL,
    carbs DECIMAL(6,2) DEFAULT NULL,
    fat DECIMAL(6,2) DEFAULT NULL,
    fiber DECIMAL(6,2) DEFAULT NULL,
    meal_type ENUM('breakfast','lunch','dinner','snack','pre_workout','post_workout') NOT NULL DEFAULT 'lunch',
    prep_time_minutes INT DEFAULT NULL,
    servings INT DEFAULT 1,
    ingredients TEXT,
    instructions TEXT,
    image_url VARCHAR(500) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    goal_tag ENUM('fat_loss','weight_gain','fitness','all') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: user_progress
-- ============================================================
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_date DATE NOT NULL DEFAULT (CURDATE()),
    weight DECIMAL(5,2) DEFAULT NULL,
    bmi DECIMAL(5,2) DEFAULT NULL,
    bmr DECIMAL(7,2) DEFAULT NULL,
    tdee DECIMAL(7,2) DEFAULT NULL,
    calories_consumed INT DEFAULT NULL,
    calories_burned INT DEFAULT NULL,
    workout_id INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA: categories
-- ============================================================
INSERT INTO categories (name, type, description) VALUES
('Strength Training', 'workout', 'Resistance and weight-based exercises'),
('Cardio', 'workout', 'Cardiovascular endurance workouts'),
('HIIT', 'workout', 'High-Intensity Interval Training'),
('Yoga & Flexibility', 'workout', 'Yoga, stretching, and mobility work'),
('Core & Abs', 'workout', 'Core stability and abdominal training'),
('High Protein', 'meal', 'Meals rich in protein for muscle building'),
('Low Carb', 'meal', 'Reduced-carbohydrate meal options'),
('Balanced Diet', 'meal', 'Well-balanced nutritional meals'),
('Pre-Workout Fuel', 'meal', 'Energy-boosting meals before exercise'),
('Recovery Meals', 'meal', 'Meals to aid recovery post workout');

-- ============================================================
-- SAMPLE DATA: users (password = "Admin@123" hashed with bcrypt)
-- ============================================================
INSERT INTO users (name, email, password, role, age, weight, height, gender, goal, activity_level) VALUES
('Admin User', 'admin@fitpro.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 30, 75.00, 175.00, 'male', 'fitness', 'moderately_active'),
('John Doe', 'john@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 28, 80.00, 178.00, 'male', 'fat_loss', 'lightly_active'),
('Jane Smith', 'jane@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 25, 60.00, 165.00, 'female', 'fitness', 'moderately_active');

-- ============================================================
-- SAMPLE DATA: workouts
-- ============================================================
INSERT INTO workouts (category_id, title, description, difficulty, duration_minutes, calories_burned, equipment, muscle_group, instructions, image_url, is_featured, goal_tag) VALUES
(1, 'Full Body Strength Blast', 'A comprehensive full-body strength workout using compound movements to build muscle and burn fat simultaneously.', 'intermediate', 45, 350, 'Dumbbells, Barbell, Bench', 'Full Body', '1. Warm up for 5 minutes with light cardio.\n2. Barbell squats: 4 sets x 8 reps.\n3. Bench press: 4 sets x 8 reps.\n4. Deadlifts: 3 sets x 6 reps.\n5. Pull-ups or lat pulldown: 3 sets x 10 reps.\n6. Overhead press: 3 sets x 10 reps.\n7. Cool down and stretch for 5 minutes.', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&q=80', 1, 'fitness'),
(2, 'Morning Cardio Run', 'A steady-state morning cardio session designed to boost metabolism and burn fat effectively.', 'beginner', 30, 280, 'None (outdoor or treadmill)', 'Legs, Cardiovascular', '1. Start with a 5-minute brisk walk.\n2. Run at a comfortable pace (60-70% max HR) for 20 minutes.\n3. Cool down with a 5-minute walk.\n4. Stretch quads, hamstrings, and calves.', 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80', 1, 'fat_loss'),
(3, 'HIIT Fat Burner', 'An intense interval training session that maximizes calorie burn in minimum time using bodyweight exercises.', 'advanced', 25, 400, 'None (bodyweight)', 'Full Body', '1. Warm up: 2 minutes jumping jacks.\n2. 8 rounds of: 20s Burpees, 10s rest, 20s Mountain Climbers, 10s rest.\n3. 8 rounds of: 20s Jump Squats, 10s rest, 20s High Knees, 10s rest.\n4. Cool down: 3 minutes light stretching.', 'https://images.unsplash.com/photo-1549060279-7e168fcee0c2?w=600&q=80', 1, 'fat_loss'),
(1, 'Upper Body Power', 'Focused upper body workout targeting chest, back, shoulders, and arms for strength and hypertrophy.', 'intermediate', 50, 300, 'Dumbbells, Pull-up bar', 'Chest, Back, Shoulders, Arms', '1. Push-ups: 3 sets x 15 reps.\n2. Dumbbell rows: 3 sets x 12 reps.\n3. Dumbbell chest press: 3 sets x 12 reps.\n4. Lateral raises: 3 sets x 15 reps.\n5. Bicep curls: 3 sets x 12 reps.\n6. Tricep dips: 3 sets x 12 reps.', 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80', 0, 'weight_gain'),
(4, 'Power Yoga Flow', 'A dynamic yoga session combining strength, flexibility, and mindfulness for overall fitness and stress relief.', 'beginner', 40, 180, 'Yoga mat', 'Full Body, Flexibility', '1. Sun salutations: 5 rounds.\n2. Warrior sequence I, II, III: hold each 30 seconds.\n3. Balance poses: Tree, Eagle (30s each).\n4. Core work: Boat pose, Plank variations.\n5. Cool down: Pigeon pose, Savasana (5 minutes).', 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=600&q=80', 0, 'fitness'),
(5, 'Core Crusher Circuit', 'An intense ab and core workout that targets all layers of the abdominal muscles for a strong, defined midsection.', 'intermediate', 20, 150, 'Exercise mat', 'Core, Abs', '1. Plank: 3 x 60 seconds.\n2. Crunches: 3 x 20 reps.\n3. Leg raises: 3 x 15 reps.\n4. Russian twists: 3 x 20 reps.\n5. Bicycle crunches: 3 x 20 reps.\n6. Side plank: 2 x 45s each side.', 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80', 1, 'fitness'),
(2, 'Beginner Walk & Jog', 'Perfect for beginners, this walk-jog interval program builds cardiovascular fitness gradually and safely.', 'beginner', 35, 200, 'None (outdoor or treadmill)', 'Legs, Cardiovascular', '1. Walk briskly for 5 minutes.\n2. Alternate: jog 1 min, walk 2 min — repeat 8 times.\n3. Cool down walk: 5 minutes.\n4. Stretch all major leg muscles.', 'https://images.unsplash.com/photo-1483721310020-03333e577078?w=600&q=80', 0, 'fat_loss'),
(3, 'Tabata Bodyweight Blitz', 'Classic Tabata protocol with 8 exercises for a metabolism-spiking full body workout.', 'advanced', 30, 380, 'None (bodyweight)', 'Full Body', '1. Complete 8 rounds of each exercise (20s on, 10s off).\n2. Exercise 1: Burpees.\n3. Exercise 2: Jump Squats.\n4. Exercise 3: Push-ups.\n5. Exercise 4: Mountain Climbers.\n6. Rest 1 minute between exercises.\n7. Cool down thoroughly.', 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80', 0, 'fat_loss'),
(1, 'Leg Day Hypertrophy', 'A focused leg workout designed to maximize muscle growth in the quads, hamstrings, and glutes.', 'advanced', 60, 420, 'Barbell, Leg press machine, Dumbbells', 'Legs, Glutes', '1. Barbell squats: 5 sets x 5 reps.\n2. Romanian deadlifts: 4 sets x 8 reps.\n3. Leg press: 4 sets x 10 reps.\n4. Lunges: 3 sets x 12 each leg.\n5. Leg curls: 3 sets x 12 reps.\n6. Calf raises: 4 sets x 20 reps.', 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80', 0, 'weight_gain'),
(4, 'Flexibility & Recovery', 'A gentle stretching and mobility routine perfect for rest days or post-workout recovery.', 'beginner', 25, 80, 'Yoga mat, Foam roller', 'Full Body, Flexibility', '1. Foam roll major muscle groups: 2 min each.\n2. Hip flexor stretch: 60s each side.\n3. Hamstring stretch: 60s each side.\n4. Thoracic spine rotation: 10 reps each side.\n5. Child pose: 2 minutes.\n6. Pigeon pose: 90s each side.', 'https://images.unsplash.com/photo-1600618528240-fb9fc964b853?w=600&q=80', 0, 'fitness');

-- ============================================================
-- SAMPLE DATA: meals
-- ============================================================
INSERT INTO meals (category_id, title, description, calories, protein, carbs, fat, fiber, meal_type, prep_time_minutes, servings, ingredients, instructions, is_featured, goal_tag) VALUES
(6, 'High-Protein Chicken Bowl', 'A muscle-building bowl packed with lean chicken, quinoa, and roasted vegetables for optimal recovery.', 520, 48.5, 42.0, 12.0, 7.5, 'lunch', 25, 1, 'Chicken breast: 200g\nQuinoa (cooked): 150g\nBroccoli: 100g\nBell pepper: 80g\nOlive oil: 1 tbsp\nLemon juice: 1 tbsp\nGarlic: 2 cloves\nSalt, pepper, paprika to taste', '1. Season chicken with salt, pepper, and paprika.\n2. Grill or bake chicken at 200°C for 20 minutes.\n3. Cook quinoa per package instructions.\n4. Roast broccoli and bell pepper with olive oil at 200°C for 15 minutes.\n5. Slice chicken and serve over quinoa with vegetables.\n6. Drizzle with lemon juice.', 1, 'weight_gain'),
(8, 'Balanced Oats Breakfast', 'A nutritious breakfast with oats, banana, and nuts that provides sustained energy throughout the morning.', 380, 14.0, 58.0, 11.0, 8.0, 'breakfast', 10, 1, 'Rolled oats: 80g\nBanana: 1 medium\nAlmonds: 20g\nHoney: 1 tsp\nMilk or almond milk: 200ml\nChia seeds: 1 tbsp\nCinnamon: 1/2 tsp', '1. Combine oats and milk in a saucepan over medium heat.\n2. Cook for 5 minutes, stirring occasionally.\n3. Remove from heat and top with sliced banana.\n4. Add almonds, chia seeds, and a drizzle of honey.\n5. Sprinkle with cinnamon and serve warm.', 1, 'fitness'),
(7, 'Greek Salad with Tuna', 'A light, low-carb lunch loaded with protein from tuna and healthy fats from olives and feta cheese.', 320, 32.0, 12.0, 15.5, 4.0, 'lunch', 15, 1, 'Tuna (canned in water): 150g\nCucumber: 100g\nCherry tomatoes: 100g\nFeta cheese: 50g\nKalamata olives: 30g\nRed onion: 40g\nOlive oil: 1.5 tbsp\nLemon juice: 1 tbsp\nOregano, salt, pepper', '1. Drain tuna thoroughly.\n2. Chop cucumber, halve tomatoes, slice red onion.\n3. Combine all vegetables in a bowl.\n4. Add tuna, crumbled feta, and olives.\n5. Whisk olive oil, lemon juice, oregano, salt, and pepper.\n6. Drizzle dressing and toss gently to combine.', 1, 'fat_loss'),
(9, 'Pre-Workout Banana Toast', 'Quick and easy pre-workout fuel combining complex carbs and potassium to power your training session.', 290, 8.0, 52.0, 6.5, 5.0, 'pre_workout', 5, 1, 'Whole grain bread: 2 slices\nBanana: 1 large\nPeanut butter: 1 tbsp\nHoney: 1 tsp', '1. Toast bread slices until golden.\n2. Spread peanut butter on each slice.\n3. Slice banana and layer on top.\n4. Drizzle with honey.\n5. Consume 30-45 minutes before workout.', 0, 'fitness'),
(10, 'Post-Workout Protein Shake', 'A fast-absorbing post-workout shake with whey protein, berries, and banana for rapid muscle recovery.', 340, 35.0, 38.0, 5.0, 4.0, 'post_workout', 5, 1, 'Whey protein powder: 1 scoop (30g)\nBanana: 1 medium\nFrozen mixed berries: 100g\nAlmond milk: 250ml\nGreek yogurt: 100g\nHoney: 1 tsp', '1. Add all ingredients to a blender.\n2. Blend on high for 30-45 seconds until smooth.\n3. Pour into a glass and consume within 30 minutes post-workout.\n4. Optional: add ice for a thicker shake.', 1, 'weight_gain'),
(6, 'Egg White Omelette', 'A high-protein, low-fat breakfast perfect for fat loss while preserving lean muscle mass.', 220, 28.0, 8.0, 7.0, 2.0, 'breakfast', 12, 1, 'Egg whites: 5 (or 150ml liquid egg whites)\nSpinach: 50g\nMushrooms: 60g\nBell pepper: 50g\nFeta cheese: 25g\nOlive oil: 1 tsp\nSalt, pepper, herbs', '1. Whisk egg whites with salt and pepper.\n2. Heat oil in non-stick pan over medium heat.\n3. Sauté mushrooms and bell pepper for 3 minutes.\n4. Add spinach and cook 1 minute until wilted.\n5. Pour egg whites over vegetables.\n6. Cook until edges set, fold in half.\n7. Top with feta and serve immediately.', 0, 'fat_loss'),
(7, 'Cauliflower Rice Stir Fry', 'A low-carb alternative to traditional fried rice, packed with vegetables and lean protein.', 280, 26.0, 18.0, 10.0, 9.0, 'dinner', 20, 2, 'Cauliflower (riced): 400g\nChicken breast: 150g\nEgg: 2\nCarrots: 80g\nGreen peas: 80g\nSoy sauce (low sodium): 2 tbsp\nSesame oil: 1 tsp\nGarlic: 3 cloves\nGinger: 1 tsp\nGreen onions: 30g', '1. Rice cauliflower in a food processor.\n2. Dice chicken and stir-fry until cooked through.\n3. Push aside, scramble eggs in the same pan.\n4. Add garlic and ginger, cook 30 seconds.\n5. Add cauliflower, carrots, and peas, stir-fry 5 minutes.\n6. Add chicken, soy sauce, sesame oil.\n7. Garnish with green onions.', 1, 'fat_loss'),
(8, 'Salmon with Sweet Potato', 'A perfectly balanced dinner with omega-3 rich salmon and vitamin-packed sweet potato.', 480, 38.0, 42.0, 14.0, 6.0, 'dinner', 30, 1, 'Salmon fillet: 200g\nSweet potato: 200g\nAsparagus: 100g\nOlive oil: 1.5 tbsp\nLemon: 1\nGarlic: 2 cloves\nRosemary, dill, salt, pepper', '1. Preheat oven to 200°C.\n2. Cube sweet potato, toss with 0.5 tbsp olive oil, roast 25 minutes.\n3. Season salmon with salt, pepper, dill, and lemon zest.\n4. Heat remaining oil, sear salmon 3 min per side.\n5. Sauté asparagus with garlic until tender-crisp.\n6. Plate with lemon wedge and serve.', 1, 'fitness'),
(6, 'Cottage Cheese Snack Bowl', 'A quick high-protein snack that is filling and supports muscle recovery between meals.', 180, 22.0, 14.0, 3.5, 1.5, 'snack', 5, 1, 'Cottage cheese (low fat): 200g\nPineapple chunks: 80g\nHoney: 1 tsp\nFlaxseeds: 1 tbsp\nCinnamon: 1/4 tsp', '1. Scoop cottage cheese into a bowl.\n2. Top with pineapple chunks.\n3. Drizzle with honey.\n4. Sprinkle flaxseeds and cinnamon.\n5. Enjoy immediately or refrigerate for up to 24 hours.', 0, 'weight_gain'),
(9, 'Whole Grain Pasta Bolognese', 'A mass-gaining pasta dish with lean beef bolognese sauce providing high carbs and quality protein.', 620, 38.0, 72.0, 16.0, 8.0, 'dinner', 35, 2, 'Whole grain pasta: 200g\nLean ground beef (90%): 200g\nCanned crushed tomatoes: 400g\nOnion: 1 medium\nGarlic: 3 cloves\nCarrots: 60g\nCelery: 60g\nOlive oil: 1 tbsp\nBasil, oregano, salt, pepper\nParmesan: 20g', '1. Cook pasta al dente per package instructions.\n2. Sauté onion, carrot, celery in oil until soft.\n3. Add garlic, cook 30 seconds.\n4. Brown ground beef, drain excess fat.\n5. Add tomatoes, herbs, salt and simmer 20 minutes.\n6. Toss with pasta, top with Parmesan.', 0, 'weight_gain');

-- ============================================================
-- SAMPLE DATA: user_progress
-- ============================================================
INSERT INTO user_progress (user_id, log_date, weight, bmi, bmr, tdee, calories_consumed, calories_burned, notes) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 80.5, 25.4, 1850.2, 2590.3, 2200, 350, 'Feeling good, started new plan'),
(2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 80.2, 25.3, 1848.8, 2588.4, 2100, 400, 'Great workout session'),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 80.0, 25.2, 1847.4, 2586.4, 2300, 280, 'Rest day'),
(2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 79.8, 25.2, 1845.9, 2584.3, 2000, 380, 'HIIT session done'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 79.6, 25.1, 1844.5, 2582.3, 2150, 320, 'Good day overall'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 79.4, 25.1, 1843.1, 2580.3, 2050, 400, 'Cardio morning run'),
(2, CURDATE(), 79.2, 25.0, 1841.7, 2578.4, 2100, 350, 'Consistent progress!'),
(3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 60.2, 22.1, 1420.5, 1988.7, 1800, 180, 'Started yoga routine'),
(3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 60.0, 22.0, 1419.3, 1987.0, 1750, 200, 'Feeling energized'),
(3, CURDATE(), 59.8, 22.0, 1418.0, 1985.2, 1700, 180, 'Great yoga session');
