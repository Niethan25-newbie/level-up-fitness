<?php
/**
 * Classes - Create New Class
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$errors = [];
$formData = [];
$trainers = [];

// Get trainers
try {
    $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name, specialization FROM trainers ORDER BY trainer_name");
    $trainerStmt->execute();
    $trainers = $trainerStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading trainers: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['class_name'] = sanitize($_POST['class_name'] ?? '');
    $formData['class_description'] = sanitize($_POST['class_description'] ?? '');
    $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    $formData['class_schedule'] = sanitize($_POST['class_schedule'] ?? '');
    $formData['max_capacity'] = sanitize($_POST['max_capacity'] ?? '');
    $formData['class_status'] = sanitize($_POST['class_status'] ?? 'Active');

    // Validate
    if (empty($formData['class_name'])) {
        $errors['class_name'] = 'Class name is required';
    }
    if (empty($formData['class_schedule'])) {
        $errors['class_schedule'] = 'Schedule is required';
    }
    if (empty($formData['max_capacity']) || !is_numeric($formData['max_capacity']) || $formData['max_capacity'] < 1) {
        $errors['max_capacity'] = 'Valid capacity is required';
    }

    if (empty($errors)) {
        try {
            $classId = generateUniqueID(CLASS_ID_PREFIX, 'classes');

            $stmt = $pdo->prepare("
                INSERT INTO classes (
                    class_id, class_name, class_description, trainer_id, 
                    class_schedule, max_capacity, class_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $classId, $formData['class_name'], $formData['class_description'],
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['class_schedule'], $formData['max_capacity'],
                $formData['class_status']
            ]);

            logAction($_SESSION['user_id'], 'CREATE_CLASS', 'Classes', 
                     'Created class: ' . $classId);

            setMessage('Class created successfully! ID: ' . $classId, 'success');
            redirect(APP_URL . 'modules/classes/');
        } catch (Exception $e) {
            setMessage('Error creating class: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-plus-circle"></i> Create New Class</h1>
                <p>Add a new group fitness class</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Class Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="class_name" class="form-label">Class Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['class_name']) ? 'is-invalid' : ''; ?>" 
                                           id="class_name" name="class_name" 
                                           value="<?php echo htmlspecialchars($formData['class_name'] ?? ''); ?>"
                                           placeholder="e.g., Yoga, CrossFit, Pilates..." required>
                                    <?php if (isset($errors['class_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['class_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="class_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="class_description" name="class_description" 
                                              rows="4" placeholder="Class description and details...">
                                              <?php echo htmlspecialchars($formData['class_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="trainer_id" class="form-label">Trainer (Optional)</label>
                                        <select class="form-select" id="trainer_id" name="trainer_id">
                                            <option value="">-- Unassigned --</option>
                                            <?php foreach ($trainers as $trainer): ?>
                                                <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>" 
                                                        <?php echo ($formData['trainer_id'] ?? '') === $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($trainer['trainer_name'] . ' (' . $trainer['specialization'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="class_schedule" class="form-label">Schedule *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['class_schedule']) ? 'is-invalid' : ''; ?>" 
                                               id="class_schedule" name="class_schedule" 
                                               value="<?php echo htmlspecialchars($formData['class_schedule'] ?? ''); ?>"
                                               placeholder="e.g., Monday & Wednesday, 6:00 PM" required>
                                        <?php if (isset($errors['class_schedule'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['class_schedule']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="max_capacity" class="form-label">Maximum Capacity *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['max_capacity']) ? 'is-invalid' : ''; ?>" 
                                               id="max_capacity" name="max_capacity" 
                                               value="<?php echo htmlspecialchars($formData['max_capacity'] ?? ''); ?>"
                                               placeholder="e.g., 20" min="1" required>
                                        <?php if (isset($errors['max_capacity'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['max_capacity']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="class_status" class="form-label">Status</label>
                                        <select class="form-select" id="class_status" name="class_status">
                                            <option value="Active" <?php echo ($formData['class_status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($formData['class_status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Class
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Information</h5>
                        </div>
                        <div class="card-body small">
                            <p><i class="fas fa-check text-success"></i> Class ID will be auto-generated</p>
                            <p><i class="fas fa-check text-success"></i> Class name is required</p>
                            <p><i class="fas fa-check text-success"></i> You can add trainer later</p>
                            <p><i class="fas fa-check text-success"></i> Set max capacity for enrollment limit</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
