<?php
/**
 * Reusable PHP Functions
 * Level Up Fitness - Gym Management System
 */

/**
 * Generate unique IDs with prefix
 */
function generateID($prefix) {
    $timestamp = time();
    $random = rand(100, 999);
    return $prefix . $timestamp . $random;
}

/**
 * Sanitize user input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number format
 * Supports: 09XXXXXXXXXX, +639XXXXXXXXXX, +63-9-XXXX-XXXX, (09) XXXX-XXXX, and other formats
 */
function isValidPhone($phone) {
    // Remove common formatting characters
    $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $phone);
    
    // Check if it's a valid international format or local format
    // Filipino: 09XXXXXXXXXX (11 digits) or +639XXXXXXXXXX (13 digits with +63)
    // International: +1-XXXXXXXXXX (10-15 digits is typical)
    if (preg_match('/^\+?[1-9]\d{7,14}$/', $cleaned)) {
        return true;
    }
    
    // Local format: starts with 09, exactly 11 digits
    if (preg_match('/^09\d{9}$/', $cleaned)) {
        return true;
    }
    
    return false;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to page
 */
function redirect($location) {
    header('Location: ' . $location);
    exit();
}

/**
 * Set session message
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type // 'success', 'error', 'warning', 'info'
    ];
}

/**
 * Get session message
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayMessage() {
    $message = getMessage();
    if ($message) {
        $alertClass = 'alert-' . $message['type'];
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$message['text']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function userHasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . 'auth/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!userHasRole($role)) {
        die('Access denied: You do not have permission to access this page.');
    }
}

/**
 * Get user info from session
 */
function getUserInfo() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null
    ];
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Check membership expiry
 */
function isMembershipExpired($joinDate, $membershipType) {
    $currentDate = date('Y-m-d');
    
    switch($membershipType) {
        case MEMBERSHIP_MONTHLY:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +1 month'));
            break;
        case MEMBERSHIP_QUARTERLY:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +3 months'));
            break;
        case MEMBERSHIP_ANNUAL:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +1 year'));
            break;
        default:
            return true;
    }
    
    return $currentDate > $expiryDate;
}

/**
 * Get membership expiry date
 */
function getMembershipExpiryDate($joinDate, $membershipType) {
    switch($membershipType) {
        case MEMBERSHIP_MONTHLY:
            return date('Y-m-d', strtotime($joinDate . ' +1 month'));
        case MEMBERSHIP_QUARTERLY:
            return date('Y-m-d', strtotime($joinDate . ' +3 months'));
        case MEMBERSHIP_ANNUAL:
            return date('Y-m-d', strtotime($joinDate . ' +1 year'));
        default:
            return null;
    }
}

/**
 * Log action (for audit trail)
 */
function logAction($userId, $action, $module, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, module, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $module, $details]);
    } catch (Exception $e) {
        error_log('Error logging action: ' . $e->getMessage());
    }
}

/**
 * Get days until membership expiry
 */
function getDaysUntilExpiry($joinDate, $membershipType) {
    $expiryDate = getMembershipExpiryDate($joinDate, $membershipType);
    if (!$expiryDate) return -1;
    
    $currentDate = new DateTime();
    $expiry = new DateTime($expiryDate);
    $interval = $currentDate->diff($expiry);
    
    return $interval->invert ? -$interval->days : $interval->days;
}

?>
