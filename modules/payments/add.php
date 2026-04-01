<?php
/**
 * Payments Management - Add/Record Payment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$errors = [];
$formData = [];
$members = [];

// Get all active members
try {
    $stmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading members: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    $formData['amount'] = floatval($_POST['amount'] ?? 0);
    $formData['payment_method'] = sanitize($_POST['payment_method'] ?? '');
    $formData['payment_status'] = sanitize($_POST['payment_status'] ?? 'Paid');
    $paymentDate = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
    $formData['notes'] = sanitize($_POST['notes'] ?? '');

    // Validate
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member is required';
    }
    if ($formData['amount'] <= 0) {
        $errors['amount'] = 'Amount must be greater than 0';
    }
    if (empty($formData['payment_method'])) {
        $errors['payment_method'] = 'Payment method is required';
    }
    if (empty($paymentDate)) {
        $errors['payment_date'] = 'Payment date is required';
    }

    if (empty($errors)) {
        try {
            // Generate payment ID
            $paymentId = generateUniqueID(PAYMENT_ID_PREFIX, 'payments');

            // Insert payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    payment_id, member_id, amount, payment_method, 
                    payment_date, payment_status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $paymentId, $formData['member_id'], $formData['amount'], 
                $formData['payment_method'], $paymentDate, 
                $formData['payment_status'], $formData['notes']
            ]);

            logAction($_SESSION['user_id'], 'ADD_PAYMENT', 'Payments', 
                     'Recorded payment of ' . formatCurrency($formData['amount']) . ' from member ' . $formData['member_id']);

            setMessage('Payment recorded successfully! ID: ' . $paymentId, 'success');
            redirect(APP_URL . 'modules/payments/');
        } catch (Exception $e) {
            setMessage('Error recording payment: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Payments
                </a>
                <h1><i class="fas fa-plus-circle"></i> Record New Payment</h1>
                <p>Enter payment details for a member</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Payment Information</h5>
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
                                                    <?php echo htmlspecialchars($member['member_name']); ?> (<?php echo htmlspecialchars($member['member_id']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['member_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                                   id="amount" name="amount" step="0.01" min="0"
                                                   value="<?php echo htmlspecialchars($formData['amount'] ?? ''); ?>" required>
                                        </div>
                                        <?php if (isset($errors['amount'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select <?php echo isset($errors['payment_method']) ? 'is-invalid' : ''; ?>" 
                                                id="payment_method" name="payment_method" required>
                                            <option value="">-- Select Method --</option>
                                            <option value="Cash" <?php echo ($formData['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                            <option value="Card" <?php echo ($formData['payment_method'] ?? '') === 'Card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
                                            <option value="GCash" <?php echo ($formData['payment_method'] ?? '') === 'GCash' ? 'selected' : ''; ?>>GCash</option>
                                            <option value="Bank Transfer" <?php echo ($formData['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                            <option value="Cheque" <?php echo ($formData['payment_method'] ?? '') === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                        </select>
                                        <?php if (isset($errors['payment_method'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['payment_method']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="payment_date" class="form-label">Payment Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['payment_date']) ? 'is-invalid' : ''; ?>" 
                                               id="payment_date" name="payment_date" 
                                               value="<?php echo htmlspecialchars($formData['payment_date'] ?? date('Y-m-d')); ?>" required>
                                        <?php if (isset($errors['payment_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['payment_date']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status">
                                        <option value="Paid" <?php echo ($formData['payment_status'] ?? 'Paid') === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Pending" <?php echo ($formData['payment_status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Overdue" <?php echo ($formData['payment_status'] ?? '') === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Optional: Add any notes about this payment..."><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Record Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Quick Reference</h5>
                        </div>
                        <div class="card-body small">
                            <h6>Payment Methods</h6>
                            <ul class="list-unstyled">
                                <li><strong>Cash</strong> - Physical cash payment</li>
                                <li><strong>Card</strong> - Credit/Debit card</li>
                                <li><strong>GCash</strong> - Mobile payment</li>
                                <li><strong>Bank</strong> - Bank transfer</li>
                                <li><strong>Cheque</strong> - Cheque payment</li>
                            </ul>

                            <hr>

                            <h6>Payment Status</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">Paid</span> - Payment completed</li>
                                <li><span class="badge bg-warning">Pending</span> - Awaiting payment</li>
                                <li><span class="badge bg-danger">Overdue</span> - Payment past due</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
