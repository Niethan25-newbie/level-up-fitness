<?php
/**
 * Trainers Management - Edit Trainer
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$trainerId = sanitize($_GET['id'] ?? '');
$trainer = null;
$errors = [];
$formData = [];

// Load trainer
if (!empty($trainerId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
        $stmt->execute([$trainerId]);
        $trainer = $stmt->fetch();
        
        if (!$trainer) {
            setMessage('Trainer not found', 'error');
            redirect(APP_URL . 'modules/trainers/');
        }
        
        $formData = $trainer;
    } catch (Exception $e) {
        setMessage('Error loading trainer: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($trainerId)) {
    $formData['trainer_name'] = sanitize($_POST['trainer_name'] ?? '');
    $formData['contact_number'] = sanitize($_POST['contact_number'] ?? '');
    $formData['specialization'] = sanitize($_POST['specialization'] ?? '');
    $formData['years_of_experience'] = intval($_POST['years_of_experience'] ?? 0);

    // Validate
    if (empty($formData['trainer_name'])) {
        $errors['trainer_name'] = 'Trainer name is required';
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

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE trainers SET 
                    trainer_name = ?, contact_number = ?, specialization = ?, 
                    years_of_experience = ?, updated_at = NOW()
                WHERE trainer_id = ?
            ");
            $stmt->execute([
                $formData['trainer_name'], $formData['contact_number'], 
                $formData['specialization'], $formData['years_of_experience'], $trainerId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_TRAINER', 'Trainers', 'Updated trainer: ' . $formData['trainer_name']);

            setMessage('Trainer updated successfully', 'success');
            redirect(APP_URL . 'modules/trainers/view.php?id=' . $trainerId);
        } catch (Exception $e) {
            setMessage('Error updating trainer: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-edit"></i> Edit Trainer</h1>
                <p>Update trainer information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($trainer): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Trainer Information - <?php echo htmlspecialchars($trainer['trainer_id']); ?></h5>
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
                                        <label for="email" class="form-label">Email Address (Read-only)</label>
                                        <input type="email" class="form-control" 
                                               id="email" disabled
                                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                                        <small class="text-muted">Email cannot be changed</small>
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
                                           id="specialization" name="specialization"
                                           value="<?php echo htmlspecialchars($formData['specialization'] ?? ''); ?>" required>
                                    <?php if (isset($errors['specialization'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['specialization']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainerId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Trainer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Trainer Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Trainer ID:</strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Current Specialization:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <hr>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($trainer['created_at']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($trainer['updated_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
