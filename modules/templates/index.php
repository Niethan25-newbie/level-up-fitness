<?php
/**
 * Workout Templates - Popular Templates Listing
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members, trainers, and admins can view workout templates
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only authenticated users can view workout templates.');
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

$templates = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDifficulty = $_GET['difficulty'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$templateTypes = [];
$difficulties = ['Beginner', 'Intermediate', 'Advanced'];

try {
    // Get unique template types for filter
    $typeStmt = $pdo->prepare("SELECT DISTINCT template_type FROM workout_templates WHERE is_active = 1 ORDER BY template_type");
    $typeStmt->execute();
    $templateTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

    // Build query
    $query = "SELECT * FROM workout_templates WHERE is_active = 1";
    $params = [];

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (template_name LIKE ? OR description LIKE ? OR goal LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Type filter
    if (!empty($filterType)) {
        $query .= " AND template_type = ?";
        $params[] = $filterType;
    }

    // Difficulty filter
    if (!empty($filterDifficulty)) {
        $query .= " AND difficulty_level = ?";
        $params[] = $filterDifficulty;
    }

    // Get total count
    $countQuery = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results - sort by popularity and name
    $query .= " ORDER BY popularity_score DESC, template_name ASC LIMIT ? OFFSET ?";
    $queryParams = array_merge($params, [(int)$itemsPerPage, (int)$offset]);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $templates = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading templates: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-heart"></i> Popular Workout Templates</h1>
                <p>Browse and customize popular workout plans used by fitness professionals</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-star"></i> Total Templates</h6>
                            <h3><?php echo $totalRecords; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control search-input" name="search" 
                                   placeholder="Search by name, goal, or description..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="type">
                                <option value="">-- All Types --</option>
                                <?php foreach ($templateTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                            <?php echo $type === $filterType ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="difficulty">
                                <option value="">-- All Levels --</option>
                                <?php foreach ($difficulties as $level): ?>
                                    <option value="<?php echo htmlspecialchars($level); ?>" 
                                            <?php echo $level === $filterDifficulty ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <?php if (!empty($searchTerm) || !empty($filterType) || !empty($filterDifficulty)): ?>
                            <div class="col-md-12">
                                <a href="<?php echo APP_URL; ?>modules/templates/" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Templates Grid -->
            <?php if (empty($templates)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No workout templates found matching your criteria.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($templates as $template): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card template-card h-100 hover-shadow">
                                <!-- Template Header -->
                                <div class="template-header bg-gradient-primary">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($template['template_type']); ?></span>
                                            <span class="badge bg-warning text-dark ms-2">
                                                <?php echo htmlspecialchars($template['difficulty_level']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <i class="fas fa-star text-warning"></i>
                                            <small><?php echo $template['popularity_score']; ?> used</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($template['template_name']); ?></h5>
                                    
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-bullseye"></i> <strong>Goal:</strong> <?php echo htmlspecialchars($template['goal']); ?>
                                    </p>

                                    <p class="card-text text-muted">
                                        <?php echo substr(htmlspecialchars($template['description']), 0, 100); ?>...
                                    </p>

                                    <!-- Template Details -->
                                    <div class="template-details">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar"></i> Duration: <?php echo $template['duration_weeks']; ?> weeks
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-dumbbell"></i> Exercises: <?php echo $template['exercises_count']; ?>
                                        </small>
                                        <?php if (!empty($template['equipment_required'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-tools"></i> 
                                                <?php echo strlen($template['equipment_required']) > 40 
                                                    ? substr(htmlspecialchars($template['equipment_required']), 0, 40) . '...' 
                                                    : htmlspecialchars($template['equipment_required']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="card-footer bg-light">
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo APP_URL; ?>modules/templates/view.php?id=<?php echo htmlspecialchars($template['template_id']); ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php if ($_SESSION['user_type'] === 'trainer' || $_SESSION['user_type'] === 'admin'): ?>
                                            <a href="<?php echo APP_URL; ?>modules/templates/customize.php?id=<?php echo htmlspecialchars($template['template_id']); ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-copy"></i> Use & Customize
                                            </a>
                                        <?php elseif ($_SESSION['user_type'] === 'member'): ?>
                                            <a href="<?php echo APP_URL; ?>modules/templates/customize.php?id=<?php echo htmlspecialchars($template['template_id']); ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-check"></i> Use This Template
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterType ? '&type=' . urlencode($filterType) : ''; ?>">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterType ? '&type=' . urlencode($filterType) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterType ? '&type=' . urlencode($filterType) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterType ? '&type=' . urlencode($filterType) : ''; ?>">Next</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterType ? '&type=' . urlencode($filterType) : ''; ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>
</div>

<style>
.template-card {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.template-card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    transform: translateY(-4px);
}

.template-header {
    padding: 15px;
    color: white;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hover-shadow {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.template-details {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}
</style>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
