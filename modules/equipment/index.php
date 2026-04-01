<?php
/**
 * Equipment Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$equipment = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['availability'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT * FROM equipment WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (equipment_id LIKE ? OR equipment_name LIKE ? OR equipment_category LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND availability = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY equipment_name ASC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $equipment = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading equipment: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/equipment/add.php" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-plus"></i> Add Equipment
                </a>
                <h1><i class="fas fa-dumbbell"></i> Equipment Management</h1>
                <p>Manage gym equipment and resources</p>
            </div>

            <?php displayMessage(); ?>

            <!-- Search and Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Search by ID, name, or category..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="availability" class="form-select">
                                <option value="">-- All Status --</option>
                                <option value="Available" <?php echo $filterStatus === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Maintenance" <?php echo $filterStatus === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Out of Service" <?php echo $filterStatus === 'Out of Service' ? 'selected' : ''; ?>>Out of Service</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Equipment Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Equipment ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No equipment found
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['equipment_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equipment_category'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info" title="Available units"><?php echo (int)$item['quantity'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($item['availability']) {
                                            'Available' => 'success',
                                            'Maintenance' => 'warning',
                                            'Out of Service' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($item['availability']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo urlencode($item['equipment_id']); ?>" class="btn btn-sm btn-info" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo urlencode($item['equipment_id']); ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo urlencode($item['equipment_id']); ?>" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($filterStatus); ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($filterStatus); ?>">Previous</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?php echo $i; ?></span>
                        </li>
                        <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($filterStatus); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($filterStatus); ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($filterStatus); ?>">Last</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <p class="text-muted mt-3">Total: <?php echo $totalRecords; ?> item<?php echo $totalRecords !== 1 ? 's' : ''; ?></p>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
