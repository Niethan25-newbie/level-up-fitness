<?php
/**
 * Workout Plans Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members, trainers, and admins can view workout plans
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only members, trainers, and admins can view workout plans.');
}

// Check if user is member and active
if ($_SESSION['user_type'] === 'member') {
    $memberCheck = $pdo->prepare("SELECT status FROM members WHERE user_id = ?");
    $memberCheck->execute([$_SESSION['user_id']]);
    $memberData = $memberCheck->fetch();
    if (!$memberData || $memberData['status'] !== 'Active') {
        die('Access denied: Your account is not active. Please contact the gym administrator.');
    }
}

$plans = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query with joins to get member and trainer names
    $query = "SELECT wp.*, m.member_name, t.trainer_name 
              FROM workout_plans wp
              LEFT JOIN members m ON wp.member_id = m.member_id
              LEFT JOIN trainers t ON wp.trainer_id = t.trainer_id
              WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (wp.workout_plan_id LIKE ? OR LOWER(m.member_name) LIKE ? OR LOWER(wp.plan_name) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Get total count
    $countQuery = str_replace('SELECT wp.*', 'SELECT COUNT(*) as total', $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY wp.created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $plans = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading plans: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-dumbbell"></i> Workout Plans</h1>
                <p>Manage member workout plans and training schedules</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-clipboard-list"></i> Total Plans</h6>
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
                                       placeholder="Search by member name or plan ID..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($searchTerm)): ?>
                                    <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-secondary ms-2">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($_SESSION['user_type'] === 'trainer' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="<?php echo APP_URL; ?>modules/workouts/add.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Plan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Workout Plans
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($plans)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No workout plans found.
                            <?php if ($_SESSION['user_type'] === 'trainer' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="<?php echo APP_URL; ?>modules/workouts/add.php">Create the first plan</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Plan ID</th>
                                        <th>Member</th>
                                        <th>Trainer</th>
                                        <th>Plan Name</th>
                                        <th>Duration</th>
                                        <th>Goal</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($plan['workout_plan_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($plan['member_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($plan['trainer_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                            <td>
                                                <?php 
                                                    $schedule = json_decode($plan['weekly_schedule'], true);
                                                    echo htmlspecialchars($schedule['duration_weeks'] ?? 'N/A'); 
                                                ?> weeks
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php 
                                                        $schedule = json_decode($plan['weekly_schedule'], true);
                                                        echo htmlspecialchars(substr($schedule['goal'] ?? 'N/A', 0, 20)); 
                                                    ?>...
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($plan['created_at']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/workouts/view.php?id=<?php echo $plan['workout_plan_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/workouts/edit.php?id=<?php echo $plan['workout_plan_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/workouts/delete.php?id=<?php echo $plan['workout_plan_id']; ?>" 
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
