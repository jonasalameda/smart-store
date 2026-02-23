-- =====================================================
-- Adaptive Recipe Service - Database Schema
-- CREATE TABLE Statements + Sample Data
-- Total: 10 Tables with ~5 records each
-- =====================================================

-- =====================================================
-- CREATE TABLE STATEMENTS
-- =====================================================

CREATE TABLE Recipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    author VARCHAR(100),
    culture VARCHAR(50),
    prep_time INT,
    cook_time INT,
    total_time INT,
    servings INT DEFAULT 4,
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    cuisine_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cuisine (cuisine_type),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_name (name)
);

CREATE TABLE Ingredients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    category ENUM('dairy', 'protein', 'vegetable', 'fruit', 'grain', 'spice', 'condiment', 'oil', 'sweetener', 'beverage', 'other') NOT NULL,
    avg_price_per_unit DECIMAL(10, 2),
    price_currency VARCHAR(3) DEFAULT 'USD',
    standard_unit ENUM('g', 'kg', 'ml', 'l', 'piece', 'cup', 'tbsp', 'tsp', 'oz', 'lb') DEFAULT 'g',
    allergen_info JSON,
    nutritional_info JSON,
    is_perishable BOOLEAN DEFAULT FALSE,
    shelf_life_days INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
);

CREATE TABLE Recipe_Ingredients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit ENUM('g', 'kg', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'piece', 'pinch', 'oz', 'lb', 'clove', 'bunch') NOT NULL,
    is_optional BOOLEAN DEFAULT FALSE,
    preparation_note VARCHAR(100),
    display_order INT DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES Recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES Ingredients(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id),
    INDEX idx_ingredient (ingredient_id),
    UNIQUE KEY unique_recipe_ingredient (recipe_id, ingredient_id)
);

CREATE TABLE Ingredient_Substitutions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_ingredient_id INT NOT NULL,
    substitute_ingredient_id INT NOT NULL,
    conversion_ratio DECIMAL(5, 2) DEFAULT 1.00,
    notes TEXT,
    substitution_type ENUM('direct', 'partial', 'functional') DEFAULT 'direct',
    confidence_score INT DEFAULT 5,
    FOREIGN KEY (original_ingredient_id) REFERENCES Ingredients(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_ingredient_id) REFERENCES Ingredients(id) ON DELETE CASCADE,
    INDEX idx_original (original_ingredient_id),
    INDEX idx_substitute (substitute_ingredient_id),
    CHECK (confidence_score BETWEEN 1 AND 10),
    UNIQUE KEY unique_substitution (original_ingredient_id, substitute_ingredient_id)
);

CREATE TABLE Unit_Conversions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_unit ENUM('g', 'kg', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'oz', 'lb', 'piece') NOT NULL,
    to_unit ENUM('g', 'kg', 'ml', 'l', 'cup', 'tbsp', 'tsp', 'oz', 'lb', 'piece') NOT NULL,
    conversion_factor DECIMAL(10, 6) NOT NULL,
    ingredient_type VARCHAR(50),
    is_volume BOOLEAN DEFAULT FALSE,
    is_weight BOOLEAN DEFAULT FALSE,
    notes TEXT,
    INDEX idx_from_to (from_unit, to_unit),
    INDEX idx_ingredient_type (ingredient_type),
    UNIQUE KEY unique_conversion (from_unit, to_unit, ingredient_type)
);

CREATE TABLE Dietary_Restrictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    severity_level ENUM('preference', 'allergy', 'religious', 'ethical', 'medical') DEFAULT 'preference',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

CREATE TABLE Recipe_Dietary_Compatibility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT NOT NULL,
    restriction_id INT NOT NULL,
    is_compatible BOOLEAN DEFAULT TRUE,
    notes TEXT,
    FOREIGN KEY (recipe_id) REFERENCES Recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (restriction_id) REFERENCES Dietary_Restrictions(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id),
    INDEX idx_restriction (restriction_id),
    UNIQUE KEY unique_recipe_restriction (recipe_id, restriction_id)
);

CREATE TABLE Workouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    intensity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    duration INT,
    calories_burned_estimate INT,
    workout_area ENUM('upper_body', 'lower_body', 'core', 'full_body', 'cardio', 'flexibility') NOT NULL,
    equipment_needed JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intensity (intensity),
    INDEX idx_area (workout_area)
);

CREATE TABLE Exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    workout_area ENUM('upper_body', 'lower_body', 'core', 'full_body', 'cardio', 'flexibility') NOT NULL,
    equipment_needed VARCHAR(100),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    demonstration_video_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_area (workout_area),
    INDEX idx_difficulty (difficulty_level)
);

