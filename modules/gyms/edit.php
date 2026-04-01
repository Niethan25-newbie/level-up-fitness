<?php
/**
 * Gym Information - Edit Branch
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$gymId = sanitize($_GET['id'] ?? '');
$gym = null;
$errors = [];
$formData = [];

// Load gym
if (!empty($gymId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM gyms WHERE gym_id = ?");
        $stmt->execute([$gymId]);
        $gym = $stmt->fetch();
        
        if (!$gym) {
            setMessage('Gym branch not found', 'error');
            redirect(APP_URL . 'modules/gyms/');
        }
        
        $formData = $gym;
    } catch (Exception $e) {
        setMessage('Error loading gym: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($gymId)) {
    $formData['gym_branch'] = sanitize($_POST['gym_branch'] ?? '');
    $formData['gym_name'] = sanitize($_POST['gym_name'] ?? '');
    $formData['location'] = sanitize($_POST['location'] ?? '');
    $formData['contact_number'] = sanitize($_POST['contact_number'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');

    // Validate
    if (empty($formData['gym_branch'])) {
        $errors['gym_branch'] = 'Branch name is required';
    }
    if (empty($formData['gym_name'])) {
        $errors['gym_name'] = 'Gym name is required';
    }
    if (empty($formData['location'])) {
        $errors['location'] = 'Location is required';
    }
    if (empty($formData['contact_number'])) {
        $errors['contact_number'] = 'Contact number is required';
    } elseif (!isValidPhone($formData['contact_number'])) {
        $errors['contact_number'] = 'Invalid phone number format (use format like 09123456789 or +639123456789)';
    }
    if (empty($formData['description'])) {
        $errors['description'] = 'Description is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE gyms SET 
                    gym_branch = ?, gym_name = ?, location = ?, contact_number = ?, 
                    description = ?, updated_at = NOW()
                WHERE gym_id = ?
            ");
            $stmt->execute([
                $formData['gym_branch'], $formData['gym_name'], $formData['location'], 
                $formData['contact_number'], $formData['description'], $gymId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_GYM', 'Gyms', 'Updated gym: ' . $formData['gym_name']);

            setMessage('Gym branch updated successfully', 'success');
            redirect(APP_URL . 'modules/gyms/view.php?id=' . $gymId);
        } catch (Exception $e) {
            setMessage('Error updating gym: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/gyms/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Branches
                </a>
                <h1><i class="fas fa-edit"></i> Edit Gym Branch</h1>
                <p>Update branch information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($gym): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Branch Information - <?php echo htmlspecialchars($gym['gym_id']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="gym_branch" class="form-label">Branch Name (Short) *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['gym_branch']) ? 'is-invalid' : ''; ?>" 
                                           id="gym_branch" name="gym_branch" 
                                           value="<?php echo htmlspecialchars($formData['gym_branch'] ?? ''); ?>" required>
                                    <?php if (isset($errors['gym_branch'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['gym_branch']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Short name for quick reference</small>
                                </div>

                                <div class="mb-3">
                                    <label for="gym_name" class="form-label">Full Gym Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['gym_name']) ? 'is-invalid' : ''; ?>" 
                                           id="gym_name" name="gym_name" 
                                           value="<?php echo htmlspecialchars($formData['gym_name'] ?? ''); ?>" required>
                                    <?php if (isset($errors['gym_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['gym_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label">Location Address *</label>
                                    <textarea class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" 
                                              id="location" name="location" rows="3" required><?php echo htmlspecialchars($formData['location'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['location'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['location']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['contact_number']) ? 'is-invalid' : ''; ?>" 
                                           id="contact_number" name="contact_number" 
                                           value="<?php echo htmlspecialchars($formData['contact_number'] ?? ''); ?>" required>
                                    <?php if (isset($errors['contact_number'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['contact_number']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                              id="description" name="description" rows="4" required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/gyms/view.php?id=<?php echo $gymId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Branch
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Branch Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Gym ID:</strong><br>
                                <code><?php echo htmlspecialchars($gym['gym_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($gym['created_at']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($gym['updated_at']); ?>
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
