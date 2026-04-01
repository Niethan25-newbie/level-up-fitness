<?php
/**
 * Activity Log - System Audit Trail
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(__FILE__)) . '/includes/header.php';

requireLogin();

$logs = [];
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$filterModule = $_GET['module'] ?? '';
$filterAction = $_GET['action'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    // Build query
    $query = "SELECT * FROM activity_log WHERE 1=1";
    $params = [];

    // Module filter
    if (!empty($filterModule)) {
        $query .= " AND module = ?";
        $params[] = $filterModule;
    }

    // Action filter
    if (!empty($filterAction)) {
        $query .= " AND action = ?";
        $params[] = $filterAction;
    }

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (details LIKE ? OR user_id LIKE ?)";
        $search = "%$searchTerm%";
        $params[] = $search;
        $params[] = $search;
    }

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM activity_log WHERE 1=1";
    if (!empty($filterModule)) {
        $countQuery .= " AND module = ?";
    }
    if (!empty($filterAction)) {
        $countQuery .= " AND action = ?";
    }
    if (!empty($searchTerm)) {
        $countQuery .= " AND (details LIKE ? OR user_id LIKE ?)";
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Get unique modules and actions for filters
    $modulesStmt = $pdo->prepare("SELECT DISTINCT module FROM activity_log ORDER BY module");
    $modulesStmt->execute();
    $modules = $modulesStmt->fetchAll();

    $actionsStmt = $pdo->prepare("SELECT DISTINCT action FROM activity_log ORDER BY action");
    $actionsStmt->execute();
    $actions = $actionsStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading activity log: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(__FILE__)) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>dashboard.php" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-history"></i> Activity Log</h1>
                <p>System audit trail and user actions</p>
            </div>

            <?php displayMessage(); ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="GET" class="d-flex" role="search">
                                <input class="form-control search-input me-2" type="search" name="search" 
                                       placeholder="Search by user or description..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm) || !empty($filterModule) || !empty($filterAction)): ?>
                                    <a href="<?php echo APP_URL; ?>activity-log/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" onchange="window.location='?module=' + this.value">
                                <option value="">All Modules</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?php echo htmlspecialchars($module['module']); ?>" 
                                            <?php echo $filterModule === $module['module'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($module['module']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" onchange="window.location='?action=' + this.value">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                            <?php echo $filterAction === $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <div class="float-end text-muted">
                        Total Records: <strong><?php echo $totalRecords; ?></strong>
                    </div>
                    <h5 class="mb-0"><i class="fas fa-table"></i> Activity Log</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No activity logs found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User ID</th>
                                        <th>Action</th>
                                        <th>Module</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo formatDate($log['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($log['user_id']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo strpos($log['action'], 'CREATE') !== false ? 'success' : 
                                                         (strpos($log['action'], 'EDIT') !== false ? 'warning' : 
                                                         (strpos($log['action'], 'DELETE') !== false ? 'danger' : 'primary'));
                                                ?>">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($log['module']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?php echo !empty($filterModule) ? '&module=' . urlencode($filterModule) : ''; ?>">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($filterModule) ? '&module=' . urlencode($filterModule) : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filterModule) ? '&module=' . urlencode($filterModule) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($filterModule) ? '&module=' . urlencode($filterModule) : ''; ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($filterModule) ? '&module=' . urlencode($filterModule) : ''; ?>">Last</a>
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

<?php require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; ?>
