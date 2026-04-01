<?php
/**
 * Database Reset Web Interface
 * Level Up Fitness - Gym Management System
 * 
 * This page allows admins to reset the database from the web interface
 * Access: /admin/reset-database.php
 * 
 * WARNING: This is a dangerous operation - only accessible to admins
 */

session_start();

// Check if user is admin
if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    die('Access denied: Only admins can reset the database.');
}

require_once dirname(dirname(__FILE__)) . '/config/database.php';

$resetSuccess = false;
$resetError = false;
$resetMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes') {
    try {
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Truncate all tables
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        // Re-insert initial data
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (user_id, email, password, user_type) VALUES 
                   (1, 'admin@levelupfitness.com', '$adminPassword', 'admin')");
        
        $pdo->exec("INSERT INTO members (member_id, user_id, member_name, contact_number, email, membership_type, join_date, status) VALUES 
                   ('MEM001', 1, 'John Doe', '09123456789', 'john@email.com', 'Monthly', CURDATE(), 'Active')");
        
        $trainerPassword = password_hash('trainer123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (user_id, email, password, user_type) VALUES 
                   (2, 'admin@levelupfitness.com', '$trainerPassword', 'trainer')");
        $pdo->exec("INSERT INTO trainers (trainer_id, user_id, trainer_name, specialization, years_of_experience, contact_number, email, status) VALUES 
                   ('TRN001', 2, 'Jane Smith', 'Strength Training', 5, '09987654321', 'trainer@levelupfitness.com', 'Active')");
        
        $pdo->exec("INSERT INTO gyms (gym_id, gym_branch, contact_number) VALUES 
                   ('GYM001', 'Main Branch', '02-1234-5678')");
        
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
        
        $resetSuccess = true;
        $resetMessage = 'Database has been reset successfully!';
        
    } catch (Exception $e) {
        $resetError = true;
        $resetMessage = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database - Level Up Fitness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-container {
            max-width: 500px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            border-radius: 15px 15px 0 0 !important;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .card-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ff6b6b;
        }
        .card-body {
            padding: 30px;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .form-check {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .btn-reset {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn-reset:hover {
            background: linear-gradient(135deg, #ff5252 0%, #ee3030 100%);
            color: white;
        }
        .btn-cancel {
            padding: 12px 30px;
            font-weight: 600;
        }
        .credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .credentials h5 {
            margin-bottom: 15px;
            color: #333;
        }
        .credential-item {
            margin-bottom: 10px;
            padding: 8px;
            background: white;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
        }
        .credential-label {
            font-weight: 600;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="card">
            <div class="card-header text-center">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Reset Database</h2>
                <p class="mb-0">⚠️ Dangerous Operation</p>
            </div>

            <div class="card-body">
                <?php if ($resetSuccess): ?>
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">
                            <i class="fas fa-check-circle"></i> Success!
                        </h4>
                        <p><?php echo $resetMessage; ?></p>
                        <hr>
                        <p class="mb-0">All data has been cleared. Initial credentials are ready below.</p>
                    </div>

                    <div class="credentials">
                        <h5><i class="fas fa-key"></i> Initial Credentials</h5>
                        <div class="credential-item">
                            <span class="credential-label">Admin Email:</span> admin@levelupfitness.com
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Admin Password:</span> admin123
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Trainer Email:</span> trainer@levelupfitness.com
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Trainer Password:</span> trainer123
                        </div>
                    </div>

                    <a href="<?php echo isset($_SESSION['user_id']) ? '/level-up-fitness/dashboard/' : '/level-up-fitness/auth/login.php'; ?>" class="btn btn-primary w-100 mt-4">
                        <i class="fas fa-home"></i> Return to Dashboard
                    </a>

                <?php elseif ($resetError): ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">
                            <i class="fas fa-times-circle"></i> Error!
                        </h4>
                        <p><?php echo $resetMessage; ?></p>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>

                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">
                            <i class="fas fa-skull-crossbones"></i> Warning!
                        </h4>
                        <p><strong>This will permanently delete ALL data including:</strong></p>
                        <ul class="mb-0">
                            <li>All users, members, and trainers</li>
                            <li>All sessions, workouts, and reservations</li>
                            <li>All payments and attendance records</li>
                            <li>All activity logs</li>
                        </ul>
                    </div>

                    <p class="text-muted mb-3">Only the database structure will remain. Initial admin user and sample data will be re-created.</p>

                    <form method="POST" onsubmit="return confirm('⚠️ Are you absolutely certain? This cannot be undone!');">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="confirm" required>
                            <label class="form-check-label" for="confirm">
                                I understand and accept the consequences
                            </label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? '/level-up-fitness/dashboard/'; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_reset" value="yes" class="btn btn-reset">
                                <i class="fas fa-trash"></i> Reset Database
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
