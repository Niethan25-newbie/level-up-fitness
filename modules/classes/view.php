<?php
/**
 * Classes - View Class Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$classId = sanitize($_GET['id'] ?? '');
$class = null;
$trainer = null;
$members = [];
$availableMembers = [];

if (!empty($classId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            setMessage('Class not found', 'error');
            redirect(APP_URL . 'modules/classes/');
        }

        // Get trainer info if assigned
        if (!empty($class['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$class['trainer_id']]);
            $trainer = $trainerStmt->fetch();
        }

        // Get class members
        $memberStmt = $pdo->prepare("
            SELECT m.member_id, m.member_name, m.email, ca.enrollment_date
            FROM class_attendance ca
            JOIN members m ON ca.member_id = m.member_id
            WHERE ca.class_id = ?
            ORDER BY ca.enrollment_date DESC
        ");
        $memberStmt->execute([$classId]);
        $members = $memberStmt->fetchAll();

        // Get members not yet enrolled (for enrollment dropdown)
        $availableMemberStmt = $pdo->prepare("
            SELECT m.member_id, m.member_name 
            FROM members m
            WHERE m.status = 'Active'
            AND m.member_id NOT IN (
                SELECT ca.member_id FROM class_attendance ca WHERE ca.class_id = ?
            )
            ORDER BY m.member_name
        ");
        $availableMemberStmt->execute([$classId]);
        $availableMembers = $availableMemberStmt->fetchAll();

    } catch (Exception $e) {
        setMessage('Error loading class: ' . $e->getMessage(), 'error');
    }
}

// Handle member enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_member'])) {
    $memberId = sanitize($_POST['member_id'] ?? '');
    
    if (empty($memberId)) {
        setMessage('Please select a member to enroll', 'error');
    } else {
        try {
            // Check if member is already enrolled
            $checkStmt = $pdo->prepare("SELECT * FROM class_attendance WHERE class_id = ? AND member_id = ?");
            $checkStmt->execute([$classId, $memberId]);
            if ($checkStmt->rowCount() > 0) {
                setMessage('Member is already enrolled in this class', 'warning');
            } else {
                // Check if class is at capacity
                if (count($members) >= $class['max_capacity']) {
                    setMessage('Class is at maximum capacity', 'error');
                } else {
                    // Enroll member
                    $enrollStmt = $pdo->prepare("
                        INSERT INTO class_attendance (class_id, member_id, enrollment_date)
                        VALUES (?, ?, NOW())
                    ");
                    $enrollStmt->execute([$classId, $memberId]);
                    
                    logAction($_SESSION['user_id'], 'ENROLL_CLASS', 'Classes', 'Enrolled member ' . $memberId . ' in class ' . $classId);
                    setMessage('Member enrolled successfully!', 'success');
                    redirect('view.php?id=' . $classId);
                }
            }
        } catch (Exception $e) {
            setMessage('Error enrolling member: ' . $e->getMessage(), 'error');
        }
    }
}

// Handle member unenrollment
if (isset($_GET['unenroll_member']) && isset($_GET['id'])) {
    $memberId = sanitize($_GET['unenroll_member']);
    $classId = sanitize($_GET['id']);
    
    try {
        $unenrollStmt = $pdo->prepare("DELETE FROM class_attendance WHERE class_id = ? AND member_id = ?");
        $unenrollStmt->execute([$classId, $memberId]);
        
        logAction($_SESSION['user_id'], 'UNENROLL_CLASS', 'Classes', 'Removed member ' . $memberId . ' from class ' . $classId);
        setMessage('Member removed from class successfully!', 'success');
        redirect('view.php?id=' . $classId);
    } catch (Exception $e) {
        setMessage('Error removing member: ' . $e->getMessage(), 'error');
    }
}
?>


<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/classes/edit.php?id=<?php echo $classId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/classes/delete.php?id=<?php echo $classId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-dumbbell"></i> Class Details</h1>
                <p>View class information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($class): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Class ID:</strong> <code><?php echo htmlspecialchars($class['class_id']); ?></code>
                            </p>
                            <hr>
                            <?php if (!empty($class['class_description'])): ?>
                                <p>
                                    <strong>Description:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($class['class_description'])); ?>
                                </p>
                                <hr>
                            <?php endif; ?>
                            <p>
                                <strong>Schedule:</strong> <?php echo htmlspecialchars($class['class_schedule']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower(str_replace('Active', 'success', str_replace('Inactive', 'secondary', $class['class_status']))); ?>" style="font-size: 14px;">
                                    <?php echo htmlspecialchars($class['class_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Class Members (<?php echo count($members); ?>/<?php echo $class['max_capacity']; ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($availableMembers) && count($members) < $class['max_capacity']): ?>
                            <div class="alert alert-info mb-3">
                                <strong>Enroll Members:</strong>
                            </div>
                            <form method="POST" class="mb-4">
                                <div class="input-group">
                                    <select name="member_id" class="form-control" required>
                                        <option value="">Select a member to enroll...</option>
                                        <?php foreach ($availableMembers as $member): ?>
                                            <option value="<?php echo $member['member_id']; ?>">
                                                <?php echo htmlspecialchars($member['member_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="enroll_member" value="1" class="btn btn-success">
                                        <i class="fas fa-user-plus"></i> Enroll
                                    </button>
                                </div>
                            </form>
                            <?php elseif (count($members) >= $class['max_capacity']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Class is at maximum capacity
                            </div>
                            <?php endif; ?>

                            <?php if (empty($members)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No members enrolled yet.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Email</th>
                                                <th>Enrolled</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                    <td><?php echo formatDate($member['enrollment_date']); ?></td>
                                                    <td>
                                                        <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Member">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="view.php?id=<?php echo $classId; ?>&unenroll_member=<?php echo $member['member_id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Remove this member from the class?')" 
                                                           title="Remove from class">
                                                            <i class="fas fa-user-minus"></i>
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
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Capacity</h5>
                        </div>
                        <div class="card-body text-center">
                            <h3><?php echo count($members); ?> / <?php echo $class['max_capacity']; ?></h3>
                            <p class="text-muted">Members Enrolled</p>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar <?php 
                                    $capacityPercent = (count($members) / $class['max_capacity'] * 100);
                                    echo $capacityPercent >= 80 ? 'bg-danger' : ($capacityPercent >= 60 ? 'bg-warning' : 'bg-success');
                                ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $capacityPercent; ?>%">
                                    <?php echo round($capacityPercent); ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($trainer): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Instructor</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong><?php echo htmlspecialchars($trainer['trainer_name']); ?></strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <p>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <div class="mt-2">
                                <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-link"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Instructor</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">No instructor assigned</p>
                            <a href="<?php echo APP_URL; ?>modules/classes/edit.php?id=<?php echo $classId; ?>" 
                               class="btn btn-sm btn-primary">
                                Assign Instructor
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($class['created_at']); ?>
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
