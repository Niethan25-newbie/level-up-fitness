<?php
require 'config/database.php';

try {
    // Create the workout_templates table
    $sql = "
    CREATE TABLE IF NOT EXISTS workout_templates (
        template_id VARCHAR(50) PRIMARY KEY,
        template_name VARCHAR(255) NOT NULL,
        template_type VARCHAR(100) NOT NULL,
        difficulty_level VARCHAR(50) NOT NULL,
        description LONGTEXT NOT NULL,
        goal VARCHAR(255) NOT NULL,
        duration_weeks INT NOT NULL,
        exercises_count INT NOT NULL,
        equipment_required VARCHAR(255),
        popularity_score INT DEFAULT 0,
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_difficulty_level (difficulty_level),
        INDEX idx_template_type (template_type),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ workout_templates table created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
}
?>
