<?php
require 'config/database.php';

try {
    // Check if template_id column already exists
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'workout_plans' AND COLUMN_NAME = 'template_id'");
    $rows = $result->fetchAll();
    
    if (empty($rows)) {
        echo "Adding template_id column to workout_plans table...\n";
        
        // Add template_id column after workout_plan_id
        $pdo->exec("ALTER TABLE workout_plans ADD COLUMN template_id VARCHAR(50) AFTER workout_plan_id");
        
        // Add the foreign key constraint
        $pdo->exec("ALTER TABLE workout_plans ADD CONSTRAINT fk_template_id FOREIGN KEY (template_id) REFERENCES workout_templates(template_id) ON DELETE SET NULL");
        
        // Add index on template_id
        $pdo->exec("ALTER TABLE workout_plans ADD INDEX idx_template_id (template_id)");
        
        echo "✓ Successfully added template_id column with foreign key and index!\n";
    } else {
        echo "✓ template_id column already exists\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
