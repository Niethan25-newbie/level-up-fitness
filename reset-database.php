<?php
/**
 * Database Reset Script
 * Level Up Fitness - Gym Management System
 * 
 * Usage: php reset-database.php
 * 
 * WARNING: This will DELETE ALL data from the database!
 * This includes all users, members, trainers, equipment, reservations, etc.
 * Only initial admin user and sample equipment will remain.
 */

require_once 'config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║       Level Up Fitness - DATABASE RESET                ║\n";
echo "║  WARNING: This will DELETE ALL data!                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Ask for confirmation
echo "⚠️  Are you sure? This action cannot be undone!\n";
echo "Type 'RESET' to confirm: ";
$input = trim(fgets(STDIN));

if ($input !== 'RESET') {
    echo "❌ Reset cancelled.\n\n";
    exit(0);
}

echo "\n⏳ Resetting database...\n\n";

try {
    // First, ensure gym table has the required columns
    echo "Checking and updating schema...\n";
    
    // Check if gym_name column exists, if not add it
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'gyms' AND COLUMN_NAME = 'gym_name'");
    $rows = $result->fetchAll();
    if (empty($rows)) {
        echo "  Adding gym_name column...\n";
        $pdo->exec("ALTER TABLE gyms ADD COLUMN gym_name VARCHAR(255) NOT NULL DEFAULT 'Gym Branch' AFTER gym_branch");
    }
    
    // Check if location column exists, if not add it
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'gyms' AND COLUMN_NAME = 'location'");
    $rows = $result->fetchAll();
    if (empty($rows)) {
        echo "  Adding location column...\n";
        $pdo->exec("ALTER TABLE gyms ADD COLUMN location TEXT NULL AFTER gym_name");
    }
    
    // Check if description column exists, if not add it
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'gyms' AND COLUMN_NAME = 'description'");
    $rows = $result->fetchAll();
    if (empty($rows)) {
        echo "  Adding description column...\n";
        $pdo->exec("ALTER TABLE gyms ADD COLUMN description TEXT NULL AFTER contact_number");
    }
    
    // Check if trainer_id column exists in members table, if not add it
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'members' AND COLUMN_NAME = 'trainer_id'");
    $rows = $result->fetchAll();
    if (empty($rows)) {
        echo "  Adding trainer_id column to members...\n";
        $pdo->exec("ALTER TABLE members ADD COLUMN trainer_id VARCHAR(50) NULL AFTER out_date");
        $pdo->exec("ALTER TABLE members ADD CONSTRAINT fk_members_trainer_id FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL");
        $pdo->exec("ALTER TABLE members ADD INDEX idx_trainer_id (trainer_id)");
    }
    
    echo "✓ Schema check complete\n\n";
    
    // Get all tables
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Truncate all tables
    echo "Truncating tables:\n";
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
        echo "  ✓ $table\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "\n✓ All tables cleared\n\n";
    
    // Re-insert initial data
    echo "Inserting initial data:\n";
    
    // Admin user
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (user_id, email, password, user_type) VALUES (1, 'admin@levelupfitness.com', '$adminPassword', 'admin')");
    echo "  ✓ Admin user (email: admin@levelupfitness.com, password: admin123)\n";
    
    // Sample trainer user account (must be created before member who references it)
    $trainerPassword = password_hash('trainer123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (user_id, email, password, user_type) VALUES (2, 'trainer@levelupfitness.com', '$trainerPassword', 'trainer')");
    $pdo->exec("INSERT INTO trainers (trainer_id, user_id, trainer_name, specialization, years_of_experience, contact_number, email, status) VALUES ('TRN001', 2, 'Jane Smith', 'Strength Training', 5, '09987654321', 'trainer@levelupfitness.com', 'Active')");
    echo "  ✓ Sample trainer: Jane Smith\n";
    
    // Sample member user account
    $memberPassword = password_hash('member123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (user_id, email, password, user_type) VALUES (3, 'john@email.com', '$memberPassword', 'member')");
    echo "  ✓ Member user account (email: john@email.com, password: member123)\n";
    
    // Sample members (assigned to trainer)
    $pdo->exec("INSERT INTO members (member_id, user_id, member_name, contact_number, email, membership_type, join_date, trainer_id, status) VALUES ('MEM001', 3, 'John Doe', '09123456789', 'john@email.com', 'Monthly', CURDATE(), 'TRN001', 'Active')");
    echo "  ✓ Sample member: John Doe (assigned to trainer Jane Smith)\n";
    
    // Sample gym
    $pdo->exec("INSERT INTO gyms (gym_id, gym_branch, gym_name, location, description, contact_number) VALUES ('GYM001', 'Main Branch', 'Level Up Fitness - Main', 'Manila, Philippines', 'Our flagship gym with state-of-the-art equipment and facilities', '02-1234-5678')");
    echo "  ✓ Sample gym: Main Branch\n";
    
    // Sample equipment
    $equipment = [
        ['EQ001', 'Treadmill', 'Cardio', 'High-speed treadmill for running and walking', 5, 'Cardio Area'],
        ['EQ002', 'Dumbbells Set', 'Weights', '10kg-50kg dumbbells', 20, 'Weight Room'],
        ['EQ003', 'Bench Press', 'Weights', 'Olympic weight bench press', 3, 'Bench Press Area'],
        ['EQ004', 'Rowing Machine', 'Cardio', 'Concept2 rowing machine', 2, 'Cardio Area'],
        ['EQ005', 'Yoga Mat', 'Yoga', 'Premium yoga mats', 15, 'Yoga Studio'],
    ];
    
    $equipStmt = $pdo->prepare("INSERT INTO equipment (equipment_id, equipment_name, equipment_category, description, quantity, location) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($equipment as $eq) {
        $equipStmt->execute($eq);
    }
    echo "  ✓ Sample equipment (5 items)\n";
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ DATABASE RESET SUCCESSFUL!                        ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n";
    echo "\nInitial Credentials:\n";
    echo "  Admin Email: admin@levelupfitness.com\n";
    echo "  Admin Password: admin123\n";
    echo "  Member Email: john@email.com\n";
    echo "  Member Password: member123\n";
    echo "  Trainer Email: trainer@levelupfitness.com\n";
    echo "  Trainer Password: trainer123\n\n";
    
} catch (Exception $e) {
    echo "❌ Error during reset: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
