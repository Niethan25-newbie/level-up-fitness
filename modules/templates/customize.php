<?php
/**
 * Workout Template - Customize and Create Plan
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$template = null;
$members = [];
$trainers = [];
$errors = [];
$formData = [];
$currentUserType = $_SESSION['user_type'];
$currentUserId = $_SESSION['user_id'];
$currentTrainerId = null;

// Get template ID
$templateId = $_GET['id'] ?? $_POST['template_id'] ?? '';

if (empty($templateId)) {
    setMessage('Template ID is required', 'error');
    redirect(APP_URL . 'modules/templates/');
}

// Load template
try {
    $stmt = $pdo->prepare("SELECT * FROM workout_templates WHERE template_id = ? AND is_active = 1");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();

    if (!$template) {
        setMessage('Template not found', 'error');
        redirect(APP_URL . 'modules/templates/');
    }

    // Get trainer ID if user is a trainer
    if ($currentUserType === 'trainer') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
        $trainerStmt->execute([$currentUserId]);
        $trainerResult = $trainerStmt->fetch();
        if ($trainerResult) {
            $currentTrainerId = $trainerResult['trainer_id'];
        }
    }

    // Get active members
    $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
    $memberStmt->execute();
    $members = $memberStmt->fetchAll();

    // Only show trainer dropdown for admins
    if ($currentUserType === 'admin') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
        $trainerStmt->execute();
        $trainers = $trainerStmt->fetchAll();
    }

} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['template_id'] = $templateId;
    $formData['plan_name'] = sanitize($_POST['plan_name'] ?? '');
    $formData['duration_weeks'] = intval($_POST['duration_weeks'] ?? $template['duration_weeks']);
    $formData['customized_details'] = sanitize($_POST['customized_details'] ?? '');
    
    // Member assignment varies by user type
    if ($currentUserType === 'member') {
        // Members create plan for themselves
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$currentUserId]);
        $memberResult = $memberStmt->fetch();
        if ($memberResult) {
            $formData['member_id'] = $memberResult['member_id'];
        } else {
            $errors['member_id'] = 'Your member account is not active';
        }
        $formData['trainer_id'] = null; // Members don't assign trainers
    } else {
        // Trainers/Admins select member
        $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
        if ($currentUserType === 'trainer') {
            $formData['trainer_id'] = $currentTrainerId;
        } else {
            $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
        }
    }

    // Validation
    if (empty($formData['plan_name'])) {
        $errors['plan_name'] = 'Plan name is required';
    }
    if (empty($formData['member_id']) && $currentUserType !== 'member') {
        $errors['member_id'] = 'Member is required';
    }
    if ($formData['duration_weeks'] <= 0) {
        $errors['duration_weeks'] = 'Duration must be greater than 0';
    }

    // Create plan if no errors
    if (empty($errors)) {
        try {
            $planId = generateUniqueID(WORKOUT_PLAN_ID_PREFIX, 'workout_plans');
            
            // Prepare schedule with customizations or use template schedule
            $baseSchedule = isset($template['weekly_schedule']) ? (json_decode($template['weekly_schedule'], true) ?? []) : [];
            $weeklySchedule = json_encode($baseSchedule);

            // Use template details or custom details
            $planDetails = !empty($formData['customized_details']) 
                ? $formData['customized_details'] 
                : $template['description'];

            // Insert new workout plan
            $insertStmt = $pdo->prepare("
                INSERT INTO workout_plans (
                    workout_plan_id, template_id, member_id, trainer_id, plan_name, 
                    weekly_schedule, plan_details
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $planId, $templateId, $formData['member_id'],
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['plan_name'], $weeklySchedule, $planDetails
            ]);

            // Increment popularity score
            $updateStmt = $pdo->prepare("UPDATE workout_templates SET popularity_score = popularity_score + 1 WHERE template_id = ?");
            $updateStmt->execute([$templateId]);

            // Log action
            logAction($currentUserId, 'CREATE_PLAN_FROM_TEMPLATE', 'Workouts', 
                     'Created plan: ' . $formData['plan_name'] . ' from template: ' . $template['template_name']);

            setMessage('Workout plan created successfully! ID: ' . $planId, 'success');
            redirect(APP_URL . 'modules/workouts/view.php?id=' . $planId);

        } catch (Exception $e) {
            setMessage('Error creating plan: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/templates/view.php?id=<?php echo htmlspecialchars($templateId); ?>" 
                   class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Template
                </a>
                <h1><i class="fas fa-cogs"></i> Customize Workout Plan</h1>
                <p>Create a personalized plan from: <strong><?php echo htmlspecialchars($template['template_name']); ?></strong></p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <!-- Template Info Sidebar -->
                <div class="col-lg-3">
                    <div class="card mb-4 sticky-top" style="top: 20px;">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Template Info</h5>
                        </div>
                        <div class="card-body">
                            <h6><?php echo htmlspecialchars($template['template_name']); ?></h6>
                            <p class="text-muted small"><?php echo htmlspecialchars($template['description']); ?></p>
                            
                            <hr>

                            <div class="mb-2">
                                <small class="text-muted d-block"><strong>Goal:</strong></small>
                                <small><?php echo htmlspecialchars($template['goal']); ?></small>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block"><strong>Duration:</strong></small>
                                <small><?php echo $template['duration_weeks']; ?> weeks</small>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block"><strong>Exercises:</strong></small>
                                <small><?php echo $template['exercises_count']; ?> exercises</small>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block"><strong>Difficulty:</strong></small>
                                <small>
                                    <span class="badge 
                                        <?php 
                                            switch($template['difficulty_level']) {
                                                case 'Beginner': echo 'bg-success'; break;
                                                case 'Intermediate': echo 'bg-warning'; break;
                                                case 'Advanced': echo 'bg-danger'; break;
                                            }
                                        ?>
                                    ">
                                        <?php echo htmlspecialchars($template['difficulty_level']); ?>
                                    </span>
                                </small>
                            </div>

                            <?php if (!empty($template['equipment_required'])): ?>
                            <div class="mb-2">
                                <small class="text-muted d-block"><strong>Equipment:</strong></small>
                                <small><?php echo htmlspecialchars($template['equipment_required']); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Customize Your Plan</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($templateId); ?>">

                                <!-- Member Selection (for trainers/admins) -->
                                <?php if ($currentUserType !== 'member'): ?>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="member_id" class="form-label">Select Member *</label>
                                        <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                                id="member_id" name="member_id" required>
                                            <option value="">-- Select Member --</option>
                                            <?php foreach ($members as $member): ?>
                                                <option value="<?php echo htmlspecialchars($member['member_id']); ?>"
                                                        <?php echo isset($formData['member_id']) && $formData['member_id'] === $member['member_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($member['member_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['member_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Trainer Selection (for admins) -->
                                    <?php if ($currentUserType === 'admin'): ?>
                                    <div class="col-md-6">
                                        <label for="trainer_id" class="form-label">Assign Trainer (Optional)</label>
                                        <select class="form-select" id="trainer_id" name="trainer_id">
                                            <option value="">-- No Trainer --</option>
                                            <?php foreach ($trainers as $trainer): ?>
                                                <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>"
                                                        <?php echo isset($formData['trainer_id']) && $formData['trainer_id'] === $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <!-- Plan Customization -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="plan_name" class="form-label">Plan Name *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['plan_name']) ? 'is-invalid' : ''; ?>" 
                                               id="plan_name" name="plan_name" 
                                               value="<?php echo htmlspecialchars($formData['plan_name'] ?? $template['template_name']) ?>"
                                               placeholder="e.g., My Custom Strength Plan"
                                               required>
                                        <?php if (isset($errors['plan_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['plan_name']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="duration_weeks" class="form-label">Program Duration (weeks) *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['duration_weeks']) ? 'is-invalid' : ''; ?>" 
                                               id="duration_weeks" name="duration_weeks" 
                                               value="<?php echo htmlspecialchars($formData['duration_weeks'] ?? $template['duration_weeks']) ?>"
                                               min="1" max="52"
                                               required>
                                        <?php if (isset($errors['duration_weeks'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['duration_weeks']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Custom Details -->
                                <div class="mb-4">
                                    <label for="customized_details" class="form-label">Custom Notes & Modifications</label>
                                    <textarea class="form-control" id="customized_details" name="customized_details" 
                                              rows="6" placeholder="Add any custom modifications, notes, or personal adjustments to the template..."><?php echo htmlspecialchars($formData['customized_details'] ?? ''); ?></textarea>
                                    <small class="text-muted">Leave empty to use the template's default plan</small>
                                </div>

                                <!-- Template Schedule Preview -->
                                <div class="mb-4">
                                    <label class="form-label"><i class="fas fa-calendar-alt"></i> Template Schedule Preview</label>
                                    <div class="alert alert-light border">
                                        <?php 
                                            $schedule = isset($template['weekly_schedule']) ? (json_decode($template['weekly_schedule'], true) ?? []) : [];
                                            if (!empty($schedule)):
                                                foreach ($schedule as $day => $exercises):
                                        ?>
                                            <div class="mb-2">
                                                <strong class="text-primary"><?php echo ucfirst(htmlspecialchars($day)); ?>:</strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($exercises); ?></small>
                                            </div>
                                        <?php endforeach; 
                                            else: 
                                        ?>
                                            <p class="text-muted">No schedule details available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/templates/" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Create Workout Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
