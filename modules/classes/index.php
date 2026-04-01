<?php
/**
 * Classes Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$classes = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query with joins
    $query = "SELECT c.*, t.trainer_name, COUNT(DISTINCT ca.member_id) as total_members
              FROM classes c
              LEFT JOIN trainers t ON c.trainer_id = t.trainer_id
              LEFT JOIN class_attendance ca ON c.class_id = ca.class_id
              WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (c.class_id LIKE ? OR c.class_name LIKE ? OR t.trainer_name LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND c.class_status = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM classes c
                   LEFT JOIN trainers t ON c.trainer_id = t.trainer_id
                   WHERE 1=1";
    if (!empty($searchTerm)) {
        $countQuery .= " AND (c.class_id LIKE ? OR c.class_name LIKE ? OR t.trainer_name LIKE ?)";
    }
    if (!empty($filterStatus)) {
        $countQuery .= " AND c.class_status = ?";
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " GROUP BY c.class_id ORDER BY c.class_schedule ASC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading classes: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-dumbbell"></i> Classes</h1>
                <p>Manage group fitness classes</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Total</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-play-circle"></i> Active</h6>
                            <h3>
                                <?php 
                                $activeStmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes WHERE class_status = 'Active'");
                                $activeStmt->execute();
                                echo $activeStmt->fetch()['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-pause-circle"></i> Inactive</h6>
                            <h3>
                                <?php 
                                $inactiveStmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes WHERE class_status = 'Inactive'");
                                $inactiveStmt->execute();
                                echo $inactiveStmt->fetch()['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-users"></i> Total Members</h6>
                            <h3>
                                <?php 
                                $membersStmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) as count FROM class_attendance");
                                $membersStmt->execute();
                                echo $membersStmt->fetch()['count'];
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
                                       placeholder="Search by class name, ID or trainer..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm) || !empty($filterStatus)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $filterStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?php echo APP_URL; ?>modules/classes/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Class
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Classes List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($classes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No classes found.
                            <a href="<?php echo APP_URL; ?>modules/classes/add.php">Create a class</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Class ID</th>
                                        <th>Class Name</th>
                                        <th>Trainer</th>
                                        <th>Schedule</th>
                                        <th>Max Capacity</th>
                                        <th>Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($class['class_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['trainer_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($class['class_schedule']); ?></td>
                                            <td><?php echo htmlspecialchars($class['max_capacity']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $class['total_members']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower(str_replace('Active', 'success', str_replace('Inactive', 'secondary', $class['class_status']))); ?>">
                                                    <?php echo htmlspecialchars($class['class_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/classes/view.php?id=<?php echo $class['class_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/classes/edit.php?id=<?php echo $class['class_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/classes/delete.php?id=<?php echo $class['class_id']; ?>" 
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
