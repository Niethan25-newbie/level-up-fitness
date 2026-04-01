<?php
/**
 * Gym Information - Add New Branch
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Generate gym ID
            $gymId = generateUniqueID(GYM_ID_PREFIX, 'gyms');

            // Insert gym
            $stmt = $pdo->prepare("
                INSERT INTO gyms (
                    gym_id, gym_branch, gym_name, location, contact_number, description
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $gymId, $formData['gym_branch'], $formData['gym_name'], $formData['location'], 
                $formData['contact_number'], $formData['description']
            ]);

            logAction($_SESSION['user_id'], 'ADD_GYM', 'Gyms', 'Added gym branch: ' . $formData['gym_name']);

            setMessage('Gym branch added successfully! ID: ' . $gymId, 'success');
            redirect(APP_URL . 'modules/gyms/');
        } catch (Exception $e) {
            setMessage('Error adding gym: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-plus-circle"></i> Add New Gym Branch</h1>
                <p>Register a new gym branch location</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Branch Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="gym_branch" class="form-label">Branch Name (Short) *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['gym_branch']) ? 'is-invalid' : ''; ?>" 
                                           id="gym_branch" name="gym_branch" placeholder="e.g., Main Branch, BGC Branch"
                                           value="<?php echo htmlspecialchars($formData['gym_branch'] ?? ''); ?>" required>
                                    <?php if (isset($errors['gym_branch'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['gym_branch']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Short name for quick reference</small>
                                </div>

                                <div class="mb-3">
                                    <label for="gym_name" class="form-label">Full Gym Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['gym_name']) ? 'is-invalid' : ''; ?>" 
                                           id="gym_name" name="gym_name" placeholder="e.g., Level Up Fitness - BGC Branch"
                                           value="<?php echo htmlspecialchars($formData['gym_name'] ?? ''); ?>" required>
                                    <?php if (isset($errors['gym_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['gym_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label">Location Address *</label>
                                    <textarea class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" 
                                              id="location" name="location" rows="3" 
                                              placeholder="Enter complete branch address..."
                                              required><?php echo htmlspecialchars($formData['location'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['location'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['location']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="contact_number" class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['contact_number']) ? 'is-invalid' : ''; ?>" 
                                           id="contact_number" name="contact_number" placeholder="+63 (2) 1234-5678"
                                           value="<?php echo htmlspecialchars($formData['contact_number'] ?? ''); ?>" required>
                                    <?php if (isset($errors['contact_number'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['contact_number']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                              id="description" name="description" rows="4" 
                                              placeholder="Describe this gym branch (facilities, equipment, services, etc.)..."
                                              required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Maximum 500 characters</small>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/gyms/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Branch
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
                            <p>
                                <i class="fas fa-check text-success"></i> Gym ID will be auto-generated
                            </p>
                            <p>
                                <i class="fas fa-check text-success"></i> All fields are required
                            </p>
                            <p>
                                <i class="fas fa-check text-success"></i> Each branch gets a unique ID
                            </p>
                            <p>
                                <i class="fas fa-check text-success"></i> You can add trainers and members to this branch later
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