CREATE TABLE Workout_Exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workout_id INT NOT NULL,
    exercise_id INT NOT NULL,
    sets INT DEFAULT 3,
    reps INT,
    duration INT,
    rest_between_sets INT DEFAULT 60,
    order_in_workout INT DEFAULT 0,
    weight_recommendation VARCHAR(50),
    FOREIGN KEY (workout_id) REFERENCES Workouts(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES Exercises(id) ON DELETE CASCADE,
    INDEX idx_workout (workout_id),
    INDEX idx_exercise (exercise_id),
    UNIQUE KEY unique_workout_exercise (workout_id, exercise_id, order_in_workout)
);

-- =====================================================
-- SAMPLE DATA INSERTS
-- =====================================================

-- Insert Recipes (5 records)
INSERT INTO Recipes (name, description, author, culture, prep_time, cook_time, total_time, servings, difficulty_level, cuisine_type) VALUES
('Classic Spaghetti Carbonara', 'Traditional Italian pasta with eggs, cheese, and pancetta. Creamy and delicious without cream!', 'Chef Antonio Rossi', 'Italian', 10, 15, 25, 4, 'easy', 'italian'),
('Chicken Tikka Masala', 'Tender chicken in a rich, creamy tomato-based curry sauce with aromatic spices.', 'Chef Priya Sharma', 'Indian', 30, 40, 70, 6, 'medium', 'indian'),
('Beef Tacos', 'Quick and easy Mexican-style tacos with seasoned ground beef and fresh toppings.', 'Chef Maria Garcia', 'Mexican', 15, 20, 35, 4, 'easy', 'mexican'),
('Pad Thai', 'Classic Thai stir-fried rice noodles with shrimp, tofu, peanuts, and tangy sauce.', 'Chef Somchai Wong', 'Thai', 20, 15, 35, 4, 'medium', 'thai'),
('Greek Salad', 'Fresh Mediterranean salad with tomatoes, cucumbers, olives, and feta cheese.', 'Chef Yiannis Papadopoulos', 'Greek', 15, 0, 15, 4, 'easy', 'greek');

-- Insert Ingredients (15 records - more needed for recipes)
INSERT INTO Ingredients (name, category, avg_price_per_unit, price_currency, standard_unit, allergen_info, nutritional_info, is_perishable, shelf_life_days) VALUES
('Spaghetti', 'grain', 2.50, 'USD', 'kg', '["gluten"]', '{"calories": 371, "protein": 13, "carbs": 74, "fat": 1.5}', FALSE, 730),
('Eggs', 'protein', 4.00, 'USD', 'piece', '["eggs"]', '{"calories": 155, "protein": 13, "carbs": 1.1, "fat": 11}', TRUE, 28),
('Parmesan Cheese', 'dairy', 12.00, 'USD', 'kg', '["dairy"]', '{"calories": 431, "protein": 38, "carbs": 4, "fat": 29}', TRUE, 60),
('Bacon', 'protein', 8.00, 'USD', 'kg', NULL, '{"calories": 541, "protein": 37, "carbs": 1.4, "fat": 42}', TRUE, 14),
('Chicken Breast', 'protein', 7.50, 'USD', 'kg', NULL, '{"calories": 165, "protein": 31, "carbs": 0, "fat": 3.6}', TRUE, 3),
('Tomatoes', 'vegetable', 3.50, 'USD', 'kg', NULL, '{"calories": 18, "protein": 0.9, "carbs": 3.9, "fat": 0.2}', TRUE, 7),
('Onions', 'vegetable', 2.00, 'USD', 'kg', NULL, '{"calories": 40, "protein": 1.1, "carbs": 9.3, "fat": 0.1}', FALSE, 60),
('Garlic', 'vegetable', 4.50, 'USD', 'kg', NULL, '{"calories": 149, "protein": 6.4, "carbs": 33, "fat": 0.5}', FALSE, 90),
('Olive Oil', 'oil', 15.00, 'USD', 'l', NULL, '{"calories": 884, "protein": 0, "carbs": 0, "fat": 100}', FALSE, 365),
('Ground Beef', 'protein', 6.00, 'USD', 'kg', NULL, '{"calories": 250, "protein": 26, "carbs": 0, "fat": 15}', TRUE, 3),
('Tortillas', 'grain', 3.00, 'USD', 'piece', '["gluten"]', '{"calories": 218, "protein": 5.7, "carbs": 36, "fat": 5.6}', TRUE, 14),
('Lettuce', 'vegetable', 2.50, 'USD', 'piece', NULL, '{"calories": 15, "protein": 1.4, "carbs": 2.9, "fat": 0.2}', TRUE, 7),
('Rice Noodles', 'grain', 4.00, 'USD', 'kg', NULL, '{"calories": 192, "protein": 1.6, "carbs": 44, "fat": 0.3}', FALSE, 365),
('Shrimp', 'protein', 18.00, 'USD', 'kg', '["shellfish"]', '{"calories": 99, "protein": 24, "carbs": 0.2, "fat": 0.3}', TRUE, 2),
('Cucumbers', 'vegetable', 2.00, 'USD', 'kg', NULL, '{"calories": 16, "protein": 0.7, "carbs": 3.6, "fat": 0.1}', TRUE, 7);

