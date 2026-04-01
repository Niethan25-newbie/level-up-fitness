<?php
/**
 * Trainers Management - Add New Trainer
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['trainer_name'] = sanitize($_POST['trainer_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['contact_number'] = sanitize($_POST['contact_number'] ?? '');
    $formData['specialization'] = sanitize($_POST['specialization'] ?? '');
    $formData['years_of_experience'] = intval($_POST['years_of_experience'] ?? 0);

    // Validate
    if (empty($formData['trainer_name'])) {
        $errors['trainer_name'] = 'Trainer name is required';
    }
    if (empty($formData['email']) || !isValidEmail($formData['email'])) {
        $errors['email'] = 'Valid email is required';
    }
    if (empty($formData['contact_number'])) {
        $errors['contact_number'] = 'Contact number is required';
    } elseif (!isValidPhone($formData['contact_number'])) {
        $errors['contact_number'] = 'Invalid phone number format (use format like 09123456789 or +639123456789)';
    }
    if (empty($formData['specialization'])) {
        $errors['specialization'] = 'Specialization is required';
    }
    if ($formData['years_of_experience'] < 0) {
        $errors['years_of_experience'] = 'Years of experience must be 0 or greater';
    }

    // Check email uniqueness
    if (empty($errors['email'])) {
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$formData['email']]);
        if ($checkStmt->rowCount() > 0) {
            $errors['email'] = 'Email already exists in system';
        }
    }

    if (empty($errors)) {
        try {
            // Create user account
            $password = hashPassword('defaultPass123');
            $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
            $userStmt->execute([$formData['email'], $password, 'trainer']);
            $userId = $pdo->lastInsertId();

            // Generate trainer ID
            $trainerId = generateID(TRAINER_ID_PREFIX);

            // Insert trainer
            $stmt = $pdo->prepare("
                INSERT INTO trainers (
                    trainer_id, user_id, trainer_name, contact_number, 
                    email, specialization, years_of_experience
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $trainerId, $userId, $formData['trainer_name'], 
                $formData['contact_number'], $formData['email'], 
                $formData['specialization'], $formData['years_of_experience']
            ]);

            logAction($_SESSION['user_id'], 'ADD_TRAINER', 'Trainers', 'Added trainer: ' . $formData['trainer_name']);

            setMessage('Trainer added successfully! ID: ' . $trainerId, 'success');
            redirect(APP_URL . 'modules/trainers/');
        } catch (Exception $e) {
            setMessage('Error adding trainer: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/trainers/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Trainers
                </a>
                <h1><i class="fas fa-user-plus"></i> Add New Trainer</h1>
                <p>Add a new trainer to the gym</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Trainer Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="trainer_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['trainer_name']) ? 'is-invalid' : ''; ?>" 
                                               id="trainer_name" name="trainer_name" 
                                               value="<?php echo htmlspecialchars($formData['trainer_name'] ?? ''); ?>" required>
                                        <?php if (isset($errors['trainer_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['trainer_name']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                               id="email" name="email" 
                                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">Contact Number *</label>
                                        <input type="tel" class="form-control <?php echo isset($errors['contact_number']) ? 'is-invalid' : ''; ?>" 
                                               id="contact_number" name="contact_number" 
                                               value="<?php echo htmlspecialchars($formData['contact_number'] ?? ''); ?>" required>
                                        <?php if (isset($errors['contact_number'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['contact_number']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="years_of_experience" class="form-label">Years of Experience *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['years_of_experience']) ? 'is-invalid' : ''; ?>" 
                                               id="years_of_experience" name="years_of_experience" min="0"
                                               value="<?php echo htmlspecialchars($formData['years_of_experience'] ?? '0'); ?>" required>
                                        <?php if (isset($errors['years_of_experience'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['years_of_experience']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="specialization" class="form-label">Specialization *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['specialization']) ? 'is-invalid' : ''; ?>" 
                                           id="specialization" name="specialization" placeholder="e.g., CrossFit, Yoga, Weight Training"
                                           value="<?php echo htmlspecialchars($formData['specialization'] ?? ''); ?>" required>
                                    <?php if (isset($errors['specialization'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['specialization']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">What is this trainer specialized in?</small>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/trainers/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Trainer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Important Notes</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> A temporary account will be created</li>
                                <li><i class="fas fa-check text-success"></i> Trainer can change password on first login</li>
                                <li><i class="fas fa-check text-success"></i> All fields marked with * are required</li>
                                <li><i class="fas fa-check text-success"></i> Email must be unique</li>
                                <li><i class="fas fa-check text-success"></i> Trainer ID will be auto-generated</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Common Specializations</h5>
                        </div>
                        <div class="card-body small">
                            <p>
                                Strength Training<br>
                                CrossFit<br>
                                Yoga<br>
                                Cardio<br>
                                Weight Training<br>
                                Personal Training<br>
                                Pilates<br>
                                Martial Arts
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
