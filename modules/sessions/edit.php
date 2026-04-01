<?php
/**
 * Training Sessions Management - Edit Session
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$message = getMessage();
$trainers = [];
$gyms = [];
$session = null;
$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    setMessage('Session ID is required', 'error');
    redirect('modules/sessions/index.php');
}

try {
    // Get trainers and gyms for dropdown
    $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers ORDER BY trainer_name");
    $trainerStmt->execute();
    $trainers = $trainerStmt->fetchAll();

    $gymStmt = $pdo->prepare("SELECT gym_id, gym_name FROM gyms ORDER BY gym_name");
    $gymStmt->execute();
    $gyms = $gymStmt->fetchAll();

    // Get session details
    $sessionStmt = $pdo->prepare("
        SELECT * FROM training_sessions WHERE session_id = ?
    ");
    $sessionStmt->execute([$sessionId]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        setMessage('Session not found', 'error');
        redirect('modules/sessions/index.php');
    }

    // Check authorization
    if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $session['trainer_id']) {
        setMessage('You do not have permission to edit this session', 'error');
        redirect('modules/sessions/index.php');
    }
} catch (Exception $e) {
    setMessage('Error loading session: ' . $e->getMessage(), 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionName = $_POST['session_name'] ?? '';
    $trainerId = $_POST['trainer_id'] ?? '';
    $gymId = $_POST['gym_id'] ?? '';
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $maxCapacity = $_POST['max_capacity'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'Scheduled';

    // Validation
    $errors = [];
    if (empty($sessionName)) $errors[] = 'Session name is required';
    if (empty($trainerId)) {
        $errors[] = 'Trainer is required';
    } else {
        // Check if trainer exists and is active
        $trainerCheckStmt = $pdo->prepare("SELECT trainer_id, status FROM trainers WHERE trainer_id = ?");
        $trainerCheckStmt->execute([$trainerId]);
        $trainerData = $trainerCheckStmt->fetch();
        if (!$trainerData) {
            $errors[] = 'Selected trainer does not exist';
        } elseif (isset($trainerData['status']) && $trainerData['status'] !== 'Active') {
            $errors[] = 'Selected trainer is not currently active';
        }
    }
    if (empty($gymId)) $errors[] = 'Gym is required';
    if (empty($sessionDate)) {
        $errors[] = 'Session date is required';
    } else {
        // Validate date format and prevent past dates (only for future sessions)
        $sessionDateObj = DateTime::createFromFormat('Y-m-d', $sessionDate);
        if (!$sessionDateObj || $sessionDateObj->format('Y-m-d') !== $sessionDate) {
            $errors[] = 'Invalid date format';
        } elseif ($sessionDateObj < new DateTime('today') && $session['session_date'] !== $sessionDate) {
            // Allow keeping current date, but prevent changing to past date
            $errors[] = 'Session date cannot be in the past';
        } elseif ($sessionDateObj > new DateTime('+90 days')) {
            $errors[] = 'Sessions can only be scheduled up to 90 days in advance';
        }
    }
    if (empty($sessionTime)) $errors[] = 'Session time is required';
    if (empty($duration)) $errors[] = 'Duration is required';
    if (empty($maxCapacity)) $errors[] = 'Max capacity is required';

    if (count($errors) > 0) {
        setMessage('Please fix the following errors: ' . implode(', ', $errors), 'error');
    } else {
        try {
            // If trainer, can only update their own sessions
            if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $trainerId) {
                throw new Exception('You can only update your own sessions');
            }

            $stmt = $pdo->prepare("
                UPDATE training_sessions SET 
                session_name = ?, trainer_id = ?, gym_id = ?, 
                session_date = ?, session_time = ?, duration = ?, 
                max_capacity = ?, description = ?, status = ?, updated_at = NOW()
                WHERE session_id = ?
            ");
            
            $stmt->execute([
                $sessionName, $trainerId, $gymId, $sessionDate, $sessionTime,
                $duration, $maxCapacity, $description, $status, $sessionId
            ]);

            setMessage('Session updated successfully!', 'success');
            redirect('modules/sessions/view.php?id=' . $sessionId);
        } catch (Exception $e) {
            setMessage('Error updating session: ' . $e->getMessage(), 'error');
        }
    }
}

?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Edit Training Session</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <?php if ($session): ?>
                <form method="POST" class="form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_name">Session Name *</label>
                            <input type="text" id="session_name" name="session_name" class="form-control" value="<?php echo htmlspecialchars($session['session_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="trainer_id">Trainer *</label>
                            <select id="trainer_id" name="trainer_id" class="form-control" required>
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['trainer_id']; ?>" <?php echo $session['trainer_id'] == $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gym_id">Gym *</label>
                            <select id="gym_id" name="gym_id" class="form-control" required>
                                <option value="">Select Gym</option>
                                <?php foreach ($gyms as $gym): ?>
                                    <option value="<?php echo $gym['gym_id']; ?>" <?php echo $session['gym_id'] == $gym['gym_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gym['gym_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="session_date">Session Date *</label>
                            <input type="date" id="session_date" name="session_date" class="form-control" value="<?php echo htmlspecialchars($session['session_date']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_time">Session Time *</label>
                            <input type="time" id="session_time" name="session_time" class="form-control" value="<?php echo htmlspecialchars($session['session_time']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (minutes) *</label>
                            <input type="number" id="duration" name="duration" class="form-control" value="<?php echo htmlspecialchars($session['duration']); ?>" min="15" step="15" required>
                        </div>

                        <div class="form-group">
                            <label for="max_capacity">Max Capacity *</label>
                            <input type="number" id="max_capacity" name="max_capacity" class="form-control" value="<?php echo htmlspecialchars($session['max_capacity']); ?>" min="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Scheduled" <?php echo $session['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="Ongoing" <?php echo $session['status'] === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="Completed" <?php echo $session['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $session['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($session['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Session</button>
                        <a href="view.php?id=<?php echo $sessionId; ?>" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