-- Insert Recipe_Ingredients (25 records - linking recipes to ingredients)
-- Spaghetti Carbonara (Recipe 1)
INSERT INTO Recipe_Ingredients (recipe_id, ingredient_id, quantity, unit, is_optional, preparation_note, display_order) VALUES
(1, 1, 400, 'g', FALSE, 'dried pasta', 1),
(1, 2, 4, 'piece', FALSE, 'room temperature', 2),
(1, 3, 100, 'g', FALSE, 'freshly grated', 3),
(1, 4, 200, 'g', FALSE, 'diced', 4),
(1, 8, 2, 'clove', FALSE, 'minced', 5);

-- Chicken Tikka Masala (Recipe 2)
INSERT INTO Recipe_Ingredients (recipe_id, ingredient_id, quantity, unit, is_optional, preparation_note, display_order) VALUES
(2, 5, 800, 'g', FALSE, 'cut into chunks', 1),
(2, 6, 400, 'g', FALSE, 'crushed', 2),
(2, 7, 1, 'piece', FALSE, 'diced', 3),
(2, 8, 4, 'clove', FALSE, 'minced', 4),
(2, 9, 3, 'tbsp', FALSE, NULL, 5);

-- Beef Tacos (Recipe 3)
INSERT INTO Recipe_Ingredients (recipe_id, ingredient_id, quantity, unit, is_optional, preparation_note, display_order) VALUES
(3, 10, 500, 'g', FALSE, 'lean ground beef', 1),
(3, 11, 8, 'piece', FALSE, 'warmed', 2),
(3, 6, 2, 'piece', FALSE, 'diced', 3),
(3, 12, 1, 'piece', FALSE, 'shredded', 4),
(3, 7, 1, 'piece', FALSE, 'diced', 5);

-- Pad Thai (Recipe 4)
INSERT INTO Recipe_Ingredients (recipe_id, ingredient_id, quantity, unit, is_optional, preparation_note, display_order) VALUES
(4, 13, 250, 'g', FALSE, 'soaked', 1),
(4, 14, 300, 'g', FALSE, 'peeled and deveined', 2),
(4, 2, 2, 'piece', FALSE, 'beaten', 3),
(4, 8, 3, 'clove', FALSE, 'minced', 4),
(4, 9, 2, 'tbsp', FALSE, NULL, 5);

-- Greek Salad (Recipe 5)
INSERT INTO Recipe_Ingredients (recipe_id, ingredient_id, quantity, unit, is_optional, preparation_note, display_order) VALUES
(5, 6, 4, 'piece', FALSE, 'cut into wedges', 1),
(5, 15, 2, 'piece', FALSE, 'sliced', 2),
(5, 7, 1, 'piece', FALSE, 'thinly sliced', 3),
(5, 9, 4, 'tbsp', FALSE, 'extra virgin', 4),
(5, 3, 150, 'g', FALSE, 'crumbled feta', 5);

-- Insert Ingredient_Substitutions (5 records)
INSERT INTO Ingredient_Substitutions (original_ingredient_id, substitute_ingredient_id, conversion_ratio, notes, substitution_type, confidence_score) VALUES
(4, 5, 1.00, 'Bacon can be replaced with diced chicken for a lighter carbonara', 'partial', 6),
(3, 3, 1.00, 'Parmesan can substitute for itself in different recipes', 'direct', 10),
(10, 5, 1.00, 'Ground beef can be replaced with ground chicken for lower fat', 'direct', 8),
(14, 5, 1.00, 'Shrimp can be replaced with chicken chunks in Pad Thai', 'partial', 7),
(2, 2, 1.00, 'Eggs are often used as a direct substitute in various recipes', 'direct', 10);

