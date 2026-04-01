<?php
/**
 * Members Management - Edit Member
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$memberId = sanitize($_GET['id'] ?? '');
$member = null;
$errors = [];
$formData = [];

// Load member
if (!empty($memberId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            setMessage('Member not found', 'error');
            redirect(APP_URL . 'modules/members/');
        }
        
        $formData = $member;
    } catch (Exception $e) {
        setMessage('Error loading member: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($memberId)) {
    $formData['member_name'] = sanitize($_POST['member_name'] ?? '');
    $formData['contact_number'] = sanitize($_POST['contact_number'] ?? '');
    $formData['membership_type'] = sanitize($_POST['membership_type'] ?? '');
    $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    $formData['status'] = sanitize($_POST['status'] ?? STATUS_ACTIVE);
    $joinDate = sanitize($_POST['join_date'] ?? '');

    // Validate
    if (empty($formData['member_name'])) {
        $errors['member_name'] = 'Member name is required';
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

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE members SET 
                    member_name = ?, contact_number = ?, membership_type = ?, 
                    join_date = ?, trainer_id = ?, status = ?, updated_at = NOW()
                WHERE member_id = ?
            ");
            $stmt->execute([
                $formData['member_name'], $formData['contact_number'], 
                $formData['membership_type'], $joinDate,
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['status'], $memberId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_MEMBER', 'Members', 'Updated member: ' . $formData['member_name']);

            setMessage('Member updated successfully', 'success');
            redirect(APP_URL . 'modules/members/view.php?id=' . $memberId);
        } catch (Exception $e) {
            setMessage('Error updating member: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-edit"></i> Edit Member</h1>
                <p>Update member information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($member): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Member Information - <?php echo htmlspecialchars($member['member_id']); ?></h5>
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
                                        <label for="join_date" class="form-label">Join Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['join_date']) ? 'is-invalid' : ''; ?>" 
                                               id="join_date" name="join_date" 
                                               value="<?php echo htmlspecialchars($formData['join_date'] ?? ''); ?>" required>
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
                                            <option value="Monthly" <?php echo ($formData['membership_type'] ?? '') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            <option value="Quarterly" <?php echo ($formData['membership_type'] ?? '') === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                            <option value="Annual" <?php echo ($formData['membership_type'] ?? '') === 'Annual' ? 'selected' : ''; ?>>Annual</option>
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
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Active" <?php echo ($formData['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($formData['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3"></div>
                                </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Active" <?php echo ($formData['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo ($formData['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="Expired" <?php echo ($formData['status'] ?? '') === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $memberId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Member
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Member Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Member ID:</strong><br>
                                <?php echo htmlspecialchars($member['member_id']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Current Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower($member['status']); ?>">
                                    <?php echo htmlspecialchars($member['status']); ?>
                                </span>
                            </p>
                            <hr>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($member['created_at']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($member['updated_at']); ?>
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
