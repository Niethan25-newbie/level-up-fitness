<?php
require 'config/database.php';

// Check templates count
$templates = $pdo->query('SELECT COUNT(*) as count FROM workout_templates')->fetch();
echo "Total Templates in DB: " . $templates['count'] . "\n";

$active = $pdo->query('SELECT COUNT(*) as count FROM workout_templates WHERE is_active = 1')->fetch();
echo "Active Templates: " . $active['count'] . "\n";

// Show sample
$samples = $pdo->query('SELECT template_id, template_name, is_active FROM workout_templates LIMIT 5')->fetchAll();
echo "\nSample Templates:\n";
foreach ($samples as $sample) {
    echo "- " . $sample['template_name'] . " (ID: " . $sample['template_id'] . ", Active: " . ($sample['is_active'] ? 'Yes' : 'No') . ")\n";
}
?>
