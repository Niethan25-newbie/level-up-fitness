<?php
/**
 * Add Dummy Trainers Script
 * Inserts multiple trainer accounts for testing
 */

require_once('config/database.php');

// Trainer data with various specializations
$trainers = [
    [
        'trainer_name' => 'Sarah Johnson',
        'email' => 'sarah.johnson@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Strength & Conditioning',
        'years_of_experience' => 8,
        'contact_number' => '09171234501'
    ],
    [
        'trainer_name' => 'Mike Rodriguez',
        'email' => 'mike.rodriguez@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Cardio & Endurance',
        'years_of_experience' => 6,
        'contact_number' => '09171234502'
    ],
    [
        'trainer_name' => 'Emma Chen',
        'email' => 'emma.chen@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Yoga & Flexibility',
        'years_of_experience' => 7,
        'contact_number' => '09171234503'
    ],
    [
        'trainer_name' => 'James Wilson',
        'email' => 'james.wilson@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'CrossFit',
        'years_of_experience' => 5,
        'contact_number' => '09171234504'
    ],
    [
        'trainer_name' => 'Lisa Anderson',
        'email' => 'lisa.anderson@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Weight Loss & Nutrition',
        'years_of_experience' => 9,
        'contact_number' => '09171234505'
    ],
    [
        'trainer_name' => 'David Martinez',
        'email' => 'david.martinez@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Functional Training',
        'years_of_experience' => 6,
        'contact_number' => '09171234506'
    ],
    [
        'trainer_name' => 'Jessica Lee',
        'email' => 'jessica.lee@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Pilates & Core',
        'years_of_experience' => 4,
        'contact_number' => '09171234507'
    ],
    [
        'trainer_name' => 'Robert Taylor',
        'email' => 'robert.taylor@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Olympic Lifting',
        'years_of_experience' => 10,
        'contact_number' => '09171234508'
    ],
    [
        'trainer_name' => 'Amanda White',
        'email' => 'amanda.white@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'HIIT & Boot Camp',
        'years_of_experience' => 5,
        'contact_number' => '09171234509'
    ],
    [
        'trainer_name' => 'Christopher Brown',
        'email' => 'christopher.brown@levelupfitness.com',
        'password' => 'password',
        'specialization' => 'Sports Performance',
        'years_of_experience' => 7,
        'contact_number' => '09171234510'
    ]
];

try {
    $added = 0;
    $skipped = 0;

    foreach ($trainers as $trainer) {
        // Check if email already exists
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$trainer['email']]);

        if ($check->rowCount() > 0) {
            echo "⏭️  Skipped: {$trainer['trainer_name']} (email already exists)\n";
            $skipped++;
            continue;
        }

        // Hash password
        $hashedPassword = password_hash($trainer['password'], PASSWORD_BCRYPT);

        // Insert user
        $userStmt = $pdo->prepare("
            INSERT INTO users (email, password, user_type)
            VALUES (?, ?, 'trainer')
        ");
        $userStmt->execute([$trainer['email'], $hashedPassword]);
        $user_id = $pdo->lastInsertId();

        // Generate trainer ID
        $trainer_id = 'TR' . str_pad($user_id, 5, '0', STR_PAD_LEFT);

        // Insert trainer
        $trainerStmt = $pdo->prepare("
            INSERT INTO trainers (trainer_id, user_id, trainer_name, specialization, 
                                 years_of_experience, contact_number, email, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
        ");
        $trainerStmt->execute([
            $trainer_id,
            $user_id,
            $trainer['trainer_name'],
            $trainer['specialization'],
            $trainer['years_of_experience'],
            $trainer['contact_number'],
            $trainer['email']
        ]);

        echo "✅ Added: {$trainer['trainer_name']} ({$trainer['specialization']}) - {$trainer_id}\n";
        $added++;
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Summary: $added trainers added, $skipped skipped\n";
    echo str_repeat("=", 60) . "\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
