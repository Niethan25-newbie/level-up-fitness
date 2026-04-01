<?php
/**
 * Enroll in Training Session - Member Action
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('member');

$message = getMessage();
$sessionId = $_POST['session_id'] ?? null;
$memberId = $_POST['member_id'] ?? null;

if (!$sessionId || !$memberId) {
    setMessage('Missing required information', 'error');
    redirect('modules/sessions/index.php');
}

try {
    // Verify member ID matches current user
    $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
    $memberStmt->execute([$_SESSION['user_id']]);
    $memberData = $memberStmt->fetch();

    if (!$memberData || $memberData['member_id'] != $memberId) {
        setMessage('You can only enroll for yourself', 'error');
        redirect('modules/sessions/index.php');
    }

    // Check if already enrolled
    $checkStmt = $pdo->prepare("
        SELECT * FROM training_session_attendees 
        WHERE session_id = ? AND member_id = ?
    ");
    $checkStmt->execute([$sessionId, $memberId]);
    if ($checkStmt->fetch()) {
        setMessage('You are already enrolled in this session', 'error');
        redirect('modules/sessions/view.php?id=' . $sessionId);
    }

    // Check if session has capacity
    $capacityStmt = $pdo->prepare("
        SELECT ts.max_capacity, COUNT(tsa.attendee_id) as current_count
        FROM training_sessions ts
        LEFT JOIN training_session_attendees tsa ON ts.session_id = tsa.session_id
        WHERE ts.session_id = ?
        GROUP BY ts.session_id
    ");
    $capacityStmt->execute([$sessionId]);
    $capacity = $capacityStmt->fetch();

    if ($capacity && $capacity['current_count'] >= $capacity['max_capacity']) {
        setMessage('This session is full', 'error');
        redirect('modules/sessions/view.php?id=' . $sessionId);
    }

    // Enroll in session
    $enrollStmt = $pdo->prepare("
        INSERT INTO training_session_attendees (session_id, member_id, attendance_status)
        VALUES (?, ?, 'Present')
    ");
    $enrollStmt->execute([$sessionId, $memberId]);

    setMessage('Successfully enrolled in the session!', 'success');
    redirect('modules/sessions/view.php?id=' . $sessionId);

} catch (Exception $e) {
    setMessage('Error enrolling in session: ' . $e->getMessage(), 'error');
    redirect('modules/sessions/index.php');
}
?>
