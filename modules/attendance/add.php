<?php
/**
 * Class Attendance - Record New Attendance
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$errors = [];
$formData = [];
$classes = [];
$classMembers = [];

// Get active classes
try {
    $classStmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE class_status = 'Active' ORDER BY class_name");
    $classStmt->execute();
    $classes = $classStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading classes: ' . $e->getMessage(), 'error');
}

// Get members for selected class
if (!empty($_POST['class_id']) || !empty($_GET['class_id'])) {
    $selectedClassId = sanitize($_POST['class_id'] ?? $_GET['class_id'] ?? '');
    
    try {
        $memberStmt = $pdo->prepare("
            SELECT DISTINCT m.member_id, m.member_name, m.email
            FROM members m
            WHERE m.status = 'Active'
            ORDER BY m.member_name
        ");
        $memberStmt->execute();
        $classMembers = $memberStmt->fetchAll();
    } catch (Exception $e) {
        setMessage('Error loading members: ' . $e->getMessage(), 'error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['class_id'] = sanitize($_POST['class_id'] ?? '');
    $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    $attendanceDate = sanitize($_POST['attendance_date'] ?? '');
    $formData['attendance_status'] = sanitize($_POST['attendance_status'] ?? '');

    // Validate
    if (empty($formData['class_id'])) {
        $errors['class_id'] = 'Class is required';
    }
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member is required';
    }
    if (empty($attendanceDate)) {
        $errors['attendance_date'] = 'Attendance date is required';
    }
    if (empty($formData['attendance_status'])) {
        $errors['attendance_status'] = 'Attendance status is required';
    }

    // Check if member is enrolled in the class
    if (!empty($formData['class_id']) && !empty($formData['member_id'])) {
        try {
            $enrollStmt = $pdo->prepare("SELECT * FROM class_attendance WHERE class_id = ? AND member_id = ? AND attendance_date = ?");
            $enrollStmt->execute([$formData['class_id'], $formData['member_id'], $attendanceDate]);
            if ($enrollStmt->fetch()) {
                $errors['duplicate'] = 'Attendance already recorded for this member on this date';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $attendanceId = generateUniqueID(ATTENDANCE_ID_PREFIX, 'attendance');

            // First, ensure member is enrolled in class
            $existingEnroll = $pdo->prepare("SELECT * FROM class_attendance WHERE class_id = ? AND member_id = ?");
            $existingEnroll->execute([$formData['class_id'], $formData['member_id']]);
            if (!$existingEnroll->fetch()) {
                // Not enrolled, add enrollment
                $enrollStmt = $pdo->prepare("
                    INSERT INTO class_attendance (attendance_id, class_id, member_id, enrollment_date, attendance_date, attendance_status)
                    VALUES (?, ?, ?, NOW(), ?, ?)
                ");
                $enrollStmt->execute([$attendanceId, $formData['class_id'], $formData['member_id'], $attendanceDate, $formData['attendance_status']]);
            } else {
                // Already enrolled, just update attendance
                $stmt = $pdo->prepare("
                    INSERT INTO class_attendance (attendance_id, class_id, member_id, enrollment_date, attendance_date, attendance_status)
                    VALUES (?, ?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([$attendanceId, $formData['class_id'], $formData['member_id'], $attendanceDate, $formData['attendance_status']]);
            }

            logAction($_SESSION['user_id'], 'RECORD_ATTENDANCE', 'Attendance', 
                     'Recorded attendance: ' . $attendanceId);

            setMessage('Attendance recorded successfully! ID: ' . $attendanceId, 'success');
            redirect(APP_URL . 'modules/attendance/');
        } catch (Exception $e) {
            setMessage('Error recording attendance: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-plus-circle"></i> Record Attendance</h1>
                <p>Mark member attendance for a class</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Attendance Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class *</label>
                                    <select class="form-select <?php echo isset($errors['class_id']) ? 'is-invalid' : ''; ?>" 
                                            id="class_id" name="class_id" required 
                                            onchange="location.href='?class_id=' + this.value;">
                                        <option value="">-- Select Class --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo htmlspecialchars($class['class_id']); ?>" 
                                                    <?php echo ($formData['class_id'] ?? '') === $class['class_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['class_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['class_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['class_id']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($classMembers)): ?>
                                <div class="mb-3">
                                    <label for="member_id" class="form-label">Member *</label>
                                    <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                            id="member_id" name="member_id" required>
                                        <option value="">-- Select Member --</option>
                                        <?php foreach ($classMembers as $member): ?>
                                            <option value="<?php echo htmlspecialchars($member['member_id']); ?>" 
                                                    <?php echo ($formData['member_id'] ?? '') === $member['member_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($member['member_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['member_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="attendance_date" class="form-label">Attendance Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['attendance_date']) ? 'is-invalid' : ''; ?>" 
                                               id="attendance_date" name="attendance_date" 
                                               value="<?php echo htmlspecialchars($formData['attendance_date'] ?? date('Y-m-d')); ?>" required>
                                        <?php if (isset($errors['attendance_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['attendance_date']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="attendance_status" class="form-label">Status *</label>
                                        <select class="form-select <?php echo isset($errors['attendance_status']) ? 'is-invalid' : ''; ?>" 
                                                id="attendance_status" name="attendance_status" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="Present" <?php echo ($formData['attendance_status'] ?? '') === 'Present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="Absent" <?php echo ($formData['attendance_status'] ?? '') === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                        </select>
                                        <?php if (isset($errors['attendance_status'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['attendance_status']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (isset($errors['duplicate'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $errors['duplicate']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Record Attendance
                                    </button>
                                </div>
                            </form>
                                <?php else: ?>
                                    <?php if (!empty($formData['class_id'])): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No members available for the selected class.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Information</h5>
                        </div>
                        <div class="card-body small">
                            <p><i class="fas fa-check text-success"></i> Attendance ID will be auto-generated</p>
                            <p><i class="fas fa-check text-success"></i> Select class and member</p>
                            <p><i class="fas fa-check text-success"></i> Mark as Present or Absent</p>
                            <p><i class="fas fa-check text-success"></i> Date required for tracking</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
