<?php
/**
 * Trainers Management - View Trainer Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$trainerId = sanitize($_GET['id'] ?? '');
$trainer = null;
$assignedMembers = [];
$sessions = [];

if (!empty($trainerId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
        $stmt->execute([$trainerId]);
        $trainer = $stmt->fetch();
        
        if (!$trainer) {
            setMessage('Trainer not found', 'error');
            redirect(APP_URL . 'modules/trainers/');
        }

        // Get assigned members - both directly assigned and through workout plans
        $membersStmt = $pdo->prepare("
            SELECT DISTINCT m.member_id, m.member_name, m.email, m.contact_number
            FROM members m
            WHERE m.trainer_id = ?
            AND m.status = 'Active'
            UNION
            SELECT DISTINCT m.member_id, m.member_name, m.email, m.contact_number
            FROM members m
            JOIN workout_plans wp ON m.member_id = wp.member_id
            WHERE wp.trainer_id = ?
            AND m.status = 'Active'
            ORDER BY member_name
            LIMIT 25
        ");
        $membersStmt->execute([$trainerId, $trainerId]);
        $assignedMembers = $membersStmt->fetchAll();

        // Get sessions assigned to this trainer
        $sessionsStmt = $pdo->prepare("
            SELECT s.*, m.member_name FROM sessions s
            LEFT JOIN members m ON s.member_id = m.member_id
            WHERE s.trainer_id = ?
            ORDER BY s.session_date DESC
            LIMIT 10
        ");
        $sessionsStmt->execute([$trainerId]);
        $sessions = $sessionsStmt->fetchAll();

    } catch (Exception $e) {
        setMessage('Error loading trainer: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/trainers/edit.php?id=<?php echo $trainerId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/trainers/delete.php?id=<?php echo $trainerId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/trainers/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-user"></i> Trainer Details</h1>
                <p>View trainer information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($trainer): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Email:</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>">
                                    <?php echo htmlspecialchars($trainer['email']); ?>
                                </a>
                            </p>
                            <hr>
                            <p>
                                <strong>Phone:</strong><br>
                                <a href="tel:<?php echo htmlspecialchars($trainer['contact_number']); ?>">
                                    <?php echo htmlspecialchars($trainer['contact_number']); ?>
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Professional Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Trainer ID:</strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Specialization:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <hr>
                            <p>
                                <strong>Experience:</strong><br>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($trainer['years_of_experience']); ?> years</span>
                            </p>
                            <hr>
                            <p>
                                <strong>Joined:</strong><br>
                                <?php echo formatDate($trainer['created_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Assigned Members (<?php echo count($assignedMembers); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assignedMembers)): ?>
                                <p class="text-muted">No members assigned yet</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Member ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignedMembers as $member): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($member['member_id']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['contact_number']); ?></td>
                                                    <td>
                                                        <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                                           class="btn btn-xs btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Recent Sessions (Last 10)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($sessions)): ?>
                                <p class="text-muted">No sessions scheduled yet</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Session ID</th>
                                                <th>Member</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sessions as $session): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($session['session_id']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($session['member_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo formatDate($session['session_date']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo strtolower($session['session_status']); ?>">
                                                            <?php echo htmlspecialchars($session['session_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