-- Insert Unit_Conversions (10 records - essential conversions)
INSERT INTO Unit_Conversions (from_unit, to_unit, conversion_factor, ingredient_type, is_volume, is_weight, notes) VALUES
-- Volume conversions
('ml', 'l', 0.001000, NULL, TRUE, FALSE, 'Milliliters to liters'),
('l', 'ml', 1000.000000, NULL, TRUE, FALSE, 'Liters to milliliters'),
('cup', 'ml', 236.588000, NULL, TRUE, FALSE, 'US cup to milliliters'),
('tbsp', 'ml', 14.787000, NULL, TRUE, FALSE, 'Tablespoon to milliliters'),
('tsp', 'ml', 4.929000, NULL, TRUE, FALSE, 'Teaspoon to milliliters'),
-- Weight conversions
('g', 'kg', 0.001000, NULL, FALSE, TRUE, 'Grams to kilograms'),
('kg', 'g', 1000.000000, NULL, FALSE, TRUE, 'Kilograms to grams'),
('oz', 'g', 28.349500, NULL, FALSE, TRUE, 'Ounces to grams'),
('lb', 'g', 453.592000, NULL, FALSE, TRUE, 'Pounds to grams'),
-- Ingredient-specific conversions
('cup', 'g', 120.000000, 'flour', TRUE, TRUE, '1 cup all-purpose flour to grams');

-- Insert Dietary_Restrictions (6 records)
INSERT INTO Dietary_Restrictions (name, description, severity_level) VALUES
('Vegetarian', 'No meat, poultry, or fish. Eggs and dairy are allowed.', 'preference'),
('Vegan', 'No animal products including meat, dairy, eggs, and honey.', 'ethical'),
('Gluten-Free', 'No wheat, barley, rye, or gluten-containing grains.', 'allergy'),
('Dairy-Free', 'No milk, cheese, butter, or dairy-based products.', 'allergy'),
('Halal', 'Food prepared according to Islamic dietary guidelines.', 'religious'),
('Nut-Free', 'No tree nuts or peanuts to avoid allergic reactions.', 'allergy');

-- Insert Recipe_Dietary_Compatibility (10 records)
INSERT INTO Recipe_Dietary_Compatibility (recipe_id, restriction_id, is_compatible, notes) VALUES
(1, 1, FALSE, 'Contains bacon. Can be made vegetarian by omitting bacon and using mushrooms.'),
(1, 2, FALSE, 'Contains eggs and parmesan cheese.'),
(1, 3, FALSE, 'Contains wheat pasta. Use gluten-free pasta instead.'),
(5, 1, TRUE, 'Greek salad is naturally vegetarian.'),
(5, 3, TRUE, 'Greek salad is naturally gluten-free.'),
(2, 5, TRUE, 'Chicken Tikka Masala can be prepared halal with proper meat sourcing.'),
(3, 3, FALSE, 'Contains wheat tortillas. Use corn tortillas for gluten-free version.'),
(4, 3, TRUE, 'Rice noodles are naturally gluten-free.'),
(4, 6, TRUE, 'Pad Thai typically does not contain nuts, but verify peanut garnish is omitted.'),
(2, 4, FALSE, 'Contains cream/yogurt. Can be made dairy-free with coconut cream.');

-- Insert Workouts (5 records)
INSERT INTO Workouts (name, description, intensity, duration, calories_burned_estimate, workout_area, equipment_needed) VALUES
('Full Body HIIT', 'High-intensity interval training targeting all major muscle groups with bodyweight exercises.', 'high', 30, 350, 'full_body', '["mat", "timer"]'),
('Upper Body Strength', 'Focused resistance training for chest, back, shoulders, and arms using dumbbells.', 'medium', 45, 280, 'upper_body', '["dumbbells", "bench"]'),
('Core Blast', 'Intensive core workout targeting abs, obliques, and lower back for stability and strength.', 'medium', 20, 150, 'core', '["mat", "medicine_ball"]'),
('Leg Day Power', 'Lower body workout focusing on quads, hamstrings, glutes, and calves with weights.', 'high', 50, 400, 'lower_body', '["barbell", "squat_rack", "dumbbells"]'),
('Cardio Burn', 'Steady-state and interval cardio for cardiovascular health and fat burning.', 'high', 40, 450, 'cardio', '["treadmill", "jump_rope"]');

