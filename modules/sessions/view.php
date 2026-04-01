<?php
/**
 * Training Sessions Management - View Session Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$message = getMessage();
$session = null;
$attendees = [];
$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    setMessage('Session ID is required', 'error');
    redirect('modules/sessions/index.php');
}

try {
    // Get session details
    $sessionStmt = $pdo->prepare("
        SELECT ts.*, t.trainer_name, g.gym_name
        FROM training_sessions ts
        LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
        LEFT JOIN gyms g ON ts.gym_id = g.gym_id
        WHERE ts.session_id = ?
    ");
    $sessionStmt->execute([$sessionId]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        setMessage('Session not found', 'error');
        redirect('modules/sessions/index.php');
    }

    // Get attendees
    $attendeeStmt = $pdo->prepare("
        SELECT tsa.*, m.member_name, m.email
        FROM training_session_attendees tsa
        LEFT JOIN members m ON tsa.member_id = m.member_id
        WHERE tsa.session_id = ?
        ORDER BY tsa.check_in_time DESC
    ");
    $attendeeStmt->execute([$sessionId]);
    $attendees = $attendeeStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading session: ' . $e->getMessage(), 'error');
}

// Handle check-in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $memberId = $_POST['member_id'] ?? null;

    if (!$memberId) {
        setMessage('Member ID is required', 'error');
    } else {
        try {
            if ($action === 'checkin') {
                // Check if already checked in
                $checkStmt = $pdo->prepare("
                    SELECT * FROM training_session_attendees 
                    WHERE session_id = ? AND member_id = ? AND check_out_time IS NULL
                ");
                $checkStmt->execute([$sessionId, $memberId]);
                if ($checkStmt->fetch()) {
                    setMessage('Member is already checked in', 'error');
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO training_session_attendees (session_id, member_id, check_in_time, attendance_status)
                        VALUES (?, ?, NOW(), 'Present')
                    ");
                    $stmt->execute([$sessionId, $memberId]);
                    setMessage('Member checked in successfully', 'success');
                    header('Location: view.php?id=' . $sessionId);
                    exit;
                }
            } elseif ($action === 'checkout') {
                $stmt = $pdo->prepare("
                    UPDATE training_session_attendees SET check_out_time = NOW()
                    WHERE session_id = ? AND member_id = ? AND check_out_time IS NULL
                ");
                $stmt->execute([$sessionId, $memberId]);
                setMessage('Member checked out successfully', 'success');
                header('Location: view.php?id=' . $sessionId);
                exit;
            }
        } catch (Exception $e) {
            setMessage('Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($session['session_name'] ?? ''); ?></h2>
            <div class="action-buttons">
                <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_id'])): ?>
                    <a href="edit.php?id=<?php echo $sessionId; ?>" class="btn btn-warning">Edit</a>
                    <a href="delete.php?id=<?php echo $sessionId; ?>" class="btn btn-danger" onclick="return confirm('Delete this session?');">Delete</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-light">Back</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <?php if ($session): ?>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Trainer</label>
                        <p><?php echo htmlspecialchars($session['trainer_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Gym</label>
                        <p><?php echo htmlspecialchars($session['gym_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date</label>
                        <p><?php echo date('F d, Y', strtotime($session['session_date'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Time</label>
                        <p><?php echo date('H:i', strtotime($session['session_time'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Duration</label>
                        <p><?php echo htmlspecialchars($session['duration']); ?> minutes</p>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <p>
                            <span class="badge badge-<?php echo strtolower($session['status']); ?>">
                                <?php echo htmlspecialchars($session['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Capacity</label>
                        <p><?php echo count($attendees); ?>/<?php echo htmlspecialchars($session['max_capacity']); ?></p>
                    </div>
                </div>

                <?php if (!empty($session['description'])): ?>
                    <div class="section">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Attendees Section -->
                <div class="section">
                    <h3>Attendees (<?php echo count($attendees); ?>)</h3>
                    
                    <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_id'])): ?>
                        <form method="POST" class="check-in-form">
                            <input type="hidden" name="action" value="checkin">
                            <div class="form-row">
                                <input type="number" name="member_id" placeholder="Enter Member ID" class="form-control" required>
                                <button type="submit" class="btn btn-success">Check In</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($attendees) > 0): ?>
                                    <?php foreach ($attendees as $attendee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendee['member_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($attendee['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo $attendee['check_in_time'] ? date('H:i', strtotime($attendee['check_in_time'])) : '-'; ?></td>
                                            <td><?php echo $attendee['check_out_time'] ? date('H:i', strtotime($attendee['check_out_time'])) : '-'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($attendee['attendance_status']); ?>">
                                                    <?php echo htmlspecialchars($attendee['attendance_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$attendee['check_out_time'] && ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_id']))): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="checkout">
                                                        <input type="hidden" name="member_id" value="<?php echo $attendee['member_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-info">Check Out</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No attendees yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
