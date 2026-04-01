<?php
require 'config/database.php';

// Sample workout templates
$templates = [
    [
        'template_id' => 'tpl_001',
        'template_name' => 'Beginner Full Body',
        'template_type' => 'Full Body',
        'difficulty_level' => 'Beginner',
        'description' => 'A perfect starting point for fitness beginners. This 3-day per week program targets all major muscle groups with compound movements and bodyweight exercises.',
        'goal' => 'Build foundation and improve overall fitness',
        'duration_weeks' => 4,
        'exercises_count' => 6,
        'equipment_required' => 'Dumbbells, Barbell, Bench',
        'popularity_score' => 145,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_002',
        'template_name' => 'Push Pull Legs',
        'template_type' => 'Split',
        'difficulty_level' => 'Intermediate',
        'description' => 'Classic 3-day split routine that divides your workouts into push (chest, shoulders, triceps), pull (back, biceps), and leg days.',
        'goal' => 'Build muscle and increase strength',
        'duration_weeks' => 8,
        'exercises_count' => 12,
        'equipment_required' => 'Dumbbells, Barbell, Machines',
        'popularity_score' => 234,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_003',
        'template_name' => 'HIIT Cardio Blast',
        'template_type' => 'Cardio',
        'difficulty_level' => 'Intermediate',
        'description' => 'High-intensity interval training program designed to burn maximum calories in minimum time. Perfect for weight loss and cardiovascular conditioning.',
        'goal' => 'Fat loss and cardiovascular fitness',
        'duration_weeks' => 6,
        'exercises_count' => 8,
        'equipment_required' => 'Treadmill, Jump Rope, Resistance Bands',
        'popularity_score' => 187,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_004',
        'template_name' => 'Upper Lower Split',
        'template_type' => 'Split',
        'difficulty_level' => 'Intermediate',
        'description' => '4-day split routine alternating between upper body and lower body focused workouts for balanced development and recovery.',
        'goal' => 'Build muscle while maintaining balance',
        'duration_weeks' => 12,
        'exercises_count' => 14,
        'equipment_required' => 'Dumbbells, Barbell, Machines, Cable Station',
        'popularity_score' => 198,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_005',
        'template_name' => 'Advanced Hypertrophy',
        'template_type' => 'Hypertrophy',
        'difficulty_level' => 'Advanced',
        'description' => 'Extreme 5-day bodybuilding split designed for serious lifters looking to maximize muscle growth through advanced training principles.',
        'goal' => 'Maximize muscle hypertrophy',
        'duration_weeks' => 16,
        'exercises_count' => 16,
        'equipment_required' => 'Full gym access required',
        'popularity_score' => 156,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_006',
        'template_name' => 'Strength Foundation',
        'template_type' => 'Strength',
        'difficulty_level' => 'Beginner',
        'description' => 'Build a strong foundation with focus on compound movements. Learn proper form and build basic strength levels.',
        'goal' => 'Develop foundational strength',
        'duration_weeks' => 6,
        'exercises_count' => 5,
        'equipment_required' => 'Barbell, Rack, Bench',
        'popularity_score' => 112,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_007',
        'template_name' => 'Core & Stability',
        'template_type' => 'Core',
        'difficulty_level' => 'Beginner',
        'description' => 'Specialized 3-day program focused on developing core strength and stability. Great for injury prevention and posture improvement.',
        'goal' => 'Strengthen core and improve stability',
        'duration_weeks' => 4,
        'exercises_count' => 9,
        'equipment_required' => 'Resistance Bands, Stability Ball, Yoga Mat',
        'popularity_score' => 98,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_008',
        'template_name' => 'Olympic Lifting Prep',
        'template_type' => 'Weightlifting',
        'difficulty_level' => 'Advanced',
        'description' => 'Train Olympic lifting techniques including snatch and clean & jerk with specialized progressions and accessory work.',
        'goal' => 'Master Olympic lifting techniques',
        'duration_weeks' => 12,
        'exercises_count' => 11,
        'equipment_required' => 'Olympic Bars, Plates, Platform',
        'popularity_score' => 87,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_009',
        'template_name' => 'Flexibility & Mobility',
        'template_type' => 'Flexibility',
        'difficulty_level' => 'Beginner',
        'description' => '5-day stretching and mobility program designed to increase range of motion and reduce muscle tightness.',
        'goal' => 'Improve flexibility and range of motion',
        'duration_weeks' => 4,
        'exercises_count' => 12,
        'equipment_required' => 'Yoga Mat, Foam Roller, Resistance Bands',
        'popularity_score' => 134,
        'is_active' => 1
    ],
    [
        'template_id' => 'tpl_010',
        'template_name' => 'Athlete Performance',
        'template_type' => 'Sport-Specific',
        'difficulty_level' => 'Advanced',
        'description' => 'Advanced training program designed to enhance athletic performance, speed, agility, and explosive power for sports.',
        'goal' => 'Enhance athletic performance',
        'duration_weeks' => 10,
        'exercises_count' => 15,
        'equipment_required' => 'Plyometric Equipment, Cones, Agility Ladder',
        'popularity_score' => 145,
        'is_active' => 1
    ]
];

try {
    // Clear existing templates first
    $pdo->query("DELETE FROM workout_templates");
    
    // Insert new templates
    $stmt = $pdo->prepare("
        INSERT INTO workout_templates 
        (template_id, template_name, template_type, difficulty_level, description, goal, duration_weeks, exercises_count, equipment_required, popularity_score, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $count = 0;
    foreach ($templates as $template) {
        $stmt->execute([
            $template['template_id'],
            $template['template_name'],
            $template['template_type'],
            $template['difficulty_level'],
            $template['description'],
            $template['goal'],
            $template['duration_weeks'],
            $template['exercises_count'],
            $template['equipment_required'],
            $template['popularity_score'],
            $template['is_active']
        ]);
        $count++;
    }
    
    echo "✓ Successfully loaded $count workout templates!\n";
    
} catch (Exception $e) {
    echo "✗ Error loading templates: " . $e->getMessage() . "\n";
}
?>
