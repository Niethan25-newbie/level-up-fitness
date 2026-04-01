<?php
/**
 * Member Personal Attendance - View Own Attendance Records
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only members can access this page
if ($_SESSION['user_type'] !== 'member') {
    header('Location: ' . APP_URL . 'modules/attendance/');
    exit;
}

$attendance = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterClass = $_GET['class'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$classes = [];

try {
    // Get only classes this member is enrolled in
    $classStmt = $pdo->prepare("
        SELECT DISTINCT c.class_id, c.class_name 
        FROM classes c
        JOIN class_attendance ca ON c.class_id = ca.class_id
        WHERE ca.member_id = ? AND c.class_status = 'Active'
        ORDER BY c.class_name
    ");
    $classStmt->execute([$_SESSION['user_id']]);
    $classes = $classStmt->fetchAll();

    // Build query with joins - members only see their own attendance
    $query = "SELECT ca.*, c.class_name, m.member_name, m.email
              FROM class_attendance ca
              JOIN classes c ON ca.class_id = c.class_id
              JOIN members m ON ca.member_id = m.member_id
              WHERE ca.member_id = ?";
    $params = [$_SESSION['user_id']];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (c.class_name LIKE ?)";
        $search = "%$searchTerm%";
        $params[] = $search;
    }

    // Class filter
    if (!empty($filterClass)) {
        $query .= " AND ca.class_id = ?";
        $params[] = $filterClass;
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND ca.attendance_status = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countQuery = str_replace('SELECT ca.*', 'SELECT COUNT(*) as total', $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY ca.attendance_date DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll();

    // Get member stats
    $statsQuery = "SELECT 
                    attendance_status,
                    COUNT(*) as count
                   FROM class_attendance
                   WHERE member_id = ?
                   GROUP BY attendance_status";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute([$_SESSION['user_id']]);
    $stats = $statsStmt->fetchAll();
    
    $presentCount = 0;
    $absentCount = 0;
    foreach ($stats as $stat) {
        if ($stat['attendance_status'] === 'Present') {
            $presentCount = $stat['count'];
        } elseif ($stat['attendance_status'] === 'Absent') {
            $absentCount = $stat['count'];
        }
    }

} catch (Exception $e) {
    setMessage('Error loading your attendance: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-clipboard-check"></i> My Attendance</h1>
                <p>View your class attendance records</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Total Classes</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-check"></i> Present</h6>
                            <h3><?php echo $presentCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-times"></i> Absent</h6>
                            <h3><?php echo $absentCount; ?></h3>
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
                                       placeholder="Search class..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="class" onchange="window.location='?class=' + this.value + (this.value ? '' : '')">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?php echo htmlspecialchars($cls['class_id']); ?>" 
                                            <?php echo $filterClass === $cls['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Present" <?php echo $filterStatus === 'Present' ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo $filterStatus === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Your Attendance Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attendance)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No attendance records yet. Once you attend a class, your attendance will appear here.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                            <td><?php echo formatDate($record['attendance_date']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $record['attendance_status'] === 'Present' ? 'success' : 'danger'; ?>">
                                                    <?php echo htmlspecialchars($record['attendance_status']); ?>
                                                </span>
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

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
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

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
