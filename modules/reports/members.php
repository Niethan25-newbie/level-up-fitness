<?php
/**
 * Reports - Member Report
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$members = [];
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterMembership = $_GET['membership'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT m.*, 
                     COUNT(DISTINCT ca.class_id) as classes_enrolled,
                     COUNT(DISTINCT s.session_id) as sessions,
                     COALESCE(SUM(p.amount), 0) as total_paid
              FROM members m
              LEFT JOIN class_attendance ca ON m.member_id = ca.member_id
              LEFT JOIN sessions s ON m.member_id = s.member_id
              LEFT JOIN payments p ON m.member_id = p.member_id AND p.payment_status = 'Completed'
              WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (m.member_id LIKE ? OR m.member_name LIKE ? OR m.email LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND m.status = ?";
        $params[] = $filterStatus;
    }

    // Membership filter
    if (!empty($filterMembership)) {
        $query .= " AND m.membership_type = ?";
        $params[] = $filterMembership;
    }

    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT m.member_id) as total FROM members m WHERE 1=1";
    if (!empty($searchTerm)) {
        $countQuery .= " AND (m.member_id LIKE ? OR m.member_name LIKE ? OR m.email LIKE ?)";
    }
    if (!empty($filterStatus)) {
        $countQuery .= " AND m.status = ?";
    }
    if (!empty($filterMembership)) {
        $countQuery .= " AND m.membership_type = ?";
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " GROUP BY m.member_id ORDER BY m.member_name ASC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading member report: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/reports/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-users"></i> Member Report</h1>
                <p>Detailed member activity and engagement metrics</p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search member..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $filterStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="membership" onchange="window.location='?membership=' + this.value">
                                <option value="">All Membership Types</option>
                                <option value="Standard" <?php echo $filterMembership === 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="Premium" <?php echo $filterMembership === 'Premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="Gold" <?php echo $filterMembership === 'Gold' ? 'selected' : ''; ?>>Gold</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Member Activity Report</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($members)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No members found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Membership</th>
                                        <th>Status</th>
                                        <th>Classes</th>
                                        <th>Sessions</th>
                                        <th>Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($member['member_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($member['membership_type']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $member['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars($member['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $member['classes_enrolled']; ?></td>
                                            <td><?php echo $member['sessions']; ?></td>
                                            <td>$<?php echo number_format($member['total_paid'], 2); ?></td>
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
                                            <a class="page-link" href="?page=1">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a>
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
