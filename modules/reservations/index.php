<?php
/**
 * Reservations Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members and admins can make reservations
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can make reservations.');
}

$reservations = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterEquipment = $_GET['equipment'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

// Get current user's member ID if they are a member
if (!$isAdmin) {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $currentMemberId = $memberData['member_id'] ?? null;
        
        // If user is a member but doesn't have a member record, deny access
        if (!$currentMemberId) {
            die('Access denied: No active member record found for your account.');
        }
    } catch (Exception $e) {
        setMessage('Error loading member data: ' . $e->getMessage(), 'error');
    }
}

try {
    // Build query
    $query = "SELECT r.*, m.member_name, e.equipment_name
              FROM reservations r
              LEFT JOIN members m ON r.member_id = m.member_id
              LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
              WHERE 1=1";
    $params = [];
    
    // Members can only see their own reservations
    if (!$isAdmin) {
        $query .= " AND r.member_id = ?";
        $params[] = $currentMemberId;
    }

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (r.reservation_id LIKE ? OR m.member_name LIKE ? OR e.equipment_name LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND r.status = ?";
        $params[] = $filterStatus;
    }

    // Equipment filter
    if (!empty($filterEquipment)) {
        $query .= " AND r.equipment_id = ?";
        $params[] = $filterEquipment;
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM reservations r WHERE 1=1";
    $countParams = [];
    
    // Members can only see their own reservations
    if (!$isAdmin) {
        $countQuery .= " AND r.member_id = ?";
        $countParams[] = $currentMemberId;
    }
    
    if (!empty($searchTerm)) {
        $countQuery .= " AND (r.reservation_id LIKE ? OR r.member_id IN (SELECT member_id FROM members WHERE member_name LIKE ?) OR r.equipment_id IN (SELECT equipment_id FROM equipment WHERE equipment_name LIKE ?))";
        $search = "%$searchTerm%";
        $countParams = array_merge($countParams, [$search, $search, $search]);
    }
    if (!empty($filterStatus)) {
        $countQuery .= " AND r.status = ?";
        $countParams[] = $filterStatus;
    }
    if (!empty($filterEquipment)) {
        $countQuery .= " AND r.equipment_id = ?";
        $countParams[] = $filterEquipment;
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY r.reservation_date DESC LIMIT ? OFFSET ?";
    $params[] = (int)$itemsPerPage;
    $params[] = (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading reservations: ' . $e->getMessage(), 'error');
}

// Get reservation statistics
try {
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM reservations
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'confirmed' => 0, 'pending' => 0, 'cancelled' => 0];
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-calendar-check"></i> Reservations</h1>
                <p>Manage equipment and facility reservations</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-bookmark"></i> Total Reservations</h6>
                            <h3><?php echo $stats['total']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-check-circle"></i> Confirmed</h6>
                            <h3><?php echo $stats['confirmed'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-hourglass"></i> Pending</h6>
                            <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-times-circle"></i> Cancelled</h6>
                            <h3><?php echo $stats['cancelled'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by name, ID or equipment..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm) || !empty($filterStatus) || !empty($filterEquipment)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Confirmed" <?php echo $filterStatus === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Pending" <?php echo $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?php echo APP_URL; ?>modules/reservations/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Reservation
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Reservations List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No reservations found.
                            <a href="<?php echo APP_URL; ?>modules/reservations/add.php">Create a reservation</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Reservation ID</th>
                                        <th>Member</th>
                                        <th>Equipment</th>
                                        <th>Reservation Date</th>
                                        <th>Time Slot</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($reservation['reservation_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($reservation['member_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['equipment_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($reservation['reservation_date']); ?></td>
                                            <td>
                                                <?php echo substr($reservation['start_time'], 0, 5); ?> - 
                                                <?php echo substr($reservation['end_time'], 0, 5); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $reservation['status'] === 'Confirmed' ? 'success' : 
                                                         ($reservation['status'] === 'Pending' ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo htmlspecialchars($reservation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/reservations/view.php?id=<?php echo $reservation['reservation_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/reservations/edit.php?id=<?php echo $reservation['reservation_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/reservations/delete.php?id=<?php echo $reservation['reservation_id']; ?>" 
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
                                            <a class="page-link" href="?page=1<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">Last</a>
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
