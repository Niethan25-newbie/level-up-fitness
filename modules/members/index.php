<?php
/**
 * Members Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

// Require login and admin role
requireLogin();
requireRole('admin');

$userInfo = getUserInfo();
$members = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT * FROM members WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (LOWER(member_name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(contact_number) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND status = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading members: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Members Management</h1>
                <p>View, add, edit, and manage member profiles</p>
            </div>

            <?php displayMessage(); ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by name, email, or phone..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm) || !empty($filterStatus)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $filterStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Expired" <?php echo $filterStatus === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?php echo APP_URL; ?>modules/members/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Member
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Members List 
                        <span class="badge bg-primary"><?php echo $totalRecords; ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($members)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No members found.
                            <a href="<?php echo APP_URL; ?>modules/members/add.php">Add the first member</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Type</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): 
                                        $expiryDate = getMembershipExpiryDate($member['join_date'], $member['membership_type']);
                                        $daysLeft = getDaysUntilExpiry($member['join_date'], $member['membership_type']);
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($member['member_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo htmlspecialchars($member['contact_number']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($member['membership_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($member['join_date']); ?></td>
                                            <td>
                                                <?php 
                                                    echo generateStatusBadge($member['status']);
                                                    if ($daysLeft > 0 && $daysLeft <= 7) {
                                                        echo '<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Expires in ' . $daysLeft . ' days</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/members/edit.php?id=<?php echo $member['member_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/members/delete.php?id=<?php echo $member['member_id']; ?>" 
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
