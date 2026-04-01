<?php
require 'config/database.php';

// Get table structure
$schema = $pdo->query("DESCRIBE workout_templates")->fetchAll(PDO::FETCH_ASSOC);
echo "Workout Templates Table Structure:\n";
echo str_repeat("=", 80) . "\n";
foreach ($schema as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
