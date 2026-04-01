<?php
/**
 * Class Attendance Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Trainers and admins can access attendance
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can access attendance.');
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
    // Get all classes for filter
    $classStmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE class_status = 'Active' ORDER BY class_name");
    $classStmt->execute();
    $classes = $classStmt->fetchAll();

    // Build query with joins
    $query = "SELECT ca.*, c.class_name, m.member_name, m.email
              FROM class_attendance ca
              JOIN classes c ON ca.class_id = c.class_id
              JOIN members m ON ca.member_id = m.member_id
              WHERE 1=1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (ca.attendance_id LIKE ? OR m.member_name LIKE ? OR c.class_name LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
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
    $countParams = array_merge($params, [(int)$itemsPerPage, (int)$offset]);
    $query .= " ORDER BY ca.attendance_date DESC LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($countParams);
    $attendance = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading attendance: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-clipboard-list"></i> Class Attendance</h1>
                <p>Track member attendance in classes</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Total Records</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-check"></i> Present</h6>
                            <h3>
                                <?php 
                                $presentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_attendance WHERE attendance_status = 'Present'");
                                $presentStmt->execute();
                                echo $presentStmt->fetch()['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-times"></i> Absent</h6>
                            <h3>
                                <?php 
                                $absentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_attendance WHERE attendance_status = 'Absent'");
                                $absentStmt->execute();
                                echo $absentStmt->fetch()['count'];
                                ?>
                            </h3>
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
                                       placeholder="Search member or class..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       aria-label="Search">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <select class="form-select" onchange="window.location='?status=' + this.value">
                                <option value="">All Status</option>
                                <option value="Present" <?php echo $filterStatus === 'Present' ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo $filterStatus === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!empty($searchTerm) || !empty($filterClass) || !empty($filterStatus)): ?>
                                <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-secondary me-2">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo APP_URL; ?>modules/attendance/add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Record Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Attendance Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attendance)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No attendance records found.
                            <a href="<?php echo APP_URL; ?>modules/attendance/add.php">Record attendance</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Attendance ID</th>
                                        <th>Member Name</th>
                                        <th>Class</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Enrollment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($record['attendance_id']); ?></code></td>
                                            <td><?php echo htmlspecialchars($record['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                            <td><?php echo formatDate($record['attendance_date']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower(str_replace('Present', 'success', str_replace('Absent', 'danger', $record['attendance_status']))); ?>">
                                                    <?php echo htmlspecialchars($record['attendance_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($record['enrollment_date']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>modules/attendance/edit.php?id=<?php echo $record['attendance_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/attendance/delete.php?id=<?php echo $record['attendance_id']; ?>" 
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
