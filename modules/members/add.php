<?php
/**
 * Members Management - Add New Member
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $formData['member_name'] = sanitize($_POST['member_name'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['contact_number'] = sanitize($_POST['contact_number'] ?? '');
    $formData['membership_type'] = sanitize($_POST['membership_type'] ?? '');
    $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    $formData['status'] = sanitize($_POST['status'] ?? STATUS_ACTIVE);
    $joinDate = sanitize($_POST['join_date'] ?? '');

    // Validate
    if (empty($formData['member_name'])) {
        $errors['member_name'] = 'Member name is required';
    }
    if (empty($formData['email']) || !isValidEmail($formData['email'])) {
        $errors['email'] = 'Valid email is required';
    }
    if (empty($formData['contact_number'])) {
        $errors['contact_number'] = 'Contact number is required';
    } elseif (!isValidPhone($formData['contact_number'])) {
        $errors['contact_number'] = 'Invalid phone number format (use format like 09123456789 or +639123456789)';
    }
    if (empty($formData['membership_type'])) {
        $errors['membership_type'] = 'Membership type is required';
    }
    if (empty($joinDate)) {
        $errors['join_date'] = 'Join date is required';
    }

    // Check email uniqueness
    if (empty($errors['email'])) {
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$formData['email']]);
        if ($checkStmt->rowCount() > 0) {
            $errors['email'] = 'Email already exists in system';
        }
    }

    // If no errors, insert
    if (empty($errors)) {
        try {
            // Create user account
            $password = hashPassword('defaultPass123');
            $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
            $userStmt->execute([$formData['email'], $password, 'member']);
            $userId = $pdo->lastInsertId();

            // Generate member ID with collision detection
            $memberId = generateUniqueID(MEMBER_ID_PREFIX, 'members');

            // Insert member
            $stmt = $pdo->prepare("
                INSERT INTO members (
                    member_id, user_id, member_name, contact_number, 
                    email, membership_type, join_date, trainer_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $memberId, $userId, $formData['member_name'], 
                $formData['contact_number'], $formData['email'], 
                $formData['membership_type'], $joinDate,
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['status']
            ]);

            // Log action
            logAction($_SESSION['user_id'], 'ADD_MEMBER', 'Members', 'Added member: ' . $formData['member_name']);

            setMessage('Member added successfully! ID: ' . $memberId, 'success');
            redirect(APP_URL . 'modules/members/');
        } catch (Exception $e) {
            setMessage('Error adding member: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Members
                </a>
                <h1><i class="fas fa-user-plus"></i> Add New Member</h1>
                <p>Fill in the form below to add a new member to the system</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Member Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="member_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['member_name']) ? 'is-invalid' : ''; ?>" 
                                               id="member_name" name="member_name" 
                                               value="<?php echo htmlspecialchars($formData['member_name'] ?? ''); ?>" required>
                                        <?php if (isset($errors['member_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member_name']; ?></div>
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
                                        <label for="join_date" class="form-label">Join Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['join_date']) ? 'is-invalid' : ''; ?>" 
                                               id="join_date" name="join_date" 
                                               value="<?php echo htmlspecialchars($formData['join_date'] ?? date('Y-m-d')); ?>" required>
                                        <?php if (isset($errors['join_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['join_date']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="membership_type" class="form-label">Membership Type *</label>
                                        <select class="form-select <?php echo isset($errors['membership_type']) ? 'is-invalid' : ''; ?>" 
                                                id="membership_type" name="membership_type" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="Monthly" <?php echo ($formData['membership_type'] ?? '') === 'Monthly' ? 'selected' : ''; ?>>Monthly (₱/month)</option>
                                            <option value="Quarterly" <?php echo ($formData['membership_type'] ?? '') === 'Quarterly' ? 'selected' : ''; ?>>Quarterly (₱/3 months)</option>
                                            <option value="Annual" <?php echo ($formData['membership_type'] ?? '') === 'Annual' ? 'selected' : ''; ?>>Annual (₱/year)</option>
                                        </select>
                                        <?php if (isset($errors['membership_type'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['membership_type']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="trainer_id" class="form-label">Assign Trainer (Optional)</label>
                                        <select class="form-select" id="trainer_id" name="trainer_id">
                                            <option value="">-- No Trainer Assigned --</option>
                                            <?php 
                                            try {
                                                $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
                                                $trainerStmt->execute();
                                                $trainers = $trainerStmt->fetchAll();
                                                foreach ($trainers as $trainer) {
                                                    $selected = ($formData['trainer_id'] ?? '') === $trainer['trainer_id'] ? 'selected' : '';
                                                    echo "<option value=\"" . htmlspecialchars($trainer['trainer_id']) . "\" $selected>" . htmlspecialchars($trainer['trainer_name']) . "</option>";
                                                }
                                            } catch (Exception $e) {
                                                echo '<option value="">Error loading trainers</option>';
                                            }
                                            ?>
                                        </select>
                                        <small class="text-muted">Member can view and contact their assigned trainer</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3"></div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Active" <?php echo ($formData['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($formData['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Member
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Important Notes</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> A temporary account will be created</li>
                                <li><i class="fas fa-check text-success"></i> Member can change password on first login</li>
                                <li><i class="fas fa-check text-success"></i> All fields marked with * are required</li>
                                <li><i class="fas fa-check text-success"></i> Email must be unique</li>
                                <li><i class="fas fa-check text-success"></i> Member ID will be auto-generated</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Membership Types</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Monthly</strong><br>Active for 1 month from join date</p>
                            <hr>
                            <p><strong>Quarterly</strong><br>Active for 3 months from join date</p>
                            <hr>
                            <p><strong>Annual</strong><br>Active for 1 year from join date</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
