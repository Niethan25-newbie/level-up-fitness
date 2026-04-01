<?php
/**
 * Reservations - Create New Reservation
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members and admins can make reservations
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can make reservations.');
}

$errors = [];
$formData = [];
$members = [];
$equipment = [];
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

// Get current user's member ID if they are a member
if (!$isAdmin) {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $currentMemberId = $memberData['member_id'] ?? null;
        
        // If user is a member but doesn't have a member record, deny access
        if (!$currentMemberId) {
            die('Access denied: No active member record found for your account.');
        }
    } catch (Exception $e) {
        setMessage('Error loading member data: ' . $e->getMessage(), 'error');
    }
}

// Load members and equipment for dropdowns
try {
    // If member, only load their own member info; if admin, load all active members
    if ($isAdmin) {
        $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
        $memberStmt->execute();
    } else {
        $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE member_id = ? AND status = 'Active'");
        $memberStmt->execute([$currentMemberId]);
    }
    $members = $memberStmt->fetchAll();

    $equipmentStmt = $pdo->prepare("SELECT equipment_id, equipment_name FROM equipment WHERE availability = 'Available' ORDER BY equipment_name");
    $equipmentStmt->execute();
    $equipment = $equipmentStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For members, automatically use their own member_id; for admins, use the form value
    if ($isAdmin) {
        $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    } else {
        $formData['member_id'] = $currentMemberId; // Auto-populate for members
    }
    
    $formData['equipment_id'] = sanitize($_POST['equipment_id'] ?? '');
    $formData['reservation_date'] = sanitize($_POST['reservation_date'] ?? '');
    $formData['start_time'] = sanitize($_POST['start_time'] ?? '');
    $formData['end_time'] = sanitize($_POST['end_time'] ?? '');
    $formData['notes'] = sanitize($_POST['notes'] ?? '');
    $formData['status'] = sanitize($_POST['status'] ?? 'Pending');

    // Validate Member
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member information is missing';
    } else {
        // Verify member exists and is active
        $memberCheck = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
        $memberCheck->execute([$formData['member_id']]);
        if (!$memberCheck->fetch()) {
            $errors['member_id'] = 'Selected member is not active or does not exist';
        }
    }

    // Validate Equipment
    if (empty($formData['equipment_id'])) {
        $errors['equipment_id'] = 'Please select equipment';
    } else {
        // Verify equipment exists and is available
        $equipCheck = $pdo->prepare("SELECT equipment_id FROM equipment WHERE equipment_id = ? AND availability = 'Available'");
        $equipCheck->execute([$formData['equipment_id']]);
        if (!$equipCheck->fetch()) {
            $errors['equipment_id'] = 'Selected equipment is not available';
        }
    }

    // Validate Reservation Date
    if (empty($formData['reservation_date'])) {
        $errors['reservation_date'] = 'Please select a reservation date';
    } else {
        $reservationDateObj = DateTime::createFromFormat('Y-m-d', $formData['reservation_date']);
        if (!$reservationDateObj || $reservationDateObj->format('Y-m-d') !== $formData['reservation_date']) {
            $errors['reservation_date'] = 'Invalid date format';
        } elseif ($reservationDateObj < new DateTime('today')) {
            $errors['reservation_date'] = 'Reservation date cannot be in the past';
        } elseif ($reservationDateObj > new DateTime('+90 days')) {
            $errors['reservation_date'] = 'Reservations can only be made up to 90 days in advance';
        }
    }

    // Validate Start Time
    if (empty($formData['start_time'])) {
        $errors['start_time'] = 'Please enter start time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['start_time'])) {
        $errors['start_time'] = 'Invalid time format (use HH:MM)';
    }

    // Validate End Time
    if (empty($formData['end_time'])) {
        $errors['end_time'] = 'Please enter end time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['end_time'])) {
        $errors['end_time'] = 'Invalid time format (use HH:MM)';
    }

    // Validate time relationship
    if (!empty($formData['start_time']) && !empty($formData['end_time']) && !isset($errors['start_time']) && !isset($errors['end_time'])) {
        $startSeconds = strtotime('2000-01-01 ' . $formData['start_time']);
        $endSeconds = strtotime('2000-01-01 ' . $formData['end_time']);
        
        if ($endSeconds <= $startSeconds) {
            $errors['end_time'] = 'End time must be after start time';
        }
        
        // Check minimum duration (at least 30 minutes)
        $durationMinutes = ($endSeconds - $startSeconds) / 60;
        if ($durationMinutes < 30) {
            $errors['duration'] = 'Reservation must be at least 30 minutes long';
        }
        
        // Check maximum duration (no more than 8 hours)
        if ($durationMinutes > 480) {
            $errors['duration'] = 'Reservation cannot exceed 8 hours';
        }
    }

    // Check for conflicts (only if other validations pass)
    if (empty($errors) || (count($errors) <= 1 && isset($errors['time_conflict']))) {
        try {
            // Check for time conflicts with same equipment
            // Handle time comparison properly (times stored as TIME format)
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM reservations 
                WHERE equipment_id = ? 
                AND reservation_date = ? 
                AND status IN ('Confirmed')
                AND (
                    (TIME(start_time) < TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) = TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) < TIME(?) AND TIME(end_time) = TIME(?))
                )
            ");
            $conflictStmt->execute([
                $formData['equipment_id'],
                $formData['reservation_date'],
                $formData['end_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['end_time'],
                $formData['end_time']
            ]);
            $conflict = $conflictStmt->fetch();
            
            if ($conflict['count'] > 0) {
                $errors['time_conflict'] = 'This equipment is already reserved during the selected time. Please choose a different time slot.';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Error checking availability: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $reservationId = generateUniqueID(RESERVATION_ID_PREFIX, 'reservations');

            $stmt = $pdo->prepare("
                INSERT INTO reservations (
                    reservation_id, member_id, equipment_id, reservation_date, 
                    start_time, end_time, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reservationId, $formData['member_id'], $formData['equipment_id'],
                $formData['reservation_date'], $formData['start_time'], 
                $formData['end_time'], !empty($formData['notes']) ? $formData['notes'] : NULL,
                $formData['status']
            ]);

            logAction($_SESSION['user_id'], 'CREATE_RESERVATION', 'Reservations', 
                     'Created reservation: ' . $reservationId);

            setMessage('Reservation created successfully! ID: ' . $reservationId, 'success');
            redirect(APP_URL . 'modules/reservations/');
        } catch (Exception $e) {
            setMessage('Error creating reservation: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-plus-circle"></i> Create New Reservation</h1>
                <p>Book equipment or facility</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Reservation Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <?php if (!empty($errors['time_conflict'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-calendar-times"></i> <strong>Time Conflict!</strong> <?php echo $errors['time_conflict']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['database'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo $errors['database']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['duration'])): ?>
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-hourglass-half"></i> <strong>Duration Issue!</strong> <?php echo $errors['duration']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <?php if ($isAdmin): ?>
                                            <!-- Admin: Show dropdown to select any member -->
                                            <label for="member_id" class="form-label">Member *</label>
                                            <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                                    id="member_id" name="member_id" required>
                                                <option value="">-- Select Member --</option>
                                                <?php foreach ($members as $member): ?>
                                                    <option value="<?php echo $member['member_id']; ?>" 
                                                            <?php echo ($formData['member_id'] ?? '') === $member['member_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errors['member_id'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Member: Show read-only member info -->
                                            <label for="member_name" class="form-label">Your Member Profile</label>
                                            <div class="form-control-plaintext bg-light border rounded p-2">
                                                <strong>
                                                    <?php 
                                                    foreach ($members as $member) {
                                                        echo htmlspecialchars($member['member_name']);
                                                    }
                                                    ?>
                                                </strong>
                                                <small class="text-muted d-block mt-1">ID: <code><?php echo htmlspecialchars($currentMemberId); ?></code></small>
                                            </div>
                                            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($currentMemberId); ?>">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="equipment_id" class="form-label">Equipment *</label>
                                        <select class="form-select <?php echo isset($errors['equipment_id']) ? 'is-invalid' : ''; ?>" 
                                                id="equipment_id" name="equipment_id" required>
                                            <option value="">-- Select Equipment --</option>
                                            <?php foreach ($equipment as $eq): ?>
                                                <option value="<?php echo $eq['equipment_id']; ?>" 
                                                        <?php echo ($formData['equipment_id'] ?? '') === $eq['equipment_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($eq['equipment_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['equipment_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['equipment_id']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-clock"></i> Time Slot Selection
                                        </h6>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label for="start_time" class="form-label">
                                            Start Time <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-play-circle text-success"></i>
                                            </span>
                                            <input type="time" class="form-control <?php echo isset($errors['start_time']) ? 'is-invalid' : ''; ?>" 
                                                   id="start_time" name="start_time" 
                                                   value="<?php echo htmlspecialchars($formData['start_time'] ?? ''); ?>"
                                                   min="06:00" max="22:00" required>
                                            <?php if (isset($errors['start_time'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo $errors['start_time']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Gym hours: 6:00 AM - 10:00 PM</small>
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="end_time" class="form-label">
                                            End Time <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-stop-circle text-danger"></i>
                                            </span>
                                            <input type="time" class="form-control <?php echo isset($errors['end_time']) ? 'is-invalid' : ''; ?>" 
                                                   id="end_time" name="end_time" 
                                                   value="<?php echo htmlspecialchars($formData['end_time'] ?? ''); ?>"
                                                   min="06:00" max="22:00" required>
                                            <?php if (isset($errors['end_time'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo $errors['end_time']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Must be after start time</small>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Duration</label>
                                        <div class="alert alert-info mb-0">
                                            <small id="duration-display">-- min</small>
                                        </div>
                                        <small class="text-muted d-block mt-1">Min: 30 min<br>Max: 8 hrs</small>
                                    </div>
                                </div>

                                <?php if (isset($errors['duration'])): ?>
                                    <div class="alert alert-warning alert-sm">
                                        <i class="fas fa-hourglass-half"></i> <?php echo $errors['duration']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Additional notes...">
                                              <?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Pending" <?php echo ($formData['status'] ?? 'Pending') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo ($formData['status'] ?? '') === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="Cancelled" <?php echo ($formData['status'] ?? '') === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Reservation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Requirements
                            </h5>
                        </div>
                        <div class="card-body small">
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Duration:</strong> 30 min - 8 hours
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Hours:</strong> 6:00 AM - 10:00 PM
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Booking:</strong> Up to 90 days ahead
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Conflicts:</strong> Auto-checked
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb"></i> Tips
                            </h5>
                        </div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li class="mb-2">Required fields marked with <span class="text-danger">*</span></li>
                                <li class="mb-2">Time is set in 24-hour format</li>
                                <li class="mb-2">Duration updates automatically</li>
                                <li class="mb-2">All conflicts are validated</li>
                                <li>Add notes for special requests</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const durationDisplay = document.getElementById('duration-display');

    function updateDuration() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date('2000-01-01 ' + startTimeInput.value);
            const end = new Date('2000-01-01 ' + endTimeInput.value);
            
            if (end > start) {
                const minutes = (end - start) / (1000 * 60);
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                
                let display = '';
                if (hours > 0) {
                    display = hours + 'h ' + mins + 'm';
                } else {
                    display = mins + ' min';
                }
                
                durationDisplay.textContent = display;
                
                // Add visual feedback
                if (minutes < 30) {
                    durationDisplay.parentElement.classList.remove('alert-info', 'alert-success', 'alert-warning');
                    durationDisplay.parentElement.classList.add('alert-danger');
                } else if (minutes > 480) {
                    durationDisplay.parentElement.classList.remove('alert-info', 'alert-success', 'alert-danger');
                    durationDisplay.parentElement.classList.add('alert-warning');
                } else {
                    durationDisplay.parentElement.classList.remove('alert-danger', 'alert-warning');
                    durationDisplay.parentElement.classList.add('alert-success', 'alert-info');
                }
            } else {
                durationDisplay.textContent = '-- min';
                durationDisplay.parentElement.classList.remove('alert-success', 'alert-danger', 'alert-warning');
                durationDisplay.parentElement.classList.add('alert-info');
            }
        } else {
            durationDisplay.textContent = '-- min';
            durationDisplay.parentElement.classList.remove('alert-success', 'alert-danger', 'alert-warning');
            durationDisplay.parentElement.classList.add('alert-info');
        }
    }

    startTimeInput.addEventListener('change', updateDuration);
    endTimeInput.addEventListener('change', updateDuration);
    
    // Initial calculation
    updateDuration();

    // End time validation
    endTimeInput.addEventListener('change', function() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date('2000-01-01 ' + startTimeInput.value);
            const end = new Date('2000-01-01 ' + endTimeInput.value);
            
            if (end <= start) {
                endTimeInput.classList.add('is-invalid');
            } else {
                endTimeInput.classList.remove('is-invalid');
            }
        }
    });
});
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
