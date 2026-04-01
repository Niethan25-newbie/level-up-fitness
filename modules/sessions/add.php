<?php
/**
 * Training Sessions Management - Add New Session
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$message = getMessage();
$trainers = [];
$gyms = [];

// Get trainers and gyms for dropdown
try {
    $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers ORDER BY trainer_name");
    $trainerStmt->execute();
    $trainers = $trainerStmt->fetchAll();

    $gymStmt = $pdo->prepare("SELECT gym_id, gym_name FROM gyms ORDER BY gym_name");
    $gymStmt->execute();
    $gyms = $gymStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
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
        // Validate date format and prevent past dates
        $sessionDateObj = DateTime::createFromFormat('Y-m-d', $sessionDate);
        if (!$sessionDateObj || $sessionDateObj->format('Y-m-d') !== $sessionDate) {
            $errors[] = 'Invalid date format';
        } elseif ($sessionDateObj < new DateTime('today')) {
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
            // If trainer is not admin, they can only create their own sessions
            if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $trainerId) {
                throw new Exception('You can only create sessions for yourself');
            }

            $stmt = $pdo->prepare("
                INSERT INTO training_sessions 
                (session_name, trainer_id, gym_id, session_date, session_time, duration, max_capacity, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $sessionName, $trainerId, $gymId, $sessionDate, $sessionTime,
                $duration, $maxCapacity, $description, $status
            ]);

            setMessage('Session created successfully!', 'success');
            redirect('modules/sessions/index.php');
        } catch (Exception $e) {
            setMessage('Error creating session: ' . $e->getMessage(), 'error');
        }
    }
}

?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Create New Training Session</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <form method="POST" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="session_name">Session Name *</label>
                        <input type="text" id="session_name" name="session_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="trainer_id">Trainer *</label>
                        <select id="trainer_id" name="trainer_id" class="form-control" required>
                            <option value="">Select Trainer</option>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo $trainer['trainer_id']; ?>">
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
                                <option value="<?php echo $gym['gym_id']; ?>">
                                    <?php echo htmlspecialchars($gym['gym_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="session_date">Session Date *</label>
                        <input type="date" id="session_date" name="session_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="session_time">Session Time *</label>
                        <input type="time" id="session_time" name="session_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (minutes) *</label>
                        <input type="number" id="duration" name="duration" class="form-control" min="15" step="15" required>
                    </div>

                    <div class="form-group">
                        <label for="max_capacity">Max Capacity *</label>
                        <input type="number" id="max_capacity" name="max_capacity" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Session</button>
                    <a href="index.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
