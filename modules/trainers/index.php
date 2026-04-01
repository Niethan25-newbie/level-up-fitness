<?php
/**
 * Trainers Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$trainers = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT * FROM trainers WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (LOWER(trainer_name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(specialization) LIKE ? OR LOWER(contact_number) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search, $search]);
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
    $trainers = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading trainers: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-dumbbell"></i> Trainers Management</h1>
                <p>Manage gym trainers and their specializations</p>
            </div>

            <?php displayMessage(); ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by name, email, specialization..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/trainers/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="<?php echo APP_URL; ?>modules/trainers/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Trainer
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Trainers List 
                        <span class="badge bg-primary"><?php echo $totalRecords; ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trainers)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No trainers found.
                            <a href="<?php echo APP_URL; ?>modules/trainers/add.php">Add the first trainer</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Trainer ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Specialization</th>
                                        <th>Experience</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($trainer['trainer_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($trainer['trainer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($trainer['contact_number']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($trainer['specialization']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($trainer['years_of_experience']); ?> years</td>
                                            <td><?php echo formatDate($trainer['created_at']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/trainers/edit.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/trainers/delete.php?id=<?php echo $trainer['trainer_id']; ?>" 
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
