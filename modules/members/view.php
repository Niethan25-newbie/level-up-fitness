<?php
/**
 * Members Management - View Member Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$memberId = sanitize($_GET['id'] ?? '');
$member = null;
$trainer = null;
$attendance = [];
$payments = [];

if (!empty($memberId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            setMessage('Member not found', 'error');
            redirect(APP_URL . 'modules/members/');
        }

        // Get assigned trainer if any
        if (!empty($member['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$member['trainer_id']]);
            $trainer = $trainerStmt->fetch();
        }

        // Get recent attendance
        $attStmt = $pdo->prepare("
            SELECT * FROM attendance 
            WHERE member_id = ? 
            ORDER BY attendance_date DESC 
            LIMIT 10
        ");
        $attStmt->execute([$memberId]);
        $attendance = $attStmt->fetchAll();

        // Get payment history
        $payStmt = $pdo->prepare("
            SELECT * FROM payments 
            WHERE member_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $payStmt->execute([$memberId]);
        $payments = $payStmt->fetchAll();

    } catch (Exception $e) {
        setMessage('Error loading member: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/members/edit.php?id=<?php echo $memberId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/members/delete.php?id=<?php echo $memberId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-user"></i> Member Details</h1>
                <p>View complete member information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($member): 
                $expiryDate = getMembershipExpiryDate($member['join_date'], $member['membership_type']);
                $daysLeft = getDaysUntilExpiry($member['join_date'], $member['membership_type']);
            ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($member['member_name']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Email:</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                    <?php echo htmlspecialchars($member['email']); ?>
                                </a>
                            </p>
                            <hr>
                            <p>
                                <strong>Phone:</strong><br>
                                <a href="tel:<?php echo htmlspecialchars($member['contact_number']); ?>">
                                    <?php echo htmlspecialchars($member['contact_number']); ?>
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Membership Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Member ID:</strong><br>
                                <code><?php echo htmlspecialchars($member['member_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower($member['status']); ?>">
                                    <?php echo htmlspecialchars($member['status']); ?>
                                </span>
                            </p>
                            <hr>
                            <p>
                                <strong>Type:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($member['membership_type']); ?></span>
                            </p>
                            <hr>
                            <p>
                                <strong>Join Date:</strong><br>
                                <?php echo formatDate($member['join_date']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Expires:</strong><br>
                                <?php echo formatDate($expiryDate); ?>
                                <?php if ($daysLeft >= 0): ?>
                                    <br><small class="<?php echo $daysLeft <= 7 ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo $daysLeft; ?> days remaining
                                    </small>
                                <?php else: ?>
                                    <br><small class="text-danger">EXPIRED</small>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($trainer): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Assigned Trainer</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong><?php echo htmlspecialchars($trainer['trainer_name']); ?></strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Specialization:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <hr>
                            <p>
                                <strong>Email:</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>">
                                    <?php echo htmlspecialchars($trainer['email']); ?>
                                </a>
                            </p>
                            <hr>
                            <div>
                                <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-link"></i> View Trainer Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Recent Attendance (Last 10)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attendance)): ?>
                                <p class="text-muted">No attendance records yet</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Check-In</th>
                                                <th>Check-Out</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance as $record): ?>
                                                <tr>
                                                    <td><?php echo formatDate($record['attendance_date']); ?></td>
                                                    <td><?php echo $record['check_in_time'] ? date('H:i', strtotime($record['check_in_time'])) : '-'; ?></td>
                                                    <td><?php echo $record['check_out_time'] ? date('H:i', strtotime($record['check_out_time'])) : '-'; ?></td>
                                                    <td>
                                                        <?php 
                                                            if ($record['check_in_time'] && $record['check_out_time']) {
                                                                $in = new DateTime($record['check_in_time']);
                                                                $out = new DateTime($record['check_out_time']);
                                                                $diff = $out->diff($in);
                                                                echo $diff->format('%h:%i');
                                                            } else {
                                                                echo '-';
                                                            }
                                                        ?>
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
                            <h5 class="mb-0">Payment History (Last 10)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($payments)): ?>
                                <p class="text-muted">No payment records yet</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($payment['payment_id']); ?></code></td>
                                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo strtolower($payment['payment_status']); ?>">
                                                            <?php echo htmlspecialchars($payment['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
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
