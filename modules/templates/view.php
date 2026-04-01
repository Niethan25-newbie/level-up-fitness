<?php
/**
 * Workout Template - Details View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$template = null;
$schedule = [];
$message = getMessage();

$templateId = $_GET['id'] ?? '';

if (empty($templateId)) {
    setMessage('Template ID is required', 'error');
    redirect(APP_URL . 'modules/templates/');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM workout_templates WHERE template_id = ? AND is_active = 1");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();

    if (!$template) {
        setMessage('Template not found', 'error');
        redirect(APP_URL . 'modules/templates/');
    }

    // Parse schedule JSON
    $schedule = json_decode($template['weekly_schedule'], true) ?? [];

} catch (Exception $e) {
    setMessage('Error loading template: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/templates/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-list-check"></i> <?php echo htmlspecialchars($template['template_name']); ?></h1>
                <p><?php echo htmlspecialchars($template['goal']); ?></p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Template Overview Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <dt class="text-muted">Template Type</dt>
                                    <dd><?php echo htmlspecialchars($template['template_type']); ?></dd>
                                </div>
                                <div class="col-md-6">
                                    <dt class="text-muted">Difficulty Level</dt>
                                    <dd>
                                        <span class="badge 
                                            <?php 
                                                switch($template['difficulty_level']) {
                                                    case 'Beginner': echo 'bg-success'; break;
                                                    case 'Intermediate': echo 'bg-warning'; break;
                                                    case 'Advanced': echo 'bg-danger'; break;
                                                }
                                            ?>
                                        ">
                                            <?php echo htmlspecialchars($template['difficulty_level']); ?>
                                        </span>
                                    </dd>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <dt class="text-muted">Duration</dt>
                                    <dd><?php echo $template['duration_weeks']; ?> weeks</dd>
                                </div>
                                <div class="col-md-6">
                                    <dt class="text-muted">Total Exercises</dt>
                                    <dd><?php echo $template['exercises_count']; ?></dd>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <dt class="text-muted">Primary Goal</dt>
                                    <dd><?php echo htmlspecialchars($template['goal']); ?></dd>
                                </div>
                                <div class="col-md-6">
                                    <dt class="text-muted">Times Used</dt>
                                    <dd><?php echo $template['popularity_score']; ?> members</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-book"></i> Description</h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo htmlspecialchars($template['description']); ?></p>
                        </div>
                    </div>

                    <!-- Equipment Required Card -->
                    <?php if (!empty($template['equipment_required'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-tools"></i> Equipment Required</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                    $equipmentList = explode(',', $template['equipment_required']);
                                    foreach ($equipmentList as $equipment):
                                ?>
                                    <div class="col-md-6 mb-2">
                                        <span class="badge bg-info">
                                            <i class="fas fa-check-circle"></i> <?php echo trim($equipment); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Weekly Schedule Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Weekly Schedule</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($schedule)): ?>
                                <div class="schedule-timeline">
                                    <?php foreach ($schedule as $day => $exercises): ?>
                                        <div class="schedule-item mb-3 p-3 border rounded bg-light">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-check-square"></i> 
                                                <?php 
                                                    $dayLabel = str_replace(['day', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'daily', 'week', '_'], 
                                                                           [' Day ', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Daily', ' Week ', ' '], 
                                                                           $day);
                                                    echo ucfirst(htmlspecialchars($dayLabel)); 
                                                ?>
                                            </h6>
                                            <p class="mb-0 text-muted">
                                                <small><?php echo htmlspecialchars($exercises); ?></small>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No schedule details available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Template Type</span>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($template['template_type']); ?></span>
                                </div>
                            </div>
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Program Duration</span>
                                    <strong><?php echo $template['duration_weeks']; ?> weeks</strong>
                                </div>
                            </div>
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Total Exercises</span>
                                    <strong><?php echo $template['exercises_count']; ?></strong>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Popularity</span>
                                    <small>
                                        <?php 
                                            $popularity = $template['popularity_score'];
                                            $stars = ceil($popularity / max($template['popularity_score'], 1) / 20) ?: 1;
                                            for ($i = 0; $i < 5; $i++) {
                                                echo $i < min($stars, 5) ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
                                            }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($_SESSION['user_type'] === 'trainer' || $_SESSION['user_type'] === 'admin'): ?>
                                    <a href="<?php echo APP_URL; ?>modules/templates/customize.php?id=<?php echo htmlspecialchars($templateId); ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-pencil-alt"></i> Customize for Member
                                    </a>
                                <?php elseif ($_SESSION['user_type'] === 'member'): ?>
                                    <a href="<?php echo APP_URL; ?>modules/templates/customize.php?id=<?php echo htmlspecialchars($templateId); ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-check"></i> Use This Template
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>modules/templates/" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Templates
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card mt-4 border-info">
                        <div class="card-body">
                            <p class="text-muted small mb-0">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <strong>Pro Tip:</strong> This template can be customized based on your specific fitness goals and available equipment. Work with a trainer for personalized guidance.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<style>
.schedule-timeline {
    border-left: 3px solid #1a1a1a;
    padding-left: 0;
}

.schedule-item {
    margin-left: 15px;
    transition: all 0.3s ease;
}

.schedule-item:hover {
    background-color: #f8f9ff !important;
}

.stat-item {
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
    border-bottom: none;
}
</style>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
