<?php
/**
 * Training Sessions Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$sessions = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterTrainer = $_GET['trainer'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$trainers = [];

try {
    // Get trainers for filter dropdown
    $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers ORDER BY trainer_name");
    $trainerStmt->execute();
    $trainers = $trainerStmt->fetchAll();

    // Build query
    $query = "SELECT ts.*, t.trainer_name, g.gym_name, 
              (SELECT COUNT(*) FROM training_session_attendees WHERE session_id = ts.session_id) as current_attendees
              FROM training_sessions ts
              LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
              LEFT JOIN gyms g ON ts.gym_id = g.gym_id
              WHERE 1=1";
    $params = [];

    // Role-based access control
    if ($_SESSION['user_type'] === 'trainer') {
        $query .= " AND ts.trainer_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (LOWER(ts.session_name) LIKE ? OR LOWER(t.trainer_name) LIKE ? OR LOWER(g.gym_name) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Trainer filter
    if (!empty($filterTrainer)) {
        $query .= " AND ts.trainer_id = ?";
        $params[] = $filterTrainer;
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND ts.status = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countStmt = $pdo->prepare(str_replace('SELECT ts.*, t.trainer_name, g.gym_name, (SELECT COUNT(*) FROM training_session_attendees WHERE session_id = ts.session_id) as current_attendees', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $countResult = $countStmt->fetch();
    $totalRecords = ($countResult && isset($countResult['total'])) ? $countResult['total'] : 0;
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY ts.session_date DESC, ts.session_time DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading sessions: ' . $e->getMessage(), 'error');
}

?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

            <div class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-calendar-alt"></i> Training Sessions</h1>
                    <?php if ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'trainer'): ?>
                        <a href="add.php" class="btn btn-primary">+ New Session</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search by session name..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="trainer" class="form-select">
                                <option value="">All Trainers</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['trainer_id']; ?>" <?php echo $filterTrainer == $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Scheduled" <?php echo $filterStatus === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="Ongoing" <?php echo $filterStatus === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="Completed" <?php echo $filterStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <?php if (!empty($searchTerm) || !empty($filterTrainer) || !empty($filterStatus)): ?>
                            <div class="col-md-12">
                                <a href="<?php echo APP_URL; ?>modules/sessions/" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php displayMessage(); ?>

            <!-- Sessions Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Session Name</th>
                                <th>Trainer</th>
                                <th>Date & Time</th>
                                <th>Gym</th>
                                <th>Attendees</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($sessions) > 0): ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($session['session_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($session['trainer_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($session['session_date'])); ?><br>
                                            <small><?php echo date('H:i', strtotime($session['session_time'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($session['gym_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $session['current_attendees']; ?>/<?php echo $session['max_capacity']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            if ($session['status'] === 'Scheduled') $statusClass = 'info';
                                            elseif ($session['status'] === 'Ongoing') $statusClass = 'warning';
                                            elseif ($session['status'] === 'Completed') $statusClass = 'success';
                                            elseif ($session['status'] === 'Cancelled') $statusClass = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($session['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-info">View</a>
                                            <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_id'])): ?>
                                                <a href="edit.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this session?');">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No sessions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&trainer=<?php echo urlencode($filterTrainer); ?>&status=<?php echo urlencode($filterStatus); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
