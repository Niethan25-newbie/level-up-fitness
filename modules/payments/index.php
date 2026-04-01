<?php
/**
 * Payments Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$payments = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$totalAmount = 0;

// Determine if viewing as member or admin
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

// Get current member ID if user is a member
if (!$isAdmin && $_SESSION['user_type'] === 'member') {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
        $memberStmt->execute([$_SESSION['user_id']]);
        $member = $memberStmt->fetch();
        $currentMemberId = $member['member_id'] ?? null;
    } catch (Exception $e) {
        setMessage('Error retrieving member information: ' . $e->getMessage(), 'error');
    }
}

try {
    // Build query
    $query = "SELECT * FROM payments WHERE 1=1";
    $params = [];
    
    // Members can only view their own payments
    if (!$isAdmin && $currentMemberId) {
        $query .= " AND member_id = ?";
        $params[] = $currentMemberId;
    } elseif (!$isAdmin) {
        // Trainers cannot view payments
        die('Access denied: You do not have permission to access this page.');
    }

    // Search filter - search by member name or ID
    if (!empty($searchTerm)) {
        $query .= " AND (member_id LIKE ? OR payment_id LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND payment_status = ?";
        $params[] = $filterStatus;
    }

    // Get total count and sum
    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total, SUM(amount) as total_amount', $query));
    $countStmt->execute($params);
    $result = $countStmt->fetch();
    $totalRecords = $result['total'] ?? 0;
    $totalAmount = $result['total_amount'] ?? 0;
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY payment_date DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading payments: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> <?php echo $isAdmin ? 'Payments Management' : 'My Payments'; ?></h1>
                <p><?php echo $isAdmin ? 'Track and manage member payments' : 'View your payment history'; ?></p>
            </div>

            <?php displayMessage(); ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Total Payments</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-money-bill"></i> Total Amount</h6>
                            <h3><?php echo formatCurrency($totalAmount); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-check-circle"></i> Paid</h6>
                            <h3>
                                <?php 
                                $paidStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_status = ?");
                                $paidStmt->execute(['Paid']);
                                echo $paidStmt->fetch()['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-clock"></i> Pending</h6>
                            <h3>
                                <?php 
                                $pendingStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_status IN ('Pending', 'Overdue')");
                                $pendingStmt->execute();
                                echo $pendingStmt->fetch()['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by member ID or payment ID..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm) || !empty($filterStatus)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Paid" <?php echo $filterStatus === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Pending" <?php echo $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Overdue" <?php echo $filterStatus === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($isAdmin): ?>
                                <a href="<?php echo APP_URL; ?>modules/payments/add.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Record Payment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Payments List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No payments found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <?php if ($isAdmin): ?>
                                            <th>Member ID</th>
                                        <?php endif; ?>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($payment['payment_id']); ?></code></td>
                                            <?php if ($isAdmin): ?>
                                                <td><?php echo htmlspecialchars($payment['member_id']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo formatCurrency($payment['amount']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($payment['payment_date']); ?></td>
                                            <td>
                                                <?php 
                                                    $statusClass = 'badge-' . strtolower($payment['payment_status']);
                                                    echo '<span class="badge ' . $statusClass . '">' . htmlspecialchars($payment['payment_status']) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/payments/invoice.php?id=<?php echo $payment['payment_id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/payments/view.php?id=<?php echo $payment['payment_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/payments/edit.php?id=<?php echo $payment['payment_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/payments/delete.php?id=<?php echo $payment['payment_id']; ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Last</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