-- Insert Exercises (10 records)
INSERT INTO Exercises (name, description, workout_area, equipment_needed, difficulty_level, demonstration_video_url) VALUES
('Push-ups', 'Classic bodyweight exercise targeting chest, shoulders, and triceps.', 'upper_body', 'none', 'beginner', 'https://youtube.com/watch?v=example1'),
('Squats', 'Fundamental lower body exercise for quads, glutes, and hamstrings.', 'lower_body', 'none', 'beginner', 'https://youtube.com/watch?v=example2'),
('Plank', 'Isometric core exercise that builds stability and endurance.', 'core', 'mat', 'beginner', 'https://youtube.com/watch?v=example3'),
('Burpees', 'Full-body explosive exercise combining squat, push-up, and jump.', 'full_body', 'none', 'intermediate', 'https://youtube.com/watch?v=example4'),
('Dumbbell Bench Press', 'Upper body pressing movement for chest and triceps development.', 'upper_body', 'dumbbells, bench', 'intermediate', 'https://youtube.com/watch?v=example5'),
('Deadlifts', 'Compound exercise targeting hamstrings, glutes, and back muscles.', 'lower_body', 'barbell', 'advanced', 'https://youtube.com/watch?v=example6'),
('Mountain Climbers', 'Dynamic cardio and core exercise performed in plank position.', 'cardio', 'mat', 'intermediate', 'https://youtube.com/watch?v=example7'),
('Russian Twists', 'Rotational core exercise targeting obliques and abs.', 'core', 'medicine ball', 'intermediate', 'https://youtube.com/watch?v=example8'),
('Pull-ups', 'Upper body pulling exercise for back, biceps, and grip strength.', 'upper_body', 'pull-up bar', 'advanced', 'https://youtube.com/watch?v=example9'),
('Jump Rope', 'Cardio exercise for coordination, endurance, and calorie burning.', 'cardio', 'jump rope', 'beginner', 'https://youtube.com/watch?v=example10');

-- Insert Workout_Exercises (20 records - linking workouts to exercises)
-- Full Body HIIT (Workout 1)
INSERT INTO Workout_Exercises (workout_id, exercise_id, sets, reps, duration, rest_between_sets, order_in_workout, weight_recommendation) VALUES
(1, 4, 4, 15, 30, 30, 1, 'bodyweight'),
(1, 7, 4, 20, 30, 30, 2, 'bodyweight'),
(1, 2, 4, 20, NULL, 30, 3, 'bodyweight'),
(1, 1, 4, 15, NULL, 30, 4, 'bodyweight');

-- Upper Body Strength (Workout 2)
INSERT INTO Workout_Exercises (workout_id, exercise_id, sets, reps, duration, rest_between_sets, order_in_workout, weight_recommendation) VALUES
(2, 5, 4, 10, NULL, 90, 1, '25-35 lbs per dumbbell'),
(2, 1, 3, 12, NULL, 60, 2, 'bodyweight'),
(2, 9, 3, 8, NULL, 120, 3, 'bodyweight or assisted'),
(2, 5, 3, 12, NULL, 90, 4, '20-30 lbs per dumbbell');

-- Core Blast (Workout 3)
INSERT INTO Workout_Exercises (workout_id, exercise_id, sets, reps, duration, rest_between_sets, order_in_workout, weight_recommendation) VALUES
(3, 3, 3, NULL, 60, 45, 1, 'bodyweight'),
(3, 8, 3, 20, NULL, 45, 2, '10-15 lbs medicine ball'),
(3, 7, 3, 30, 30, 45, 3, 'bodyweight'),
(3, 3, 2, NULL, 45, 30, 4, 'bodyweight');

-- Leg Day Power (Workout 4)
INSERT INTO Workout_Exercises (workout_id, exercise_id, sets, reps, duration, rest_between_sets, order_in_workout, weight_recommendation) VALUES
(4, 2, 4, 12, NULL, 90, 1, 'bodyweight or 45-95 lbs'),
(4, 6, 4, 8, NULL, 120, 2, '95-185 lbs'),
(4, 2, 3, 15, NULL, 90, 3, '25-45 lbs per dumbbell'),
(4, 6, 3, 10, NULL, 120, 4, '135-225 lbs');

-- Cardio Burn (Workout 5)
INSERT INTO Workout_Exercises (workout_id, exercise_id, sets, reps, duration, rest_between_sets, order_in_workout, weight_recommendation) VALUES
(5, 10, 4, NULL, 180, 60, 1, 'none'),
(5, 7, 4, 40, 45, 45, 2, 'bodyweight'),
(5, 4, 3, 15, NULL, 45, 3, 'bodyweight'),
(5, 10, 3, NULL, 120, 60, 4, 'none');

-- =====================================================
-- End of SQL Script
-- =====================================================
