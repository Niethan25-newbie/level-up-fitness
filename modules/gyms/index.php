<?php
/**
 * Gym Information Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$gyms = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT * FROM gyms WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (gym_name LIKE ? OR location LIKE ? OR contact_number LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Get total count
    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $countParams = $params;
    $countParams[] = (int)$itemsPerPage;
    $countParams[] = (int)$offset;
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($countParams);
    $gyms = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading gyms: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-building"></i> Gym Branches</h1>
                <p>Manage gym branch locations and information</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-map-marker-alt"></i> Total Branches</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by gym name or location..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/gyms/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?php echo APP_URL; ?>modules/gyms/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Branch
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Gym Branches
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($gyms)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No gym branches found.
                            <a href="<?php echo APP_URL; ?>modules/gyms/add.php">Add the first branch</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($gyms as $gym): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($gym['gym_name']); ?>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p>
                                                <strong>Gym ID:</strong> <code><?php echo htmlspecialchars($gym['gym_id']); ?></code>
                                            </p>
                                            <p>
                                                <strong>Location:</strong><br>
                                                <?php echo htmlspecialchars($gym['location']); ?>
                                            </p>
                                            <p>
                                                <strong>Contact:</strong><br>
                                                <a href="tel:<?php echo htmlspecialchars($gym['contact_number']); ?>">
                                                    <?php echo htmlspecialchars($gym['contact_number']); ?>
                                                </a>
                                            </p>
                                            <p>
                                                <strong>Description:</strong><br>
                                                <small><?php echo nl2br(htmlspecialchars(substr($gym['description'], 0, 100))); ?>...</small>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <a href="<?php echo APP_URL; ?>modules/gyms/view.php?id=<?php echo $gym['gym_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="<?php echo APP_URL; ?>modules/gyms/edit.php?id=<?php echo $gym['gym_id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?php echo APP_URL; ?>modules/gyms/delete.php?id=<?php echo $gym['gym_id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
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
