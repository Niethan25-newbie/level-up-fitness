<?php
/**
 * Workout Plans - Create New Plan
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Only admins and trainers can CREATE workout plans
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can create workout plans.');
}

$currentUserType = $_SESSION['user_type'];
$currentUserId = $_SESSION['user_id'];
$currentTrainerId = null;

// Get current trainer ID if user is a trainer
if ($currentUserType === 'trainer') {
    try {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
        $trainerStmt->execute([$currentUserId]);
        $trainerResult = $trainerStmt->fetch();
        if ($trainerResult) {
            $currentTrainerId = $trainerResult['trainer_id'];
        }
    } catch (Exception $e) {
        setMessage('Error loading trainer info: ' . $e->getMessage(), 'error');
    }
}

$errors = [];
$formData = [];
$members = [];
$trainers = [];

// Get active members and trainers
try {
    // Trainers can only see members; admins can see all active members
    $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
    $memberStmt->execute();
    $members = $memberStmt->fetchAll();

    // Only show trainer dropdown for admins (trainers auto-assign themselves)
    if ($currentUserType === 'admin') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers ORDER BY trainer_name");
        $trainerStmt->execute();
        $trainers = $trainerStmt->fetchAll();
    } else {
        $trainers = []; // Empty for trainers
    }
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    // For trainers, auto-assign themselves; for admins, allow selection
    if ($currentUserType === 'trainer') {
        $formData['trainer_id'] = $currentTrainerId;
    } else {
        $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    }
    $formData['plan_name'] = sanitize($_POST['plan_name'] ?? '');
    $formData['goal'] = sanitize($_POST['goal'] ?? '');
    $formData['duration_weeks'] = intval($_POST['duration_weeks'] ?? 0);
    $formData['details'] = sanitize($_POST['details'] ?? '');

    // Validate
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member is required';
    }
    if (empty($formData['plan_name'])) {
        $errors['plan_name'] = 'Plan name is required';
    }
    if (empty($formData['goal'])) {
        $errors['goal'] = 'Goal is required';
    }
    if ($formData['duration_weeks'] <= 0) {
        $errors['duration_weeks'] = 'Duration must be greater than 0';
    }
    if (empty($formData['details'])) {
        $errors['details'] = 'Plan details are required';
    }

    if (empty($errors)) {
        try {
            $planId = generateUniqueID(WORKOUT_PLAN_ID_PREFIX, 'workout_plans');
            
            // Build weekly schedule and plan details
            $weeklySchedule = json_encode([
                'goal' => $formData['goal'],
                'duration_weeks' => $formData['duration_weeks']
            ]);

            $stmt = $pdo->prepare("
                INSERT INTO workout_plans (
                    workout_plan_id, member_id, trainer_id, plan_name, 
                    weekly_schedule, plan_details
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $planId, $formData['member_id'], 
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['plan_name'], $weeklySchedule, $formData['details']
            ]);

            logAction($_SESSION['user_id'], 'CREATE_PLAN', 'Workouts', 
                     'Created plan: ' . $formData['plan_name']);

            setMessage('Workout plan created successfully! ID: ' . $planId, 'success');
            redirect(APP_URL . 'modules/workouts/');
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
                <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-plus-circle"></i> Create Workout Plan</h1>
                <p><?php echo $currentUserType === 'trainer' ? 'Create a personalized workout plan for your member' : 'Create a personalized workout plan for a member'; ?></p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Plan Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="member_id" class="form-label">Member *</label>
                                        <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                                id="member_id" name="member_id" required>
                                            <option value="">-- Select Member --</option>
                                            <?php foreach ($members as $member): ?>
                                                <option value="<?php echo htmlspecialchars($member['member_id']); ?>" 
                                                        <?php echo ($formData['member_id'] ?? '') === $member['member_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($member['member_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['member_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($currentUserType === 'admin'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="trainer_id" class="form-label">Trainer (Optional)</label>
                                        <select class="form-select" id="trainer_id" name="trainer_id">
                                            <option value="">-- Unassigned --</option>
                                            <?php foreach ($trainers as $trainer): ?>
                                                <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>" 
                                                        <?php echo ($formData['trainer_id'] ?? '') === $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Assigned Trainer</label>
                                        <input type="text" class="form-control" disabled value="You (Auto-assigned)">
                                        <small class="text-muted">Plans created by trainers are automatically assigned to you</small>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="plan_name" class="form-label">Plan Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['plan_name']) ? 'is-invalid' : ''; ?>" 
                                           id="plan_name" name="plan_name" placeholder="e.g., Strength Building, Weight Loss"
                                           value="<?php echo htmlspecialchars($formData['plan_name'] ?? ''); ?>" required>
                                    <?php if (isset($errors['plan_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['plan_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="goal" class="form-label">Goal *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['goal']) ? 'is-invalid' : ''; ?>" 
                                               id="goal" name="goal" placeholder="e.g., Lose 10 kg"
                                               value="<?php echo htmlspecialchars($formData['goal'] ?? ''); ?>" required>
                                        <?php if (isset($errors['goal'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['goal']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="duration_weeks" class="form-label">Duration (Weeks) *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['duration_weeks']) ? 'is-invalid' : ''; ?>" 
                                               id="duration_weeks" name="duration_weeks" min="1" max="52"
                                               value="<?php echo htmlspecialchars($formData['duration_weeks'] ?? '12'); ?>" required>
                                        <?php if (isset($errors['duration_weeks'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['duration_weeks']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="details" class="form-label">Plan Details *</label>
                                    <textarea class="form-control <?php echo isset($errors['details']) ? 'is-invalid' : ''; ?>" 
                                              id="details" name="details" rows="5" 
                                              placeholder="Describe the workout plan, exercises, schedule, etc..."
                                              required><?php echo htmlspecialchars($formData['details'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['details'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['details']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Plan Tips</h5>
                        </div>
                        <div class="card-body small">
                            <p>
                                <i class="fas fa-lightbulb text-warning"></i> 
                                Make plans specific and measurable
                            </p>
                            <p>
                                <i class="fas fa-lightbulb text-warning"></i> 
                                Include rest days in your schedule
                            </p>
                            <p>
                                <i class="fas fa-lightbulb text-warning"></i> 
                                Assign a trainer for better guidance
                            </p>
                            <p>
                                <i class="fas fa-lightbulb text-warning"></i> 
                                Review and adjust plans regularly
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Common Goals</h5>
                        </div>
                        <div class="card-body small">
                            <ul class="list-unstyled">
                                <li>• Weight Loss</li>
                                <li>• Muscle Gain</li>
                                <li>• Strength Building</li>
                                <li>• Flexibility</li>
                                <li>• Endurance</li>
                                <li>• General Fitness</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
